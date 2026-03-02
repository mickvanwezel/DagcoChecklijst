        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.complete-form').forEach(function(form) {
                var checkbox = form.querySelector('.complete-checkbox');
                checkbox.addEventListener('change', async function(e) {
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
                                tr.classList.add('removing');
                                var removed = function() {
                                    if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
                                };
                                var onTransitionEnd = function(ev) {
                                    if (ev.propertyName === 'opacity') {
                                        tr.removeEventListener('transitionend', onTransitionEnd);
                                        removed();
                                    }
                                };
                                tr.addEventListener('transitionend', onTransitionEnd);
                                setTimeout(removed, 520);
                            }
                            if (checked === '0') location.reload();
                        } else {
                            console.error('Completion request failed');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
            });

            // Modal add/edit logic
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
                    document.getElementById('modal-beschrijving').value = '';
                    // default herhaling to current tab
                    document.getElementById('modal-herhaling').value = '<?= htmlspecialchars($herhaling) ?>';
                } else if (mode === 'edit') {
                    modalTitle.textContent = 'Taak bewerken';
                    document.getElementById('modal-id').value = data.id || '';
                    document.getElementById('modal-taak').value = data.taak || '';
                    document.getElementById('modal-beschrijving').value = data.beschrijving || '';
                    document.getElementById('modal-herhaling').value = data.herhaling || '<?= htmlspecialchars($herhaling) ?>';
                }
            }

            function closeModal() {
                modal.classList.add('hidden');
            }

            addBtn.addEventListener('click', function() {
                openModal('add');
            });
            document.getElementById('modal-cancel').addEventListener('click', closeModal);

            modalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
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

            document.querySelectorAll('tbody tr').forEach(function(tr) {
                var td = document.createElement('td');
                td.className = 'row-actions';
                var inner = document.createElement('div');
                inner.className = 'actions-inner';
                // edit button
                var edit = document.createElement('button');
                edit.className = 'btn btn-secondary';
                edit.textContent = 'Bewerk';
                edit.style.marginRight = '6px';
                edit.addEventListener('click', function() {
                    var id = tr.querySelector('input[name="complete_id"]').value;
                    var taak = tr.querySelector('td[data-label="Taak"]').innerText.trim();
                    var beschrijving = tr.querySelector('td[data-label="Beschrijving"]').innerText.trim();
                    var herh = tr.getAttribute('data-herhaling') || '<?= htmlspecialchars($herhaling) ?>';
                    openModal('edit', {
                        id: id,
                        taak: taak,
                        beschrijving: beschrijving,
                        herhaling: herh
                    });
                });
                // delete button
                var del = document.createElement('button');
                del.className = 'btn';
                del.textContent = 'Verwijder';
                del.addEventListener('click', async function() {
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
                            setTimeout(function() {
                                tr.remove();
                            }, 520);
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
                td.appendChild(inner);
                inner.appendChild(edit);
                inner.appendChild(del);
                tr.querySelector('.checkbox-cell').after(td);
            });
        });