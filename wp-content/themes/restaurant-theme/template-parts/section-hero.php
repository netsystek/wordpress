<?php
$bg_image   = get_theme_mod( 'header_background_image', '' );
$bg_video   = get_theme_mod( 'hero_background_video', '' );
$headline   = get_theme_text( 'hero_headline', 'Bienvenue dans notre restaurant' );
$subline    = get_theme_text( 'hero_subheadline', 'Cuisine traditionnelle française' );

// L'image de fond sert de poster (affiché pendant le chargement de la vidéo)
// Si une vidéo est définie, on enlève background-image car la vidéo prend le dessus
$hero_style = ( $bg_image && ! $bg_video )
    ? 'style="background-image: url(' . esc_url( $bg_image ) . ');"'
    : '';
?>

<section class="hero-section<?php echo $bg_video ? ' hero-section--video' : ''; ?>" id="accueil" <?php echo $hero_style; ?>>

    <?php if ( $bg_video ) : ?>
    <video
        class="hero-video"
        autoplay
        muted
        loop
        playsinline
        <?php if ( $bg_image ) : ?>poster="<?php echo esc_url( $bg_image ); ?>"<?php endif; ?>
    >
        <source src="<?php echo esc_url( $bg_video ); ?>" type="video/mp4">
    </video>
    <?php endif; ?>

    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1 class="hero-headline"><?php echo esc_html( $headline ); ?></h1>
        <p class="hero-subheadline"><?php echo esc_html( $subline ); ?></p>
    </div>

    <a href="#reservation" class="btn btn-primary hero-cta">
        <?php echo esc_html( get_theme_text( 'hero_cta_label', 'Réserver une table' ) ); ?>
    </a>

    <a href="#about" class="hero-scroll-indicator" aria-label="Défiler vers le bas">
        <i class="fa-solid fa-chevron-down"></i>
    </a>

</section>
