/**
 * main.js — Scripts principaux du thème restaurant
 *
 * Vanilla JS ES6+, aucune dépendance jQuery.
 * Organisé en modules IIFE (Immediately Invoked Function Expression)
 * pour éviter de polluer le scope global.
 *
 * Parallèle Symfony : équivalent à un petit bundle JS sans Webpack.
 * WordPress charge ce fichier avant </body> (grâce au paramètre true
 * dans wp_enqueue_script() dans inc/enqueue.php).
 */

'use strict';

// =============================================================================
// 1. Navigation sticky — ajoute la classe .scrolled quand on défile
// =============================================================================
(function initStickyNav() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    function handleScroll() {
        // 80px = hauteur approx du hero avant qu'on veuille le header opaque
        header.classList.toggle('scrolled', window.scrollY > 80);
    }

    // passive: true améliore les performances sur mobile (le navigateur
    // sait qu'on ne va pas appeler preventDefault())
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // Vérification initiale au chargement
})();


// =============================================================================
// 2. Swiper.js — Initialisation du carousel
//    Swiper est chargé via wp_enqueue_script() depuis inc/enqueue.php.
//    On attend que le DOM soit prêt avant de l'initialiser.
// =============================================================================
(function initSwiper() {
    // Vérifie que Swiper est disponible (le script est chargé)
    if (typeof Swiper === 'undefined') return;

    new Swiper('.restaurant-swiper', {
        // Options du carousel
        slidesPerView: 1,
        spaceBetween: 0,
        loop: true,                  // Boucle infinie
        autoplay: {
            delay: 4000,             // 4 secondes entre chaque slide
            disableOnInteraction: false,
        },

        // Navigation fléchée
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },

        // Points de pagination
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },

        // Responsive breakpoints
        breakpoints: {
            640:  { slidesPerView: 2, spaceBetween: 16 },
            1024: { slidesPerView: 3, spaceBetween: 24 },
        },
    });
})();


// =============================================================================
// 3. Tabs du menu restaurant
//    Gestion des onglets (Entrées / Plats / Desserts) en JS vanilla.
// =============================================================================
(function initMenuTabs() {
    const tabs       = document.querySelectorAll('.menu-tab');
    const categories = document.querySelectorAll('.menu-category');

    if (tabs.length === 0) return;

    // Active le premier onglet par défaut
    if (tabs[0])       tabs[0].classList.add('active');
    if (categories[0]) categories[0].classList.add('active');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetCategory = tab.dataset.category;

            // Désactive tous les onglets et catégories
            tabs.forEach(t => t.classList.remove('active'));
            categories.forEach(c => c.classList.remove('active'));

            // Active l'onglet cliqué et la catégorie correspondante
            tab.classList.add('active');
            const activeCategory = document.querySelector(
                `.menu-category[data-category="${targetCategory}"]`
            );
            if (activeCategory) activeCategory.classList.add('active');
        });
    });
})();


// =============================================================================
// 4. Parallax JS (fallback pour iOS qui ne supporte pas background-attachment: fixed)
//    Utilise IntersectionObserver pour ne calculer que quand la section est visible.
// =============================================================================
(function initParallax() {
    const parallaxSection = document.querySelector('.parallax-section');
    if (!parallaxSection) return;

    // Détecte iOS (où background-attachment: fixed ne fonctionne pas)
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    if (!isIOS) return; // Sur desktop, le CSS suffit

    // Sur iOS : on simule l'effet parallax avec transform
    let isVisible = false;

    const observer = new IntersectionObserver(
        entries => { isVisible = entries[0].isIntersecting; },
        { threshold: 0 }
    );
    observer.observe(parallaxSection);

    function updateParallax() {
        if (!isVisible) return;

        const rect   = parallaxSection.getBoundingClientRect();
        const offset = rect.top * 0.3; // 0.3 = vitesse du parallax (30% de la vitesse de scroll)

        // On déplace l'image de fond (pseudo-element ou l'image directement)
        parallaxSection.style.setProperty('--parallax-offset', `${offset}px`);
        requestAnimationFrame(updateParallax);
    }

    window.addEventListener('scroll', () => {
        if (isVisible) requestAnimationFrame(updateParallax);
    }, { passive: true });
})();


// =============================================================================
// 5. Sélecteur de langue IT / EN
//    Stocke la langue choisie dans un cookie (30j) et recharge la page.
//    Le champ caché #res-site-lang est aussi initialisé au chargement.
// =============================================================================
(function initLangSwitcher() {
    // Synchronise le champ caché du formulaire avec la langue courante (injectée par PHP)
    const langField = document.getElementById('res-site-lang');
    if (langField && typeof resConfig !== 'undefined' && resConfig.currentLang) {
        langField.value = resConfig.currentLang;
    }

    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const lang = this.dataset.lang;
            if (!lang) return;

            // Cookie valable 30 jours sur tout le site
            const expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
            document.cookie = `site_lang=${lang}; expires=${expires}; path=/; SameSite=Lax`;

            window.location.reload();
        });
    });
})();


// =============================================================================
// 6. Scroll fluide pour les ancres de navigation (polyfill pour Safari < 15.4)
// =============================================================================
(function initSmoothScroll() {
    // CSS scroll-behavior: smooth couvre les navigateurs modernes.
    // Ce code est un polyfill pour les anciens Safari.
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (!target) return;

            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Ferme le menu mobile si ouvert (pour future implémentation)
            document.querySelector('.site-header')?.classList.remove('menu-open');
        });
    });
})();
