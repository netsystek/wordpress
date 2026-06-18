<?php
/**
 * Section Menu — Affichage de la carte du restaurant
 *
 * Utilise WP_Query pour récupérer les CPT (Custom Post Types) "plat".
 * Le CPT "plat" sera enregistré dans functions.php à l'étape 6 du plan.
 *
 * Parallèle Symfony : WP_Query ≈ Repository->findBy(['categorie' => 'plat'])
 * avec une pagination intégrée et un système de cache automatique.
 *
 * Pour l'instant (avant l'étape 6), on affiche des données d'exemple.
 */

$menu_title     = get_theme_text( 'menu_section_title', 'Notre carte' );
$menu_btn_label = get_theme_text( 'menu_btn_label', '' );
$menu_btn_url   = get_theme_mod( 'menu_btn_url', '' );

// WP_Query — l'ORM de WordPress pour interroger les posts/CPT
$plats_query = new WP_Query( [
    'post_type'      => 'plat',       // Récupère uniquement les CPT "plat"
    'posts_per_page' => -1,           // -1 = tous les résultats (pas de limite)
    'post_status'    => 'publish',    // Uniquement les plats publiés
    'orderby'        => 'menu_order', // Respecte l'ordre défini par l'admin
    'order'          => 'ASC',
] );
?>

<section class="menu-section" id="menu">
    <div class="container">
        <span class="section-label"><?php echo esc_html( get_theme_text( 'menu_section_label', __( 'À table', 'restaurant-theme' ) ) ); ?></span>
        <h2><?php echo esc_html( $menu_title ); ?></h2>

        <?php if ( $plats_query->have_posts() ) : ?>

            <?php
            /*
             * get_terms() récupère les termes d'une taxonomie.
             * Ici : les catégories de plats (entrées, plats, desserts…)
             * Parallèle Symfony : équivalent à repository->findAllCategories()
             */
            $categories = get_terms( [
                'taxonomy'   => 'categorie_plat',
                'hide_empty' => true,
            ] );
            ?>

            <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                <div class="menu-tabs">
                    <?php foreach ( $categories as $index => $cat ) : ?>
                        <button class="menu-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                                data-category="<?php echo esc_attr( $cat->slug ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            // Regroupe les plats par catégorie
            $plats_par_categorie = [];
            while ( $plats_query->have_posts() ) :
                $plats_query->the_post(); // Positionne le curseur sur le post courant
                $cats = get_the_terms( get_the_ID(), 'categorie_plat' );
                $cat_slug = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->slug : 'sans-categorie';
                $plats_par_categorie[ $cat_slug ][] = [
                    'id'          => get_the_ID(),
                    'title'       => get_the_title(),
                    'description' => get_the_excerpt(),
                    'price'       => get_post_meta( get_the_ID(), '_plat_price', true ),
                    'image'       => get_the_post_thumbnail_url( get_the_ID(), 'medium' ),
                ];
            endwhile;
            wp_reset_postdata(); // IMPORTANT : réinitialise la query globale après WP_Query custom
            ?>

            <?php foreach ( $plats_par_categorie as $cat_slug => $plats ) : ?>
                <div class="menu-category" data-category="<?php echo esc_attr( $cat_slug ); ?>">
                    <div class="menu-grid">
                        <?php foreach ( $plats as $plat ) : ?>
                            <div class="menu-item">
                                <?php if ( $plat['image'] ) : ?>
                                    <div class="menu-item-image">
                                        <img src="<?php echo esc_url( $plat['image'] ); ?>"
                                             alt="<?php echo esc_attr( $plat['title'] ); ?>"
                                             loading="lazy">
                                    </div>
                                <?php endif; ?>
                                <div class="menu-item-content">
                                    <div class="menu-item-header">
                                        <h3><?php echo esc_html( $plat['title'] ); ?></h3>
                                        <?php if ( $plat['price'] ) : ?>
                                            <span class="menu-item-price">
                                                <?php echo esc_html( $plat['price'] ); ?> €
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ( $plat['description'] ) : ?>
                                        <p class="menu-item-desc"><?php echo esc_html( $plat['description'] ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else : ?>
            <div class="menu-placeholder">
                <i class="fa-solid fa-utensils"></i>
                <p><?php esc_html_e( 'Ajoutez vos plats depuis WP Admin > Carte du restaurant.', 'restaurant-theme' ); ?></p>
            </div>
        <?php endif; ?>

        <?php if ( $menu_btn_label && $menu_btn_url ) : ?>
            <div class="menu-btn-wrap">
                <a href="<?php echo esc_url( $menu_btn_url ); ?>"
                   class="btn btn-primary"
                   target="_blank"
                   rel="noopener noreferrer">
                    <?php echo esc_html( $menu_btn_label ); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
