<?php
/**
 * Vue : Page de configuration du plugin
 * La Settings API WordPress gère la sauvegarde automatiquement.
 *
 * Fonctionnement de la Settings API :
 * 1. settings_fields('group') — génère le nonce + champ 'option_page' cachés
 * 2. do_settings_sections('page_slug') — affiche les sections enregistrées dans admin_init
 * 3. submit_button() — bouton Enregistrer
 * 4. WordPress intercepte le POST, valide le nonce, appelle sanitize_callback,
 *    sauvegarde en base et redirige avec un message de succès
 *
 * Tout cela sans écrire une ligne de logique de sauvegarde.
 * Parallèle Symfony : équivalent à $form->handleRequest($request) + $form->isValid()
 * + $em->flush() — géré automatiquement par le framework.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Configuration — Restaurant Reservation', 'restaurant-reservation' ); ?></h1>

    <?php settings_errors( 'restaurant_res_settings' ); ?>

    <form method="post" action="options.php">
        <?php
        // settings_fields() génère :
        // - <input type="hidden" name="option_page" value="restaurant_res_settings_group">
        // - <input type="hidden" name="action" value="update">
        // - Un nonce WordPress (anti-CSRF)
        settings_fields( 'restaurant_res_settings_group' );

        // do_settings_sections() affiche toutes les sections enregistrées
        // via add_settings_section() + add_settings_field() pour cette page
        do_settings_sections( 'restaurant-res-settings' );

        // Bouton Enregistrer avec le texte par défaut WP
        submit_button( __( 'Enregistrer les modifications', 'restaurant-reservation' ) );
        ?>
    </form>

    <!-- Lien pour tester l'envoi d'email (utile en dev avec Mailpit) -->
    <div class="postbox" style="margin-top: 30px;">
        <div class="postbox-header">
            <h2><?php esc_html_e( 'Test d\'envoi d\'email', 'restaurant-reservation' ); ?></h2>
        </div>
        <div class="inside">
            <p>
                <?php esc_html_e( 'Envoyez un email de test pour vérifier la configuration SMTP.', 'restaurant-reservation' ); ?>
                <?php printf(
                    /* translators: %s: URL de Mailpit */
                    esc_html__( 'En développement, les emails sont capturés par Mailpit : %s', 'restaurant-reservation' ),
                    '<a href="http://localhost:8025" target="_blank">http://localhost:8025</a>'
                ); ?>
            </p>
            <?php
            $test_action_url = add_query_arg( [
                'page'        => 'restaurant-res-settings',
                'test_email'  => '1',
                '_wpnonce'    => wp_create_nonce( 'res_test_email' ),
            ], admin_url( 'admin.php' ) );

            // Traitement du test email
            if ( isset( $_GET['test_email'] ) && check_admin_referer( 'res_test_email' ) ) {
                $settings = get_option( 'restaurant_res_settings', [] );
                $to       = $settings['sender_email'] ?? get_bloginfo( 'admin_email' );
                $sent     = wp_mail(
                    $to,
                    sprintf( '[%s] Test email — Restaurant Reservation', get_bloginfo( 'name' ) ),
                    '<p>Ceci est un email de test envoyé depuis le plugin <strong>Restaurant Reservation</strong>.</p>',
                    [ 'Content-Type: text/html; charset=UTF-8' ]
                );
                if ( $sent ) {
                    echo '<div class="notice notice-success inline"><p>' . sprintf( esc_html__( 'Email de test envoyé à %s !', 'restaurant-reservation' ), esc_html( $to ) ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Échec de l\'envoi. Vérifiez la configuration SMTP.', 'restaurant-reservation' ) . '</p></div>';
                }
            }
            ?>
            <a href="<?php echo esc_url( $test_action_url ); ?>" class="button">
                <?php esc_html_e( 'Envoyer un email de test', 'restaurant-reservation' ); ?>
            </a>
        </div>
    </div>
</div>
