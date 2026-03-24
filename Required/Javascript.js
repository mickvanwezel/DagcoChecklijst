function updateProgressBar() {
    var container = document.querySelector('.progress-container');
    var totalTasks = parseInt(container.getAttribute('data-total-tasks')) || 0;
    var completedTasks = parseInt(container.getAttribute('data-completed-tasks')) || 0;
    var visibleCheckboxes = document.querySelectorAll('tbody tr .complete-checkbox');
    var visibleCheckedCount = 0;
    visibleCheckboxes.forEach(function (cb) {
        if (cb.checked) visibleCheckedCount++;
    });

    var totalCompleted = completedTasks + visibleCheckedCount;
    var percentage = totalTasks > 0 ? Math.round((totalCompleted / totalTasks) * 100) : 0;

    var progressFill = document.querySelector('.progress-fill');
    var progressText = document.querySelector('.progress-text');
    var progressStats = document.querySelector('.progress-stats');

    if (progressFill) {
        progressFill.style.width = percentage + '%';
    }
    if (progressText) {
        progressText.textContent = percentage + '%';
    }
    if (progressStats) {
        progressStats.textContent = totalCompleted + ' / ' + totalTasks + ' taken voltooid';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.complete-form').forEach(function (form) {
        var checkbox = form.querySelector('.complete-checkbox');
        checkbox.addEventListener('change', async function (e) {
            var id = form.querySelector('input[name="complete_id"]').value;
            var checked = checkbox.checked ? '1' : '0';

            var fd = new FormData();
            fd.append('complete_id', id);
            fd.append('checked', checked);

            try {
                var res = await fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                if (res.ok) {
                    var tr = form.closest('tr');
                    if (checked === '1' && tr) {
                        var container = document.querySelector('.progress-container');
                        var currentCompleted = parseInt(container.getAttribute('data-completed-tasks')) || 0;
                        container.setAttribute('data-completed-tasks', currentCompleted + 1);

                        tr.classList.add('removing');
                        var removed = function () {
                            if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
                            ensurePlaceholders();
                            updateProgressBar();
                        };
                        var onTransitionEnd = function (ev) {
                            if (ev.propertyName === 'opacity') {
                                tr.removeEventListener('transitionend', onTransitionEnd);
                                removed();
                            }
                        };
                        tr.addEventListener('transitionend', onTransitionEnd);
                        setTimeout(removed, 520);
                    }
                    if (checked === '0') {
                        updateProgressBar();
                    }
                } else {
                    console.error('Completion request failed');
                }
            } catch (err) {
                console.error(err);
            }
        });
    });

    var modal = document.getElementById('task-modal');
    var modalForm = document.getElementById('task-form');
    var addBtn = document.getElementById('add-task');
    var modalTitle = document.getElementById('modal-title');

    function openModal(mode, data) {
        modal.classList.remove('hidden');
        document.getElementById('modal-action').value = mode;
        if (mode === 'add') {
            modalTitle.textContent = 'Taak toevoegen';
            document.getElementById('modal-id').value = '';
            document.getElementById('modal-taak').value = '';
            var md = document.getElementById('modal-beschrijving');
            if (md) md.innerText = '';
            document.getElementById('modal-herhaling').value = 'dagelijks';
            var catEl = document.getElementById('modal-categorie');
            var catGroup = document.getElementById('modal-categorie-group');
            if (catEl && catGroup) { catEl.value = 'door'; catEl.disabled = false; catGroup.style.display = ''; }
            var weekdagGroup = document.getElementById('modal-weekdag-group');
            if (weekdagGroup) {
                var checks = weekdagGroup.querySelectorAll('input[type="checkbox"]');
                checks.forEach(function (chk) { chk.checked = false; chk.disabled = true; });
            }
            if (typeof modalHerh !== 'undefined' && modalHerh) modalHerh.dispatchEvent(new Event('change'));
        } else if (mode === 'edit') {
            modalTitle.textContent = 'Taak bewerken';
            document.getElementById('modal-id').value = data.id || '';
            document.getElementById('modal-taak').value = data.taak || '';
            var md2 = document.getElementById('modal-beschrijving');
            if (md2) md2.innerText = data.beschrijving || '';
            document.getElementById('modal-herhaling').value = data.herhaling || '<?= htmlspecialchars($herhaling) ?>';
            var catEl2 = document.getElementById('modal-categorie');
            var catGroup2 = document.getElementById('modal-categorie-group');
            if (catEl2 && catGroup2) {
                catEl2.value = data.categorie || 'door';
            }
            var weekdagGroup = document.getElementById('modal-weekdag-group');
            if (weekdagGroup) {
                var checks = weekdagGroup.querySelectorAll('input[type="checkbox"]');
                checks.forEach(function (chk) { chk.checked = false; });
                if (data.weekdag) {
                    var selected = JSON.parse(data.weekdag);
                    selected.forEach(function (day) {
                        var chk = weekdagGroup.querySelector('input[value="' + day + '"]');
                        if (chk) chk.checked = true;
                    });
                }
            }
            modalHerh.dispatchEvent(new Event('change'));
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    addBtn.addEventListener('click', function () {
        openModal('add');
    });
    document.getElementById('modal-cancel').addEventListener('click', closeModal);

    var modalHerh = document.getElementById('modal-herhaling');
    if (modalHerh) {
        modalHerh.addEventListener('change', function () {
            var val = this.value;
            var cg = document.getElementById('modal-categorie-group');
            var sel = document.getElementById('modal-categorie');
            var wg = document.getElementById('modal-weekdag-group');
            var checks = wg ? wg.querySelectorAll('input[type="checkbox"]') : [];
            if (cg) {
                if (val === 'dagelijks') {
                    cg.style.display = '';
                    if (sel) sel.disabled = false;
                } else {
                    cg.style.display = 'none';
                    if (sel) sel.disabled = true;
                }
            }
            if (wg) {
                if (val === 'wekelijks') {
                    wg.style.display = '';
                    checks.forEach(function (chk) { chk.disabled = false; });
                } else {
                    wg.style.display = 'none';
                    checks.forEach(function (chk) { chk.disabled = true; });
                }
            }
        });
    }

    modalForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        var editable = document.getElementById('modal-beschrijving');
        var hidden = document.getElementById('modal-beschrijving-hidden');
        if (editable && hidden) hidden.value = editable.innerText.trim();
        var fd = new FormData(modalForm);
        try {
            var res = await fetch(window.location.pathname + window.location.search, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            var json = await res.json();
            if (json.ok) {
                location.reload();
            } else {
                alert('Fout bij opslaan');
            }
        } catch (err) {
            console.error(err);
            alert('Netwerkfout');
        }
    });

    document.querySelectorAll('tbody tr').forEach(function (tr) {
        if (tr.classList && tr.classList.contains('group')) return;
        if (tr.querySelector('.row-actions')) return;
        var checkboxCell = tr.querySelector('.checkbox-cell');
        if (!checkboxCell) return;

        var td = document.createElement('td');
        td.className = 'row-actions';
        var inner = document.createElement('div');
        inner.className = 'actions-inner';
        var edit = document.createElement('button');
        edit.className = 'icon-btn edit';
        edit.title = 'Bewerk';
        edit.setAttribute('aria-label', 'Bewerk taak');
        edit.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="currentColor"/><path d="M20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/></svg>';
        edit.addEventListener('click', function () {
            var id = tr.querySelector('input[name="complete_id"]').value;
            var taak = tr.querySelector('td[data-label="Taak"]').innerText.trim();
            var beschrijving = tr.querySelector('td[data-label="Beschrijving"]').innerText.trim();
            var herh = tr.getAttribute('data-herhaling') || '<?= htmlspecialchars($herhaling) ?>';
            var categorie = tr.getAttribute('data-categorie') || 'door';
            var weekdag = tr.getAttribute('data-weekdag') || '';
            openModal('edit', {
                id: id,
                taak: taak,
                beschrijving: beschrijving,
                herhaling: herh,
                categorie: categorie,
                weekdag: weekdag
            });
        });
        var del = document.createElement('button');
        del.className = 'icon-btn danger';
        del.title = 'Verwijder';
        del.setAttribute('aria-label', 'Verwijder taak');
        del.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m5 0V4a2 2 0 012-2h0a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        del.addEventListener('click', async function () {
            if (!confirm('Weet je zeker dat je deze taak wilt verwijderen?')) return;
            var id = tr.querySelector('input[name="complete_id"]').value;
            var f = new FormData();
            f.append('action', 'delete');
            f.append('id', id);
            try {
                var r = await fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    body: f,
                    credentials: 'same-origin'
                });
                var j = await r.json();
                if (j.ok) {
                    tr.classList.add('removing');
                    setTimeout(function () {
                        if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
                        ensurePlaceholders();
                    }, 520);
                }
            } catch (err) {
                console.error(err);
            }
        });
        td.appendChild(inner);
        inner.appendChild(edit);
        inner.appendChild(del);
        tr.appendChild(td);
    });

    function createEmptyRow(message) {
        var tr = document.createElement('tr');
        tr.className = 'empty-cat';
        var td = document.createElement('td');
        var colspan = document.querySelectorAll('thead th').length || 4;
        td.setAttribute('colspan', colspan);
        td.style.padding = '10px 16px';
        td.style.color = getComputedStyle(document.documentElement).getPropertyValue('--tl-text-muted') || '#6B6B7A';
        td.textContent = message;
        tr.appendChild(td);
        return tr;
    }

    function ensurePlaceholders() {
        var groupHeaders = Array.from(document.querySelectorAll('tbody tr.group'));
        if (groupHeaders.length) {
            groupHeaders.forEach(function (hdr) {
                var next = hdr.nextElementSibling;
                var hasTasks = false;
                var existingEmpty = null;
                while (next && !next.classList.contains('group')) {
                    if (next.classList && next.classList.contains('empty-cat')) existingEmpty = next;
                    if (next.querySelector && next.querySelector('.checkbox-cell')) hasTasks = true;
                    next = next.nextElementSibling;
                }
                if (!hasTasks && !existingEmpty) {
                    hdr.parentNode.insertBefore(createEmptyRow('Geen taken meer in deze categorie.'), hdr.nextSibling);
                }
                if (hasTasks && existingEmpty) {
                    existingEmpty.parentNode.removeChild(existingEmpty);
                }
            });
            return;
        }

        var tbody = document.querySelector('tbody');
        if (!tbody) return;
        var taskRows = tbody.querySelectorAll('tr .checkbox-cell');
        var existing = tbody.querySelector('tr.empty-cat');
        if (taskRows.length === 0 && !existing) {
            tbody.appendChild(createEmptyRow('Alle taken voltooid voor deze periode.'));
        }
        if (taskRows.length > 0 && existing) {
            existing.parentNode.removeChild(existing);
        }
    }
    ensurePlaceholders();
});