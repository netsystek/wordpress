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
    <?php
    /*
     * Sélecteur de langue — affiché uniquement si Polylang est actif.
     *
     * pll_the_languages() est la fonction de Polylang qui génère les liens
     * vers les versions dans chaque langue de la page courante.
     *
     * Parallèle Symfony : équivalent au LocaleSwitcher de Symfony UX
     * ou à un menu construit avec $router->generate('route', ['_locale' => 'it']).
     *
     * Le paramètre 'raw' => 1 retourne un tableau PHP au lieu du HTML,
     * ce qui nous permet de personnaliser complètement le rendu.
     */
    if ( function_exists( 'pll_the_languages' ) ) :
        $languages = pll_the_languages( [ 'raw' => 1 ] );
        if ( ! empty( $languages ) ) :
            ?>
            <nav class="language-switcher" aria-label="<?php esc_attr_e( 'Sélecteur de langue', 'restaurant-theme' ); ?>">
                <?php foreach ( $languages as $lang ) :
                    $classes  = 'lang-item';
                    $classes .= $lang['current_lang'] ? ' lang-item--active' : '';
                    ?>
                    <a href="<?php echo esc_url( $lang['url'] ); ?>"
                       class="<?php echo esc_attr( $classes ); ?>"
                       lang="<?php echo esc_attr( $lang['locale'] ); ?>"
                       hreflang="<?php echo esc_attr( $lang['locale'] ); ?>"
                       <?php echo $lang['current_lang'] ? 'aria-current="true"' : ''; ?>>
                        <?php echo esc_html( strtoupper( $lang['slug'] ) ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif;
    endif; ?>
</header>
