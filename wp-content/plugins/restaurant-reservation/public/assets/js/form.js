/**
 * form.js — Soumission AJAX du formulaire de réservation (frontend)
 *
 * Utilise l'API Fetch (ES6+) — pas de jQuery.
 * resConfig est injecté par wp_localize_script() dans Reservation_Form::enqueue_assets()
 */

'use strict';

(function initReservationForm() {
    const form       = document.getElementById('reservation-form');
    const feedback   = document.getElementById('res-feedback');
    const submitBtn  = document.getElementById('res-submit-btn');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Réinitialise les erreurs précédentes
        clearErrors();
        setLoading(true);

        // Collecte les données du formulaire
        // FormData sérialise automatiquement tous les champs du formulaire
        // Parallèle Symfony : équivalent à $form->getData() après handleRequest()
        const formData = new FormData(form);

        // Ajoute les paramètres AJAX WordPress
        formData.append('action', 'submit_reservation');  // Détermine quel hook WP est appelé
        formData.append('nonce',  resConfig.nonce);        // Token anti-CSRF

        try {
            // fetch() remplace jQuery.ajax() — API native moderne
            // Parallèle Symfony : requête vers /reservation/submit en POST
            const response = await fetch(resConfig.ajaxUrl, {
                method: 'POST',
                body:   formData,
                // Ne pas mettre Content-Type : FormData le gère automatiquement
                // avec le boundary multipart/form-data
            });

            // WordPress retourne toujours du JSON via wp_send_json_success/error
            const data = await response.json();

            if (data.success) {
                // Succès : affiche le message et cache le formulaire
                showFeedback(data.data.message, 'success');
                form.style.display = 'none';
            } else {
                // Erreur : affiche les messages de validation
                const messages = data.data?.messages || [resConfig.i18n.error];
                showFeedback(messages.join('<br>'), 'error');
            }

        } catch (error) {
            // Erreur réseau ou JSON invalide
            showFeedback(resConfig.i18n.error, 'error');
            console.error('Reservation form error:', error);
        } finally {
            setLoading(false);
        }
    });

    function setLoading(isLoading) {
        const submitText    = submitBtn.querySelector('.res-submit-text');
        const submitSpinner = submitBtn.querySelector('.res-submit-spinner');

        submitBtn.disabled           = isLoading;
        submitText.style.display     = isLoading ? 'none' : 'inline';
        submitSpinner.style.display  = isLoading ? 'flex' : 'none';
    }

    function showFeedback(message, type) {
        feedback.innerHTML    = message;
        feedback.className    = `res-feedback res-feedback--${type}`;
        feedback.style.display = 'block';
        // Scroll vers le message pour les mobiles
        feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearErrors() {
        feedback.style.display = 'none';
        document.querySelectorAll('.res-field-error').forEach(el => el.textContent = '');
    }
})();
