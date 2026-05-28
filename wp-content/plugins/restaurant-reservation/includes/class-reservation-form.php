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
            [],
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
        wp_localize_script( 'restaurant-res-form', 'resConfig', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            // wp_create_nonce() génère un token unique lié à la session utilisateur
            'nonce'   => wp_create_nonce( 'submit_reservation' ),
            'i18n'    => [
                'sending'  => __( 'Envoi en cours…', 'restaurant-reservation' ),
                'success'  => __( 'Votre demande de réservation a bien été envoyée ! Vous recevrez une confirmation par email.', 'restaurant-reservation' ),
                'error'    => __( 'Une erreur est survenue. Veuillez réessayer.', 'restaurant-reservation' ),
            ],
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
        $required = [ 'prenom', 'nom', 'email', 'telephone', 'date', 'heure', 'personnes' ];
        $errors   = [];

        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                $errors[] = sprintf(
                    __( 'Le champ "%s" est obligatoire.', 'restaurant-reservation' ),
                    $field
                );
            }
        }

        // Validation email
        if ( ! empty( $_POST['email'] ) && ! is_email( $_POST['email'] ) ) {
            $errors[] = __( 'L\'adresse email n\'est pas valide.', 'restaurant-reservation' );
        }

        // Validation date (format YYYY-MM-DD et pas dans le passé)
        if ( ! empty( $_POST['date'] ) ) {
            $date = sanitize_text_field( wp_unslash( $_POST['date'] ) );
            if ( strtotime( $date ) < strtotime( 'today' ) ) {
                $errors[] = __( 'La date de réservation doit être dans le futur.', 'restaurant-reservation' );
            }
        }

        // Validation nombre de personnes
        $personnes = absint( $_POST['personnes'] ?? 0 );
        if ( $personnes < 1 || $personnes > 50 ) {
            $errors[] = __( 'Le nombre de personnes doit être entre 1 et 50.', 'restaurant-reservation' );
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
            'prenom'    => sanitize_text_field( wp_unslash( $_POST['prenom'] ) ),
            'nom'       => sanitize_text_field( wp_unslash( $_POST['nom'] ) ),
            'email'     => sanitize_email( wp_unslash( $_POST['email'] ) ),
            'telephone' => sanitize_text_field( wp_unslash( $_POST['telephone'] ) ),
            'date'      => sanitize_text_field( wp_unslash( $_POST['date'] ) ),
            'heure'     => sanitize_text_field( wp_unslash( $_POST['heure'] ) ),
            'personnes' => $personnes,
            'message'   => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
        ];

        // 4. CRÉATION DE LA RÉSERVATION
        $post_id = Reservation_CPT::create( $data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [
                'messages' => [ __( 'Impossible d\'enregistrer la réservation. Veuillez réessayer.', 'restaurant-reservation' ) ],
            ], 500 );
        }

        // 5. RÉPONSE JSON DE SUCCÈS
        wp_send_json_success( [
            'message' => __( 'Votre demande de réservation a bien été envoyée ! Vous recevrez une confirmation par email.', 'restaurant-reservation' ),
            'id'      => $post_id,
        ] );
    }
}
