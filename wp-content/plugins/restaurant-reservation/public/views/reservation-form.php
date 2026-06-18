<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings      = get_option( 'restaurant_res_settings', [] );
$max_personnes = intval( $settings['max_personnes'] ?? 12 );
$l_nom         = $settings['label_nom']         ?? 'Nom';
$l_email       = $settings['label_email']       ?? 'Email';
$l_telephone   = $settings['label_telephone']   ?? 'Téléphone';
$l_date        = $settings['label_date']        ?? 'Date';
$l_heure       = $settings['label_heure']       ?? 'Heure';
$l_personnes   = $settings['label_personnes']   ?? 'Couverts';
$l_required    = $settings['label_required']    ?? '* Champs obligatoires';
$l_submit      = $settings['label_submit']      ?? 'Demander une réservation';
$l_heure_placeholder     = $settings['label_heure_placeholder']    ?? '-- Choisir --';
$l_personnes_placeholder = $settings['label_personnes_placeholder'] ?? '1';
$ph_nom        = $settings['placeholder_nom']       ?? 'Dupont';
$ph_email      = $settings['placeholder_email']     ?? 'jean.dupont@email.com';
$ph_telephone  = $settings['placeholder_telephone'] ?? '+39 06 12 34 56 78';
?>

<div class="res-form-wrapper" id="reservation-form-wrapper">

    <div class="res-feedback" id="res-feedback" role="alert" aria-live="polite" style="display:none;"></div>

    <form class="res-form" id="reservation-form" novalidate>

        <input type="hidden" name="site_lang" id="res-site-lang" value="it">

        <div class="res-form-group">
            <label for="res-nom"><?php echo esc_html( $l_nom ); ?> <span aria-hidden="true">*</span></label>
            <input type="text"
                   id="res-nom"
                   name="nom"
                   required
                   autocomplete="family-name"
                   placeholder="<?php echo esc_attr( $ph_nom ); ?>">
            <span class="res-field-error" role="alert"></span>
        </div>

        <div class="res-form-row">
            <div class="res-form-group">
                <label for="res-email"><?php echo esc_html( $l_email ); ?> <span aria-hidden="true">*</span></label>
                <input type="email"
                       id="res-email"
                       name="email"
                       required
                       autocomplete="email"
                       placeholder="<?php echo esc_attr( $ph_email ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
            <div class="res-form-group">
                <label for="res-telephone"><?php echo esc_html( $l_telephone ); ?> <span aria-hidden="true">*</span></label>
                <input type="tel"
                       id="res-telephone"
                       name="telephone"
                       required
                       autocomplete="tel"
                       placeholder="<?php echo esc_attr( $ph_telephone ); ?>">
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
                    <option value=""><?php echo esc_html( $l_heure_placeholder ); ?></option>
                    <?php
                    $start = strtotime( 'today 12:00' );
                    $end   = strtotime( 'tomorrow 01:00' );
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
                <input type="number"
                       id="res-personnes"
                       name="personnes"
                       required
                       min="1"
                       max="<?php echo esc_attr( $max_personnes ); ?>"
                       placeholder="<?php echo esc_attr( $l_personnes_placeholder ); ?>">
                <span class="res-field-error" role="alert"></span>
            </div>
        </div>

        <div class="res-form-footer">
            <p class="res-form-required-note"><?php echo esc_html( $l_required ); ?></p>
            <button type="submit" class="res-submit-btn" id="res-submit-btn">
                <span class="res-submit-text"><?php echo esc_html( $l_submit ); ?></span>
                <span class="res-submit-spinner" style="display:none;">
                    <span class="res-spinner"></span>
                    Invio in corso…
                </span>
            </button>
        </div>

    </form>
</div>
