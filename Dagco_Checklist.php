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

        <h2><?php echo ucfirst($herhaling) ?> Taken</h2>

        <div style="text-align:center; margin-bottom:14px;">
            <button id="add-task" class="btn">+ Taak toevoegen</button>
        </div>

        <div class="nav-tabs">
            <a href="?type=dagelijks" class="<?php echo $herhaling == 'dagelijks' ? 'active' : '' ?>">Dagelijks</a>
            <a href="?type=wekelijks" class="<?php echo $herhaling == 'wekelijks' ? 'active' : '' ?>">Wekelijks</a>
            <a href="?type=maandelijks" class="<?php echo $herhaling == 'maandelijks' ? 'active' : '' ?>">Maandelijks</a>
        </div>

        <div class="table-wrapper">
            <table>
                <colgroup>
                    <col style="width: 64px;">
                    <col style="width: 30%;">
                    <col style="width: auto;">
                    <?php if ($herhaling === 'wekelijks'): ?>
                        <col style="width: 120px;">
                    <?php endif; ?>
                    <col style="width: 160px;">
                </colgroup>
                <thead>
                    <tr>
                        <th></th>
                        <th>Taak</th>
                        <th>Beschrijving</th>
                        <?php if ($herhaling === 'wekelijks'): ?>
                            <th>Dagen</th>
                        <?php endif; ?>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if ($herhaling === 'dagelijks'): ?>
                        <?php
                        $labels = [
                            'start' => 'Start van de dag',
                            'door' => 'Door de dag heen',
                            'eind' => 'Eind van de dag'
                        ];
                        $groups = ['start' => [], 'door' => [], 'eind' => []];
                        foreach ($rows as $r) {
                            $cat = $r['categorie'] ?? 'door';
                            if (!isset($groups[$cat])) $groups[$cat] = [];
                            $groups[$cat][] = $r;
                        }

                        foreach (['start', 'door', 'eind'] as $cat):
                            echo '<tr class="group"><td colspan="4">' . htmlspecialchars($labels[$cat]) . '</td></tr>';
                            if (empty($groups[$cat])):
                                echo '<tr class="empty-cat"><td colspan="4" style="padding:10px 16px;color:var(--tl-text-muted)">Geen taken meer in deze categorie.</td></tr>';
                            else:
                                foreach ($groups[$cat] as $row):
                        ?>

                                    <tr data-herhaling="<?php echo htmlspecialchars($herhaling) ?>" data-categorie="<?php echo htmlspecialchars($row['categorie'] ?? 'door') ?>" data-weekdag="<?php echo htmlspecialchars($row['weekdag'] ?? '') ?>">

                                        <td class="checkbox-cell">
                                            <form method="POST" class="complete-form">
                                                <input type="hidden" name="complete_id" value="<?php echo $row['id'] ?>">
                                                <input type="hidden" name="checked" value="1" class="checked-input">
                                                <label class="checkbox-container">
                                                    <input type="checkbox" class="complete-checkbox">
                                                    <span class="checkmark"></span>
                                                </label>
                                            </form>
                                        </td>

                                        <td data-label="Taak">
                                            <?php echo htmlspecialchars($row['taak']) ?>
                                        </td>

                                        <td data-label="Beschrijving">
                                            <?php echo htmlspecialchars($row['beschrijving']) ?>
                                        </td>

                                    </tr>

                        <?php endforeach;
                            endif;
                        endforeach;
                        ?>
                    <?php else: ?>
                        <?php if (empty($rows)): ?>
                            <tr class="empty-cat">
                                <td colspan="<?php echo $herhaling === 'wekelijks' ? 5 : 4 ?>" style="padding:10px 16px;color:var(--tl-text-muted)">Alle taken voltooid voor deze periode.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr data-herhaling="<?php echo htmlspecialchars($herhaling) ?>" data-weekdag="<?php echo htmlspecialchars($row['weekdag'] ?? '') ?>">
                                    <td class="checkbox-cell">
                                        <form method="POST" class="complete-form">
                                            <input type="hidden" name="complete_id" value="<?php echo $row['id'] ?>">
                                            <input type="hidden" name="checked" value="1" class="checked-input">
                                            <label class="checkbox-container">
                                                <input type="checkbox" class="complete-checkbox">
                                                <span class="checkmark"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td data-label="Taak"><?php echo htmlspecialchars($row['taak']) ?></td>
                                    <td data-label="Beschrijving"><?php echo htmlspecialchars($row['beschrijving']) ?></td>
                                    <?php if ($herhaling === 'wekelijks'): ?>
                                        <td data-label="Dagen">
                                            <?php
                                            $weekdag = $row['weekdag'];
                                            if ($weekdag) {
                                                $days = json_decode($weekdag, true);
                                                echo htmlspecialchars(implode(', ', $days));
                                            } else {
                                                echo 'Alle dagen';
                                            }
                                            ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

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
                <div id="modal-beschrijving" class="text-input-multiline" contenteditable="true" role="textbox" aria-multiline="true"></div>
                <input type="hidden" name="beschrijving" id="modal-beschrijving-hidden">
                <label for="modal-herhaling">Herhaling</label>
                <select id="modal-herhaling" name="herhaling">
                    <option value="dagelijks">Dagelijks</option>
                    <option value="wekelijks">Wekelijks</option>
                    <option value="maandelijks">Maandelijks</option>
                </select>

                <div id="modal-categorie-group">
                    <label for="modal-categorie">Categorie</label>
                    <select id="modal-categorie" name="categorie">
                        <option value="start">Start van de dag</option>
                        <option value="door">Door de dag heen</option>
                        <option value="eind">Eind van de dag</option>
                    </select>
                </div>

                <div id="modal-weekdag-group" style="display: none;">
                    <label>Dagen van de week</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <label><input type="checkbox" name="weekdag[]" value="maandag"> Maandag</label>
                        <label><input type="checkbox" name="weekdag[]" value="dinsdag"> Dinsdag</label>
                        <label><input type="checkbox" name="weekdag[]" value="woensdag"> Woensdag</label>
                        <label><input type="checkbox" name="weekdag[]" value="donderdag"> Donderdag</label>
                        <label><input type="checkbox" name="weekdag[]" value="vrijdag"> Vrijdag</label>
                    </div>
                </div>
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