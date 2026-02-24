<?php
$pdo = new PDO('mysql:host=localhost;dbname=technolab-dashboard', 'root', '');

$sql = "
    SELECT t.taak, t.beschrijving, t.herhaling, t.status, w.voornaam AS dagco_naam
    FROM dagco_checklist t
    LEFT JOIN werknemers w ON t.dagco_id = w.id
    ORDER BY w.voornaam
";
$stmt = $pdo->query($sql);

$dagcoTaken = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['dagco_naam'])) {
        $dagcoTaken[$row['dagco_naam']][] = [
            'taak'         => $row['taak'],
            'beschrijving' => $row['beschrijving'],
            'herhaling'    => $row['herhaling'],
            'status'       => $row['status']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dagco Checklist</titles>
    <link rel="stylesheet" href="Style/Style.css">
</head>

<body>

    <div class="dagco-lijst">
        <h1>Dagco Checklist</h1>
        <?php foreach ($dagcoTaken as $dagco => $taken): ?>
            <div class="dagco-kaart">
                <h2><?= htmlspecialchars($dagco) ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Taak</th>
                            <th>Beschrijving</th>
                            <th>Herhaling</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taken as $taak): ?>
                            <tr class="status-<?= htmlspecialchars($taak['status']) ?>">
                                <td><?= htmlspecialchars($taak['taak']) ?></td>
                                <td><?= htmlspecialchars($taak['beschrijving']) ?></td>
                                <td><?= htmlspecialchars($taak['herhaling']) ?></td>
                                <td>
                                    <?php
                                    $labels = [
                                        'pending'     => '⏳ Openstaand',
                                        'in_progress' => '🔄 In uitvoering',
                                        'completed'   => '✅ Voltooid'
                                    ];
                                    echo $labels[$taak['status']] ?? htmlspecialchars($taak['status']);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

    </div>

</body>

</html>