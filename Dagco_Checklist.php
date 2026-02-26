<?php
$pdo = new PDO(
    'mysql:host=localhost;dbname=technolab-dashboard;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

// Column Check
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM dagco_checklist LIKE 'last_completed'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE dagco_checklist ADD COLUMN last_completed DATETIME NULL DEFAULT NULL");
    }
} catch (Exception $e) {
}

// Handle actions (add / edit / delete) via AJAX
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'add') {
        $taak = $_POST['taak'] ?? '';
        $beschrijving = $_POST['beschrijving'] ?? '';
        $herhaling = $_POST['herhaling'] ?? 'dagelijks';
        $stmt = $pdo->prepare("INSERT INTO dagco_checklist (taak, beschrijving, herhaling) VALUES (?, ?, ?)");
        $stmt->execute([$taak, $beschrijving, $herhaling]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $taak = $_POST['taak'] ?? '';
        $beschrijving = $_POST['beschrijving'] ?? '';
        $stmt = $pdo->prepare("UPDATE dagco_checklist SET taak = ?, beschrijving = ? WHERE id = ?");
        $stmt->execute([$taak, $beschrijving, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM dagco_checklist WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }
}

// Completion Toggle
if (isset($_POST['complete_id'])) {
    $id = $_POST['complete_id'];
    $checked = isset($_POST['checked']) && $_POST['checked'] == '1';

    if ($checked) {
        $tz = new DateTimeZone('Europe/Amsterdam');
        $now = new DateTime('now', $tz);
        $stmt = $pdo->prepare("UPDATE dagco_checklist SET last_completed = ? WHERE id = ?");
        $stmt->execute([$now->format('Y-m-d H:i:s'), $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE dagco_checklist SET last_completed = NULL WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$herhaling = $_GET['type'] ?? 'dagelijks';
$allowed = ['dagelijks', 'wekelijks', 'maandelijks'];

if (!in_array($herhaling, $allowed)) {
    $herhaling = 'dagelijks';
}

// Query
$sql = "
    SELECT 
        t.id,
        t.taak, 
        t.beschrijving, 
        t.last_completed
    FROM dagco_checklist t
    WHERE t.herhaling = :herhaling
    ORDER BY t.taak ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['herhaling' => $herhaling]);
$rows = $stmt->fetchAll();

// Reset Logic
$tz = new DateTimeZone('Europe/Amsterdam');
$now = new DateTime('now', $tz);

function get_last_reset(string $type, DateTime $now, DateTimeZone $tz): DateTime
{
    if ($type === 'dagelijks') {
        $today7 = new DateTime($now->format('Y-m-d') . ' 07:00:00', $tz);
        if ($now >= $today7) {
            return $today7;
        }
        return (clone $today7)->modify('-1 day');
    }
    if ($type === 'wekelijks') {
        $dow = (int)$now->format('N'); // 1 (Mon) - 7 (Sun)
        $monday = (clone $now)->modify("-" . ($dow - 1) . " days");
        $monday->setTime(7, 0, 0);
        if ($now >= $monday) {
            return $monday;
        }
        return (clone $monday)->modify('-7 days');
    }
    $first = new DateTime($now->format('Y-m-01') . ' 07:00:00', $tz);
    if ($now >= $first) {
        return $first;
    }
    return (clone $first)->modify('-1 month');
}

// Last reset
$last_reset = get_last_reset($herhaling, $now, $tz);
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technolab Checklist</title>
    <link rel="stylesheet" href="Style/Style.css">
</head>

<body>

    <section class="hero">
        <div class="hero-content">
            <h1>
                Technolab<br>
                Dag Coördinator<br>
                Checklist
            </h1>
            <p>medemogelijk gemaakt door stagiaires.</p>
            <p>gebracht door toekomstkunde.</p>

        </div>
        <div class="hero-image"></div>
    </section>

    <section class="checklist">

        <h2><?= ucfirst($herhaling) ?> Taken</h2>

        <div style="text-align:center; margin-bottom:14px;">
            <button id="add-task" class="btn">+ Taak toevoegen</button>
        </div>

        <div class="nav-tabs">
            <a href="?type=dagelijks" class="<?= $herhaling == 'dagelijks' ? 'active' : '' ?>">Dagelijks</a>
            <a href="?type=wekelijks" class="<?= $herhaling == 'wekelijks' ? 'active' : '' ?>">Wekelijks</a>
            <a href="?type=maandelijks" class="<?= $herhaling == 'maandelijks' ? 'active' : '' ?>">Maandelijks</a>
        </div>

        <?php if (empty($rows)): ?>
            <div class="empty-state">Geen taken gevonden.</div>
        <?php else: ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Taak</th>
                            <th>Beschrijving</th>
                            <th></th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($rows as $row): ?>
                            <?php
                            $completed = false;
                            if (!empty($row['last_completed'])) {
                                try {
                                    $lc = new DateTime($row['last_completed'], $tz);
                                    if ($lc >= $last_reset) {
                                        $completed = true;
                                    }
                                } catch (Exception $e) {
                                }
                            }
                            if ($completed) {
                                continue;
                            }
                            ?>
                            <tr>

                                <td data-label="Taak">
                                    <?= htmlspecialchars($row['taak']) ?>
                                </td>

                                <td data-label="Beschrijving">
                                    <?= htmlspecialchars($row['beschrijving']) ?>
                                </td>

                                <td class="checkbox-cell">
                                    <form method="POST" class="complete-form">
                                        <input type="hidden" name="complete_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="checked" value="1" class="checked-input">
                                        <label class="checkbox-container">
                                            <input type="checkbox" class="complete-checkbox">
                                            <span class="checkmark"></span>
                                        </label>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <!-- Modal: add / edit task -->
    <div id="task-modal" class="modal hidden">
        <div class="modal-content">
            <h3 id="modal-title">Taak toevoegen</h3>
            <form id="task-form">
                <input type="hidden" name="action" id="modal-action" value="add">
                <input type="hidden" name="id" id="modal-id" value="">
                <input type="hidden" name="herhaling" id="modal-herhaling" value="<?= htmlspecialchars($herhaling) ?>">
                <label for="modal-taak">Taak</label>
                <input type="text" id="modal-taak" name="taak" required>
                <label for="modal-beschrijving">Beschrijving</label>
                <textarea id="modal-beschrijving" name="beschrijving" rows="3"></textarea>
                <div class="modal-actions">
                    <button type="button" id="modal-cancel" class="btn btn-secondary">Annuleren</button>
                    <button type="submit" class="btn" id="modal-save">Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
                } else if (mode === 'edit') {
                    modalTitle.textContent = 'Taak bewerken';
                    document.getElementById('modal-id').value = data.id || '';
                    document.getElementById('modal-taak').value = data.taak || '';
                    document.getElementById('modal-beschrijving').value = data.beschrijving || '';
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
                // edit button
                var edit = document.createElement('button');
                edit.className = 'btn btn-secondary';
                edit.textContent = 'Bewerk';
                edit.style.marginRight = '6px';
                edit.addEventListener('click', function() {
                    var id = tr.querySelector('input[name="complete_id"]').value;
                    var taak = tr.querySelector('td[data-label="Taak"]').innerText.trim();
                    var beschrijving = tr.querySelector('td[data-label="Beschrijving"]').innerText.trim();
                    openModal('edit', {
                        id: id,
                        taak: taak,
                        beschrijving: beschrijving
                    });
                });
                td.appendChild(edit);
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
                td.appendChild(del);
                tr.querySelector('.checkbox-cell').after(td);
            });
        });
    </script>

</body>

</html>