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

    // Setting : couleur de fond du header
    $wp_customize->add_setting( 'header_bg_color', [
        'default'           => '#010101',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'header_bg_color', [
        'label'   => __( 'Couleur de fond du header', 'restaurant-theme' ),
        'section' => 'restaurant_header',
    ] ) );

    // Setting : hauteur du logo dans le header (px)
    $wp_customize->add_setting( 'header_logo_height', [
        'default'           => 72,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'header_logo_height', [
        'label'       => __( 'Hauteur du logo (px)', 'restaurant-theme' ),
        'description' => __( 'Taille du logo dans le header. Recommandé : entre 40 et 120px.', 'restaurant-theme' ),
        'section'     => 'restaurant_header',
        'type'        => 'range',
        'input_attrs' => [
            'min'  => 30,
            'max'  => 150,
            'step' => 2,
        ],
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

    // Setting : vidéo de fond du hero (URL mp4)
    $wp_customize->add_setting( 'hero_background_video', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'hero_background_video', [
        'label'       => __( 'Vidéo de fond du hero (URL .mp4)', 'restaurant-theme' ),
        'description' => __( 'Coller l\'URL directe vers un fichier .mp4 (uploadé dans Médias). Si renseignée, la vidéo remplace l\'image de fond.', 'restaurant-theme' ),
        'section'     => 'restaurant_header',
        'type'        => 'url',
        'input_attrs' => [ 'placeholder' => 'https://…/video.mp4' ],
    ] );

    // Setting : texte du bouton CTA du hero
    $wp_customize->add_setting( 'hero_cta_label', [
        'default'           => 'Réserver une table',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'hero_cta_label', [
        'label'   => 'Texte du bouton CTA',
        'section' => 'restaurant_header',
        'type'    => 'text',
    ] );

    // Setting : position du bouton CTA dans le header
    $wp_customize->add_setting( 'cta_position', [
        'default'           => 'bottom-right',
        'sanitize_callback' => function( $val ) {
            $allowed = [ 'top-right', 'bottom-right', 'bottom-left', 'hidden' ];
            return in_array( $val, $allowed, true ) ? $val : 'bottom-right';
        },
    ] );
    $wp_customize->add_control( 'cta_position', [
        'label'   => 'Position du bouton CTA (header)',
        'section' => 'restaurant_header',
        'type'    => 'select',
        'choices' => [
            'top-right'    => 'En haut à droite (inline avec le menu)',
            'bottom-right' => 'En bas à droite du header',
            'bottom-left'  => 'En bas à gauche du header',
            'hidden'       => 'Masqué',
        ],
    ] );

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
        'about_label' => [ 'label' => 'Label (ex: Notre histoire)', 'default' => 'Notre histoire', 'type' => 'text' ],
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

    $wp_customize->add_setting( 'parallax_height', [
        'default'           => 400,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'parallax_height', [
        'label'       => __( 'Hauteur de la section (px)', 'restaurant-theme' ),
        'description' => __( 'Hauteur minimale de la section parallax. Recommandé : entre 200 et 700px.', 'restaurant-theme' ),
        'section'     => 'restaurant_parallax',
        'type'        => 'range',
        'input_attrs' => [
            'min'  => 150,
            'max'  => 700,
            'step' => 10,
        ],
    ] );

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
    // SECTION : Location per Eventi
    // =========================================================================
    $wp_customize->add_section( 'restaurant_eventi', [
        'title'    => __( 'Location per Eventi', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 32,
    ] );

    $wp_customize->add_setting( 'eventi_title', [
        'default'           => 'Location per Eventi',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'eventi_title', [
        'label'   => __( 'Titre de la section', 'restaurant-theme' ),
        'section' => 'restaurant_eventi',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'eventi_text', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_textarea_field',
    ] );
    $wp_customize->add_control( 'eventi_text', [
        'label'       => __( 'Description', 'restaurant-theme' ),
        'description' => __( 'Laissez une ligne vide entre chaque paragraphe.', 'restaurant-theme' ),
        'section'     => 'restaurant_eventi',
        'type'        => 'textarea',
    ] );

    $wp_customize->add_setting( 'eventi_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'eventi_image', [
        'label'   => __( 'Image', 'restaurant-theme' ),
        'section' => 'restaurant_eventi',
    ] ) );

    // =========================================================================
    // SECTION : Carousel / Galerie
    // =========================================================================
    $wp_customize->add_section( 'restaurant_carousel', [
        'title'    => __( 'Carousel / Galerie', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 35,
    ] );

    $wp_customize->add_setting( 'carousel_label', [
        'default'           => 'Photos',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'carousel_label', [
        'label'   => __( 'Label (petit texte au-dessus du titre)', 'restaurant-theme' ),
        'section' => 'restaurant_carousel',
        'type'    => 'text',
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

    for ( $i = 1; $i <= 19; $i++ ) {
        $wp_customize->add_setting( "carousel_image_{$i}", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ] );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "carousel_image_{$i}", [
            'label'   => sprintf( __( 'Image %d', 'restaurant-theme' ), $i ),
            'section' => 'restaurant_carousel',
        ] ) );

        $wp_customize->add_setting( "carousel_image_{$i}_caption", [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( "carousel_image_{$i}_caption", [
            'label'   => sprintf( __( 'Légende image %d', 'restaurant-theme' ), $i ),
            'section' => 'restaurant_carousel',
            'type'    => 'text',
        ] );
    }

    // =========================================================================
    // SECTION : Réservation (textes)
    // =========================================================================
    $wp_customize->add_section( 'restaurant_reservation_texts', [
        'title'    => __( 'Section Réservation', 'restaurant-theme' ),
        'panel'    => 'restaurant_panel',
        'priority' => 45,
    ] );

    $wp_customize->add_setting( 'reservation_label', [
        'default'           => 'Réservation',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'reservation_label', [
        'label'   => 'Label (petit texte au-dessus du titre)',
        'section' => 'restaurant_reservation_texts',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'reservation_title', [
        'default'           => 'Réserver une table',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'reservation_title', [
        'label'   => 'Titre de la section',
        'section' => 'restaurant_reservation_texts',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'reservation_text', [
        'default'           => 'Réservez votre table en quelques secondes. Nous vous confirmerons votre réservation dans les plus brefs délais.',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'reservation_text', [
        'label'   => 'Texte d\'introduction',
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

    $wp_customize->add_setting( 'menu_section_label', [
        'default'           => __( 'À table', 'restaurant-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'menu_section_label', [
        'label'   => __( 'Label (ex: À table)', 'restaurant-theme' ),
        'section' => 'restaurant_menu_section',
        'type'    => 'text',
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

    $wp_customize->add_setting( 'menu_btn_label', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'menu_btn_label', [
        'label'       => __( 'Texte du bouton', 'restaurant-theme' ),
        'description' => __( 'Laisser vide pour ne pas afficher le bouton.', 'restaurant-theme' ),
        'section'     => 'restaurant_menu_section',
        'type'        => 'text',
    ] );

    $wp_customize->add_setting( 'menu_btn_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ] );
    $wp_customize->add_control( 'menu_btn_url', [
        'label'   => __( 'URL du bouton', 'restaurant-theme' ),
        'section' => 'restaurant_menu_section',
        'type'    => 'url',
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
        'whatsapp'    => [ 'label' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp' ],
        'tripadvisor' => [ 'label' => 'TripAdvisor', 'icon' => 'fa-brands fa-tripadvisor' ],
    ];

    foreach ( $social_networks as $key => $network ) {
        $wp_customize->add_setting( "social_{$key}", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ] );
        $placeholder = $key === 'whatsapp'
            ? 'https://wa.me/votrenuméro'
            : "https://www.{$key}.com/votrerestaurant";

        $wp_customize->add_control( "social_{$key}", [
            'label'       => $network['label'] . ' URL',
            'section'     => 'restaurant_social',
            'type'        => 'url',
            'input_attrs' => [ 'placeholder' => $placeholder ],
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

    // --- Colonne 2 : Adresse ---
    $wp_customize->add_setting( 'footer_col_address_title', [
        'default'           => 'Dove siamo',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'footer_col_address_title', [
        'label'   => __( 'Col. Adresse — titre', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'footer_address', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_textarea_field',
    ] );
    $wp_customize->add_control( 'footer_address', [
        'label'       => __( 'Adresse (une ligne par retour chariot)', 'restaurant-theme' ),
        'section'     => 'restaurant_footer',
        'type'        => 'textarea',
    ] );

    // --- Colonne 3 : Contact ---
    $wp_customize->add_setting( 'footer_col_contact_title', [
        'default'           => 'Contatti',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'footer_col_contact_title', [
        'label'   => __( 'Col. Contact — titre', 'restaurant-theme' ),
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

    $wp_customize->add_setting( 'footer_email', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_email',
    ] );
    $wp_customize->add_control( 'footer_email', [
        'label'   => __( 'Email de contact', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
        'type'    => 'email',
    ] );

    // --- Colonne 4 : Horaires ---
    $wp_customize->add_setting( 'footer_col_hours_title', [
        'default'           => 'Orari di apertura',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'footer_col_hours_title', [
        'label'   => __( 'Col. Horaires — titre', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
        'type'    => 'text',
    ] );

    for ( $i = 1; $i <= 5; $i++ ) {
        $wp_customize->add_setting( "footer_hours_{$i}_days", [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( "footer_hours_{$i}_days", [
            'label'       => sprintf( __( 'Ligne %d — Jours', 'restaurant-theme' ), $i ),
            'description' => $i === 1 ? __( 'Ex : Lun - Mar', 'restaurant-theme' ) : '',
            'section'     => 'restaurant_footer',
            'type'        => 'text',
            'input_attrs' => [ 'placeholder' => 'Lun - Ven' ],
        ] );

        $wp_customize->add_setting( "footer_hours_{$i}_time", [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( "footer_hours_{$i}_time", [
            'label'       => sprintf( __( 'Ligne %d — Horaires', 'restaurant-theme' ), $i ),
            'description' => $i === 1 ? __( 'Ex : 12:00 - 14:30 / 19:00 - 22:30 ou Chiuso', 'restaurant-theme' ) : '',
            'section'     => 'restaurant_footer',
            'type'        => 'text',
            'input_attrs' => [ 'placeholder' => '12:00 - 14:30 / 19:00 - 22:30' ],
        ] );
    }

    // --- Taille de police ---
    $wp_customize->add_setting( 'footer_font_size', [
        'default'           => 14,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'footer_font_size', [
        'label'       => __( 'Taille de police (px)', 'restaurant-theme' ),
        'description' => __( 'Taille de base du texte dans le footer. Recommandé : entre 12 et 18px.', 'restaurant-theme' ),
        'section'     => 'restaurant_footer',
        'type'        => 'range',
        'input_attrs' => [
            'min'  => 10,
            'max'  => 22,
            'step' => 1,
        ],
    ] );

    // --- Taille du logo ---
    $wp_customize->add_setting( 'footer_logo_height', [
        'default'           => 90,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'footer_logo_height', [
        'label'       => __( 'Hauteur du logo (px)', 'restaurant-theme' ),
        'description' => __( 'Recommandé : entre 40 et 200px.', 'restaurant-theme' ),
        'section'     => 'restaurant_footer',
        'type'        => 'range',
        'input_attrs' => [
            'min'  => 40,
            'max'  => 200,
            'step' => 2,
        ],
    ] );

    // --- Couleur de fond ---
    $wp_customize->add_setting( 'footer_bg_color', [
        'default'           => '#010101',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_bg_color', [
        'label'   => __( 'Couleur de fond du footer', 'restaurant-theme' ),
        'section' => 'restaurant_footer',
    ] ) );

    // --- Bas de footer ---
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
    // SECTION : Traductions — English
    // =========================================================================
    $wp_customize->add_section( 'restaurant_translations_en', [
        'title'       => __( '🇬🇧 Traductions — English', 'restaurant-theme' ),
        'description' => __( 'Textes affichés quand le visiteur choisit la langue anglaise. Laissez un champ vide pour conserver le texte italien.', 'restaurant-theme' ),
        'panel'       => 'restaurant_panel',
        'priority'    => 100,
    ] );

    $en_fields = [
        // Hero
        'hero_headline'        => [ 'label' => 'Hero — Titre principal',       'type' => 'text' ],
        'hero_subheadline'     => [ 'label' => 'Hero — Sous-titre',            'type' => 'text' ],
        'hero_cta_label'       => [ 'label' => 'Hero — Bouton CTA',            'type' => 'text' ],
        // About
        'about_label'          => [ 'label' => 'À propos — Label',             'type' => 'text' ],
        'about_title'          => [ 'label' => 'À propos — Titre',             'type' => 'text' ],
        'about_text'           => [ 'label' => 'À propos — Paragraphe 1',      'type' => 'textarea' ],
        'about_text_2'         => [ 'label' => 'À propos — Paragraphe 2',      'type' => 'textarea' ],
        // Events
        'eventi_label'         => [ 'label' => 'Événements — Label',           'type' => 'text' ],
        'eventi_title'         => [ 'label' => 'Événements — Titre',           'type' => 'text' ],
        'eventi_text'          => [ 'label' => 'Événements — Description',     'type' => 'textarea' ],
        // Parallax
        'parallax_quote'       => [ 'label' => 'Parallax — Citation',          'type' => 'textarea' ],
        // Menu
        'menu_section_label'   => [ 'label' => 'Menu — Label',                 'type' => 'text' ],
        'menu_section_title'   => [ 'label' => 'Menu — Titre',                 'type' => 'text' ],
        'menu_btn_label'       => [ 'label' => 'Menu — Bouton',                'type' => 'text' ],
        // Carousel
        'carousel_label'       => [ 'label' => 'Galerie — Label',              'type' => 'text' ],
        'carousel_title'       => [ 'label' => 'Galerie — Titre',              'type' => 'text' ],
        // Reservation
        'reservation_label'    => [ 'label' => 'Réservation — Label',          'type' => 'text' ],
        'reservation_title'    => [ 'label' => 'Réservation — Titre',          'type' => 'text' ],
        'reservation_text'     => [ 'label' => 'Réservation — Introduction',   'type' => 'textarea' ],
        // Footer
        'footer_col_address_title' => [ 'label' => 'Footer — Col. Adresse titre',  'type' => 'text' ],
        'footer_col_contact_title' => [ 'label' => 'Footer — Col. Contact titre',  'type' => 'text' ],
        'footer_col_hours_title'   => [ 'label' => 'Footer — Col. Horaires titre', 'type' => 'text' ],
        'footer_copyright'         => [ 'label' => 'Footer — Copyright',           'type' => 'text' ],
    ];

    foreach ( $en_fields as $key => $args ) {
        $wp_customize->add_setting( $key . '_en', [
            'default'           => '',
            'sanitize_callback' => $args['type'] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
        ] );
        $wp_customize->add_control( $key . '_en', [
            'label'   => __( $args['label'], 'restaurant-theme' ),
            'section' => 'restaurant_translations_en',
            'type'    => $args['type'],
        ] );
    }

    // Horaires footer (5 lignes × jours + heures)
    for ( $i = 1; $i <= 5; $i++ ) {
        $wp_customize->add_setting( "footer_hours_{$i}_days_en", [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( "footer_hours_{$i}_days_en", [
            'label'   => sprintf( __( 'Footer — Ligne %d Jours (EN)', 'restaurant-theme' ), $i ),
            'section' => 'restaurant_translations_en',
            'type'    => 'text',
        ] );

        $wp_customize->add_setting( "footer_hours_{$i}_time_en", [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( "footer_hours_{$i}_time_en", [
            'label'   => sprintf( __( 'Footer — Ligne %d Horaires (EN)', 'restaurant-theme' ), $i ),
            'section' => 'restaurant_translations_en',
            'type'    => 'text',
        ] );
    }

    // Légendes des photos (carousel)
    for ( $i = 1; $i <= 19; $i++ ) {
        $wp_customize->add_setting( "carousel_image_{$i}_caption_en", [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( "carousel_image_{$i}_caption_en", [
            'label'   => sprintf( __( 'Légende image %d (EN)', 'restaurant-theme' ), $i ),
            'section' => 'restaurant_translations_en',
            'type'    => 'text',
        ] );
    }

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
    $logo_height     = absint( get_theme_mod( 'header_logo_height', 72 ) );
    $logo_height_sm  = max( 30, intval( $logo_height * 0.75 ) );
    $header_bg        = get_theme_mod( 'header_bg_color', '#010101' );
    $footer_bg        = get_theme_mod( 'footer_bg_color', '#010101' );
    $footer_font_size = absint( get_theme_mod( 'footer_font_size', 14 ) );
    $parallax_height  = absint( get_theme_mod( 'parallax_height', 400 ) );
    $footer_logo_h    = absint( get_theme_mod( 'footer_logo_height', 90 ) );
    ?>
    <style id="restaurant-customizer-css">
        :root {
            --color-primary:               <?php echo sanitize_hex_color( $color_primary ); ?>;
            --color-secondary:             <?php echo sanitize_hex_color( $color_secondary ); ?>;
            --header-logo-height:          <?php echo $logo_height; ?>px;
            --header-logo-height-scrolled: <?php echo $logo_height_sm; ?>px;
            --header-bg-color:             <?php echo sanitize_hex_color( $header_bg ); ?>;
            --footer-bg-color:             <?php echo sanitize_hex_color( $footer_bg ); ?>;
            --footer-font-size:            <?php echo $footer_font_size; ?>px;
            --parallax-height:             <?php echo $parallax_height; ?>px;
            --footer-logo-height:          <?php echo $footer_logo_h; ?>px;
        }
    </style>
    <?php
}
