<?php
/**
 * functions.php — Point d'entrée du thème
 *
 * Parallèle Symfony : ce fichier joue le rôle de services.yaml + Kernel.php
 * C'est ici qu'on enregistre tout : supports, menus, scripts, hooks Customizer.
 *
 * WordPress charge ce fichier automatiquement à chaque requête.
 */

// Sécurité : empêche l'accès direct au fichier (bonne pratique WP)
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constante pour raccourcir les chemins (équivalent %kernel.project_dir% en Symfony)
define( 'RESTAURANT_THEME_DIR', get_template_directory() );
define( 'RESTAURANT_THEME_URI', get_template_directory_uri() );

/**
 * Configuration principale du thème.
 *
 * add_action() est le système de hooks de WordPress.
 * Parallèle Symfony : c'est l'EventDispatcher.
 *   - 'after_setup_theme' = le nom de l'événement (l'action)
 *   - 'restaurant_theme_setup' = le listener (le callable à exécuter)
 *
 * WordPress émet des dizaines d'actions à chaque requête.
 * On s'y "abonne" ici pour injecter notre comportement.
 */
add_action( 'after_setup_theme', 'restaurant_theme_setup' );

function restaurant_theme_setup() {
    /*
     * load_theme_textdomain() charge le fichier .mo correspondant à la locale active.
     *
     * WordPress cherche le fichier dans ce dossier avec le pattern :
     *   {text-domain}-{locale}.mo  →  restaurant-theme-it_IT.mo
     *
     * La locale est définie par :
     *   1. Polylang selon la langue de la page visitée
     *   2. Le réglage WP Admin > Réglages > Général > Langue du site
     *
     * Parallèle Symfony : équivalent à la config translator.default_locale
     * et aux fichiers translations/messages.it.yaml.
     */
    load_theme_textdomain( 'restaurant-theme', get_template_directory() . '/languages' );
    /*
     * add_theme_support() déclare les fonctionnalités WP que le thème utilise.
     * Sans ces déclarations, certaines fonctions WP ne fonctionnent pas.
     */

    // Permet d'utiliser <title> automatique généré par WP (SEO friendly)
    add_theme_support( 'title-tag' );

    // Active le logo uploadable depuis Apparence > Personnaliser > Identité du site
    add_theme_support( 'custom-logo', [
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    // Active les images mises en avant (thumbnail) pour les articles/CPT
    add_theme_support( 'post-thumbnails' );

    // Active le support HTML5 pour les balises de formulaire, galerie, etc.
    add_theme_support( 'html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'script',
        'style',
    ] );

    // Enregistre un menu de navigation
    // Parallèle Symfony : équivalent à déclarer une route nommée
    register_nav_menus( [
        'primary' => __( 'Menu principal', 'restaurant-theme' ),
        'footer'  => __( 'Menu footer', 'restaurant-theme' ),
    ] );
}

// Inclure les modules du thème (séparation des responsabilités)
// Parallèle Symfony : équivalent aux imports dans services.yaml
require_once RESTAURANT_THEME_DIR . '/inc/enqueue.php';
require_once RESTAURANT_THEME_DIR . '/inc/customizer.php';

/**
 * Retourne un texte du Customizer dans la langue active (cookie site_lang).
 * Si la traduction EN est vide, retourne la valeur IT par défaut.
 *
 * @param string $key     Clé du setting Customizer (sans suffixe de langue)
 * @param string $default Valeur par défaut si le setting n'est pas configuré
 */
function get_theme_text( string $key, string $default = '' ): string {
    $lang = ( isset( $_COOKIE['site_lang'] ) && $_COOKIE['site_lang'] === 'en' ) ? 'en' : 'it';
    if ( $lang === 'en' ) {
        $en = get_theme_mod( $key . '_en', '' );
        if ( $en !== '' ) {
            return $en;
        }
    }
    return get_theme_mod( $key, $default );
}

/**
 * Custom Post Type "plat" — La carte du restaurant
 *
 * Un Custom Post Type est un type de contenu personnalisé, distinct des
 * articles (post) et pages WordPress natifs.
 *
 * Parallèle Symfony : c'est l'équivalent d'une Entity Doctrine avec son
 * propre CRUD dans l'admin (que WP génère automatiquement).
 *
 * L'action 'init' est émise à chaque requête WP, après le chargement des
 * plugins et du thème — c'est le bon moment pour enregistrer les CPT.
 */
add_action( 'init', 'restaurant_register_cpt_plat' );

function restaurant_register_cpt_plat() {
    register_post_type( 'plat', [
        'labels' => [
            'name'               => __( 'Carte du restaurant', 'restaurant-theme' ),
            'singular_name'      => __( 'Plat', 'restaurant-theme' ),
            'add_new_item'       => __( 'Ajouter un plat', 'restaurant-theme' ),
            'edit_item'          => __( 'Modifier le plat', 'restaurant-theme' ),
            'new_item'           => __( 'Nouveau plat', 'restaurant-theme' ),
            'view_item'          => __( 'Voir le plat', 'restaurant-theme' ),
            'search_items'       => __( 'Rechercher un plat', 'restaurant-theme' ),
            'not_found'          => __( 'Aucun plat trouvé', 'restaurant-theme' ),
            'menu_name'          => __( 'Carte', 'restaurant-theme' ),
        ],
        'public'              => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-food',  // Icône dans le menu admin WP
        'supports'            => [ 'title', 'excerpt', 'thumbnail', 'page-attributes' ],
        'has_archive'         => false,
        'rewrite'             => [ 'slug' => 'plat' ],
        'show_in_rest'        => true,  // Active l'éditeur Gutenberg pour ce CPT
        'menu_position'       => 5,
    ] );

    // Taxonomie "categorie_plat" (Entrées, Plats, Desserts, Boissons…)
    // Parallèle Symfony : équivalent à une relation ManyToMany vers une table de catégories
    register_taxonomy( 'categorie_plat', 'plat', [
        'labels' => [
            'name'          => __( 'Catégories', 'restaurant-theme' ),
            'singular_name' => __( 'Catégorie', 'restaurant-theme' ),
            'add_new_item'  => __( 'Ajouter une catégorie', 'restaurant-theme' ),
        ],
        'hierarchical'      => true,   // true = comme des catégories (parent/enfant), false = comme des tags
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => [ 'slug' => 'categorie-plat' ],
    ] );

    // Métadonnée "prix" pour chaque plat
    // Parallèle Symfony : équivalent à une propriété nullable dans l'Entity
    register_post_meta( 'plat', '_plat_price', [
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => fn() => current_user_can( 'edit_posts' ),
    ] );
}

/**
 * Ajoute une meta box "Prix" dans l'éditeur des plats.
 *
 * Une Meta Box est un panneau dans l'interface d'édition d'un post/CPT.
 * Parallèle Symfony : équivalent à un FormType avec un champ supplémentaire
 * dans le CRUD EasyAdmin.
 */
add_action( 'add_meta_boxes', 'restaurant_add_price_meta_box' );

function restaurant_add_price_meta_box() {
    add_meta_box(
        'plat_price',                          // ID unique de la meta box
        __( 'Prix du plat', 'restaurant-theme' ), // Titre affiché
        'restaurant_render_price_meta_box',    // Callback qui affiche le HTML
        'plat',                                // Sur quel CPT afficher
        'side',                                // Position : 'side', 'normal', 'advanced'
        'high'                                 // Priorité d'affichage
    );
}

function restaurant_render_price_meta_box( WP_Post $post ) {
    // Nonce de sécurité — équivalent au token CSRF Symfony
    wp_nonce_field( 'restaurant_save_price', 'restaurant_price_nonce' );
    $price = get_post_meta( $post->ID, '_plat_price', true );
    ?>
    <label for="plat_price_input"><?php esc_html_e( 'Prix (€)', 'restaurant-theme' ); ?></label>
    <input type="text"
           id="plat_price_input"
           name="plat_price"
           value="<?php echo esc_attr( $price ); ?>"
           placeholder="12.50"
           style="width:100%; margin-top: 5px;">
    <?php
}

// Sauvegarde du prix lors de l'enregistrement du plat
add_action( 'save_post_plat', 'restaurant_save_price_meta' );

function restaurant_save_price_meta( int $post_id ) {
    // Vérifications de sécurité (toujours faire ces 3 vérifications avant de sauvegarder)
    if ( ! isset( $_POST['restaurant_price_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['restaurant_price_nonce'], 'restaurant_save_price' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $price = isset( $_POST['plat_price'] )
        ? sanitize_text_field( wp_unslash( $_POST['plat_price'] ) )
        : '';

    update_post_meta( $post_id, '_plat_price', $price );
}
