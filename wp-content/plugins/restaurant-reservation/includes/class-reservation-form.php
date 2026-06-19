<?php
/**
 * includes/class-reservation-form.php
 *
 * Shortcode [restaurant_reservation] + traitement AJAX du formulaire frontend.
 *
 * =============================================================================
 * SHORTCODES WORDPRESS
 * =============================================================================
 * Un shortcode est une balise courte que l'admin insère dans l'éditeur :
 * [restaurant_reservation]
 * WordPress la remplace par le HTML retourné par le callback.
 *
 * Parallèle Symfony : équivalent à un Twig Component ou à un sous-contrôleur
 * rendu avec {{ render(controller('ReservationController::widget')) }}
 *
 * AJAX WORDPRESS
 * =============================================================================
 * WordPress centralise TOUTES les requêtes AJAX sur admin-ajax.php.
 * Le paramètre POST "action" détermine quel hook est déclenché :
 *   action = "submit_reservation" → wp_ajax_submit_reservation (connectés)
 *                                  → wp_ajax_nopriv_submit_reservation (anonymes)
 *
 * Parallèle Symfony : équivalent à une Route POST /reservation/submit
 * avec un Controller qui répond en JSON.
 *
 * NONCES DE SÉCURITÉ
 * =============================================================================
 * Un nonce (Number Used Once) est un token anti-CSRF généré par WP.
 * wp_create_nonce('action') génère le token côté serveur.
 * check_ajax_referer('action') le vérifie et tue la requête si invalide.
 *
 * Parallèle Symfony : équivalent au CsrfToken + isCsrfTokenValid().
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Reservation_Form {

    /**
     * Charge les assets CSS/JS du formulaire sur le frontend.
     * Appelé sur le hook wp_enqueue_scripts (cf. restaurant-reservation.php).
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'restaurant-res-form',
            RESTAURANT_RES_URL . 'public/assets/css/form.css',
            [ 'restaurant-main' ],
            RESTAURANT_RES_VERSION
        );

        wp_enqueue_script(
            'restaurant-res-form',
            RESTAURANT_RES_URL . 'public/assets/js/form.js',
            [],
            RESTAURANT_RES_VERSION,
            true
        );

        // Injecte les variables PHP → JS
        // Parallèle Symfony : équivalent à json_encode et output dans un <script> Twig
        $settings = get_option( 'restaurant_res_settings', [] );
        $lang     = ( isset( $_COOKIE['site_lang'] ) && $_COOKIE['site_lang'] === 'en' ) ? 'en' : 'it';

        $day_closed_it = $settings['error_day_closed'] ?? '';
        $day_closed_en = $settings['error_day_closed_en'] ?? '';

        $all_i18n = [
            'it' => [
                'sending'  => 'Invio in corso…',
                'error'    => 'Si è verificato un errore. Riprova.',
                'dayFerme' => $day_closed_it ?: 'Il ristorante è chiuso quel giorno. Scegli un\'altra data.',
            ],
            'en' => [
                'sending'  => 'Sending…',
                'error'    => 'An error occurred. Please try again.',
                'dayFerme' => $day_closed_en ?: ( $day_closed_it ?: 'The restaurant is closed on that day. Please choose another date.' ),
            ],
        ];

        wp_localize_script( 'restaurant-res-form', 'resConfig', [
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'submit_reservation' ),
            'joursFermeture' => array_map( 'intval', $settings['jours_fermeture'] ?? [] ),
            'maxPersonnes'   => intval( $settings['max_personnes'] ?? 12 ),
            'currentLang'    => $lang,
            'i18n'           => $all_i18n[ $lang ],
        ] );
    }

    /**
     * Callback du shortcode [restaurant_reservation].
     *
     * RÈGLE IMPORTANTE : un shortcode ne doit JAMAIS afficher directement (echo).
     * Il doit TOUJOURS retourner le HTML sous forme de chaîne.
     * WordPress insère ce retour dans le contenu de la page.
     *
     * Parallèle Symfony : le retour de render() d'un controller, pas echo.
     *
     * @param array $atts Attributs du shortcode (ex: [restaurant_reservation columns="2"])
     * @return string HTML du formulaire
     */
    public function render_shortcode( array $atts = [] ): string {
        // Capture la sortie du template dans un buffer
        // ob_start() / ob_get_clean() = équivalent à $twig->render() qui retourne une string
        ob_start();
        include RESTAURANT_RES_DIR . 'public/views/reservation-form.php';
        return ob_get_clean();
    }

    /**
     * Traitement de la soumission AJAX du formulaire.
     *
     * WordPress appelle cette méthode quand une requête POST arrive sur
     * admin-ajax.php avec action=submit_reservation.
     *
     * La réponse JSON est envoyée avec wp_send_json_success() ou wp_send_json_error()
     * qui set les bons headers, encode en JSON, et appelle die() automatiquement.
     *
     * Parallèle Symfony : équivalent au corps d'une méthode de Controller
     * qui retourne new JsonResponse($data) ou JsonResponse($error, 400).
     */
    public function handle_ajax(): void {
        // 1. VÉRIFICATION DU NONCE (anti-CSRF)
        // check_ajax_referer() vérifie le nonce ET appelle die() si invalide
        // Parallèle Symfony : équivalent à $this->isCsrfTokenValid('submit_reservation', $token)
        check_ajax_referer( 'submit_reservation', 'nonce' );

        // 2. VALIDATION DES DONNÉES REQUISES
        $lang     = ( isset( $_POST['site_lang'] ) && $_POST['site_lang'] === 'en' ) ? 'en' : 'it';
        $errors   = [];

        $settings        = get_option( 'restaurant_res_settings', [] );
        $max_personnes   = intval( $settings['max_personnes'] ?? 12 );
        $jours_fermeture = array_map( 'intval', $settings['jours_fermeture'] ?? [] );

        // Helper : lit le message configuré dans la bonne langue, avec fallback IT puis défaut codé
        $_e = function( string $key, string $default ) use ( $settings, $lang ): string {
            if ( $lang === 'en' ) {
                $en = $settings[ $key . '_en' ] ?? '';
                if ( $en !== '' ) return $en;
            }
            return $settings[ $key ] ?? $default;
        };

        // Champs obligatoires — messages individuels par champ
        $required_map = [
            'nom'       => $_e( 'error_required_nom',       'Il campo «Nome» è obbligatorio.' ),
            'email'     => $_e( 'error_required_email',     'Il campo «Email» è obbligatorio.' ),
            'telephone' => $_e( 'error_required_telephone', 'Il campo «Telefono» è obbligatorio.' ),
            'date'      => $_e( 'error_required_date',      'Il campo «Data» è obbligatorio.' ),
            'heure'     => $_e( 'error_required_heure',     'Il campo «Ora» è obbligatorio.' ),
        ];

        foreach ( $required_map as $field => $message ) {
            if ( empty( $_POST[ $field ] ) ) {
                $errors[] = $message;
            }
        }

        // Validation email
        if ( ! empty( $_POST['email'] ) && ! is_email( $_POST['email'] ) ) {
            $errors[] = $_e( 'error_email_invalid', 'L\'indirizzo email non è valido.' );
        }

        // Validation date (format YYYY-MM-DD, pas dans le passé, pas un jour fermé)
        if ( ! empty( $_POST['date'] ) ) {
            $date = sanitize_text_field( wp_unslash( $_POST['date'] ) );
            if ( strtotime( $date ) < strtotime( 'today' ) ) {
                $errors[] = $_e( 'error_date_past', 'La data deve essere futura.' );
            } elseif ( ! empty( $jours_fermeture ) ) {
                $day_of_week = intval( date( 'w', strtotime( $date ) ) );
                if ( in_array( $day_of_week, $jours_fermeture, true ) ) {
                    $errors[] = $_e( 'error_day_closed', 'Il ristorante è chiuso quel giorno. Scegli un\'altra data.' );
                }
            }
        }

        // Validation heure (créneaux bloqués)
        if ( ! empty( $_POST['heure'] ) ) {
            $heure = sanitize_text_field( wp_unslash( $_POST['heure'] ) );
            if ( preg_match( '/^\d{2}:\d{2}$/', $heure ) ) {
                $to_min = function( string $t ): int {
                    [ $h, $m ] = explode( ':', $t );
                    $h = (int) $h;
                    return ( $h < 6 ? $h + 24 : $h ) * 60 + (int) $m;
                };
                $min             = $to_min( $heure );
                $min_pause_debut = $to_min( $settings['heure_debut_pause'] ?? '15:30' );
                $min_pause_fin   = $to_min( $settings['heure_fin_pause']   ?? '17:30' );
                $min_fermeture   = $to_min( $settings['heure_fermeture']   ?? '23:00' );

                if ( ( $min >= $min_pause_debut && $min < $min_pause_fin ) || $min > $min_fermeture ) {
                    $errors[] = $_e( 'error_heure_bloquee', 'Questo orario non è disponibile. Scegli un altro orario.' );
                }
            }
        }

        // Validation nombre de personnes (range couvre aussi le cas "vide")
        $personnes = absint( $_POST['personnes'] ?? 0 );
        if ( $personnes < 1 || $personnes > $max_personnes ) {
            $tpl = $_e( 'error_personnes_range', 'Il numero di coperti deve essere tra 1 e {max}.' );
            $errors[] = str_replace( '{max}', (string) $max_personnes, $tpl );
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'messages' => $errors ], 422 );
            // wp_send_json_error() appelle die() automatiquement — on n'atteint pas la suite
        }

        // 3. SANITISATION DES DONNÉES
        // sanitize_* = équivalent aux Assert et transformations dans un FormType Symfony
        // WP fournit des fonctions spécialisées par type de données :
        //   sanitize_text_field() : nettoie le HTML, les tags, les sauts de ligne
        //   sanitize_email()      : normalise l'email
        //   sanitize_textarea_field() : comme text_field mais conserve les sauts de ligne
        //   wp_unslash()          : supprime les antislashs ajoutés par magic_quotes (legacy PHP)
        $data = [
            'nom'       => sanitize_text_field( wp_unslash( $_POST['nom'] ) ),
            'email'     => sanitize_email( wp_unslash( $_POST['email'] ) ),
            'telephone' => sanitize_text_field( wp_unslash( $_POST['telephone'] ) ),
            'date'      => sanitize_text_field( wp_unslash( $_POST['date'] ) ),
            'heure'     => sanitize_text_field( wp_unslash( $_POST['heure'] ) ),
            'personnes' => $personnes,
            'lang'      => $lang,
        ];

        // 4. CRÉATION DE LA RÉSERVATION
        $post_id = Reservation_CPT::create( $data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [
                'messages' => [ 'Impossible d\'enregistrer la réservation. Veuillez réessayer.' ],
            ], 500 );
        }

        // 5. CONFIRMATION AUTOMATIQUE (si activée dans les settings)
        if ( ! empty( $settings['auto_confirm'] ) ) {
            Reservation_CPT::update_status( $post_id, Reservation_CPT::STATUS_ACCEPTED );
            // update_status() déclenche do_action('reservation_status_changed', $post_id, STATUS_ACCEPTED)
            // ce qui appelle Reservation_Email::send_status_email() automatiquement
        }

        // 6. RÉPONSE JSON DE SUCCÈS
        $success_messages = [
            'it' => [
                'confirmed' => 'La tua prenotazione è confermata! Ti è stata inviata un\'email di conferma.',
                'pending'   => 'La tua richiesta è stata ricevuta. Ti confermeremo la prenotazione al più presto.',
            ],
            'en' => [
                'confirmed' => 'Your reservation is confirmed! A confirmation email has been sent to you.',
                'pending'   => 'Your request has been received. We will confirm your reservation as soon as possible.',
            ],
        ];
        $status = ! empty( $settings['auto_confirm'] ) ? 'confirmed' : 'pending';
        if ( $status === 'confirmed' ) {
            $custom  = trim( $settings[ "success_confirmed_{$lang}" ] ?? '' );
            $message = $custom !== '' ? $custom : $success_messages[ $lang ]['confirmed'];
        } else {
            $message = $success_messages[ $lang ]['pending'];
        }

        wp_send_json_success( [
            'message' => $message,
            'id'      => $post_id,
        ] );
    }
}
