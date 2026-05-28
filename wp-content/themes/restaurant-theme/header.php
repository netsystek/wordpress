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

<header class="site-header" id="top">
    <?php
    /*
     * Navigation principale.
     * wp_nav_menu() génère le HTML du menu enregistré dans functions.php.
     * Parallèle Symfony : équivalent à {{ knp_menu_render('main') }} en Twig.
     *
     * Le menu doit être assigné dans WP Admin > Apparence > Menus
     * (ou via le Customizer > Menus).
     */
    wp_nav_menu( [
        'theme_location' => 'primary',
        'menu_class'     => 'nav-menu',
        'container'      => 'nav',
        'container_class' => 'site-nav',
        'fallback_cb'    => false,  // Ne rien afficher si aucun menu assigné
    ] );
    ?>
</header>
