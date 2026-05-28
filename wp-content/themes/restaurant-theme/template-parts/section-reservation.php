<?php
/**
 * Section Réservation — Formulaire intégré via shortcode du plugin
 *
 * Cette section intègre le plugin de réservation via le shortcode
 * [restaurant_reservation] que nous créerons en Phase 2 du plan.
 *
 * do_shortcode() exécute un shortcode WordPress programmatiquement.
 * Parallèle Symfony : équivalent à {{ render(controller('App\\Controller\\ReservationController::form')) }}
 *
 * Pour l'instant (avant la Phase 2), un formulaire HTML statique est affiché
 * comme maquette visuelle.
 */

$reservation_title = get_theme_mod( 'reservation_title', 'Réserver une table' );
$reservation_text  = get_theme_mod( 'reservation_text', 'Réservez votre table en quelques secondes. Nous vous confirmerons votre réservation dans les plus brefs délais.' );
?>

<section class="reservation-section" id="reservation">
    <div class="container">
        <span class="section-label"><?php esc_html_e( 'Réservation', 'restaurant-theme' ); ?></span>
        <h2><?php echo esc_html( $reservation_title ); ?></h2>
        <p class="reservation-intro"><?php echo esc_html( $reservation_text ); ?></p>

        <?php
        /*
         * Vérifie si le shortcode [restaurant_reservation] est enregistré.
         * Si le plugin est actif : affiche le vrai formulaire.
         * Si le plugin est inactif : affiche le formulaire de démonstration.
         *
         * shortcode_exists() — fonction WP pour vérifier si un shortcode existe.
         */
        if ( shortcode_exists( 'restaurant_reservation' ) ) :
            echo do_shortcode( '[restaurant_reservation]' );
        else : ?>
            <div class="reservation-form-demo">
                <p class="notice-demo">
                    <i class="fa-solid fa-circle-info"></i>
                    <?php esc_html_e( 'Activez le plugin "Restaurant Reservation" pour afficher le formulaire de réservation.', 'restaurant-theme' ); ?>
                </p>
                <form class="reservation-form" aria-label="Formulaire de réservation (démonstration)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demo-prenom"><?php esc_html_e( 'Prénom', 'restaurant-theme' ); ?> *</label>
                            <input type="text" id="demo-prenom" name="prenom" disabled placeholder="Jean">
                        </div>
                        <div class="form-group">
                            <label for="demo-nom"><?php esc_html_e( 'Nom', 'restaurant-theme' ); ?> *</label>
                            <input type="text" id="demo-nom" name="nom" disabled placeholder="Dupont">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demo-email"><?php esc_html_e( 'Email', 'restaurant-theme' ); ?> *</label>
                            <input type="email" id="demo-email" name="email" disabled placeholder="jean.dupont@email.com">
                        </div>
                        <div class="form-group">
                            <label for="demo-phone"><?php esc_html_e( 'Téléphone', 'restaurant-theme' ); ?> *</label>
                            <input type="tel" id="demo-phone" name="telephone" disabled placeholder="+33 6 12 34 56 78">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demo-date"><?php esc_html_e( 'Date', 'restaurant-theme' ); ?> *</label>
                            <input type="date" id="demo-date" name="date" disabled>
                        </div>
                        <div class="form-group">
                            <label for="demo-heure"><?php esc_html_e( 'Heure', 'restaurant-theme' ); ?> *</label>
                            <input type="time" id="demo-heure" name="heure" disabled>
                        </div>
                        <div class="form-group">
                            <label for="demo-personnes"><?php esc_html_e( 'Nombre de personnes', 'restaurant-theme' ); ?> *</label>
                            <input type="number" id="demo-personnes" name="personnes" min="1" max="20" disabled placeholder="2">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="demo-message"><?php esc_html_e( 'Message (optionnel)', 'restaurant-theme' ); ?></label>
                        <textarea id="demo-message" name="message" rows="4" disabled
                                  placeholder="<?php esc_attr_e( 'Allergies, occasion spéciale, demandes particulières...', 'restaurant-theme' ); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" disabled>
                        <?php esc_html_e( 'Demander une réservation', 'restaurant-theme' ); ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
