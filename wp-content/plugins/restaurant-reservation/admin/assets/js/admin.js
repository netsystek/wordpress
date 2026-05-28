/**
 * admin.js — Scripts backoffice du plugin Restaurant Reservation
 * Gère le changement de statut via AJAX (boutons Accepter/Refuser).
 */

'use strict';

(function initReservationAdmin() {
    // Délégation d'événements : écoute sur le document pour gérer les boutons
    // créés dynamiquement (dans la WP_List_Table et la page de détail)
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.res-action-btn');
        if (!btn) return;

        const reservationId = btn.dataset.id;
        const newStatus     = btn.dataset.action;

        if (!reservationId || !newStatus) return;

        // Confirmation selon le type d'action
        const confirmMsg = newStatus.includes('accepted')
            ? resAdmin.i18n.confirmAccept
            : resAdmin.i18n.confirmReject;

        if (!confirm(confirmMsg)) return;

        // Désactive le bouton pendant la requête
        btn.disabled    = true;
        btn.textContent = resAdmin.i18n.updating;

        try {
            const formData = new FormData();
            formData.append('action',         'update_reservation_status');
            formData.append('nonce',          resAdmin.nonce);
            formData.append('reservation_id', reservationId);
            formData.append('new_status',     newStatus);

            const response = await fetch(resAdmin.ajaxUrl, {
                method: 'POST',
                body:   formData,
            });
            const data = await response.json();

            if (data.success) {
                // Met à jour l'UI selon le contexte (liste ou détail)
                updateUI(reservationId, data.data.new_status, data.data.label, btn);
            } else {
                showError(btn, data.data?.message || resAdmin.i18n.error);
                btn.disabled = false;
            }

        } catch (err) {
            showError(btn, resAdmin.i18n.error);
            btn.disabled = false;
            console.error('Admin reservation error:', err);
        }
    });

    /**
     * Met à jour l'interface après un changement de statut.
     */
    function updateUI(reservationId, newStatus, label, clickedBtn) {
        const statusClassMap = {
            'res_pending':  'res-badge--pending',
            'res_accepted': 'res-badge--accepted',
            'res_rejected': 'res-badge--rejected',
        };

        // Page de détail : met à jour le badge et cache les boutons
        const feedback = document.getElementById('res-action-feedback');
        if (feedback) {
            feedback.textContent    = `Statut mis à jour : "${label}"`;
            feedback.className      = 'success';
            feedback.style.display  = 'block';

            // Cache les boutons d'action
            const actionWrapper = clickedBtn.closest('.res-action-buttons');
            if (actionWrapper) actionWrapper.style.display = 'none';
        }

        // Page de liste : met à jour le badge dans la ligne du tableau
        const row = document.querySelector(`[data-id="${reservationId}"]`)?.closest('tr');
        if (row) {
            // Met à jour le badge de statut
            const badge = row.querySelector('.res-badge');
            if (badge) {
                badge.className   = `res-badge ${statusClassMap[newStatus] || ''}`;
                badge.textContent = label;
            }

            // Remplace les boutons d'action par un texte informatif
            const actionsCell = row.querySelector('.column-actions');
            if (actionsCell) {
                actionsCell.innerHTML = `<em>${label}</em>`;
            }
        }
    }

    function showError(btn, message) {
        btn.textContent = '⚠ ' + message;
        setTimeout(() => { btn.disabled = false; btn.textContent = btn.dataset.originalText; }, 3000);
    }

    // Sauvegarde les textes originaux des boutons pour la restauration après erreur
    document.querySelectorAll('.res-action-btn').forEach(btn => {
        btn.dataset.originalText = btn.textContent;
    });
})();
