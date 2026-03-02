<?php
require 'Required/PHP.php';
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
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
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
                            <tr data-herhaling="<?= htmlspecialchars($herhaling) ?>">

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
                <label for="modal-taak">Taak</label>
                <input type="text" id="modal-taak" name="taak" required>
                <label for="modal-beschrijving">Beschrijving</label>
                <textarea id="modal-beschrijving" name="beschrijving" rows="3"></textarea>
                <label for="modal-herhaling">Herhaling</label>
                <select id="modal-herhaling" name="herhaling">
                    <option value="dagelijks">Dagelijks</option>
                    <option value="wekelijks">Wekelijks</option>
                    <option value="maandelijks">Maandelijks</option>
                </select>
                <div class="modal-actions">
                    <button type="button" id="modal-cancel" class="btn btn-secondary">Annuleren</button>
                    <button type="submit" class="btn" id="modal-save">Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    

</body>

<script src="Required/Javascript.js"></script>

</html>