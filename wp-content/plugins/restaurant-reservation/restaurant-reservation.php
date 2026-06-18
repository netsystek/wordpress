<?php
/**
 * Plugin Name:  Restaurant Reservation
 * Plugin URI:   https://example.com
 * Description:  Système de réservation de table avec backoffice complet et emails automatiques.
 * Version:      1.0.0
 * Author:       Votre Nom
 * Author URI:   https://example.com
 * Text Domain:  restaurant-reservation
 * License:      GPL v2 or later
 *
 * =============================================================================
 * STRUCTURE D'UN PLUGIN WORDPRESS
 * =============================================================================
 * Ce commentaire en en-tête est OBLIGATOIRE — WordPress le parse pour afficher
 * les informations dans WP Admin > Extensions.
 * Parallèle Symfony : c'est l'équivalent du composer.json du bundle.
 *
 * ARCHITECTURE : On utilise une classe principale qui bootstrappe tout.
 * Parallèle Symfony : cette classe joue le rôle de Kernel.php + Bundle.php.
 * =============================================================================
 */

// Sécurité : empêche l'accès direct au fichier PHP
// ABSPATH est défini par WordPress au démarrage
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes du plugin — accessibles partout dans le plugin
// Parallèle Symfony : équivalent aux paramètres dans services.yaml
define( 'RESTAURANT_RES_VERSION', '1.0.0' );
define( 'RESTAURANT_RES_DIR',     plugin_dir_path( __FILE__ ) );  // Chemin absolu sur le serveur
define( 'RESTAURANT_RES_URL',     plugin_dir_url( __FILE__ ) );   // URL HTTP du plugin
define( 'RESTAURANT_RES_FILE',    __FILE__ );

/**
 * Classe principale du plugin.
 *
 * Pattern Singleton utilisé ici pour garantir une seule instance.
 * Parallèle Symfony : comme un Service déclaré en singleton dans services.yaml.
 */
final class Restaurant_Reservation {

    private static ?self $instance = null;

    /**
     * Retourne l'instance unique (pattern Singleton).
     * WordPress charge les plugins en global scope — le Singleton évite
     * les collisions si le plugin est inclus plusieurs fois.
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé — charge les dépendances et enregistre les hooks.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    /**
     * Inclut les fichiers de classes du plugin.
     * Parallèle Symfony : équivalent aux imports dans services.yaml
     * (ou l'autoload PSR-4 de Composer).
     *
     * Note : WordPress n'a pas de vrai autoloader PSR-4 natif.
     * Pour un plugin simple, les require_once sont suffisants.
     * Pour un plugin plus complexe, on peut ajouter un spl_autoload_register().
     */
    private function load_dependencies(): void {
        require_once RESTAURANT_RES_DIR . 'includes/class-reservation-cpt.php';
        require_once RESTAURANT_RES_DIR . 'includes/class-reservation-form.php';
        require_once RESTAURANT_RES_DIR . 'includes/class-reservation-email.php';
        require_once RESTAURANT_RES_DIR . 'includes/class-reservation-admin.php';

        if ( is_admin() ) {
            // La classe WP_List_Table n'est chargée que dans l'admin
            require_once RESTAURANT_RES_DIR . 'admin/class-list-table.php';
        }
    }

