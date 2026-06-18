<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$cta_label = get_theme_text( 'hero_cta_label', 'Réserver une table' );
$cta_show  = get_theme_mod( 'cta_position', 'bottom-right' ) !== 'hidden';
$site_lang = ( isset( $_COOKIE['site_lang'] ) && $_COOKIE['site_lang'] === 'en' ) ? 'en' : 'it';
?>
<header class="site-header" id="top">

    <!-- Colonne gauche : navigation -->
    <nav class="site-nav" aria-label="Navigation principale">
        <?php
        wp_nav_menu( [
            'theme_location' => 'primary',
            'menu_class'     => 'nav-menu',
            'container'      => false,
            'fallback_cb'    => false,
        ] );
        ?>
    </nav>

    <!-- Centre : logo cliquable → home -->
    <div class="site-logo">
        <?php if ( has_custom_logo() ) : ?>
            <?php the_custom_logo(); ?>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="site-logo__text">
                <?php bloginfo( 'name' ); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Colonne droite : langue + bouton réservation -->
    <div class="header-actions">
        <div class="lang-switcher">
            <a href="#" class="lang-btn<?php echo $site_lang === 'it' ? ' active' : ''; ?>" data-lang="it" aria-label="Italiano">🇮🇹 IT</a>
            <a href="#" class="lang-btn<?php echo $site_lang === 'en' ? ' active' : ''; ?>" data-lang="en" aria-label="English">🇬🇧 EN</a>
        </div>
        <?php if ( $cta_show ) : ?>
            <a href="#reservation" class="btn btn-primary header-cta">
                <?php echo esc_html( $cta_label ); ?>
            </a>
        <?php endif; ?>
    </div>

</header>
