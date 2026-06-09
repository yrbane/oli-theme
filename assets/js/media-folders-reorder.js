/**
 * Drag & drop natif (HTML5) pour réordonner les photos d'un dossier dans
 * Médias → Ordonner les galeries. Aucune dépendance externe (jQuery, Sortable,
 * etc.) — le thème reste auto-suffisant.
 *
 * Au drop, l'utilisateur clique « Enregistrer l'ordre » et le DOM courant est
 * POSTé en AJAX vers `wp_ajax_oli_media_folder_reorder_save`, qui met à jour
 * `menu_order` sur chaque attachment.
 *
 * @global oliReorder {ajaxUrl, action, nonce, i18n:{saving, saved, error}}
 */
(function () {
    'use strict';

    const grid = document.getElementById('oli-reorder-grid');
    const saveButton = document.getElementById('oli-reorder-save');
    const status = document.getElementById('oli-reorder-status');
    if (!grid || !saveButton || typeof oliReorder !== 'object') {
        return;
    }

    let dragged = null;

    // Réordonne le DOM en insérant `dragged` avant/après `target` selon la
    // position horizontale du pointeur (gauche → avant, droite → après).
    function reorderDom(target, event) {
        if (!dragged || dragged === target || !grid.contains(target)) {
            return;
        }
        const rect = target.getBoundingClientRect();
        const insertAfter = (event.clientX - rect.left) > (rect.width / 2);
        if (insertAfter) {
            target.after(dragged);
        } else {
            target.before(dragged);
        }
    }

    grid.addEventListener('dragstart', function (event) {
        const item = event.target.closest('.oli-reorder__item');
        if (!item) {
            return;
        }
        dragged = item;
        item.classList.add('is-dragging');
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            // Firefox exige un setData pour activer le drag.
            event.dataTransfer.setData('text/plain', item.dataset.id || '');
        }
    });

    grid.addEventListener('dragend', function () {
        if (dragged) {
            dragged.classList.remove('is-dragging');
        }
        dragged = null;
        grid.querySelectorAll('.is-drop-target').forEach(function (el) {
            el.classList.remove('is-drop-target');
        });
    });

    grid.addEventListener('dragover', function (event) {
        const target = event.target.closest('.oli-reorder__item');
        if (!target || target === dragged) {
            return;
        }
        event.preventDefault();
        target.classList.add('is-drop-target');
        reorderDom(target, event);
    });

    grid.addEventListener('dragleave', function (event) {
        const target = event.target.closest('.oli-reorder__item');
        if (target) {
            target.classList.remove('is-drop-target');
        }
    });

    grid.addEventListener('drop', function (event) {
        event.preventDefault();
    });

    saveButton.addEventListener('click', function () {
        const folder = saveButton.dataset.folder || '';
        const ids = Array.from(grid.querySelectorAll('.oli-reorder__item'))
            .map(function (el) { return el.dataset.id; })
            .filter(Boolean);
        if (!folder || ids.length === 0) {
            return;
        }

        saveButton.disabled = true;
        status.textContent = oliReorder.i18n.saving || '';
        status.classList.remove('is-error', 'is-success');

        const body = new URLSearchParams();
        body.append('action', oliReorder.action);
        body.append('nonce', oliReorder.nonce);
        body.append('folder', folder);
        ids.forEach(function (id) { body.append('order[]', id); });

        fetch(oliReorder.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
        })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (result.ok && result.data && result.data.success) {
                    status.textContent = oliReorder.i18n.saved || '';
                    status.classList.add('is-success');
                } else {
                    status.textContent = oliReorder.i18n.error || '';
                    status.classList.add('is-error');
                }
            })
            .catch(function () {
                status.textContent = oliReorder.i18n.error || '';
                status.classList.add('is-error');
            })
            .finally(function () {
                saveButton.disabled = false;
            });
    });
})();
