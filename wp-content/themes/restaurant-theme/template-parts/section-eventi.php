<?php
$eventi_title = get_theme_mod( 'eventi_title', 'Location per Eventi' );
$eventi_text  = get_theme_mod( 'eventi_text', '' );
$eventi_image = get_theme_mod( 'eventi_image', '' );
?>

<section class="eventi-section" id="eventi">
    <div class="container">
        <div class="eventi-content<?php echo $eventi_image ? '' : ' eventi-content--no-image'; ?>">
            <div class="eventi-text">
                <span class="section-label"><?php esc_html_e( 'Eventi', 'restaurant-theme' ); ?></span>
                <h2><?php echo esc_html( $eventi_title ); ?></h2>
                <?php if ( $eventi_text ) : ?>
                    <?php echo wpautop( esc_html( $eventi_text ) ); ?>
                <?php endif; ?>
            </div>

            <?php if ( $eventi_image ) : ?>
                <div class="eventi-image">
                    <img src="<?php echo esc_url( $eventi_image ); ?>"
                         alt="<?php echo esc_attr( $eventi_title ); ?>"
                         loading="lazy">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
