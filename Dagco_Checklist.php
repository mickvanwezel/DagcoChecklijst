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
                        <th></th>
                        <th>Taak</th>
                        <th>Beschrijving</th>
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

                                    <tr data-herhaling="<?= htmlspecialchars($herhaling) ?>" data-categorie="<?= htmlspecialchars($row['categorie'] ?? 'door') ?>">

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

                                        <td data-label="Taak">
                                            <?= htmlspecialchars($row['taak']) ?>
                                        </td>

                                        <td data-label="Beschrijving">
                                            <?= htmlspecialchars($row['beschrijving']) ?>
                                        </td>

                                    </tr>

                        <?php endforeach;
                            endif;
                        endforeach;
                        ?>
                    <?php elseif ($herhaling === 'wekelijks'): ?>
                        <?php
                        $weekdays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag'];
                        $labels = [
                            'start' => 'Start van de dag',
                            'door' => 'Door de dag heen',
                            'eind' => 'Eind van de dag'
                        ];

                        // build nested groups: weekday -> category -> rows
                        $groups = [];
                        foreach ($weekdays as $wd) {
                            $groups[$wd] = ['start' => [], 'door' => [], 'eind' => []];
                        }
                        $other = ['start' => [], 'door' => [], 'eind' => []];
                        foreach ($rows as $r) {
                            $wd = $r['weekdag'] ?? '';
                            $cat = $r['categorie'] ?? 'door';
                            if ($wd && isset($groups[$wd])) {
                                $groups[$wd][$cat][] = $r;
                            } else {
                                $other[$cat][] = $r;
                            }
                        }

                        foreach ($weekdays as $wd):
                            echo '<tr class="group"><td colspan="4">' . htmlspecialchars(ucfirst($wd)) . '</td></tr>';
                            // for each category under this weekday
                            foreach (['start', 'door', 'eind'] as $cat):
                                echo '<tr class="group"><td colspan="4" style="font-weight:600; padding-left:18px">' . htmlspecialchars($labels[$cat]) . '</td></tr>';
                                if (empty($groups[$wd][$cat])):
                                    echo '<tr class="empty-cat"><td colspan="4" style="padding:10px 16px;color:var(--tl-text-muted)">Geen taken meer in deze categorie.</td></tr>';
                                else:
                                    foreach ($groups[$wd][$cat] as $row):
                        ?>

                                        <tr data-herhaling="<?= htmlspecialchars($herhaling) ?>" data-categorie="<?= htmlspecialchars($row['categorie'] ?? 'door') ?>" data-weekdag="<?= htmlspecialchars($row['weekdag'] ?? '') ?>">

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

                                            <td data-label="Taak">
                                                <?= htmlspecialchars($row['taak']) ?>
                                            </td>

                                            <td data-label="Beschrijving">
                                                <?= htmlspecialchars($row['beschrijving']) ?>
                                            </td>

                                        </tr>

                        <?php endforeach;
                                endif;
                            endforeach;
                        endforeach;

                        // render any tasks without a weekday at the end
                        if (array_sum(array_map('count', $other)) > 0) {
                            echo '<tr class="group"><td colspan="4">Ongecategoriseerde dagen</td></tr>';
                            foreach (['start', 'door', 'eind'] as $cat):
                                if (empty($other[$cat])) {
                                    echo '<tr class="empty-cat"><td colspan="4" style="padding:10px 16px;color:var(--tl-text-muted)">Geen taken meer in deze categorie.</td></tr>';
                                } else {
                                    foreach ($other[$cat] as $row) {
                                        echo '<tr data-herhaling="' . htmlspecialchars($herhaling) . '" data-categorie="' . htmlspecialchars($row['categorie'] ?? 'door') . '">';
                                        echo '<td class="checkbox-cell"><form method="POST" class="complete-form"><input type="hidden" name="complete_id" value="' . $row['id'] . '"><input type="hidden" name="checked" value="1" class="checked-input"><label class="checkbox-container"><input type="checkbox" class="complete-checkbox"><span class="checkmark"></span></label></form></td>';
                                        echo '<td data-label="Taak">' . htmlspecialchars($row['taak']) . '</td>';
                                        echo '<td data-label="Beschrijving">' . htmlspecialchars($row['beschrijving']) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            endforeach;
                        }
                        ?>
                    <?php else: ?>
                        <?php if (empty($rows)): ?>
                            <tr class="empty-cat">
                                <td colspan="4" style="padding:10px 16px;color:var(--tl-text-muted)">Alle taken voltooid voor deze periode.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr data-herhaling="<?= htmlspecialchars($herhaling) ?>">
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
                                    <td data-label="Taak"><?= htmlspecialchars($row['taak']) ?></td>
                                    <td data-label="Beschrijving"><?= htmlspecialchars($row['beschrijving']) ?></td>
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

                <div id="modal-weekdag-group" style="display:none;">
                    <label for="modal-weekdag">Weekdag</label>
                    <select id="modal-weekdag" name="weekdag">
                        <option value="maandag">Maandag</option>
                        <option value="dinsdag">Dinsdag</option>
                        <option value="woensdag">Woensdag</option>
                        <option value="donderdag">Donderdag</option>
                        <option value="vrijdag">Vrijdag</option>
                    </select>
                </div>

                <div id="modal-categorie-group">
                    <label for="modal-categorie">Categorie</label>
                    <select id="modal-categorie" name="categorie">
                        <option value="start">Start van de dag</option>
                        <option value="door">Door de dag heen</option>
                        <option value="eind">Eind van de dag</option>
                    </select>
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