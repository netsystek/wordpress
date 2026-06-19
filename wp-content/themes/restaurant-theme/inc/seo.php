<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =========================================================================
// SECTION SEO dans le Customizer
// =========================================================================
add_action( 'customize_register', 'restaurant_seo_customizer' );

function restaurant_seo_customizer( WP_Customize_Manager $wp_customize ) {
    $wp_customize->add_section( 'restaurant_seo', [
        'title'       => __( 'SEO', 'restaurant-theme' ),
        'description' => __( 'Métadonnées pour Google et le partage sur les réseaux sociaux.', 'restaurant-theme' ),
        'panel'       => 'restaurant_panel',
        'priority'    => 5,
    ] );

    $wp_customize->add_setting( 'seo_description', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_textarea_field',
    ] );
    $wp_customize->add_control( 'seo_description', [
        'label'       => __( 'Meta description', 'restaurant-theme' ),
        'description' => __( '150 caractères max. Texte affiché dans les résultats Google sous le titre.', 'restaurant-theme' ),
        'section'     => 'restaurant_seo',
        'type'        => 'textarea',
    ] );

    $wp_customize->add_setting( 'seo_og_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'seo_og_image', [
        'label'       => __( 'Image de partage (Open Graph)', 'restaurant-theme' ),
        'description' => __( 'Image affichée lors d\'un partage sur Facebook/WhatsApp/iMessage. Recommandé : 1200×630px.', 'restaurant-theme' ),
        'section'     => 'restaurant_seo',
    ] ) );

    $wp_customize->add_setting( 'seo_cuisine', [
        'default'           => 'Italian',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'seo_cuisine', [
        'label'       => __( 'Type de cuisine', 'restaurant-theme' ),
        'description' => __( 'Ex : Italian, French, Mediterranean…', 'restaurant-theme' ),
        'section'     => 'restaurant_seo',
        'type'        => 'text',
    ] );

    $wp_customize->add_setting( 'seo_price_range', [
        'default'           => '€€',
        'sanitize_callback' => function( $val ) {
            return in_array( $val, [ '€', '€€', '€€€', '€€€€' ], true ) ? $val : '€€';
        },
    ] );
    $wp_customize->add_control( 'seo_price_range', [
        'label'   => __( 'Fourchette de prix', 'restaurant-theme' ),
        'section' => 'restaurant_seo',
        'type'    => 'select',
        'choices' => [
            '€'    => '€ — Économique',
            '€€'   => '€€ — Moyen',
            '€€€'  => '€€€ — Gastronomique',
            '€€€€' => '€€€€ — Luxe',
        ],
    ] );
}

// =========================================================================
// Balises meta + Open Graph + Twitter Card
// =========================================================================
add_action( 'wp_head', 'restaurant_seo_meta_tags', 1 );

function restaurant_seo_meta_tags() {
    $site_name   = get_bloginfo( 'name' );
    $canonical   = esc_url( home_url( '/' ) );
    $description = get_theme_mod( 'seo_description', '' );
    $og_image    = get_theme_mod( 'seo_og_image', '' );

    if ( $description ) {
        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    }
    echo '<link rel="canonical" href="' . $canonical . '">' . "\n";

    // Open Graph
    echo '<meta property="og:type" content="restaurant">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $site_name ) . '">' . "\n";
    echo '<meta property="og:url" content="' . $canonical . '">' . "\n";
    echo '<meta property="og:locale" content="it_IT">' . "\n";
    if ( $description ) {
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    }
    if ( $og_image ) {
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $site_name ) . '">' . "\n";
    if ( $description ) {
        echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
    }
    if ( $og_image ) {
        echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '">' . "\n";
    }
}

// =========================================================================
// JSON-LD Schema.org — type Restaurant
// =========================================================================
add_action( 'wp_head', 'restaurant_seo_schema', 2 );

function restaurant_seo_schema() {
    $name        = get_bloginfo( 'name' );
    $url         = home_url( '/' );
    $description = get_theme_mod( 'seo_description', '' );
    $og_image    = get_theme_mod( 'seo_og_image', '' );
    $address_raw = get_theme_mod( 'footer_address', '' );
    $phone       = get_theme_mod( 'footer_phone', '' );
    $email       = get_theme_mod( 'footer_email', '' );
    $cuisine     = get_theme_mod( 'seo_cuisine', 'Italian' );
    $price_range = get_theme_mod( 'seo_price_range', '€€' );
    $facebook    = get_theme_mod( 'social_facebook', '' );
    $instagram   = get_theme_mod( 'social_instagram', '' );

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'Restaurant',
        'name'     => $name,
        'url'      => $url,
    ];

    if ( $description )  { $schema['description']   = $description; }
    if ( $og_image )     { $schema['image']         = $og_image; }
    if ( $cuisine )      { $schema['servesCuisine'] = $cuisine; }
    if ( $price_range )  { $schema['priceRange']    = $price_range; }
    if ( $phone )        { $schema['telephone']     = $phone; }
    if ( $email )        { $schema['email']         = $email; }

    // Adresse : chaque ligne du customizer = une partie de l'adresse
    $address_lines = array_values( array_filter( array_map( 'trim', explode( "\n", $address_raw ) ) ) );
    if ( ! empty( $address_lines ) ) {
        $schema['address'] = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $address_lines[0] ?? '',
            'addressLocality' => $address_lines[1] ?? '',
            'addressCountry'  => 'IT',
        ];
    }

    // Horaires d'ouverture (lignes non vides et non "Chiuso")
    $opening_hours = [];
    for ( $i = 1; $i <= 5; $i++ ) {
        $days = trim( get_theme_mod( "footer_hours_{$i}_days", '' ) );
        $time = trim( get_theme_mod( "footer_hours_{$i}_time", '' ) );
        if ( $days && $time && stripos( $time, 'chiuso' ) === false ) {
            $opening_hours[] = sanitize_text_field( "$days $time" );
        }
    }
    if ( ! empty( $opening_hours ) ) {
        $schema['openingHours'] = $opening_hours;
    }

    // Réseaux sociaux
    $same_as = array_values( array_filter( [ $facebook, $instagram ] ) );
    if ( ! empty( $same_as ) ) {
        $schema['sameAs'] = $same_as;
    }

    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    echo "\n" . '</script>' . "\n";
}
