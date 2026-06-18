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
            '{{client_nom}}'              => __( 'Nom du client', 'restaurant-reservation' ),
            '{{client_email}}'            => __( 'Email du client', 'restaurant-reservation' ),
            '{{client_telephone}}'        => __( 'Téléphone du client', 'restaurant-reservation' ),
            '{{reservation_date}}'        => __( 'Date de la réservation', 'restaurant-reservation' ),
            '{{reservation_heure}}'       => __( 'Heure de la réservation', 'restaurant-reservation' ),
            '{{reservation_personnes}}'   => __( 'Nombre de personnes', 'restaurant-reservation' ),
            '{{restaurant_nom}}'          => __( 'Nom du restaurant', 'restaurant-reservation' ),
            '{{restaurant_contact_email}}'=> __( 'Email de contact du restaurant', 'restaurant-reservation' ),
            '{{restaurant_contact_phone}}'=> __( 'Téléphone du restaurant', 'restaurant-reservation' ),
            '{{restaurant_address}}'      => __( 'Adresse du restaurant', 'restaurant-reservation' ),
            '{{detail_url}}'              => __( 'Lien vers la réservation dans le backoffice (notification propriétaire uniquement)', 'restaurant-reservation' ),
        ];
    }

    /**
     * Listener de l'événement "reservation_created".
     * Envoie une notification à l'adresse admin configurée dans les settings.
     *
     * @param int $post_id ID de la réservation nouvellement créée
     */
    public function send_admin_notification( int $post_id ): void {
        $reservation = Reservation_CPT::get( $post_id );
        if ( ! $reservation ) {
            return;
        }

        $settings     = get_option( 'restaurant_res_settings', [] );
        $notify_email = $settings['notify_email'] ?? get_bloginfo( 'admin_email' );

        if ( empty( $notify_email ) || ! is_email( $notify_email ) ) {
            return;
        }

        // Lien direct vers la page de détail dans le backoffice.
        // L'admin clique dessus depuis son email et arrive directement sur la réservation.
        $detail_url = add_query_arg( [
            'page'           => 'restaurant-reservations',
            'action'         => 'view',
            'reservation_id' => $post_id,
        ], admin_url( 'admin.php' ) );

        $subject_tpl = $settings['subject_notify'] ?? '';
        $body_tpl    = $settings['email_notify']   ?? '';

        if ( ! empty( $subject_tpl ) && ! empty( $body_tpl ) ) {
            $subject = $this->render_template( $subject_tpl, $reservation, [ '{{detail_url}}' => $detail_url ] );
            $body    = $this->render_template( $body_tpl,    $reservation, [ '{{detail_url}}' => $detail_url ] );
        } else {
            $subject = sprintf(
                '🍽️ Nouvelle réservation — %s le %s',
                $reservation['nom'],
                date_i18n( 'd/m/Y', strtotime( $reservation['date'] ) )
            );
            $body = $this->build_admin_notification_html( $reservation, $detail_url );
        }

        $this->send(
            $notify_email,
            $subject,
            $body,
            get_bloginfo( 'name' ),
            $settings['sender_email'] ?? get_bloginfo( 'admin_email' )
        );
    }

    /**
     * Construit le HTML de l'email de notification admin.
     */
    private function build_admin_notification_html( array $r, string $detail_url ): string {
        $primary = get_theme_mod( 'color_primary', '#c8a96e' );
        $date_fmt = date_i18n( 'l j F Y', strtotime( $r['date'] ) );

        $message_row = $r['message']
            ? sprintf( '<tr><td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;width:180px;">Message</td><td style="padding:8px 0;border-bottom:1px solid #eee;">%s</td></tr>', nl2br( esc_html( $r['message'] ) ) )
            : '';

        return sprintf( '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Nouvelle réservation</title></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Georgia,serif;">
<table width="100%%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 20px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;max-width:100%%;">

      <!-- En-tête -->
      <tr>
        <td style="background:%s;padding:28px 30px;text-align:center;">
          <p style="margin:0;color:rgba(255,255,255,0.8);font-size:13px;letter-spacing:0.1em;text-transform:uppercase;">%s</p>
          <h1 style="margin:8px 0 0;color:#fff;font-size:22px;">🍽️ Nouvelle demande de réservation</h1>
        </td>
      </tr>

      <!-- Corps -->
      <tr>
        <td style="padding:30px;">
          <table width="100%%" cellpadding="0" cellspacing="0" style="font-size:15px;color:#333;line-height:1.6;">
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;width:180px;">Client</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;"><strong>%s</strong></td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;">Email</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;"><a href="mailto:%s" style="color:%s;">%s</a></td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;">Téléphone</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;">%s</td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;">Date</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;"><strong>%s</strong></td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;">Heure</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;"><strong>%s</strong></td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;color:#888;font-size:13px;">Couverts</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;"><strong>%d personne(s)</strong></td>
            </tr>
            %s
          </table>

          <!-- Bouton CTA -->
          <table width="100%%" cellpadding="0" cellspacing="0" style="margin-top:30px;">
            <tr>
              <td align="center">
                <a href="%s"
                   style="display:inline-block;padding:14px 32px;background:%s;color:#fff;text-decoration:none;border-radius:4px;font-family:sans-serif;font-size:14px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;">
                  ✓ Traiter cette réservation
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding-top:10px;">
                <p style="font-size:12px;color:#aaa;margin:0;">
                  Ou copiez ce lien : <a href="%s" style="color:%s;font-size:11px;">%s</a>
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Pied de page -->
      <tr>
        <td style="padding:16px 30px;background:#f9f9f9;text-align:center;font-size:12px;color:#aaa;">
          %s &mdash; Notification automatique
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>',
            esc_attr( sanitize_hex_color( $primary ) ),
            esc_html( get_bloginfo( 'name' ) ),
            esc_html( $r['nom'] ),
            esc_attr( $r['email'] ),
            esc_attr( sanitize_hex_color( $primary ) ),
            esc_html( $r['email'] ),
            esc_html( $r['telephone'] ),
            esc_html( $date_fmt ),
            esc_html( $r['heure'] ),
            (int) $r['personnes'],
            $message_row,
            esc_url( $detail_url ),
            esc_attr( sanitize_hex_color( $primary ) ),
            esc_url( $detail_url ),
            esc_attr( sanitize_hex_color( $primary ) ),
            esc_html( $detail_url ),
            esc_html( get_bloginfo( 'name' ) )
        );
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

        $settings    = get_option( 'restaurant_res_settings', [] );
        $is_accepted = $new_status === Reservation_CPT::STATUS_ACCEPTED;
        $action_key  = $is_accepted ? 'accepted' : 'rejected';

        $lang     = in_array( $reservation['lang'], [ 'it', 'en' ], true ) ? $reservation['lang'] : 'it';
        $subject  = $settings[ "subject_{$action_key}_{$lang}" ] ?? $settings[ "subject_{$action_key}_it" ] ?? '';
        $template = $settings[ "email_{$action_key}_{$lang}" ]   ?? $settings[ "email_{$action_key}_it" ]   ?? '';

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
    private function render_template( string $template, array $reservation, array $extras = [] ): string {
        $settings = get_option( 'restaurant_res_settings', [] );

        $replacements = [
            '{{client_prenom}}'           => $reservation['prenom'] ?? '',
            '{{client_nom}}'              => $reservation['nom'],
            '{{client_email}}'            => $reservation['email'],
            '{{client_telephone}}'        => $reservation['telephone'],
            '{{reservation_date}}'        => $this->format_date( $reservation['date'] ),
            '{{reservation_heure}}'       => $reservation['heure'],
            '{{reservation_personnes}}'   => $reservation['personnes'],
            '{{restaurant_nom}}'          => get_bloginfo( 'name' ),
            '{{restaurant_contact_email}}'=> $settings['restaurant_contact_email'] ?? '',
            '{{restaurant_contact_phone}}'=> $settings['restaurant_contact_phone'] ?? '',
            '{{restaurant_address}}'      => $settings['restaurant_address'] ?? '',
        ];

        $replacements = array_merge( $replacements, $extras );

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

        // Si le corps est du HTML mais PAS un document complet (template admin),
        // on échappe et on enveloppe. Sinon (build_admin_notification_html), on
        // envoie tel quel — le document HTML est déjà complet et escapé.
        if ( $is_html && ! str_contains( $body, '<!DOCTYPE' ) ) {
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