    /**
     * Enregistre tous les hooks WordPress du plugin.
     *
     * Parallèle Symfony : c'est l'équivalent de la méthode getSubscribedEvents()
     * d'un EventSubscriber — on déclare quels événements on écoute et avec quel listener.
     *
     * Différence clé avec Symfony :
     * - Symfony : les services sont instanciés par le container (injection de dépendances)
     * - WordPress : on instancie soi-même et on s'abonne aux hooks manuellement
     */
    private function register_hooks(): void {
        // Instanciation des classes métier
        $cpt   = new Reservation_CPT();
        $form  = new Reservation_Form();
        $email = new Reservation_Email();
        $admin = new Reservation_Admin( $email );

        // Enregistrement du CPT et des statuts
        add_action( 'init',          [ $cpt, 'register_post_type' ] );
        add_action( 'init',          [ $cpt, 'register_post_status' ] );

        // Formulaire frontend : shortcode + AJAX
        add_shortcode( 'restaurant_reservation', [ $form, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts',        [ $form, 'enqueue_assets' ] );
        // Traitement AJAX pour visiteurs NON connectés (clients qui réservent)
        add_action( 'wp_ajax_nopriv_submit_reservation', [ $form, 'handle_ajax' ] );
        // Traitement AJAX pour visiteurs connectés (admin qui teste)
        add_action( 'wp_ajax_submit_reservation',        [ $form, 'handle_ajax' ] );

        // Interface admin
        add_action( 'admin_menu',          [ $admin, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_assets' ] );
        // AJAX admin : changement de statut de réservation
        add_action( 'wp_ajax_update_reservation_status', [ $admin, 'handle_status_update' ] );

        // Hook custom pour découpler le changement de statut de l'envoi d'email
        // Parallèle Symfony : équivalent à dispatcher un Event personnalisé
        // et y abonner un Listener séparé
        add_action( 'reservation_status_changed', [ $email, 'send_status_email' ], 10, 2 );

        // Notification email à l'admin quand une nouvelle réservation est soumise
        add_action( 'reservation_created', [ $email, 'send_admin_notification' ] );
    }
}

/**
 * Hooks d'activation et de désactivation du plugin.
 *
 * register_activation_hook() est appelé UNE SEULE FOIS quand l'admin
 * clique "Activer" dans WP Admin > Extensions.
 * Parallèle Symfony : équivalent à une Migration Doctrine (crée les tables/données initiales).
 *
 * IMPORTANT : ce hook doit être appelé AVANT que la classe soit instanciée
 * via plugins_loaded (ci-dessous), d'où son placement ici.
 */
register_activation_hook( RESTAURANT_RES_FILE, function () {
    // Initialise les options par défaut si elles n'existent pas encore
    // get_option() retourne false si l'option n'existe pas
    if ( ! get_option( 'restaurant_res_settings' ) ) {
        update_option( 'restaurant_res_settings', [
            'sender_name'  => get_bloginfo( 'name' ),
            'sender_email' => get_bloginfo( 'admin_email' ),
            'notify_email' => get_bloginfo( 'admin_email' ),
            'max_personnes'=> 12,

            'restaurant_contact_email' => '',
            'restaurant_contact_phone' => '',
            'restaurant_address'       => '',

            // Templates génériques (fallback si la langue n'est pas trouvée)
            'subject_accepted' => 'La tua prenotazione è confermata — {{restaurant_nom}}',
            'email_accepted'   => "Gentile {{client_nom}},\n\nLa tua prenotazione per {{reservation_personnes}} persona/e il {{reservation_date}} alle {{reservation_heure}} è confermata.\n\nA presto!\n{{restaurant_nom}}",
            'subject_rejected' => 'La tua prenotazione — {{restaurant_nom}}',
            'email_rejected'   => "Gentile {{client_nom}},\n\nCi dispiace, non è possibile confermare la tua prenotazione del {{reservation_date}} alle {{reservation_heure}}.\n\nCordialmente,\n{{restaurant_nom}}",

            // Templates 🇮🇹 Italiano
            'subject_accepted_it' => 'La tua prenotazione è confermata — {{restaurant_nom}}',
            'email_accepted_it'   => "Gentile {{client_nom}},\n\nLa tua prenotazione per {{reservation_personnes}} persona/e il {{reservation_date}} alle {{reservation_heure}} è confermata.\n\nA presto!\n{{restaurant_nom}}",
            'subject_rejected_it' => 'La tua prenotazione — {{restaurant_nom}}',
            'email_rejected_it'   => "Gentile {{client_nom}},\n\nCi dispiace, non è possibile confermare la tua prenotazione del {{reservation_date}} alle {{reservation_heure}}.\n\nCordialmente,\n{{restaurant_nom}}",

            // Templates 🇬🇧 English
            'subject_accepted_en' => 'Your reservation is confirmed — {{restaurant_nom}}',
            'email_accepted_en'   => "Dear {{client_nom}},\n\nYour reservation for {{reservation_personnes}} guest(s) on {{reservation_date}} at {{reservation_heure}} is confirmed.\n\nSee you soon!\n{{restaurant_nom}}",
            'subject_rejected_en' => 'Your reservation — {{restaurant_nom}}',
            'email_rejected_en'   => "Dear {{client_nom}},\n\nWe are sorry, we cannot confirm your reservation for {{reservation_date}} at {{reservation_heure}}.\n\nKind regards,\n{{restaurant_nom}}",

            // Templates 🇫🇷 Français
            'subject_accepted_fr' => 'Votre réservation est confirmée — {{restaurant_nom}}',
            'email_accepted_fr'   => "Bonjour {{client_nom}},\n\nVotre réservation pour {{reservation_personnes}} personne(s) le {{reservation_date}} à {{reservation_heure}} est confirmée.\n\nÀ bientôt !\n{{restaurant_nom}}",
            'subject_rejected_fr' => 'Votre réservation — {{restaurant_nom}}',
            'email_rejected_fr'   => "Bonjour {{client_nom}},\n\nNous sommes désolés, nous ne pouvons pas confirmer votre réservation du {{reservation_date}} à {{reservation_heure}}.\n\nCordialement,\n{{restaurant_nom}}",
        ] );
    }

    // flush_rewrite_rules() force WordPress à recalculer les permaliens.
    // Nécessaire après l'enregistrement d'un nouveau CPT pour que ses URLs fonctionnent.
    // Parallèle Symfony : équivalent à php bin/console cache:clear après ajout de routes.
    flush_rewrite_rules();
} );

register_deactivation_hook( RESTAURANT_RES_FILE, function () {
    flush_rewrite_rules();
} );

/**
 * Point d'entrée principal.
 *
 * 'plugins_loaded' est l'action émise par WordPress une fois que TOUS les
 * plugins sont chargés (fichiers inclus). C'est le bon moment pour instancier
 * notre plugin car toutes les dépendances sont disponibles.
 *
 * Parallèle Symfony : équivalent à l'appel de $kernel->boot() dans index.php.
 */
/**
 * Charge le text domain du plugin.
 *
 * Doit être appelé sur 'plugins_loaded' AVANT l'instanciation du plugin,
 * car les chaînes sont évaluées à l'instanciation.
 *
 * Le fichier chargé : languages/restaurant-reservation-{locale}.mo
 * Parallèle Symfony : équivalent à $translator->setLocale('it_IT')
 * et au chargement des fichiers translations/messages.it.yaml.
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'restaurant-reservation',
        false,
        dirname( plugin_basename( RESTAURANT_RES_FILE ) ) . '/languages'
    );
}, 1 );  // Priorité 1 = chargé avant l'instanciation (priorité 10 par défaut)

add_action( 'plugins_loaded', [ Restaurant_Reservation::class, 'get_instance' ] );
