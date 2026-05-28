<?php
/**
 * inc/customizer.php — WordPress Customizer API
 *
 * Le Customizer (Apparence > Personnaliser) permet aux admins de modifier
 * le thème avec un aperçu en temps réel.
 *
 * Parallèle Symfony : c'est l'équivalent d'un formulaire EasyAdmin/SonataAdmin
 * pour configurer le thème. Les valeurs sont stockées dans wp_options (la table
 * de config de WP), récupérables avec get_theme_mod() ou get_option().
 *
 * Concepts clés :
 *   Panel    = groupe de sections (ex: "Restaurant Theme")
 *   Section  = groupe de contrôles (ex: "Header", "Couleurs")
 *   Setting  = la valeur stockée (clé/valeur dans wp_options)
 *   Control  = le champ HTML affiché dans l'admin (input, color picker, upload...)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'customize_register', 'restaurant_customizer_register' );

function restaurant_customizer_register( WP_Customize_Manager $wp_customize ) {

    // =========================================================================
    // PANEL principal — regroupe toutes nos sections
    // =========================================================================
    $wp_customize->add_panel( 'restaurant_panel', [
        'title'       => __( 'Restaurant Theme', 'restaurant-theme' ),
        'description' => __( 'Personnalisez votre site restaurant.', 'restaurant-theme' ),
        'priority'    => 30,
    ] );

    // =========================================================================
    // SECTION : Header
    // =========================================================================
    $wp_customize->add_section( 'restaurant_header', [
        'title'    => __( 'Header', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 10,
    ] );

    // Setting : image de fond du header
    $wp_customize->add_setting( 'header_background_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',  // Sécurité : nettoie l'URL
        'transport'         => 'refresh',       // 'refresh' recharge la page, 'postMessage' = temps réel JS
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'header_background_image', [
        'label'   => __( "Image de fond du header", 'restaurant-theme' ),
        'section' => 'restaurant_header',
    ] ) );

    // Setting : texte d'accroche (headline) du hero
    $wp_customize->add_setting( 'hero_headline', [
        'default'           => __( 'Bienvenue dans notre restaurant', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'hero_headline', [
        'label'   => __( 'Titre principal (hero)', 'restaurant-theme' ),
        'section' => 'restaurant_header',
        'type'    => 'text',
    ] );

    // Setting : sous-titre du hero
    $wp_customize->add_setting( 'hero_subheadline', [
        'default'           => __( 'Cuisine traditionnelle française', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'hero_subheadline', [
        'label'   => __( 'Sous-titre (hero)', 'restaurant-theme' ),
        'section' => 'restaurant_header',
        'type'    => 'text',
    ] );

    // =========================================================================
    // SECTION : Couleurs
    // =========================================================================
    $wp_customize->add_section( 'restaurant_colors', [
        'title'    => __( 'Couleurs', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 20,
    ] );

    $wp_customize->add_setting( 'color_primary', [
        'default'           => '#c8a96e',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',  // Mise à jour en temps réel sans refresh
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'color_primary', [
        'label'   => __( 'Couleur principale (or/accent)', 'restaurant-theme' ),
        'section' => 'restaurant_colors',
    ] ) );

    $wp_customize->add_setting( 'color_secondary', [
        'default'           => '#1a1a1a',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'color_secondary', [
        'label'   => __( 'Couleur secondaire (fond sombre)', 'restaurant-theme' ),
        'section' => 'restaurant_colors',
    ] ) );

    // =========================================================================
    // SECTION : À propos
    // =========================================================================
    $wp_customize->add_section( 'restaurant_about', [
        'title'    => __( 'À propos', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 25,
    ] );

    foreach ( [
        'about_title'  => [ 'label' => 'Titre de la section', 'default' => 'Une cuisine du cœur', 'type' => 'text' ],
        'about_text'   => [ 'label' => 'Paragraphe 1', 'default' => 'Depuis 1985, nous perpétuons la tradition de la cuisine française avec des produits frais sélectionnés chaque matin.', 'type' => 'textarea' ],
        'about_text_2' => [ 'label' => 'Paragraphe 2', 'default' => 'Venez découvrir un cadre chaleureux au cœur de la ville.', 'type' => 'textarea' ],
    ] as $key => $args ) {
        $wp_customize->add_setting( $key, [
            'default'           => $args['default'],
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( $key, [
            'label'   => __( $args['label'], 'restaurant-theme' ),
            'section' => 'restaurant_about',
            'type'    => $args['type'],
        ] );
    }

    $wp_customize->add_setting( 'about_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'about_image', [
        'label'   => __( 'Photo de la section À propos', 'restaurant-theme' ),
        'section' => 'restaurant_about',
    ] ) );

    // =========================================================================
    // SECTION : Section Parallax
    // =========================================================================
    $wp_customize->add_section( 'restaurant_parallax', [
        'title'    => __( 'Section Parallax', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 30,
    ] );

    $wp_customize->add_setting( 'parallax_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'parallax_image', [
        'label'   => __( 'Image de fond (parallax)', 'restaurant-theme' ),
        'section' => 'restaurant_parallax',
    ] ) );

    $wp_customize->add_setting( 'parallax_quote', [
        'default'           => __( '"La cuisine est l\'art de transformer les produits de la nature en plaisir."', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'parallax_quote', [
        'label'   => __( 'Citation / texte de la section parallax', 'restaurant-theme' ),
        'section' => 'restaurant_parallax',
        'type'    => 'textarea',
    ] );

    // =========================================================================
    // SECTION : Carousel / Galerie
    // =========================================================================
    $wp_customize->add_section( 'restaurant_carousel', [
        'title'    => __( 'Carousel / Galerie', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 35,
    ] );

    $wp_customize->add_setting( 'carousel_title', [
        'default'           => __( 'Notre galerie', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'carousel_title', [
        'label'   => __( 'Titre de la section', 'restaurant-theme' ),
        'section' => 'restaurant_carousel',
        'type'    => 'text',
    ] );

    // =========================================================================
    // SECTION : Réservation (textes)
    // =========================================================================
    $wp_customize->add_section( 'restaurant_reservation_texts', [
        'title'    => __( 'Section Réservation', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 45,
    ] );

    $wp_customize->add_setting( 'reservation_title', [
        'default'           => __( 'Réserver une table', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'reservation_title', [
        'label'   => __( 'Titre de la section', 'restaurant-theme' ),
        'section' => 'restaurant_reservation_texts',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'reservation_text', [
        'default'           => __( 'Réservez votre table en quelques secondes.', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'reservation_text', [
        'label'   => __( 'Texte d\'introduction', 'restaurant-theme' ),
        'section' => 'restaurant_reservation_texts',
        'type'    => 'textarea',
    ] );

    // =========================================================================
    // SECTION : Menu de la carte (textes)
    // =========================================================================
    $wp_customize->add_section( 'restaurant_menu_section', [
        'title'    => __( 'Section Carte / Menu', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 38,
    ] );

    $wp_customize->add_setting( 'menu_section_title', [
        'default'           => __( 'Notre carte', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'menu_section_title', [
        'label'   => __( 'Titre de la section', 'restaurant-theme' ),
        'section' => 'restaurant_menu_section',
        'type'    => 'text',
    ] );

    // =========================================================================
    // SECTION : Réseaux sociaux
    // =========================================================================
    $wp_customize->add_section( 'restaurant_social', [
        'title'    => __( 'Réseaux sociaux', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 40,
    ] );

    $social_networks = [
        'facebook'    => [ 'label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f' ],
        'instagram'   => [ 'label' => 'Instagram', 'icon' => 'fa-brands fa-instagram' ],
        'tripadvisor' => [ 'label' => 'TripAdvisor', 'icon' => 'fa-brands fa-tripadvisor' ],
    ];

    foreach ( $social_networks as $key => $network ) {
        $wp_customize->add_setting( "social_{$key}", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ] );
        $wp_customize->add_control( "social_{$key}", [
            'label'       => $network['label'] . ' URL',
            'section'     => 'restaurant_social',
            'type'        => 'url',
            'input_attrs' => [ 'placeholder' => "https://www.{$key}.com/votrerestaurant" ],
        ] );
    }

    // =========================================================================
    // SECTION : Footer
    // =========================================================================
    $wp_customize->add_section( 'restaurant_footer', [
        'title'    => __( 'Footer', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 50,
    ] );

    $wp_customize->add_setting( 'footer_address', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'footer_address', [
        'label'   => __( 'Adresse', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'footer_phone', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'footer_phone', [
        'label'   => __( 'Téléphone', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
        'type'    => 'tel',
    ] );

    $wp_customize->add_setting( 'footer_copyright', [
        'default'           => '© ' . date( 'Y' ) . ' Mon Restaurant. Tous droits réservés.',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'footer_copyright', [
        'label'   => __( 'Texte copyright', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
        'type'    => 'text',
    ] );

    // =========================================================================
    // CSS dynamique : inject les couleurs du Customizer comme CSS Variables
    // =========================================================================
    // transport: 'postMessage' + ce code JS permet la mise à jour en temps réel
    $wp_customize->get_setting( 'color_primary' )->transport   = 'postMessage';
    $wp_customize->get_setting( 'color_secondary' )->transport = 'postMessage';
}

/**
 * Génère le CSS inline avec les valeurs du Customizer.
 *
 * Parallèle Symfony : équivalent à injecter des variables dans un template Twig
 * via {% set color = app.config.color_primary %} puis `color: {{ color }}`.
 *
 * get_theme_mod( $setting_name, $default ) récupère la valeur stockée
 * par le Customizer, ou la valeur par défaut si non configurée.
 */
add_action( 'wp_head', 'restaurant_customizer_css' );

function restaurant_customizer_css() {
    $color_primary   = get_theme_mod( 'color_primary', '#c8a96e' );
    $color_secondary = get_theme_mod( 'color_secondary', '#1a1a1a' );
    ?>
    <style id="restaurant-customizer-css">
        :root {
            --color-primary:   <?php echo sanitize_hex_color( $color_primary ); ?>;
            --color-secondary: <?php echo sanitize_hex_color( $color_secondary ); ?>;
        }
    </style>
    <?php
}
