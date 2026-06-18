<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    /*
     * wp_head() est un hook obligatoire.
     * WordPress et les plugins y injectent leurs <meta>, <link>, <script>.
     * Parallèle Symfony : équivalent à {{ encore_entry_link_tags() }} en Twig,
     * mais en bien plus puissant — des dizaines de plugins l'utilisent.
     * Ne JAMAIS l'oublier, sinon les plugins ne fonctionnent pas.
     */
    wp_head();
    ?>
</head>
<body <?php body_class(); ?>>
<?php
/*
 * wp_body_open() — Hook émis juste après <body>.
 * Requis depuis WP 5.2. Plugins de tracking (GTM, etc.) l'utilisent.
 */
wp_body_open();
?>

<?php
$cta_label    = get_theme_mod( 'hero_cta_label', 'Réserver une table' );
$cta_position = get_theme_mod( 'cta_position', 'bottom-right' );
$site_lang    = ( isset( $_COOKIE['site_lang'] ) && $_COOKIE['site_lang'] === 'en' ) ? 'en' : 'it';
?>
<header class="site-header<?php echo $cta_position !== 'hidden' && $cta_position !== 'top-right' ? ' site-header--has-bottom-cta' : ''; ?>" id="top">
    <?php
    wp_nav_menu( [
        'theme_location'  => 'primary',
        'menu_class'      => 'nav-menu',
        'container'       => 'nav',
        'container_class' => 'site-nav',
        'fallback_cb'     => false,
    ] );
    ?>

    <div class="lang-switcher">
        <a href="#" class="lang-btn<?php echo $site_lang === 'it' ? ' active' : ''; ?>" data-lang="it" aria-label="Italiano">🇮🇹 IT</a>
        <a href="#" class="lang-btn<?php echo $site_lang === 'en' ? ' active' : ''; ?>" data-lang="en" aria-label="English">🇬🇧 EN</a>
    </div>

    <?php if ( $cta_position !== 'hidden' ) : ?>
        <a href="#reservation"
           class="btn btn-primary header-cta header-cta--<?php echo esc_attr( $cta_position ); ?>">
            <?php echo esc_html( $cta_label ); ?>
        </a>
    <?php endif; ?>
</header>
