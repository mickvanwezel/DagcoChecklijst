<?php

$pdo = new PDO('mysql:host=localhost;dbname=technolab-dashboard', 'root', '');

$sql = "
    SELECT t.taak, t.beschrijving, t.herhaling, t.status, w.voornaam AS dagco_naam
    FROM dagco_checklist t
    LEFT JOIN werknemers w ON t.dagco_id = w.id
    ORDER BY w.voornaam
";

$stmt = $pdo->query($sql);

?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Technolab Intern</title>
    <link rel="stylesheet" href="Style/Style.css" />
</head>

<body>

    <section class="hero">
        <article class="hero-content">
            <h1>
                Technolab-intern.nl<br>
                Dag coördinator<br>
                Checklist
            </h1>
            <p>
                medemogelijk gemaakt door stagiaires.<br>
                gebracht door toekomstkunde.
            </p>
        </article>
        <figure class="hero-image"></figure>
    </section>

    <!-- Checklist Section -->
    <section class="checklist">
        <h2>Dagcoördinator Taken</h2>

        <table border="1" cellpadding="8" cellspacing="0">
            <tr>
                <th>Dagco</th>
                <th>Taak</th>
                <th>Beschrijving</th>
                <th>Herhaling</th>
                <th>Status</th>
            </tr>

            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['dagco_naam']) ?></td>
                    <td><?= htmlspecialchars($row['taak']) ?></td>
                    <td><?= htmlspecialchars($row['beschrijving']) ?></td>
                    <td><?= htmlspecialchars($row['herhaling']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
            <?php endwhile; ?>

        </table>
    </section>

</body>

</html>