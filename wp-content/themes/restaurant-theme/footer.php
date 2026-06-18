<?php
$col_address_title = get_theme_text( 'footer_col_address_title', 'Dove siamo' );
$col_contact_title = get_theme_text( 'footer_col_contact_title', 'Contatti' );
$col_hours_title   = get_theme_text( 'footer_col_hours_title',   'Orari di apertura' );
$address           = get_theme_mod( 'footer_address', '' );
$phone             = get_theme_mod( 'footer_phone', '' );
$email             = get_theme_mod( 'footer_email', '' );
$copyright         = get_theme_text( 'footer_copyright', '© ' . date( 'Y' ) . ' Mon Restaurant.' );

$social_networks = [
    'facebook'    => [ 'icon' => 'fa-brands fa-facebook-f',  'label' => 'Facebook' ],
    'instagram'   => [ 'icon' => 'fa-brands fa-instagram',   'label' => 'Instagram' ],
    'whatsapp'    => [ 'icon' => 'fa-brands fa-whatsapp',    'label' => 'WhatsApp' ],
    'tripadvisor' => [ 'icon' => 'fa-brands fa-tripadvisor', 'label' => 'TripAdvisor' ],
];

$hours_rows = [];
for ( $i = 1; $i <= 5; $i++ ) {
    $days = get_theme_mod( "footer_hours_{$i}_days", '' );
    $time = get_theme_mod( "footer_hours_{$i}_time", '' );
    if ( $days || $time ) {
        $hours_rows[] = [ 'days' => $days, 'time' => $time ];
    }
}
?>
<footer class="site-footer" id="contact">

    <div class="footer-grid">

        <!-- Colonne 1 : Logo -->
        <div class="footer-col footer-col--logo">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span class="footer-logo-text"><?php bloginfo( 'name' ); ?></span>
            <?php endif; ?>
        </div>

        <!-- Colonne 2 : Adresse -->
        <div class="footer-col footer-col--address">
            <?php if ( $col_address_title ) : ?>
                <h4 class="footer-col-title"><?php echo esc_html( $col_address_title ); ?></h4>
            <?php endif; ?>
            <?php if ( $address ) :
                $lines = array_filter( array_map( 'trim', explode( "\n", $address ) ) );
                foreach ( $lines as $line ) : ?>
                    <p class="footer-address-line"><?php echo esc_html( $line ); ?></p>
                <?php endforeach;
            endif; ?>
        </div>

        <!-- Colonne 3 : Contact + Réseaux sociaux -->
        <div class="footer-col footer-col--contact">
            <?php if ( $col_contact_title ) : ?>
                <h4 class="footer-col-title"><?php echo esc_html( $col_contact_title ); ?></h4>
            <?php endif; ?>

            <?php if ( $phone ) : ?>
                <p class="footer-contact-item">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
                        <?php echo esc_html( $phone ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php if ( $email ) : ?>
                <p class="footer-contact-item">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    <a href="mailto:<?php echo esc_attr( $email ); ?>">
                        <?php echo esc_html( $email ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php
            $has_social = false;
            foreach ( $social_networks as $key => $net ) {
                if ( get_theme_mod( "social_{$key}", '' ) ) { $has_social = true; break; }
            }
            if ( $has_social ) : ?>
                <div class="footer-social">
                    <?php foreach ( $social_networks as $key => $net ) :
                        $url = get_theme_mod( "social_{$key}", '' );
                        if ( $url ) : ?>
                            <a href="<?php echo esc_url( $url ); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="<?php echo esc_attr( $net['label'] ); ?>"
                               class="footer-social-link footer-social-link--<?php echo esc_attr( $key ); ?>">
                                <i class="<?php echo esc_attr( $net['icon'] ); ?>"></i>
                            </a>
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Colonne 4 : Horaires -->
        <div class="footer-col footer-col--hours">
            <?php if ( $col_hours_title ) : ?>
                <h4 class="footer-col-title"><?php echo esc_html( $col_hours_title ); ?></h4>
            <?php endif; ?>

            <?php if ( ! empty( $hours_rows ) ) : ?>
                <dl class="footer-hours-list">
                    <?php foreach ( $hours_rows as $row ) : ?>
                        <div class="footer-hours-row">
                            <dt class="footer-hours-days"><?php echo esc_html( $row['days'] ); ?></dt>
                            <dd class="footer-hours-time"><?php echo esc_html( $row['time'] ); ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>

    </div>

    <!-- Barre copyright -->
    <div class="footer-bottom">
        <p class="footer-copyright"><?php echo esc_html( $copyright ); ?></p>
    </div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
