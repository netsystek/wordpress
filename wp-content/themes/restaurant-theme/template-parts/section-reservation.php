<?php
$reservation_label = get_theme_mod( 'reservation_label', 'Réservation' );
$reservation_title = get_theme_mod( 'reservation_title', 'Réserver une table' );
$reservation_text  = get_theme_mod( 'reservation_text', 'Réservez votre table en quelques secondes. Nous vous confirmerons votre réservation dans les plus brefs délais.' );
?>

<section class="reservation-section" id="reservation">
    <div class="container">
        <span class="section-label"><?php echo esc_html( $reservation_label ); ?></span>
        <h2><?php echo esc_html( $reservation_title ); ?></h2>
        <p class="reservation-intro"><?php echo esc_html( $reservation_text ); ?></p>

        <?php if ( shortcode_exists( 'restaurant_reservation' ) ) : ?>
            <?php echo do_shortcode( '[restaurant_reservation]' ); ?>
        <?php else : ?>
            <div class="reservation-form-demo">
                <p class="notice-demo">
                    <i class="fa-solid fa-circle-info"></i>
                    Activez le plugin "Restaurant Reservation" pour afficher le formulaire de réservation.
                </p>
                <form class="reservation-form" aria-label="Formulaire de réservation (démonstration)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demo-prenom">Prénom *</label>
                            <input type="text" id="demo-prenom" name="prenom" disabled placeholder="Jean">
                        </div>
                        <div class="form-group">
                            <label for="demo-nom">Nom *</label>
                            <input type="text" id="demo-nom" name="nom" disabled placeholder="Dupont">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demo-email">Email *</label>
                            <input type="email" id="demo-email" name="email" disabled placeholder="jean.dupont@email.com">
                        </div>
                        <div class="form-group">
                            <label for="demo-phone">Téléphone *</label>
                            <input type="tel" id="demo-phone" name="telephone" disabled placeholder="+33 6 12 34 56 78">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demo-date">Date *</label>
                            <input type="date" id="demo-date" name="date" disabled>
                        </div>
                        <div class="form-group">
                            <label for="demo-heure">Heure *</label>
                            <input type="time" id="demo-heure" name="heure" disabled>
                        </div>
                        <div class="form-group">
                            <label for="demo-personnes">Nombre de personnes *</label>
                            <input type="number" id="demo-personnes" name="personnes" min="1" max="20" disabled placeholder="2">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="demo-message">Message (optionnel)</label>
                        <textarea id="demo-message" name="message" rows="4" disabled
                                  placeholder="Allergies, occasion spéciale, demandes particulières..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" disabled>
                        Demander une réservation
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
