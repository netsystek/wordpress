<?php
/**
 * Section Hero — Image de fond plein écran avec titre
 *
 * get_theme_mod( $setting, $default ) récupère la valeur du Customizer.
 * Si l'admin n'a pas encore configuré l'image, on utilise une couleur de fond.
 */

$bg_image  = get_theme_mod( 'header_background_image', '' );
$headline  = get_theme_mod( 'hero_headline', 'Bienvenue dans notre restaurant' );
$subline   = get_theme_mod( 'hero_subheadline', 'Cuisine traditionnelle française' );

// Construit l'attribut style inline uniquement si une image est définie
$hero_style = $bg_image
    ? 'style="background-image: url(' . esc_url( $bg_image ) . ');"'
    : '';
?>

<section class="hero-section" id="accueil" <?php echo $hero_style; ?>>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-headline"><?php echo esc_html( $headline ); ?></h1>
        <p class="hero-subheadline"><?php echo esc_html( $subline ); ?></p>
        <a href="#reservation" class="btn btn-primary">
            <?php echo esc_html( get_theme_mod( 'hero_cta_label', __( 'Réserver une table', 'restaurant-theme' ) ) ); ?>
        </a>
    </div>
    <a href="#about" class="hero-scroll-indicator" aria-label="Défiler vers le bas">
        <i class="fa-solid fa-chevron-down"></i>
    </a>
</section>
