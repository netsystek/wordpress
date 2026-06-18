<?php
/**
 * inc/enqueue.php — Chargement des assets (CSS et JS)
 *
 * Parallèle Symfony : équivalent à webpack.config.js + importmap
 * WordPress gère les dépendances entre scripts via un système de handles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * wp_enqueue_scripts est l'action émise par WP quand il est temps
 * de charger les assets frontend. C'est ici ET SEULEMENT ici qu'on
 * doit appeler wp_enqueue_style() et wp_enqueue_script().
 * Ne JAMAIS mettre de balises <link> ou <script> manuellement dans le HTML.
 */
add_action( 'wp_enqueue_scripts', 'restaurant_enqueue_assets' );
add_action( 'customize_preview_init', 'restaurant_customizer_preview_assets' );

function restaurant_customizer_preview_assets() {
    wp_enqueue_script(
        'restaurant-customizer-preview',
        RESTAURANT_THEME_URI . '/assets/js/customizer-preview.js',
        [ 'customize-preview' ],
        wp_get_theme()->get( 'Version' ),
        true
    );
}

function restaurant_enqueue_assets() {
    $theme_uri     = RESTAURANT_THEME_URI;
    $theme_version = wp_get_theme()->get( 'Version' );

    /*
     * wp_enqueue_style( $handle, $src, $deps, $version, $media )
     * - $handle : identifiant unique (utilisé pour les dépendances)
     * - $src     : URL du fichier
     * - $deps    : tableau de handles dont ce style dépend
     * - $version : pour le cache-busting (?ver=1.0.0 ajouté à l'URL)
     */
    wp_enqueue_style(
        'restaurant-main',
        $theme_uri . '/assets/css/main.css',
        [],
        $theme_version
    );

    // Font Awesome pour les icônes réseaux sociaux
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );

    // Swiper.js — Carousel (CSS)
    wp_enqueue_style(
        'swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        [],
        '11'
    );

    // Google Fonts — Playfair Display (heading) + Lato (body)
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Lato:wght@400;600;700&display=swap',
        [],
        null
    );

    /*
     * wp_enqueue_script( $handle, $src, $deps, $version, $in_footer )
     * - $in_footer : true = script chargé avant </body> (recommandé)
     * - $deps      : ['jquery'] si on dépend de jQuery (on évite ici)
     */
    // Swiper.js — Carousel (JS) — chargé avant notre main.js (dépendance)
    wp_enqueue_script(
        'swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        '11',
        true
    );

    wp_enqueue_script(
        'restaurant-main',
        $theme_uri . '/assets/js/main.js',
        ['swiper'],  // Dépend de swiper — WP garantit que swiper est chargé avant
        $theme_version,
        true  // Chargé dans le footer = meilleures performances
    );

    /*
     * wp_localize_script() injecte des variables PHP → JavaScript.
     * Parallèle Symfony : équivalent à passer des variables à un template Twig.
     * Ici on passe l'URL AJAX de WordPress (nécessaire pour les requêtes fetch).
     */
    wp_localize_script( 'restaurant-main', 'restaurantAjax', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'restaurant_nonce' ),
    ] );
}
