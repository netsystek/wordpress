<?php
/**
 * Section Parallax
 *
 * Technique CSS : background-attachment: fixed
 * L'image reste fixe pendant que le contenu défile par-dessus.
 * C'est la méthode la plus simple — aucun JS nécessaire.
 *
 * Note : background-attachment: fixed ne fonctionne pas sur iOS mobile.
 * Pour un support complet mobile, il faudrait du JS (voir assets/js/main.js).
 */

$parallax_image = get_theme_mod( 'parallax_image', '' );
$parallax_quote = get_theme_text( 'parallax_quote', '"La cuisine est l\'art de transformer les produits de la nature en plaisir."' );

$parallax_style = $parallax_image
    ? 'style="background-image: url(' . esc_url( $parallax_image ) . ');"'
    : '';
?>

<section class="parallax-section" <?php echo $parallax_style; ?>>
    <div class="parallax-overlay"></div>
    <div class="parallax-content">
        <blockquote class="parallax-quote">
            <?php echo esc_html( $parallax_quote ); ?>
        </blockquote>
    </div>
</section>
