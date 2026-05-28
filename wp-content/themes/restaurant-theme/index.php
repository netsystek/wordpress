<?php
/**
 * index.php — Template principal du thème one-page
 *
 * Dans la hiérarchie des templates WordPress, index.php est le fallback ultime.
 * Pour un one-page, c'est notre seul template de page — il orchestre toutes
 * les sections via get_template_part().
 *
 * Parallèle Symfony : c'est l'équivalent du layout Twig principal (base.html.twig)
 * qui étend les blocs. Ici on utilise l'inclusion plutôt que l'héritage.
 *
 * get_template_part( 'template-parts/section-hero' ) cherche le fichier :
 *   wp-content/themes/restaurant-theme/template-parts/section-hero.php
 */

get_header();
?>

<main class="site-main" id="main">
    <?php
    /*
     * Chaque section est dans un fichier séparé dans template-parts/.
     * Avantages :
     *   - Lisibilité (chaque fichier = une responsabilité)
     *   - Réutilisabilité (on peut inclure une section ailleurs)
     *   - Maintenabilité (on modifie une section sans toucher aux autres)
     *
     * Parallèle Symfony : équivalent à {% include 'partials/hero.html.twig' %}
     */
    get_template_part( 'template-parts/section-hero' );
    get_template_part( 'template-parts/section-about' );
    get_template_part( 'template-parts/section-parallax' );
    get_template_part( 'template-parts/section-carousel' );
    get_template_part( 'template-parts/section-menu' );
    get_template_part( 'template-parts/section-reservation' );
    ?>
</main>

<?php get_footer(); ?>
