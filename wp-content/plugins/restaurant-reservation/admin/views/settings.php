<?php
/**
 * Vue : Page de configuration avec onglets par langue
 *
 * La Settings API affiche les sections d'un "page slug" donné.
 * On utilise un onglet par langue, chaque onglet = un page slug différent.
 * Toutes les sections partagent le même groupe (restaurant_res_settings_group)
 * et la même option (restaurant_res_settings) — un seul tableau en base.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$langs       = Reservation_Admin::get_supported_langs();
$active_lang = sanitize_key( $_GET['lang'] ?? 'it' );
if ( ! array_key_exists( $active_lang, $langs ) ) {
    $active_lang = 'it';
}
$active_page = "restaurant-res-settings-{$active_lang}";
$base_url    = admin_url( 'admin.php?page=restaurant-res-settings' );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Configuration — Restaurant Reservation', 'restaurant-reservation' ); ?></h1>

    <?php settings_errors( 'restaurant_res_settings' ); ?>

    <!-- Onglets de langue -->
    <nav class="nav-tab-wrapper res-lang-tabs" style="margin-bottom: 20px;">
        <?php foreach ( $langs as $slug => $label ) :
            $tab_url = add_query_arg( 'lang', $slug, $base_url );
            $active  = $slug === $active_lang ? 'nav-tab-active' : '';
            ?>
            <a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Formulaire Settings API -->
    <form method="post" action="options.php">
        <?php settings_fields( 'restaurant_res_settings_group' ); ?>

        <?php if ( $active_lang === 'it' ) : ?>
            <!-- Expéditeur affiché seulement sur l'onglet principal (it) -->
            <?php do_settings_sections( 'restaurant-res-settings' ); ?>
        <?php endif; ?>

        <!-- Sections email de la langue active -->
        <?php do_settings_sections( $active_page ); ?>

        <?php submit_button( __( 'Enregistrer les modifications', 'restaurant-reservation' ) ); ?>
    </form>

    <?php if ( $active_lang === 'it' ) : ?>
    <!-- Section test email -->
    <div class="postbox" style="margin-top: 30px;">
        <div class="postbox-header">
            <h2><?php esc_html_e( 'Test d\'envoi d\'email', 'restaurant-reservation' ); ?></h2>
        </div>
        <div class="inside">
            <p>
                <?php esc_html_e( 'Envoyez un email de test pour vérifier la configuration SMTP.', 'restaurant-reservation' ); ?>
                <?php printf(
                    esc_html__( 'En développement, les emails sont capturés par Mailpit : %s', 'restaurant-reservation' ),
                    '<a href="http://localhost:8025" target="_blank">http://localhost:8025</a>'
                ); ?>
            </p>
            <?php
            $test_url = add_query_arg( [
                'page'       => 'restaurant-res-settings',
                'test_email' => '1',
                '_wpnonce'   => wp_create_nonce( 'res_test_email' ),
            ], admin_url( 'admin.php' ) );

            if ( isset( $_GET['test_email'] ) && check_admin_referer( 'res_test_email' ) ) {
                $settings = get_option( 'restaurant_res_settings', [] );
                $to       = $settings['sender_email'] ?? get_bloginfo( 'admin_email' );
                $sent     = wp_mail( $to, sprintf( '[%s] Test email', get_bloginfo( 'name' ) ),
                    '<p>Email de test — <strong>Restaurant Reservation</strong>.</p>',
                    [ 'Content-Type: text/html; charset=UTF-8' ] );
                echo $sent
                    ? '<div class="notice notice-success inline"><p>' . sprintf( esc_html__( 'Email de test envoyé à %s !', 'restaurant-reservation' ), esc_html( $to ) ) . '</p></div>'
                    : '<div class="notice notice-error inline"><p>' . esc_html__( 'Échec de l\'envoi. Vérifiez la configuration SMTP.', 'restaurant-reservation' ) . '</p></div>';
            }
            ?>
            <a href="<?php echo esc_url( $test_url ); ?>" class="button">
                <?php esc_html_e( 'Envoyer un email de test', 'restaurant-reservation' ); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Aide : valeurs par défaut des templates -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2>📋 <?php esc_html_e( 'Templates par défaut', 'restaurant-reservation' ); ?> — <?php echo esc_html( $langs[ $active_lang ] ); ?></h2>
        </div>
        <div class="inside">
            <?php
            $defaults = [
                'it' => [
                    'subject_accepted' => 'La tua prenotazione è confermata — {{restaurant_nom}}',
                    'email_accepted'   => "Ciao {{client_prenom}},\n\nLa tua prenotazione per {{reservation_personnes}} persone il {{reservation_date}} alle {{reservation_heure}} è confermata.\n\nA presto,\n{{restaurant_nom}}",
                    'subject_rejected' => 'La tua prenotazione — {{restaurant_nom}}',
                    'email_rejected'   => "Ciao {{client_prenom}},\n\nSiamo spiacenti, non è possibile confermare la tua prenotazione del {{reservation_date}} alle {{reservation_heure}}.\n\nCordiali saluti,\n{{restaurant_nom}}",
                ],
                'en' => [
                    'subject_accepted' => 'Your reservation is confirmed — {{restaurant_nom}}',
                    'email_accepted'   => "Hello {{client_prenom}},\n\nYour reservation for {{reservation_personnes}} guests on {{reservation_date}} at {{reservation_heure}} is confirmed.\n\nSee you soon,\n{{restaurant_nom}}",
                    'subject_rejected' => 'Your reservation — {{restaurant_nom}}',
                    'email_rejected'   => "Hello {{client_prenom}},\n\nWe are sorry, we cannot confirm your reservation for {{reservation_date}} at {{reservation_heure}}.\n\nKind regards,\n{{restaurant_nom}}",
                ],
                'fr' => [
                    'subject_accepted' => 'Votre réservation est confirmée — {{restaurant_nom}}',
                    'email_accepted'   => "Bonjour {{client_prenom}},\n\nVotre réservation pour {{reservation_personnes}} personne(s) le {{reservation_date}} à {{reservation_heure}} est confirmée.\n\nÀ bientôt,\n{{restaurant_nom}}",
                    'subject_rejected' => 'Votre réservation — {{restaurant_nom}}',
                    'email_rejected'   => "Bonjour {{client_prenom}},\n\nNous sommes désolés, nous ne pouvons pas confirmer votre réservation du {{reservation_date}} à {{reservation_heure}}.\n\nCordialement,\n{{restaurant_nom}}",
                ],
            ];
            $d = $defaults[ $active_lang ] ?? $defaults['it'];
            ?>
            <p style="color:#666; font-size:13px;"><?php esc_html_e( 'Copiez-collez ces valeurs si les champs sont vides.', 'restaurant-reservation' ); ?></p>
            <table class="widefat striped" style="font-size:13px;">
                <tr><th style="width:200px;">subject_accepted</th><td><code><?php echo esc_html( $d['subject_accepted'] ); ?></code></td></tr>
                <tr><th>email_accepted</th><td><pre style="margin:0; white-space:pre-wrap;"><?php echo esc_html( $d['email_accepted'] ); ?></pre></td></tr>
                <tr><th>subject_rejected</th><td><code><?php echo esc_html( $d['subject_rejected'] ); ?></code></td></tr>
                <tr><th>email_rejected</th><td><pre style="margin:0; white-space:pre-wrap;"><?php echo esc_html( $d['email_rejected'] ); ?></pre></td></tr>
            </table>
        </div>
    </div>

</div>
