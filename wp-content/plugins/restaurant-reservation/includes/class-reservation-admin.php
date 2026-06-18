<?php
/**
 * includes/class-reservation-admin.php
 *
 * Interface d'administration : menu, pages, gestion AJAX des statuts, settings.
 *
 * =============================================================================
 * SYSTÈME DE MENUS WORDPRESS
 * =============================================================================
 * add_menu_page()    — crée un menu de premier niveau dans la sidebar admin
 * add_submenu_page() — ajoute une sous-page à un menu existant
 *
 * Chaque page a :
 * - Un $menu_slug (identifiant unique, utilisé dans les URLs)
 * - Un $capability (qui peut voir cette page — ex: 'manage_options' = admins)
 * - Un $callback (fonction qui génère le HTML de la page)
 *
 * Parallèle Symfony : équivalent à des routes dans routes.yaml avec des
 * voters de sécurité IS_GRANTED('ROLE_ADMIN').
 *
 * SETTINGS API WORDPRESS
 * =============================================================================
 * La Settings API est le système natif de WP pour les pages de configuration.
 * register_setting()   — enregistre une option avec validation
 * add_settings_section() — groupe des champs dans la page
 * add_settings_field() — ajoute un champ HTML
 * settings_fields()    — génère le nonce et les champs cachés du formulaire
 * do_settings_sections() — affiche les sections enregistrées
 *
 * Parallèle Symfony : équivalent à un FormType avec OptionsResolver.
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Reservation_Admin {

    private Reservation_Email $email_service;

    public function __construct( Reservation_Email $email_service ) {
        // Injection de dépendance manuelle (WP n'a pas de container DI natif)
        // Parallèle Symfony : équivalent à l'autowiring du service dans le constructeur
        $this->email_service = $email_service;
        $this->register_settings_api();
    }

    /**
     * Charge les assets admin (CSS/JS) uniquement sur nos pages.
     * Evite de charger des scripts inutiles sur toutes les pages admin.
     */
    public function enqueue_assets( string $hook ): void {
        // $hook contient l'identifiant de la page courante
        // ex: "toplevel_page_restaurant-reservations"
        if ( strpos( $hook, 'restaurant-res' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'restaurant-res-admin',
            RESTAURANT_RES_URL . 'admin/assets/css/admin.css',
            [],
            RESTAURANT_RES_VERSION
        );

        wp_enqueue_script(
            'restaurant-res-admin',
            RESTAURANT_RES_URL . 'admin/assets/js/admin.js',
            [],
            RESTAURANT_RES_VERSION,
            true
        );

        wp_localize_script( 'restaurant-res-admin', 'resAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'update_reservation_status' ),
            'i18n'    => [
                'confirmAccept' => __( 'Confirmer l\'acceptation de cette réservation ?', 'restaurant-reservation' ),
                'confirmReject' => __( 'Confirmer le refus de cette réservation ?', 'restaurant-reservation' ),
                'updating'      => __( 'Mise à jour…', 'restaurant-reservation' ),
                'error'         => __( 'Une erreur est survenue.', 'restaurant-reservation' ),
            ],
        ] );
    }

    /**
     * Enregistre les menus dans le backoffice WordPress.
     * Appelé sur le hook admin_menu.
     */
    public function register_menu(): void {
        // Menu principal
        add_menu_page(
            __( 'Réservations', 'restaurant-reservation' ),    // Titre de la page (<title>)
            __( 'Réservations', 'restaurant-reservation' ),    // Texte dans le menu
            'manage_options',                                    // Capacité requise
            'restaurant-reservations',                          // Slug unique (identifiant URL)
            [ $this, 'page_list' ],                             // Callback HTML
            'dashicons-calendar-alt',                           // Icône Dashicons
            25                                                  // Position dans le menu
        );

        // Sous-menu : liste (duplique le menu parent pour renommer le lien)
        add_submenu_page(
            'restaurant-reservations',              // Parent slug
            __( 'Toutes les réservations', 'restaurant-reservation' ),
            __( 'Toutes les réservations', 'restaurant-reservation' ),
            'manage_options',
            'restaurant-reservations',
            [ $this, 'page_list' ]
        );

        // Sous-menu : configuration
        add_submenu_page(
            'restaurant-reservations',
            __( 'Configuration', 'restaurant-reservation' ),
            __( 'Configuration', 'restaurant-reservation' ),
            'manage_options',
            'restaurant-res-settings',
            [ $this, 'page_settings' ]
        );
    }

    /**
     * Page : Liste des réservations.
     * Délègue l'affichage à la classe Reservation_List_Table (WP_List_Table).
     */
    public function page_list(): void {
        // Vérifie la capacité de l'utilisateur courant
        // Parallèle Symfony : équivalent à $this->denyAccessUnlessGranted('ROLE_ADMIN')
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Accès refusé.', 'restaurant-reservation' ) );
        }

        // Détermine si on affiche le détail d'une réservation
        $action  = $_GET['action'] ?? 'list';
        $post_id = absint( $_GET['reservation_id'] ?? 0 );

        if ( $action === 'view' && $post_id > 0 ) {
            $this->render_detail_page( $post_id );
            return;
        }

        // Affichage de la liste via WP_List_Table
        include RESTAURANT_RES_DIR . 'admin/views/list-reservations.php';
    }

    /**
     * Affiche la page de détail d'une réservation.
     */
    private function render_detail_page( int $post_id ): void {
        $reservation = Reservation_CPT::get( $post_id );
        if ( ! $reservation ) {
            wp_die( __( 'Réservation introuvable.', 'restaurant-reservation' ) );
        }
        include RESTAURANT_RES_DIR . 'admin/views/reservation-detail.php';
    }

    /**
     * Page : Configuration du plugin (Settings API).
     */
    public function page_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Accès refusé.', 'restaurant-reservation' ) );
        }
        include RESTAURANT_RES_DIR . 'admin/views/settings.php';
    }

    /**
     * Enregistre les champs de configuration via la Settings API WordPress.
     *
     * La Settings API gère automatiquement :
     * - La sauvegarde en base (table wp_options)
     * - La validation via le callback sanitize
     * - La sécurité CSRF (nonce intégré)
     * - Les messages de succès/erreur
     *
     * Parallèle Symfony : équivalent à un FormType + handleRequest() + isValid()
     * + $em->flush() — tout ça en quelques fonctions WP.
     */
    private function register_settings_api(): void {
        add_action( 'admin_init', function() {
            // register_setting() : lie un nom de formulaire à une option en base
            // Le troisième paramètre est le callback de validation/sanitisation
            register_setting( 'restaurant_res_settings_group', 'restaurant_res_settings', [
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
            ] );

            // Section : Capacité & Jours d'ouverture
            add_settings_section(
                'restaurant_res_capacity_section',
                'Capacité & Jours d\'ouverture',
                function() {
                    echo '<p class="description">Contraintes appliquées au formulaire de réservation et à la validation serveur.</p>';
                },
                'restaurant-res-settings'
            );

            // Confirmation automatique
            add_settings_field(
                'restaurant_res_auto_confirm',
                'Confirmation automatique',
                function() {
                    $settings = get_option( 'restaurant_res_settings', [] );
                    $checked  = ! empty( $settings['auto_confirm'] ) ? ' checked' : '';
                    echo '<label>';
                    printf( '<input type="checkbox" name="restaurant_res_settings[auto_confirm]" value="1"%s>', $checked );
                    echo ' Confirmer et envoyer l\'email de confirmation au client dès la soumission du formulaire</label>';
                    echo '<p class="description" style="margin-top:6px;">Si décoché, les réservations restent <em>En attente</em> jusqu\'à votre validation manuelle.</p>';
                },
                'restaurant-res-settings',
                'restaurant_res_capacity_section'
            );

            $this->add_settings_text_field( 'max_personnes', 'Nombre maximum de couverts', 'restaurant_res_capacity_section', 'number' );

            add_settings_field(
                'restaurant_res_jours_fermeture',
                'Jours de fermeture',
                function() {
                    $settings = get_option( 'restaurant_res_settings', [] );
                    $closed   = array_map( 'intval', $settings['jours_fermeture'] ?? [] );
                    $days     = [ 0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi' ];
                    echo '<fieldset>';
                    foreach ( $days as $num => $label ) {
                        printf(
                            '<label style="display:inline-block;margin-right:1.25rem;"><input type="checkbox" name="restaurant_res_settings[jours_fermeture][]" value="%d"%s> %s</label>',
                            $num,
                            in_array( $num, $closed, true ) ? ' checked' : '',
                            esc_html( $label )
                        );
                    }
                    echo '</fieldset><p class="description">Les clients ne pourront pas sélectionner ces jours dans le formulaire.</p>';
                },
                'restaurant-res-settings',
                'restaurant_res_capacity_section'
            );

            // Section : Notification au propriétaire
            add_settings_section(
                'restaurant_res_notify_section',
                '🔔 Email de notification au propriétaire',
                function() {
                    echo '<p class="description">Email envoyé au propriétaire dès qu\'une nouvelle réservation est soumise. Laissez vide pour utiliser le template HTML par défaut.</p>';
                    $this->render_variables_hint();
                },
                'restaurant-res-settings'
            );

            $this->add_settings_text_field(     'subject_notify', __( 'Objet', 'restaurant-reservation' ),           'restaurant_res_notify_section' );
            $this->add_settings_textarea_field( 'email_notify',   __( 'Corps du message', 'restaurant-reservation' ), 'restaurant_res_notify_section' );

            // Section : Labels du formulaire public
            add_settings_section(
                'restaurant_res_labels_section',
                'Labels du formulaire de réservation',
                function() {
                    echo '<p class="description">Textes affichés dans le formulaire public de réservation.</p>';
                },
                'restaurant-res-settings'
            );

            $form_labels = [
                'label_nom'                  => 'Nom',
                'placeholder_nom'            => 'Placeholder — Nom',
                'label_email'                => 'Email',
                'placeholder_email'          => 'Placeholder — Email',
                'label_telephone'            => 'Téléphone',
                'placeholder_telephone'      => 'Placeholder — Téléphone',
                'label_date'                 => 'Date',
                'label_heure'                => 'Heure',
                'label_heure_placeholder'    => 'Placeholder select Heure',
                'label_personnes'            => 'Couverts',
                'label_personnes_placeholder'=> 'Placeholder champ Couverts',
                'label_required'             => '* Champs obligatoires',
                'label_submit'               => 'Demander une réservation',
            ];
            foreach ( $form_labels as $key => $default ) {
                $this->add_settings_text_field( $key, $default, 'restaurant_res_labels_section' );
            }

            // Section : Expéditeur des emails
            add_settings_section(
                'restaurant_res_sender_section',
                __( 'Expéditeur des emails', 'restaurant-reservation' ),
                function() {
                    echo '<p>' . esc_html__( 'Nom et adresse email utilisés comme expéditeur des emails automatiques.', 'restaurant-reservation' ) . '</p>';
                },
                'restaurant-res-settings'
            );

            $this->add_settings_text_field( 'sender_name',  __( 'Nom de l\'expéditeur', 'restaurant-reservation' ),  'restaurant_res_sender_section' );
            $this->add_settings_text_field( 'sender_email', __( 'Email de l\'expéditeur', 'restaurant-reservation' ), 'restaurant_res_sender_section', 'email' );
            $this->add_settings_text_field( 'notify_email', __( 'Email de notification (admin)', 'restaurant-reservation' ), 'restaurant_res_sender_section', 'email' );
            $this->add_settings_text_field( 'restaurant_contact_email', __( 'Email de contact du restaurant', 'restaurant-reservation' ), 'restaurant_res_sender_section', 'email' );
            $this->add_settings_text_field( 'restaurant_contact_phone', __( 'Téléphone du restaurant', 'restaurant-reservation' ), 'restaurant_res_sender_section' );
            $this->add_settings_text_field( 'restaurant_address', __( 'Adresse du restaurant', 'restaurant-reservation' ), 'restaurant_res_sender_section' );

            // Sections de templates d'email par langue (IT / EN / FR)
            $langs = [ 'it' => '🇮🇹 Italiano', 'en' => '🇬🇧 English', 'fr' => '🇫🇷 Français' ];
            foreach ( $langs as $lang_slug => $lang_label ) {
                $page_slug = "restaurant-res-settings-{$lang_slug}";

                add_settings_section(
                    "restaurant_res_accepted_{$lang_slug}",
                    sprintf( '✅ Email de confirmation — %s', $lang_label ),
                    function() { $this->render_variables_hint(); },
                    $page_slug
                );
                $this->add_settings_text_field(     "subject_accepted_{$lang_slug}", __( 'Objet', 'restaurant-reservation' ), "restaurant_res_accepted_{$lang_slug}", 'text', $page_slug );
                $this->add_settings_textarea_field( "email_accepted_{$lang_slug}",   __( 'Corps', 'restaurant-reservation' ), "restaurant_res_accepted_{$lang_slug}", $page_slug );

                add_settings_section(
                    "restaurant_res_rejected_{$lang_slug}",
                    sprintf( '❌ Email de refus — %s', $lang_label ),
                    function() { $this->render_variables_hint(); },
                    $page_slug
                );
                $this->add_settings_text_field(     "subject_rejected_{$lang_slug}", __( 'Objet', 'restaurant-reservation' ), "restaurant_res_rejected_{$lang_slug}", 'text', $page_slug );
                $this->add_settings_textarea_field( "email_rejected_{$lang_slug}",   __( 'Corps', 'restaurant-reservation' ), "restaurant_res_rejected_{$lang_slug}", $page_slug );
            }
        } );
    }

    /** Langues supportées */
    public static function get_supported_langs(): array {
        return [ 'it' => '🇮🇹 Italiano', 'en' => '🇬🇧 English' ];
    }

    /**
     * Helper : ajoute un champ texte dans la Settings API.
     * $page = page slug WP où afficher le champ (default = restaurant-res-settings)
     */
    private function add_settings_text_field( string $key, string $label, string $section, string $type = 'text', string $page = 'restaurant-res-settings' ): void {
        add_settings_field(
            "restaurant_res_{$key}",
            $label,
            function() use ( $key, $type ) {
                $settings = get_option( 'restaurant_res_settings', [] );
                $value    = $settings[ $key ] ?? '';
                printf(
                    '<input type="%s" name="restaurant_res_settings[%s]" value="%s" class="regular-text">',
                    esc_attr( $type ),
                    esc_attr( $key ),
                    esc_attr( $value )
                );
            },
            $page,
            $section
        );
    }

    /**
     * Helper : ajoute un champ textarea dans la Settings API.
     */
    private function add_settings_textarea_field( string $key, string $label, string $section, string $page = 'restaurant-res-settings' ): void {
        add_settings_field(
            "restaurant_res_{$key}",
            $label,
            function() use ( $key ) {
                $settings = get_option( 'restaurant_res_settings', [] );
                $value    = $settings[ $key ] ?? '';
                printf(
                    '<textarea name="restaurant_res_settings[%s]" rows="8" class="large-text">%s</textarea>',
                    esc_attr( $key ),
                    esc_textarea( $value )
                );
            },
            $page,
            $section
        );
    }

    /**
     * Affiche la liste des variables disponibles dans les templates.
     */
    private function render_variables_hint(): void {
        echo '<p class="description">' . esc_html__( 'Variables disponibles :', 'restaurant-reservation' ) . '</p>';
        echo '<ul class="res-variables-list">';
        foreach ( Reservation_Email::get_template_variables() as $var => $desc ) {
            printf(
                '<li><code>%s</code> — %s</li>',
                esc_html( $var ),
                esc_html( $desc )
            );
        }
        echo '</ul>';
    }

    /**
     * Callback de validation des settings.
     * Appelé automatiquement par la Settings API avant la sauvegarde.
     *
     * Parallèle Symfony : équivalent à un FormType avec des constraints Assert.
     */
    public function sanitize_settings( array $input ): array {
        /*
         * On part des valeurs existantes en base de données.
         *
         * POURQUOI : la page de configuration a plusieurs onglets (IT / EN / FR),
         * chacun avec son propre <form>. Quand l'onglet IT est soumis, $_POST ne
         * contient QUE les champs IT — les templates EN et FR sont absents de $input.
         * Si on partait d'un tableau vide, chaque sauvegarde d'un onglet effacerait
         * les données des autres onglets.
         *
         * En partant des valeurs existantes, on ne met à jour que les clés présentes
         * dans $input, tout le reste est conservé tel quel.
         *
         * Parallèle Symfony : équivalent à $form->getData() qui part de l'objet
         * existant et n'écrase que les champs soumis.
         */
        $sanitized = get_option( 'restaurant_res_settings', [] );

        // Capacité & jours de fermeture
        if ( array_key_exists( 'max_personnes', $input ) ) {
            $max = absint( $input['max_personnes'] );
            $sanitized['max_personnes']   = $max >= 1 ? $max : 12;
            $sanitized['auto_confirm']    = ! empty( $input['auto_confirm'] ) ? 1 : 0;
            $sanitized['jours_fermeture'] = isset( $input['jours_fermeture'] )
                ? array_map( 'intval', (array) $input['jours_fermeture'] )
                : [];
        }

        // Template de notification propriétaire
        if ( array_key_exists( 'subject_notify', $input ) ) {
            $sanitized['subject_notify'] = sanitize_text_field( $input['subject_notify'] );
        }
        if ( array_key_exists( 'email_notify', $input ) ) {
            $sanitized['email_notify'] = sanitize_textarea_field( $input['email_notify'] );
        }

        // Labels du formulaire public
        foreach ( [ 'label_nom', 'placeholder_nom', 'label_email', 'placeholder_email', 'label_telephone', 'placeholder_telephone', 'label_date', 'label_heure', 'label_heure_placeholder', 'label_personnes', 'label_personnes_placeholder', 'label_required', 'label_submit' ] as $key ) {
            if ( array_key_exists( $key, $input ) ) {
                $sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
            }
        }

        // Champs de la section "Expéditeur" (présents sur l'onglet IT uniquement)
        if ( array_key_exists( 'sender_name', $input ) ) {
            $sanitized['sender_name'] = sanitize_text_field( $input['sender_name'] );
        }
        if ( array_key_exists( 'sender_email', $input ) ) {
            $sanitized['sender_email'] = sanitize_email( $input['sender_email'] );
        }
        if ( array_key_exists( 'notify_email', $input ) ) {
            $sanitized['notify_email'] = sanitize_email( $input['notify_email'] );
        }
        if ( array_key_exists( 'restaurant_contact_email', $input ) ) {
            $sanitized['restaurant_contact_email'] = sanitize_email( $input['restaurant_contact_email'] );
        }
        if ( array_key_exists( 'restaurant_contact_phone', $input ) ) {
            $sanitized['restaurant_contact_phone'] = sanitize_text_field( $input['restaurant_contact_phone'] );
        }
        if ( array_key_exists( 'restaurant_address', $input ) ) {
            $sanitized['restaurant_address'] = sanitize_text_field( $input['restaurant_address'] );
        }

        // Templates par langue — boucle sur toutes les langues et les deux statuts
        foreach ( array_keys( self::get_supported_langs() ) as $lang ) {
            foreach ( [ 'accepted', 'rejected' ] as $action ) {
                $sk = "subject_{$action}_{$lang}";
                $tk = "email_{$action}_{$lang}";
                if ( array_key_exists( $sk, $input ) ) {
                    $sanitized[ $sk ] = sanitize_text_field( $input[ $sk ] );
                }
                if ( array_key_exists( $tk, $input ) ) {
                    $sanitized[ $tk ] = sanitize_textarea_field( $input[ $tk ] );
                }
            }
        }

        // Validation email expéditeur
        if ( ! empty( $sanitized['sender_email'] ) && ! is_email( $sanitized['sender_email'] ) ) {
            add_settings_error(
                'restaurant_res_settings',
                'invalid_email',
                __( 'L\'adresse email de l\'expéditeur n\'est pas valide.', 'restaurant-reservation' ),
                'error'
            );
        }

        return $sanitized;
    }

    /**
     * Handler AJAX : change le statut d'une réservation depuis le backoffice.
     * Appelé sur wp_ajax_update_reservation_status.
     */
    public function handle_status_update(): void {
        // Vérification nonce admin
        check_ajax_referer( 'update_reservation_status', 'nonce' );

        // Vérification des droits
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Droits insuffisants.', 'restaurant-reservation' ) ], 403 );
        }

        $post_id    = absint( $_POST['reservation_id'] ?? 0 );
        $new_status = sanitize_key( $_POST['new_status'] ?? '' );

        if ( ! $post_id || ! $new_status ) {
            wp_send_json_error( [ 'message' => __( 'Paramètres manquants.', 'restaurant-reservation' ) ], 422 );
        }

        $success = Reservation_CPT::update_status( $post_id, $new_status );

        if ( ! $success ) {
            wp_send_json_error( [ 'message' => __( 'Impossible de mettre à jour le statut.', 'restaurant-reservation' ) ], 500 );
        }

        $labels = [
            Reservation_CPT::STATUS_ACCEPTED => __( 'Acceptée', 'restaurant-reservation' ),
            Reservation_CPT::STATUS_REJECTED => __( 'Refusée', 'restaurant-reservation' ),
            Reservation_CPT::STATUS_PENDING  => __( 'En attente', 'restaurant-reservation' ),
        ];

        wp_send_json_success( [
            'message'    => __( 'Statut mis à jour avec succès.', 'restaurant-reservation' ),
            'new_status' => $new_status,
            'label'      => $labels[ $new_status ] ?? $new_status,
        ] );
    }
}
