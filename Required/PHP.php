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
} catch (Exception $e) {
}

// toevoegen, bewerken, verwijderen
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
        $herhaling = $_POST['herhaling'] ?? 'dagelijks';
        $stmt = $pdo->prepare("UPDATE dagco_checklist SET taak = ?, beschrijving = ?, herhaling = ? WHERE id = ?");
        $stmt->execute([$taak, $beschrijving, $herhaling, $id]);
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

// SQL
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

// bereken laatste reset tijd
$last_reset = get_last_reset($herhaling, $now, $tz);
?>