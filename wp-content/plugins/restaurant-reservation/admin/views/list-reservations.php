<?php
/**
 * Vue : Liste des réservations
 * Incluse depuis Reservation_Admin::page_list()
 * La variable $reservation n'est pas disponible ici — on instancie la table directement.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Instancie et prépare la WP_List_Table
$table = new Reservation_List_Table();

// Traitement des actions groupées (bulk actions)
$action = $table->current_action();  // Retourne l'action sélectionnée ou false

if ( $action && ! empty( $_POST['reservation_ids'] ) ) {
    check_admin_referer( 'bulk-reservations' );  // Nonce auto-généré par WP_List_Table

    $ids = array_map( 'absint', $_POST['reservation_ids'] );
    $new_status = match( $action ) {
        'accept' => Reservation_CPT::STATUS_ACCEPTED,
        'reject' => Reservation_CPT::STATUS_REJECTED,
        default  => null,
    };

    if ( $new_status ) {
        foreach ( $ids as $id ) {
            Reservation_CPT::update_status( $id, $new_status );
        }
        $label = $action === 'accept' ? 'acceptées' : 'refusées';
        echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( '%d réservation(s) %s.', 'restaurant-reservation' ), count( $ids ), $label ) . '</p></div>';
    } elseif ( $action === 'trash' ) {
        foreach ( $ids as $id ) {
            wp_trash_post( $id );
        }
    }
}

$table->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Réservations', 'restaurant-reservation' ); ?></h1>
    <hr class="wp-header-end">

    <?php
    // settings_errors() affiche les messages de succès/erreur de la Settings API
    settings_errors( 'restaurant_res_settings' );
    ?>

    <?php $table->views(); // Affiche les liens de filtre (Toutes | En attente | Acceptées…) ?>

    <form method="get" action="">
        <?php
        // Les champs cachés conservent les paramètres de page dans l'URL
        echo '<input type="hidden" name="page" value="restaurant-reservations">';
        $table->search_box( __( 'Rechercher', 'restaurant-reservation' ), 'reservation-search' );
        ?>
    </form>

    <form method="post">
        <?php
        // Champs cachés pour conserver les filtres lors des bulk actions
        echo '<input type="hidden" name="page" value="restaurant-reservations">';
        if ( isset( $_GET['status'] ) ) {
            echo '<input type="hidden" name="status" value="' . esc_attr( $_GET['status'] ) . '">';
        }

        $table->display();  // Affiche la table complète (header, body, footer, pagination)
        ?>
    </form>
</div>
