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


if (isset($_POST['delete_id'])) {
    $delete = $pdo->prepare("DELETE FROM dagco_checklist WHERE id = ?");
    $delete->execute([$_POST['delete_id']]);

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
        w.voornaam AS dagco_naam
    FROM dagco_checklist t
    LEFT JOIN werknemers w ON t.dagco_id = w.id
    WHERE t.herhaling = :herhaling
    ORDER BY w.voornaam ASC, t.taak ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['herhaling' => $herhaling]);
$rows = $stmt->fetchAll();
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
                                    <form method="POST">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <label class="checkbox-container">
                                            <input type="checkbox" onchange="this.form.submit()">
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

</body>

</html>