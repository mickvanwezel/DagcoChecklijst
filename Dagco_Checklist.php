<?php

$pdo = new PDO('mysql:host=localhost;dbname=technolab-dashboard', 'root', '');
$stmt = $pdo->query('SELECT * FROM dagco_checklist');

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<p>De dagco moet een ' . ($row['taak']) . ' dit doet hij door ' . ($row['beschrijving']) . '</p>';
}
?>
