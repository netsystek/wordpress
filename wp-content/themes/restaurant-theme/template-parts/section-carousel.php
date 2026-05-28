<?php
/**
 * Section Carousel — Galerie de photos des plats
 *
 * Utilise Swiper.js (chargé via inc/enqueue.php).
 * Les images sont gérées via le Customizer (section 'restaurant_carousel').
 *
 * Pour le moment : images hardcodées avec texte éditable.
 * Étape 5 du plan : on branchera le vrai Customizer avec upload multiple.
 */

$carousel_title = get_theme_mod( 'carousel_title', 'Notre galerie' );
?>

<section class="carousel-section" id="galerie">
    <div class="container">
        <span class="section-label"><?php esc_html_e( 'Photos', 'restaurant-theme' ); ?></span>
        <h2><?php echo esc_html( $carousel_title ); ?></h2>
    </div>

    <div class="swiper restaurant-swiper">
        <div class="swiper-wrapper">
            <?php
            /*
             * Récupère les images du carousel depuis le Customizer.
             * get_theme_mod() retourne null si le setting n'existe pas encore.
             * json_decode() car on stocke un JSON array d'URLs d'images.
             *
             * Pour l'instant on affiche des slides de démonstration si aucune
             * image n'est configurée.
             */
            $carousel_images_json = get_theme_mod( 'carousel_images', '' );
            $carousel_images      = $carousel_images_json ? json_decode( $carousel_images_json, true ) : [];

            if ( ! empty( $carousel_images ) ) :
                foreach ( $carousel_images as $image_url ) : ?>
                    <div class="swiper-slide">
                        <img src="<?php echo esc_url( $image_url ); ?>"
                             alt="<?php esc_attr_e( 'Photo du restaurant', 'restaurant-theme' ); ?>"
                             loading="lazy">
                    </div>
                <?php endforeach;
            else :
                // Slides de démonstration (placeholder visuel)
                for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <div class="swiper-slide swiper-slide--placeholder">
                        <div class="slide-placeholder">
                            <i class="fa-solid fa-camera"></i>
                            <span><?php printf( esc_html__( 'Photo %d', 'restaurant-theme' ), $i ); ?></span>
                            <small><?php esc_html_e( 'Configurez les images dans Apparence > Personnaliser', 'restaurant-theme' ); ?></small>
                        </div>
                    </div>
                <?php endfor;
            endif; ?>
        </div>

        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
</section>
