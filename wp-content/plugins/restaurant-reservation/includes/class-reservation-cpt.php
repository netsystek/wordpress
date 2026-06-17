<?php
/**
 * includes/class-reservation-cpt.php
 *
 * Custom Post Type "reservation" + statuts personnalisés.
 *
 * =============================================================================
 * CUSTOM POST TYPE vs TABLE SQL CUSTOM
 * =============================================================================
 * On pourrait créer une table SQL dédiée avec $wpdb.
 * On choisit un CPT car :
 * - L'API WordPress (wp_insert_post, get_post_meta, WP_Query) est déjà optimisée
 * - La pagination, le tri, la recherche sont gérés nativement
 * - On bénéficie du système de statuts (pending/accepted/rejected)
 *
 * Parallèle Symfony :
 * - CPT ≈ Entity Doctrine (mais stocké dans wp_posts + wp_postmeta)
 * - post_meta ≈ colonnes additionnelles (EAV pattern — Entity-Attribute-Value)
 * - post_status ≈ propriété d'état avec enum
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Reservation_CPT {

    // Noms des statuts personnalisés — constantes pour éviter les typos
    const STATUS_PENDING  = 'res_pending';
    const STATUS_ACCEPTED = 'res_accepted';
    const STATUS_REJECTED = 'res_rejected';

    // Noms des métadonnées — préfixées avec _ (convention WP pour les meta "privées")
    // Les meta avec _ ne sont pas affichées dans l'éditeur WP par défaut
    const META_PRENOM    = '_res_prenom';
    const META_NOM       = '_res_nom';
    const META_EMAIL     = '_res_email';
    const META_TELEPHONE = '_res_telephone';
    const META_DATE      = '_res_date';
    const META_HEURE     = '_res_heure';
    const META_PERSONNES = '_res_personnes';
    const META_MESSAGE   = '_res_message';
    const META_LANG      = '_res_lang';       // Langue du visiteur au moment de la soumission

    /**
     * Enregistre le CPT "reservation".
     *
     * register_post_type() crée un nouveau type de contenu dans WordPress.
     * Les réservations sont stockées dans la table wp_posts avec post_type = 'reservation'.
     * Les métadonnées sont dans wp_postmeta.
     *
     * Parallèle Symfony : c'est l'équivalent de créer une Entity + une Migration.
     * Mais ici, pas besoin de migration SQL — WordPress gère la structure.
     */
    public function register_post_type(): void {
        register_post_type( 'reservation', [
            'labels' => [
                'name'               => __( 'Réservations', 'restaurant-reservation' ),
                'singular_name'      => __( 'Réservation', 'restaurant-reservation' ),
                'menu_name'          => __( 'Réservations', 'restaurant-reservation' ),
                'all_items'          => __( 'Toutes les réservations', 'restaurant-reservation' ),
                'view_item'          => __( 'Voir la réservation', 'restaurant-reservation' ),
                'search_items'       => __( 'Rechercher', 'restaurant-reservation' ),
                'not_found'          => __( 'Aucune réservation', 'restaurant-reservation' ),
                'not_found_in_trash' => __( 'Aucune réservation dans la corbeille', 'restaurant-reservation' ),
            ],

            // public: false — les réservations ne sont PAS visibles sur le frontend
            // (pas d'URL /reservation/123, pas dans les résultats de recherche)
            'public'              => false,

            // show_ui: true — on veut quand même une interface dans l'admin
            // (même si public=false, on accède via notre menu custom)
            'show_ui'             => false,  // false car on gère notre propre UI

            // show_in_menu: false — on gère notre propre menu via Reservation_Admin
            'show_in_menu'        => false,

            // supports: quelles meta boxes WP afficher dans l'éditeur
            // On désactive tout car on gère notre propre vue de détail
            'supports'            => [ 'title' ],

            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ] );

        // Enregistre les métadonnées du CPT
        // register_post_meta() documente les meta et les expose via l'API REST
        // Parallèle Symfony : équivalent à déclarer les propriétés de l'Entity
        $metas = [
            self::META_PRENOM    => 'string',
            self::META_NOM       => 'string',
            self::META_EMAIL     => 'string',
            self::META_TELEPHONE => 'string',
            self::META_DATE      => 'string',
            self::META_HEURE     => 'string',
            self::META_PERSONNES => 'integer',
            self::META_MESSAGE   => 'string',
            self::META_LANG      => 'string',
        ];

        foreach ( $metas as $meta_key => $type ) {
            register_post_meta( 'reservation', $meta_key, [
                'single'        => true,   // Une seule valeur (pas un tableau)
                'type'          => $type,
                'show_in_rest'  => false,  // Pas exposé via l'API REST (données privées)
                'auth_callback' => fn() => current_user_can( 'manage_options' ),
            ] );
        }
    }

    /**
     * Enregistre les statuts personnalisés de réservation.
     *
     * WordPress a des statuts natifs (publish, draft, trash…).
     * On crée les nôtres pour le workflow de réservation.
     *
     * Parallèle Symfony : équivalent à un enum PHP ou à l'utilisation de
     * states/transitions avec le composant Workflow de Symfony.
     */
    public function register_post_status(): void {
        $statuses = [
            self::STATUS_PENDING => [
                'label'                     => __( 'En attente', 'restaurant-reservation' ),
                'label_count'               => _n_noop(
                    'En attente <span class="count">(%s)</span>',
                    'En attente <span class="count">(%s)</span>',
                    'restaurant-reservation'
                ),
                'public'                    => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'exclude_from_search'       => true,
            ],
            self::STATUS_ACCEPTED => [
                'label'                     => __( 'Acceptée', 'restaurant-reservation' ),
                'label_count'               => _n_noop(
                    'Acceptée <span class="count">(%s)</span>',
                    'Acceptées <span class="count">(%s)</span>',
                    'restaurant-reservation'
                ),
                'public'                    => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'exclude_from_search'       => true,
            ],
            self::STATUS_REJECTED => [
                'label'                     => __( 'Refusée', 'restaurant-reservation' ),
                'label_count'               => _n_noop(
                    'Refusée <span class="count">(%s)</span>',
                    'Refusées <span class="count">(%s)</span>',
                    'restaurant-reservation'
                ),
                'public'                    => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'exclude_from_search'       => true,
            ],
        ];

        foreach ( $statuses as $status => $args ) {
            register_post_status( $status, $args );
        }
    }

    /**
     * Crée une réservation en base de données.
     *
     * wp_insert_post() insère un enregistrement dans wp_posts.
     * Retourne l'ID du post créé, ou un WP_Error en cas d'échec.
     *
     * Parallèle Symfony : équivalent à $entityManager->persist($reservation) + flush().
     *
     * @param array $data Données validées et sanitisées du formulaire
     * @return int|WP_Error ID de la réservation créée, ou WP_Error
     */
    public static function create( array $data ): int|\WP_Error {
        // Le "titre" du post = nom du client (visible dans les listes admin)
        $title = sprintf(
            '%s %s — %s %s',
            sanitize_text_field( $data['prenom'] ),
            sanitize_text_field( $data['nom'] ),
            sanitize_text_field( $data['date'] ),
            sanitize_text_field( $data['heure'] )
        );

        $post_id = wp_insert_post( [
            'post_title'  => $title,
            'post_type'   => 'reservation',
            'post_status' => self::STATUS_PENDING,  // Toujours "En attente" à la création
            'post_author' => 0,  // 0 = pas d'auteur (réservation anonyme)
        ], true );  // true = retourne WP_Error si échec (pas juste 0)

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Stocke les métadonnées de la réservation
        // update_post_meta() crée ou met à jour la métadonnée
        // Parallèle Symfony : équivalent aux setters sur l'Entity avant flush()
        update_post_meta( $post_id, self::META_PRENOM,    sanitize_text_field( $data['prenom'] ) );
        update_post_meta( $post_id, self::META_NOM,       sanitize_text_field( $data['nom'] ) );
        update_post_meta( $post_id, self::META_EMAIL,     sanitize_email( $data['email'] ) );
        update_post_meta( $post_id, self::META_TELEPHONE, sanitize_text_field( $data['telephone'] ) );
        update_post_meta( $post_id, self::META_DATE,      sanitize_text_field( $data['date'] ) );
        update_post_meta( $post_id, self::META_HEURE,     sanitize_text_field( $data['heure'] ) );
        update_post_meta( $post_id, self::META_PERSONNES, absint( $data['personnes'] ) );
        update_post_meta( $post_id, self::META_MESSAGE,   sanitize_textarea_field( $data['message'] ?? '' ) );

        // Déclenche l'événement "nouvelle réservation créée".
        // Parallèle Symfony : $dispatcher->dispatch(new ReservationCreatedEvent($postId))
        // Le listener (send_admin_notification) est branché dans restaurant-reservation.php.
        do_action( 'reservation_created', $post_id );

        return $post_id;
    }

    /**
     * Récupère toutes les données d'une réservation.
     *
     * Parallèle Symfony : équivalent à $reservationRepository->find($id).
     *
     * @return array|null Tableau associatif ou null si introuvable
     */
    public static function get( int $post_id ): ?array {
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'reservation' ) {
            return null;
        }

        return [
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'status'    => $post->post_status,
            'created'   => $post->post_date,
            'prenom'    => get_post_meta( $post_id, self::META_PRENOM,    true ),
            'nom'       => get_post_meta( $post_id, self::META_NOM,       true ),
            'email'     => get_post_meta( $post_id, self::META_EMAIL,     true ),
            'telephone' => get_post_meta( $post_id, self::META_TELEPHONE, true ),
            'date'      => get_post_meta( $post_id, self::META_DATE,      true ),
            'heure'     => get_post_meta( $post_id, self::META_HEURE,     true ),
            'personnes' => (int) get_post_meta( $post_id, self::META_PERSONNES, true ),
            'message'   => get_post_meta( $post_id, self::META_MESSAGE,   true ),
            'lang'      => get_post_meta( $post_id, self::META_LANG,      true ) ?: 'it',
        ];
    }

    /**
     * Change le statut d'une réservation et déclenche l'événement custom.
     *
     * do_action() déclenche un hook custom — tous les listeners abonnés sont appelés.
     * Parallèle Symfony : équivalent à $eventDispatcher->dispatch(new ReservationStatusChangedEvent(...))
     */
    public static function update_status( int $post_id, string $new_status ): bool {
        $allowed = [ self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_REJECTED ];
        if ( ! in_array( $new_status, $allowed, true ) ) {
            return false;
        }

        $result = wp_update_post( [
            'ID'          => $post_id,
            'post_status' => $new_status,
        ] );

        if ( is_wp_error( $result ) || $result === 0 ) {
            return false;
        }

        // do_action() : déclenche notre événement custom
        // Dans restaurant-reservation.php, on a abonné Reservation_Email::send_status_email
        // à cet événement — le découplage est complet
        do_action( 'reservation_status_changed', $post_id, $new_status );

        return true;
    }
}
