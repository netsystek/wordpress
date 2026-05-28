<?php
/**
 * includes/class-reservation-email.php
 *
 * Envoi des emails de confirmation/refus via wp_mail().
 *
 * =============================================================================
 * WP_MAIL vs SYMFONY MAILER
 * =============================================================================
 * wp_mail($to, $subject, $message, $headers, $attachments)
 *
 * Parallèle Symfony :
 *   $email = (new Email())
 *       ->from($from)
 *       ->to($to)
 *       ->subject($subject)
 *       ->html($html);
 *   $mailer->send($email);
 *
 * Points clés :
 * - wp_mail() utilise PHPMailer en interne (comme Symfony Mailer en mode SMTP)
 * - On configure SMTP via le hook phpmailer_init (cf. mu-plugin smtp-mailpit.php)
 * - Les headers HTML s'ajoutent via le 4ème paramètre
 * - Le filtre 'wp_mail_from' surcharge l'expéditeur
 *
 * TEMPLATES D'EMAIL
 * =============================================================================
 * On utilise de simples variables {{placeholder}} remplacées par str_replace().
 * Le template est configuré par l'admin depuis le backoffice (page Settings).
 * Parallèle Symfony : équivalent aux templates Twig pour les emails (Email.html.twig).
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Reservation_Email {

    /**
     * Variables disponibles dans les templates d'email.
     * Documentées ici et affichées dans la page de settings pour l'admin.
     */
    public static function get_template_variables(): array {
        return [
            '{{client_prenom}}'         => __( 'Prénom du client', 'restaurant-reservation' ),
            '{{client_nom}}'            => __( 'Nom du client', 'restaurant-reservation' ),
            '{{client_email}}'          => __( 'Email du client', 'restaurant-reservation' ),
            '{{client_telephone}}'      => __( 'Téléphone du client', 'restaurant-reservation' ),
            '{{reservation_date}}'      => __( 'Date de la réservation', 'restaurant-reservation' ),
            '{{reservation_heure}}'     => __( 'Heure de la réservation', 'restaurant-reservation' ),
            '{{reservation_personnes}}' => __( 'Nombre de personnes', 'restaurant-reservation' ),
            '{{reservation_message}}'   => __( 'Message du client', 'restaurant-reservation' ),
            '{{restaurant_nom}}'        => __( 'Nom du restaurant', 'restaurant-reservation' ),
        ];
    }

    /**
     * Listener de l'événement "reservation_status_changed".
     *
     * Cette méthode est appelée par do_action('reservation_status_changed', $id, $status)
     * déclenché dans Reservation_CPT::update_status().
     *
     * Parallèle Symfony : c'est la méthode d'un EventListener/EventSubscriber
     * qui reçoit un ReservationStatusChangedEvent.
     *
     * @param int    $post_id    ID de la réservation
     * @param string $new_status Nouveau statut (res_accepted ou res_rejected)
     */
    public function send_status_email( int $post_id, string $new_status ): void {
        // On n'envoie un email que pour les statuts terminaux (accepté/refusé)
        if ( ! in_array( $new_status, [ Reservation_CPT::STATUS_ACCEPTED, Reservation_CPT::STATUS_REJECTED ], true ) ) {
            return;
        }

        $reservation = Reservation_CPT::get( $post_id );
        if ( ! $reservation || empty( $reservation['email'] ) ) {
            return;
        }

        $settings = get_option( 'restaurant_res_settings', [] );
        $is_accepted = $new_status === Reservation_CPT::STATUS_ACCEPTED;

        $subject_key  = $is_accepted ? 'subject_accepted' : 'subject_rejected';
        $template_key = $is_accepted ? 'email_accepted'   : 'email_rejected';

        $subject  = $settings[ $subject_key ] ?? '';
        $template = $settings[ $template_key ] ?? '';

        if ( empty( $subject ) || empty( $template ) ) {
            return;
        }

        // Remplace les variables {{placeholder}} par les vraies valeurs
        $body = $this->render_template( $template, $reservation );
        $subject = $this->render_template( $subject, $reservation );

        $this->send(
            $reservation['email'],
            $subject,
            $body,
            $settings['sender_name']  ?? get_bloginfo( 'name' ),
            $settings['sender_email'] ?? get_bloginfo( 'admin_email' )
        );
    }

    /**
     * Remplace les variables {{placeholder}} dans un template.
     *
     * @param string $template Corps ou objet du template
     * @param array  $reservation Données de la réservation
     * @return string Template avec les variables substituées
     */
    private function render_template( string $template, array $reservation ): string {
        $replacements = [
            '{{client_prenom}}'         => $reservation['prenom'],
            '{{client_nom}}'            => $reservation['nom'],
            '{{client_email}}'          => $reservation['email'],
            '{{client_telephone}}'      => $reservation['telephone'],
            '{{reservation_date}}'      => $this->format_date( $reservation['date'] ),
            '{{reservation_heure}}'     => $reservation['heure'],
            '{{reservation_personnes}}' => $reservation['personnes'],
            '{{reservation_message}}'   => $reservation['message'] ?: '—',
            '{{restaurant_nom}}'        => get_bloginfo( 'name' ),
        ];

        return str_replace(
            array_keys( $replacements ),
            array_values( $replacements ),
            $template
        );
    }

    /**
     * Envoie l'email via wp_mail().
     *
     * @param string $to           Email destinataire
     * @param string $subject      Objet de l'email
     * @param string $body         Corps du message (texte brut ou HTML)
     * @param string $sender_name  Nom de l'expéditeur
     * @param string $sender_email Email de l'expéditeur
     * @return bool true si envoyé, false sinon
     */
    public function send( string $to, string $subject, string $body, string $sender_name, string $sender_email ): bool {
        // Filtre WordPress pour surcharger l'expéditeur
        // add_filter() fonctionne comme add_action() mais retourne une valeur modifiée
        // Parallèle Symfony : équivalent à un EventListener sur MessageEvent qui modifie le from
        $set_from = function() use ( $sender_email ) { return $sender_email; };
        $set_name = function() use ( $sender_name )  { return $sender_name; };
        add_filter( 'wp_mail_from',      $set_from );
        add_filter( 'wp_mail_from_name', $set_name );

        // Détecte si le corps contient du HTML
        $is_html = $body !== strip_tags( $body );
        $headers = $is_html
            ? [ 'Content-Type: text/html; charset=UTF-8' ]
            : [ 'Content-Type: text/plain; charset=UTF-8' ];

        // Convertit les sauts de ligne en <br> si HTML
        if ( $is_html ) {
            $body = nl2br( esc_html( $body ) );
            $body = $this->wrap_html( $body, get_bloginfo( 'name' ) );
        }

        $result = wp_mail( $to, $subject, $body, $headers );

        // Retire les filtres après envoi — ne pas laisser des filtres permanents
        remove_filter( 'wp_mail_from',      $set_from );
        remove_filter( 'wp_mail_from_name', $set_name );

        return $result;
    }

    /**
     * Enveloppe le contenu dans un template HTML minimal.
     * Garantit un email HTML valide et visuellement correct.
     */
    private function wrap_html( string $content, string $restaurant_name ): string {
        $primary_color = get_theme_mod( 'color_primary', '#c8a96e' );
        return sprintf( '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>%s</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family: Georgia, serif;">
    <table width="100%%" cellpadding="0" cellspacing="0" style="background:#f4f4f4; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; max-width:100%%;">
                    <tr>
                        <td style="background:%s; padding: 30px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px;">%s</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px; color:#333; font-size:16px; line-height:1.6;">
                            %s
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; background:#f9f9f9; text-align:center; font-size:12px; color:#999;">
                            &copy; %s %s
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
            esc_html( $restaurant_name ),
            esc_attr( sanitize_hex_color( $primary_color ) ),
            esc_html( $restaurant_name ),
            $content,
            date( 'Y' ),
            esc_html( $restaurant_name )
        );
    }

    /**
     * Formate une date YYYY-MM-DD en format lisible.
     */
    private function format_date( string $date ): string {
        $timestamp = strtotime( $date );
        return $timestamp ? date_i18n( 'l j F Y', $timestamp ) : $date;
    }
}
