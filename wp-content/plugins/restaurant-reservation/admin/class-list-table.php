<?php
/**
 * admin/class-list-table.php
 *
 * Table de liste des réservations en backoffice (étend WP_List_Table).
 *
 * =============================================================================
 * WP_LIST_TABLE
 * =============================================================================
 * WP_List_Table est la classe abstraite de WordPress pour toutes les listes
 * admin (articles, utilisateurs, extensions…).
 * Elle gère nativement :
 * - La pagination
 * - Le tri par colonnes (cliquable)
 * - Les filtres par statut (liens en haut)
 * - Les actions groupées (bulk actions)
 * - La recherche
 *
 * Pour l'utiliser :
 * 1. Étendre WP_List_Table
 * 2. Implémenter get_columns(), get_sortable_columns(), prepare_items()
 * 3. Implémenter column_{slug}() pour chaque colonne personnalisée
 *
 * Parallèle Symfony : équivalent à un CRUD EasyAdmin ou SonataAdmin list view,
 * mais en PHP pur sans configuration YAML.
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table n'est disponible que dans l'admin — on la charge si nécessaire
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Reservation_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'reservation',   // Nom singulier (pour les messages WP)
            'plural'   => 'reservations',  // Nom pluriel
            'ajax'     => false,           // Pas de rechargement AJAX de la table
        ] );
    }

    /**
     * Déclare les colonnes de la table.
     * Clé = slug interne, Valeur = libellé affiché.
     *
     * La méthode column_{key}() sera appelée pour chaque colonne custom.
     * La colonne 'cb' est réservée pour les checkboxes (bulk actions).
     */
    public function get_columns(): array {
        return [
            'cb'        => '<input type="checkbox">',  // Checkbox pour sélection multiple
            'client'    => __( 'Client', 'restaurant-reservation' ),
            'contact'   => __( 'Contact', 'restaurant-reservation' ),
            'datetime'  => __( 'Date & Heure', 'restaurant-reservation' ),
            'personnes' => __( 'Couverts', 'restaurant-reservation' ),
            'status'    => __( 'Statut', 'restaurant-reservation' ),
            'created'   => __( 'Demande reçue', 'restaurant-reservation' ),
            'actions'   => __( 'Actions', 'restaurant-reservation' ),
        ];
    }

    /**
     * Colonnes triables : clé = slug colonne, valeur = [orderby, is_currently_sorted]
     * WP ajoute automatiquement les liens de tri dans les en-têtes de colonnes.
     */
    protected function get_sortable_columns(): array {
        return [
            'datetime' => [ 'meta_date', false ],
            'status'   => [ 'post_status', false ],
            'created'  => [ 'post_date', true ],  // true = trié par défaut
        ];
    }

    /**
     * Actions groupées disponibles (menu déroulant "Actions groupées").
     */
    protected function get_bulk_actions(): array {
        return [
            'accept' => __( 'Accepter', 'restaurant-reservation' ),
            'reject' => __( 'Refuser', 'restaurant-reservation' ),
            'trash'  => __( 'Mettre à la corbeille', 'restaurant-reservation' ),
        ];
    }

    /**
     * Liens de filtre par statut (au-dessus de la table).
     * Similaire aux filtres "Tout | Publiés | Brouillons" des articles WP.
     */
    protected function get_views(): array {
        $current_status = $_GET['status'] ?? 'all';
        $base_url       = admin_url( 'admin.php?page=restaurant-reservations' );

        // Compte les réservations par statut
        $counts = $this->count_by_status();

        $views = [];

        $all_count = array_sum( $counts );
        $views['all'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            esc_url( $base_url ),
            $current_status === 'all' ? 'class="current"' : '',
            __( 'Toutes', 'restaurant-reservation' ),
            $all_count
        );

        $status_labels = [
            Reservation_CPT::STATUS_PENDING  => __( 'En attente', 'restaurant-reservation' ),
            Reservation_CPT::STATUS_ACCEPTED => __( 'Acceptées', 'restaurant-reservation' ),
            Reservation_CPT::STATUS_REJECTED => __( 'Refusées', 'restaurant-reservation' ),
        ];

        foreach ( $status_labels as $status => $label ) {
            $count = $counts[ $status ] ?? 0;
            $views[ $status ] = sprintf(
                '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'status', $status, $base_url ) ),
                $current_status === $status ? 'class="current"' : '',
                esc_html( $label ),
                $count
            );
        }

        return $views;
    }

    /**
     * Prépare les données de la table.
     * C'est la méthode principale — elle effectue la requête et configure la pagination.
     *
     * Parallèle Symfony : équivalent à une méthode de Repository qui retourne
     * un PaginationInterface (KnpPaginatorBundle).
     */
    public function prepare_items(): void {
        // Pagination
        $per_page     = 20;
        $current_page = $this->get_pagenum();  // Numéro de page depuis l'URL

        // Paramètres de tri
        $orderby = sanitize_key( $_GET['orderby'] ?? 'post_date' );
        $order   = strtoupper( sanitize_key( $_GET['order'] ?? 'DESC' ) );
        $order   = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';

        // Filtre par statut
        $status = sanitize_key( $_GET['status'] ?? 'all' );
        $statuses = [ Reservation_CPT::STATUS_PENDING, Reservation_CPT::STATUS_ACCEPTED, Reservation_CPT::STATUS_REJECTED ];
        $post_status = ( $status !== 'all' && in_array( $status, $statuses, true ) )
            ? $status
            : $statuses;

        // Recherche
        $search = sanitize_text_field( $_GET['s'] ?? '' );

        // Construction de la WP_Query
        // Parallèle Symfony : équivalent à un QueryBuilder Doctrine
        $args = [
            'post_type'      => 'reservation',
            'post_status'    => $post_status,
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => $orderby === 'meta_date' ? 'meta_value' : $orderby,
            'order'          => $order,
        ];

        if ( $orderby === 'meta_date' ) {
            $args['meta_key'] = Reservation_CPT::META_DATE;
        }

        if ( $search ) {
            // Recherche dans les meta (nom, email, téléphone)
            // WP_Query ne supporte pas nativement la recherche dans les meta —
            // on utilise meta_query avec LIKE
            $args['meta_query'] = [
                'relation' => 'OR',
                [ 'key' => Reservation_CPT::META_NOM,       'value' => $search, 'compare' => 'LIKE' ],
                [ 'key' => Reservation_CPT::META_PRENOM,    'value' => $search, 'compare' => 'LIKE' ],
                [ 'key' => Reservation_CPT::META_EMAIL,     'value' => $search, 'compare' => 'LIKE' ],
                [ 'key' => Reservation_CPT::META_TELEPHONE, 'value' => $search, 'compare' => 'LIKE' ],
            ];
        }

        $query = new WP_Query( $args );

        // Construit le tableau de données pour la table
        $this->items = [];
        foreach ( $query->posts as $post ) {
            $this->items[] = Reservation_CPT::get( $post->ID );
        }

        // Configure la pagination
        // set_pagination_args() est requis par WP_List_Table pour les liens prev/next
        $this->set_pagination_args( [
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages,
        ] );

        // Déclare les colonnes (requis par WP_List_Table)
        $this->_column_headers = [
            $this->get_columns(),
            [],  // Colonnes cachées
            $this->get_sortable_columns(),
            'client',  // Colonne primaire (affichée en gras)
        ];
    }

    // =========================================================================
    // Callbacks de colonnes — column_{slug}( $item )
    // WP_List_Table appelle column_{slug}() pour chaque colonne déclarée.
    // Si la méthode n'existe pas, column_default() est appelée comme fallback.
    // =========================================================================

    /** Colonne checkbox (bulk actions) */
    protected function column_cb( $item ): string {
        return sprintf( '<input type="checkbox" name="reservation_ids[]" value="%d">', $item['id'] );
    }

    /** Colonne : Nom + Prénom avec lien vers le détail */
    protected function column_client( $item ): string {
        $detail_url = add_query_arg( [
            'page'           => 'restaurant-reservations',
            'action'         => 'view',
            'reservation_id' => $item['id'],
        ], admin_url( 'admin.php' ) );

        $name = $item['prenom']
            ? sprintf( '%s %s', esc_html( $item['prenom'] ), esc_html( $item['nom'] ) )
            : esc_html( $item['nom'] );

        return sprintf(
            '<strong><a href="%s">%s</a></strong>',
            esc_url( $detail_url ),
            $name
        );
    }

    /** Colonne : Email + Téléphone */
    protected function column_contact( $item ): string {
        return sprintf(
            '<a href="mailto:%s">%s</a><br><small>%s</small>',
            esc_attr( $item['email'] ),
            esc_html( $item['email'] ),
            esc_html( $item['telephone'] )
        );
    }

    /** Colonne : Date + Heure */
    protected function column_datetime( $item ): string {
        $timestamp = strtotime( $item['date'] );
        $formatted = $timestamp ? date_i18n( 'd/m/Y', $timestamp ) : esc_html( $item['date'] );
        return sprintf( '<strong>%s</strong><br><small>%s</small>',
            $formatted,
            esc_html( $item['heure'] )
        );
    }

    /** Colonne : Nombre de personnes */
    protected function column_personnes( $item ): string {
        return '<strong>' . esc_html( $item['personnes'] ) . '</strong>';
    }

    /** Colonne : Badge de statut coloré */
    protected function column_status( $item ): string {
        $config = [
            Reservation_CPT::STATUS_PENDING  => [ 'label' => __( 'En attente', 'restaurant-reservation' ), 'class' => 'res-badge--pending' ],
            Reservation_CPT::STATUS_ACCEPTED => [ 'label' => __( 'Acceptée', 'restaurant-reservation' ),  'class' => 'res-badge--accepted' ],
            Reservation_CPT::STATUS_REJECTED => [ 'label' => __( 'Refusée', 'restaurant-reservation' ),   'class' => 'res-badge--rejected' ],
        ];

        $cfg = $config[ $item['status'] ] ?? [ 'label' => $item['status'], 'class' => '' ];
        return sprintf(
            '<span class="res-badge %s">%s</span>',
            esc_attr( $cfg['class'] ),
            esc_html( $cfg['label'] )
        );
    }

    /** Colonne : Date de soumission */
    protected function column_created( $item ): string {
        return esc_html( date_i18n( 'd/m/Y H:i', strtotime( $item['created'] ) ) );
    }

    /** Colonne : Boutons d'action Accepter/Refuser */
    protected function column_actions( $item ): string {
        if ( $item['status'] === Reservation_CPT::STATUS_ACCEPTED ) {
            return '<em>' . esc_html__( 'Déjà acceptée', 'restaurant-reservation' ) . '</em>';
        }
        if ( $item['status'] === Reservation_CPT::STATUS_REJECTED ) {
            return '<em>' . esc_html__( 'Déjà refusée', 'restaurant-reservation' ) . '</em>';
        }

        return sprintf(
            '<button class="button button-primary res-action-btn" data-id="%d" data-action="%s">%s</button> '
            . '<button class="button res-action-btn" data-id="%d" data-action="%s" style="color:#a00;">%s</button>',
            $item['id'], Reservation_CPT::STATUS_ACCEPTED, esc_html__( 'Accepter', 'restaurant-reservation' ),
            $item['id'], Reservation_CPT::STATUS_REJECTED, esc_html__( 'Refuser', 'restaurant-reservation' )
        );
    }

    /** Fallback pour les colonnes sans méthode dédiée */
    protected function column_default( $item, $column_name ): string {
        return esc_html( $item[ $column_name ] ?? '—' );
    }

    /** Texte affiché quand la table est vide */
    public function no_items(): void {
        esc_html_e( 'Aucune réservation trouvée.', 'restaurant-reservation' );
    }

    /** Compte les réservations par statut pour les liens de filtre */
    private function count_by_status(): array {
        $counts   = [];
        $statuses = [ Reservation_CPT::STATUS_PENDING, Reservation_CPT::STATUS_ACCEPTED, Reservation_CPT::STATUS_REJECTED ];

        foreach ( $statuses as $status ) {
            $query = new WP_Query( [
                'post_type'   => 'reservation',
                'post_status' => $status,
                'fields'      => 'ids',   // Ne charge que les IDs (requête légère)
            ] );
            $counts[ $status ] = $query->found_posts;
        }

        return $counts;
    }
}
