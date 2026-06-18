<section class="about-section" id="about">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <span class="section-label"><?php echo esc_html( get_theme_text( 'about_label', __( 'Notre histoire', 'restaurant-theme' ) ) ); ?></span>
                <h2><?php echo esc_html( get_theme_text( 'about_title', 'Une cuisine du cœur' ) ); ?></h2>
                <p><?php echo esc_html( get_theme_text( 'about_text', 'Depuis 1985, nous perpétuons la tradition de la cuisine française avec des produits frais sélectionnés chaque matin. Notre chef, passionné par les saveurs authentiques, vous propose une carte qui change au fil des saisons.' ) ); ?></p>
                <p><?php echo esc_html( get_theme_text( 'about_text_2', 'Venez découvrir un cadre chaleureux au cœur de la ville, pour un déjeuner en famille ou un dîner romantique.' ) ); ?></p>
            </div>
            <?php
            $about_image = get_theme_mod( 'about_image', '' );
            if ( $about_image ) : ?>
                <div class="about-image">
                    <img src="<?php echo esc_url( $about_image ); ?>"
                         alt="<?php esc_attr_e( 'Notre restaurant', 'restaurant-theme' ); ?>"
                         loading="lazy">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
