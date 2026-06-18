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

$carousel_title = get_theme_text( 'carousel_title', 'Notre galerie' );
?>

<section class="carousel-section" id="galerie">
    <div class="container">
        <span class="section-label"><?php echo esc_html( get_theme_text( 'carousel_label', 'Photos' ) ); ?></span>
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
            $carousel_images = [];
            for ( $i = 1; $i <= 19; $i++ ) {
                $url = get_theme_mod( "carousel_image_{$i}", '' );
                if ( $url ) {
                    $carousel_images[] = [
                        'url'     => $url,
                        'caption' => get_theme_text( "carousel_image_{$i}_caption", '' ),
                    ];
                }
            }

            if ( ! empty( $carousel_images ) ) :
                foreach ( $carousel_images as $slide ) : ?>
                    <div class="swiper-slide">
                        <img src="<?php echo esc_url( $slide['url'] ); ?>"
                             alt="<?php echo esc_attr( $slide['caption'] ?: get_bloginfo( 'name' ) ); ?>"
                             loading="lazy">
                        <?php if ( $slide['caption'] ) : ?>
                            <p class="carousel-caption"><?php echo esc_html( $slide['caption'] ); ?></p>
                        <?php endif; ?>
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
