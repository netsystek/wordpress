<?php
/**
 * Vue : Formulaire de réservation frontend
 * Incluse via ob_start() / ob_get_clean() dans Reservation_Form::render_shortcode().
 * Ne pas echo directement — le contenu est capturé par le buffer de sortie.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="res-form-wrapper" id="reservation-form-wrapper">

    <!-- Message de feedback (succès ou erreur) — caché par défaut -->
    <div class="res-feedback" id="res-feedback" role="alert" aria-live="polite" style="display:none;"></div>

    <form class="res-form" id="reservation-form" novalidate>
        <?php
        /*
         * wp_nonce_field() génère deux champs cachés :
         * - _wpnonce       : le token anti-CSRF
         * - _wp_http_referer : l'URL de la page (pour la redirection après soumission)
         *
         * Côté serveur dans handle_ajax(), check_ajax_referer() les vérifie.
         * Parallèle Symfony : équivalent à {{ csrf_token('submit_reservation') }}
         * dans un template Twig + $this->isCsrfTokenValid() dans le Controller.
         *
         * Note : ici on passe le nonce via JS (resConfig.nonce) plutôt que via
         * ce champ caché, car on envoie la requête en fetch() et non via form submit.
         * Les deux approches sont valides.
         */
        ?>

        <div class="res-form-row">
            <div class="res-form-group">
                <label for="res-prenom"><?php esc_html_e( 'Prénom', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <input type="text"
                       id="res-prenom"
                       name="prenom"
                       required
                       autocomplete="given-name"
                       placeholder="<?php esc_attr_e( 'Jean', 'restaurant-reservation' ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-nom"><?php esc_html_e( 'Nom', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <input type="text"
                       id="res-nom"
                       name="nom"
                       required
                       autocomplete="family-name"
                       placeholder="<?php esc_attr_e( 'Dupont', 'restaurant-reservation' ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-row">
            <div class="res-form-group">
                <label for="res-email"><?php esc_html_e( 'Email', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <input type="email"
                       id="res-email"
                       name="email"
                       required
                       autocomplete="email"
                       placeholder="<?php esc_attr_e( 'jean.dupont@email.com', 'restaurant-reservation' ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-telephone"><?php esc_html_e( 'Téléphone', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <input type="tel"
                       id="res-telephone"
                       name="telephone"
                       required
                       autocomplete="tel"
                       placeholder="<?php esc_attr_e( '+33 6 12 34 56 78', 'restaurant-reservation' ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-row res-form-row--three">
            <div class="res-form-group">
                <label for="res-date"><?php esc_html_e( 'Date', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <input type="date"
                       id="res-date"
                       name="date"
                       required
                       min="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'tomorrow' ) ) ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-heure"><?php esc_html_e( 'Heure', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <select id="res-heure" name="heure" required>
                    <option value=""><?php esc_html_e( '-- Choisir --', 'restaurant-reservation' ); ?></option>
                    <?php
                    // Créneaux horaires de 12h00 à 22h30 par pas de 30 minutes
                    $start = strtotime( '12:00' );
                    $end   = strtotime( '22:30' );
                    for ( $t = $start; $t <= $end; $t += 30 * 60 ) :
                        $time_str = date( 'H:i', $t );
                        ?>
                        <option value="<?php echo esc_attr( $time_str ); ?>"><?php echo esc_html( $time_str ); ?></option>
                    <?php endfor; ?>
                </select>
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-personnes"><?php esc_html_e( 'Couverts', 'restaurant-reservation' ); ?> <span aria-hidden="true">*</span></label>
                <select id="res-personnes" name="personnes" required>
                    <option value=""><?php esc_html_e( '-- Nombre --', 'restaurant-reservation' ); ?></option>
                    <?php for ( $i = 1; $i <= 20; $i++ ) : ?>
                        <option value="<?php echo esc_attr( $i ); ?>">
                            <?php echo esc_html( $i ); ?> <?php echo $i === 1 ? esc_html__( 'personne', 'restaurant-reservation' ) : esc_html__( 'personnes', 'restaurant-reservation' ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-group">
            <label for="res-message"><?php esc_html_e( 'Message (optionnel)', 'restaurant-reservation' ); ?></label>
            <textarea id="res-message"
                      name="message"
                      rows="4"
                      placeholder="<?php esc_attr_e( 'Allergies, occasion spéciale, demandes particulières…', 'restaurant-reservation' ); ?>"></textarea>
        </div>

        <div class="res-form-footer">
            <p class="res-form-required-note">
                <span aria-hidden="true">*</span> <?php esc_html_e( 'Champs obligatoires', 'restaurant-reservation' ); ?>
            </p>
            <button type="submit" class="res-submit-btn" id="res-submit-btn">
                <span class="res-submit-text"><?php esc_html_e( 'Demander une réservation', 'restaurant-reservation' ); ?></span>
                <span class="res-submit-spinner" style="display:none;">
                    <span class="res-spinner"></span>
                    <?php esc_html_e( 'Envoi en cours…', 'restaurant-reservation' ); ?>
                </span>
            </button>
        </div>

    </form>
</div>
