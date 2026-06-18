<?php
/**
 * Vue : Détail d'une réservation
 * Variable disponible : $reservation (array) — injectée par Reservation_Admin::render_detail_page()
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! isset( $reservation ) ) return;

$back_url = admin_url( 'admin.php?page=restaurant-reservations' );

$status_config = [
    Reservation_CPT::STATUS_PENDING  => [ 'label' => __( 'En attente', 'restaurant-reservation' ),  'class' => 'res-badge--pending' ],
    Reservation_CPT::STATUS_ACCEPTED => [ 'label' => __( 'Acceptée', 'restaurant-reservation' ),    'class' => 'res-badge--accepted' ],
    Reservation_CPT::STATUS_REJECTED => [ 'label' => __( 'Refusée', 'restaurant-reservation' ),     'class' => 'res-badge--rejected' ],
];
$status_cfg = $status_config[ $reservation['status'] ] ?? [ 'label' => $reservation['status'], 'class' => '' ];
?>

<div class="wrap">
    <h1>
        <a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
            ← <?php esc_html_e( 'Retour à la liste', 'restaurant-reservation' ); ?>
        </a>
        <?php esc_html_e( 'Détail de la réservation', 'restaurant-reservation' ); ?>
        <span class="res-badge <?php echo esc_attr( $status_cfg['class'] ); ?>" style="font-size:14px; vertical-align:middle;">
            <?php echo esc_html( $status_cfg['label'] ); ?>
        </span>
    </h1>
    <hr class="wp-header-end">

    <div class="res-detail-grid">

        <!-- Informations du client -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Informations client', 'restaurant-reservation' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table res-detail-table">
                    <?php if ( $reservation['prenom'] ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Prénom', 'restaurant-reservation' ); ?></th>
                        <td><?php echo esc_html( $reservation['prenom'] ); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e( 'Nom', 'restaurant-reservation' ); ?></th>
                        <td><?php echo esc_html( $reservation['nom'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Email', 'restaurant-reservation' ); ?></th>
                        <td><a href="mailto:<?php echo esc_attr( $reservation['email'] ); ?>"><?php echo esc_html( $reservation['email'] ); ?></a></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Téléphone', 'restaurant-reservation' ); ?></th>
                        <td><?php echo esc_html( $reservation['telephone'] ); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Détails de la réservation -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Détails de la réservation', 'restaurant-reservation' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table res-detail-table">
                    <tr>
                        <th><?php esc_html_e( 'Date', 'restaurant-reservation' ); ?></th>
                        <td><?php echo esc_html( date_i18n( 'l j F Y', strtotime( $reservation['date'] ) ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Heure', 'restaurant-reservation' ); ?></th>
                        <td><?php echo esc_html( $reservation['heure'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Nombre de couverts', 'restaurant-reservation' ); ?></th>
                        <td><strong><?php echo esc_html( $reservation['personnes'] ); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Demande reçue le', 'restaurant-reservation' ); ?></th>
                        <td><?php echo esc_html( date_i18n( 'd/m/Y à H:i', strtotime( $reservation['created'] ) ) ); ?></td>
                    </tr>
                    <?php if ( $reservation['message'] ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Message du client', 'restaurant-reservation' ); ?></th>
                        <td><em><?php echo nl2br( esc_html( $reservation['message'] ) ); ?></em></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e( 'Langue de la réservation', 'restaurant-reservation' ); ?></th>
                        <td>
                            <?php
                            $lang_flags = [ 'it' => '🇮🇹 Italiano', 'en' => '🇬🇧 English', 'fr' => '🇫🇷 Français' ];
                            echo esc_html( $lang_flags[ $reservation['lang'] ] ?? strtoupper( $reservation['lang'] ) );
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

    <!-- Actions -->
    <?php if ( $reservation['status'] === Reservation_CPT::STATUS_PENDING ) : ?>
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2><?php esc_html_e( 'Traiter cette réservation', 'restaurant-reservation' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php esc_html_e( 'Un email sera automatiquement envoyé au client avec le template configuré.', 'restaurant-reservation' ); ?></p>
            <div class="res-action-buttons">
                <button class="button button-primary button-hero res-action-btn"
                        data-id="<?php echo esc_attr( $reservation['id'] ); ?>"
                        data-action="<?php echo esc_attr( Reservation_CPT::STATUS_ACCEPTED ); ?>">
                    ✓ <?php esc_html_e( 'Accepter la réservation', 'restaurant-reservation' ); ?>
                </button>
                <button class="button button-hero res-action-btn"
                        data-id="<?php echo esc_attr( $reservation['id'] ); ?>"
                        data-action="<?php echo esc_attr( Reservation_CPT::STATUS_REJECTED ); ?>"
                        style="color: #a00; border-color: #a00; margin-left: 10px;">
                    ✗ <?php esc_html_e( 'Refuser la réservation', 'restaurant-reservation' ); ?>
                </button>
            </div>
            <div id="res-action-feedback" style="display:none; margin-top:15px;"></div>
        </div>
    </div>

    <?php elseif ( $reservation['status'] === Reservation_CPT::STATUS_ACCEPTED ) : ?>
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2><?php esc_html_e( 'Réservation confirmée', 'restaurant-reservation' ); ?></h2>
        </div>
        <div class="inside">
            <div class="notice notice-success inline">
                <p><?php esc_html_e( 'Cette réservation a été acceptée. Un email de confirmation a été envoyé au client.', 'restaurant-reservation' ); ?></p>
            </div>
            <div class="res-action-buttons" style="margin-top: 15px;">
                <button class="button button-hero res-action-btn"
                        data-id="<?php echo esc_attr( $reservation['id'] ); ?>"
                        data-action="<?php echo esc_attr( Reservation_CPT::STATUS_REJECTED ); ?>"
                        style="color: #a00; border-color: #a00;">
                    ✗ <?php esc_html_e( 'Annuler cette réservation', 'restaurant-reservation' ); ?>
                </button>
            </div>
            <div id="res-action-feedback" style="display:none; margin-top:15px;"></div>
        </div>
    </div>

    <?php else : ?>
    <div class="notice notice-info inline" style="margin-top: 20px;">
        <p><?php esc_html_e( 'Cette réservation a été refusée. Un email d\'information a été envoyé au client.', 'restaurant-reservation' ); ?></p>
    </div>
    <?php endif; ?>

</div>
