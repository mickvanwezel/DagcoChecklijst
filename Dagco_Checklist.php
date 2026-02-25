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


// Ensure there is a `last_completed` column to record when a task was completed.
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM dagco_checklist LIKE 'last_completed'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE dagco_checklist ADD COLUMN last_completed DATETIME NULL DEFAULT NULL");
    }
} catch (Exception $e) {
    // If altering fails, continue — site still works but completion timestamps won't persist.
}

// Handle completion toggle (instead of deleting tasks). The form sends `complete_id` and `checked` (0/1).
if (isset($_POST['complete_id'])) {
    $id = $_POST['complete_id'];
    $checked = isset($_POST['checked']) && $_POST['checked'] == '1';

    if ($checked) {
        // store timestamp in Europe/Amsterdam timezone
        $tz = new DateTimeZone('Europe/Amsterdam');
        $now = new DateTime('now', $tz);
        $stmt = $pdo->prepare("UPDATE dagco_checklist SET last_completed = ? WHERE id = ?");
        $stmt->execute([$now->format('Y-m-d H:i:s'), $id]);
    } else {
        // mark as not completed
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

$sql = "
    SELECT 
        t.id,
        t.taak, 
        t.beschrijving, 
        t.last_completed,
        w.voornaam AS dagco_naam
    FROM dagco_checklist t
    LEFT JOIN werknemers w ON t.dagco_id = w.id
    WHERE t.herhaling = :herhaling
    ORDER BY w.voornaam ASC, t.taak ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['herhaling' => $herhaling]);
$rows = $stmt->fetchAll();

// Determine the last reset time for the selected repetition type (Europe/Amsterdam timezone)
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

    // maandelijks
    $first = new DateTime($now->format('Y-m-01') . ' 07:00:00', $tz);
    if ($now >= $first) {
        return $first;
    }
    return (clone $first)->modify('-1 month');
}

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
        </div>
        <div class="hero-image"></div>
    </section>

    <section class="checklist">

        <h2><?= ucfirst($herhaling) ?> Taken</h2>

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
                            <th>Dagco</th>
                            <th>Taak</th>
                            <th>Beschrijving</th>
                            <th></th>
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
                                    // ignore parse errors
                                }
                            }
                            // If already completed for this period, don't render the row (it should be hidden)
                            if ($completed) {
                                continue;
                            }
                            ?>
                            <tr>

                                <td data-label="Dagco">
                                    <?= htmlspecialchars($row['dagco_naam']) ?>
                                </td>

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

    <script>
        // Send completion via fetch and remove row immediately (graceful fallback: form still works without JS)
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
                            // remove the row from the table so it disappears immediately
                            var tr = form.closest('tr');
                            if (checked === '1' && tr) tr.remove();
                            // if unchecked, reload to show the item again
                            if (checked === '0') location.reload();
                        } else {
                            console.error('Completion request failed');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
            });
        });
    </script>

</body>

</html>