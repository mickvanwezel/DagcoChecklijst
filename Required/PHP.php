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

// colom check
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM dagco_checklist LIKE 'last_completed'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE dagco_checklist ADD COLUMN last_completed DATETIME NULL DEFAULT NULL");
    }
    $colCheck2 = $pdo->query("SHOW COLUMNS FROM dagco_checklist LIKE 'weekdag'")->fetch();
    if (!$colCheck2) {
        $pdo->exec("ALTER TABLE dagco_checklist ADD COLUMN weekdag TEXT NULL DEFAULT NULL");
    }
} catch (Exception $e) {
}

// toevoegen, bewerken, verwijderen
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'add') {
        $taak = $_POST['taak'] ?? '';
        $beschrijving = $_POST['beschrijving'] ?? '';
        $herhaling = $_POST['herhaling'] ?? 'dagelijks';
        $categorie = $_POST['categorie'] ?? 'door';
        $weekdag = isset($_POST['weekdag']) && is_array($_POST['weekdag']) ? json_encode($_POST['weekdag']) : null;
        $stmt = $pdo->prepare("INSERT INTO dagco_checklist (taak, beschrijving, herhaling, categorie, weekdag) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$taak, $beschrijving, $herhaling, $categorie, $weekdag]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $taak = $_POST['taak'] ?? '';
        $beschrijving = $_POST['beschrijving'] ?? '';
        $herhaling = $_POST['herhaling'] ?? 'dagelijks';
        $weekdag = isset($_POST['weekdag']) && is_array($_POST['weekdag']) ? json_encode($_POST['weekdag']) : null;
        if (isset($_POST['categorie'])) {
            $categorie = $_POST['categorie'] ?? 'door';
            $stmt = $pdo->prepare("UPDATE dagco_checklist SET taak = ?, beschrijving = ?, herhaling = ?, categorie = ?, weekdag = ? WHERE id = ?");
            $stmt->execute([$taak, $beschrijving, $herhaling, $categorie, $weekdag, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE dagco_checklist SET taak = ?, beschrijving = ?, herhaling = ?, weekdag = ? WHERE id = ?");
            $stmt->execute([$taak, $beschrijving, $herhaling, $weekdag, $id]);
        }
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

// voltooien taak
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

// herhaling en reset
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
        $dow = (int)$now->format('N');
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
$last_reset = get_last_reset($herhaling, $now, $tz);

$days_nl = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
$current_day = (int)$now->format('N') - 1; // 0=maandag
$current_day_name = $days_nl[$current_day];

if ($herhaling === 'dagelijks') {
    $sql = "
        SELECT
            t.id,
            t.taak,
            t.beschrijving,
            t.last_completed,
            t.categorie,
            t.weekdag,
            t.herhaling
        FROM dagco_checklist t
        WHERE t.herhaling IN ('dagelijks','wekelijks')
        ORDER BY FIELD(t.categorie,'start','door','eind'), t.taak ASC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
} else {
    $sql = "
        SELECT
            t.id,
            t.taak,
            t.beschrijving,
            t.last_completed,
            t.categorie,
            t.weekdag,
            t.herhaling
        FROM dagco_checklist t
        WHERE t.herhaling = :herhaling
        ORDER BY FIELD(t.categorie,'start','door','eind'), t.taak ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['herhaling' => $herhaling]);
    $rows = $stmt->fetchAll();
}

$visible = [];
$total_tasks = 0;
$completed_tasks = 0;

foreach ($rows as $row) {
    // Count for progress bar
    if ($herhaling === 'dagelijks' && $row['herhaling'] === 'wekelijks') {
        $weekdag = $row['weekdag'];
        if ($weekdag) {
            $selected_days = json_decode($weekdag, true) ?: [];
            if (!in_array($current_day_name, $selected_days)) continue;
        }
    }
    $total_tasks++;
    
    $task_reset = get_last_reset($row['herhaling'], $now, $tz);
    $completed = false;
    if (!empty($row['last_completed'])) {
        try {
            $lc = new DateTime($row['last_completed'], $tz);
            if ($lc >= $task_reset) {
                $completed = true;
            }
        } catch (Exception $e) {
        }
    }
    if ($completed) {
        $completed_tasks++;
        continue;
    }

    $visible[] = $row;
}
$rows = $visible;

$progress_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

function getBijzonderheden(PDO $pdo): array
{
    $days = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
    $groups = array_fill_keys($days, []);

    try {
        $stmt = $pdo->query("SELECT * FROM bijzonderheden ORDER BY id ASC");
        $items = $stmt->fetchAll();
    } catch (Exception $e) {
        return $groups;
    }

    foreach ($items as $it) {
        $rawDay = $it['dag'] ?? ($it['day'] ?? '');
        $key = mb_strtolower(trim((string)$rawDay));
        if ($key === '') continue;
        if (!isset($groups[$key])) {
            if (!isset($groups[$key])) $groups[$key] = [];
        }
        $groups[$key][] = $it;
    }

    return $groups;
}
