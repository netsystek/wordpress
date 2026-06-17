<footer class="site-footer" id="contact">
    <div class="footer-container">

        <div class="footer-info">
            <?php
            $address = get_theme_mod( 'footer_address', '' );
            $phone   = get_theme_mod( 'footer_phone', '' );
            if ( $address ) : ?>
                <p class="footer-address">
                    <i class="fa-solid fa-location-dot"></i>
                    <?php echo esc_html( $address ); ?>
                </p>
            <?php endif; ?>

            <?php if ( $phone ) : ?>
                <p class="footer-phone">
                    <i class="fa-solid fa-phone"></i>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
                        <?php echo esc_html( $phone ); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <div class="footer-social">
            <?php
            /*
             * get_theme_mod() récupère une valeur du Customizer.
             * esc_url() nettoie l'URL avant affichage (sécurité XSS).
             * Parallèle Symfony : {{ url|escape('html_attr') }} en Twig.
             */
            $social_networks = [
                'facebook'    => 'fa-brands fa-facebook-f',
                'instagram'   => 'fa-brands fa-instagram',
                'whatsapp'    => 'fa-brands fa-whatsapp',
                'tripadvisor' => 'fa-brands fa-tripadvisor',
            ];
            foreach ( $social_networks as $key => $icon ) :
                $url = get_theme_mod( "social_{$key}", '' );
                if ( $url ) : ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
                        <i class="<?php echo esc_attr( $icon ); ?>"></i>
                    </a>
                <?php endif;
            endforeach; ?>
        </div>

        <div class="footer-copyright">
            <p><?php echo esc_html( get_theme_mod( 'footer_copyright', '© ' . date( 'Y' ) . ' Mon Restaurant.' ) ); ?></p>
        </div>

    </div>
</footer>

<?php
/*
 * wp_footer() — Hook obligatoire, symétrique de wp_head().
 * WordPress et les plugins y injectent leurs scripts JS.
 * Parallèle Symfony : équivalent à {{ encore_entry_script_tags() }} en Twig.
 * JAMAIS l'oublier.
 */
wp_footer();
?>
</body>
</html>
