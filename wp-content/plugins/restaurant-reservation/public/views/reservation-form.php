<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings      = get_option( 'restaurant_res_settings', [] );
$max_personnes = intval( $settings['max_personnes'] ?? 20 );
$l_prenom      = $settings['label_prenom']      ?? 'Prénom';
$l_nom         = $settings['label_nom']         ?? 'Nom';
$l_email       = $settings['label_email']       ?? 'Email';
$l_telephone   = $settings['label_telephone']   ?? 'Téléphone';
$l_date        = $settings['label_date']        ?? 'Date';
$l_heure       = $settings['label_heure']       ?? 'Heure';
$l_personnes   = $settings['label_personnes']   ?? 'Couverts';
$l_message     = $settings['label_message']     ?? 'Message (optionnel)';
$l_placeholder = $settings['label_placeholder'] ?? 'Allergies, occasion spéciale, demandes particulières…';
$l_required    = $settings['label_required']    ?? '* Champs obligatoires';
$l_submit      = $settings['label_submit']      ?? 'Demander une réservation';
?>

<div class="res-form-wrapper" id="reservation-form-wrapper">

    <div class="res-feedback" id="res-feedback" role="alert" aria-live="polite" style="display:none;"></div>

    <form class="res-form" id="reservation-form" novalidate>

        <div class="res-form-row">
            <div class="res-form-group">
                <label for="res-prenom"><?php echo esc_html( $l_prenom ); ?> <span aria-hidden="true">*</span></label>
                <input type="text"
                       id="res-prenom"
                       name="prenom"
                       required
                       autocomplete="given-name"
                       placeholder="Jean">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-nom"><?php echo esc_html( $l_nom ); ?> <span aria-hidden="true">*</span></label>
                <input type="text"
                       id="res-nom"
                       name="nom"
                       required
                       autocomplete="family-name"
                       placeholder="Dupont">
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-row">
            <div class="res-form-group">
                <label for="res-email"><?php echo esc_html( $l_email ); ?> <span aria-hidden="true">*</span></label>
                <input type="email"
                       id="res-email"
                       name="email"
                       required
                       autocomplete="email"
                       placeholder="jean.dupont@email.com">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-telephone"><?php echo esc_html( $l_telephone ); ?> <span aria-hidden="true">*</span></label>
                <input type="tel"
                       id="res-telephone"
                       name="telephone"
                       required
                       autocomplete="tel"
                       placeholder="+33 6 12 34 56 78">
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-row res-form-row--three">
            <div class="res-form-group">
                <label for="res-date"><?php echo esc_html( $l_date ); ?> <span aria-hidden="true">*</span></label>
                <input type="date"
                       id="res-date"
                       name="date"
                       required
                       min="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'tomorrow' ) ) ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-heure"><?php echo esc_html( $l_heure ); ?> <span aria-hidden="true">*</span></label>
                <select id="res-heure" name="heure" required>
                    <option value="">-- Choisir --</option>
                    <?php
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
                <label for="res-personnes"><?php echo esc_html( $l_personnes ); ?> <span aria-hidden="true">*</span></label>
                <select id="res-personnes" name="personnes" required>
                    <option value="">-- Nombre --</option>
                    <?php for ( $i = 1; $i <= $max_personnes; $i++ ) : ?>
                        <option value="<?php echo esc_attr( $i ); ?>">
                            <?php echo esc_html( $i ); ?> <?php echo $i === 1 ? 'personne' : 'personnes'; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-group">
            <label for="res-message"><?php echo esc_html( $l_message ); ?></label>
            <textarea id="res-message"
                      name="message"
                      rows="4"
                      placeholder="<?php echo esc_attr( $l_placeholder ); ?>"></textarea>
        </div>

        <div class="res-form-footer">
            <p class="res-form-required-note"><?php echo esc_html( $l_required ); ?></p>
            <button type="submit" class="res-submit-btn" id="res-submit-btn">
                <span class="res-submit-text"><?php echo esc_html( $l_submit ); ?></span>
                <span class="res-submit-spinner" style="display:none;">
                    <span class="res-spinner"></span>
                    Envoi en cours…
                </span>
            </button>
        </div>

    </form>
</div>
