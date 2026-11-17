<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';

$db = database();
$units = $db->query(
    "SELECT pu.uniqueid, pu.polling_unit_number, pu.polling_unit_name, l.lga_name
     FROM polling_unit pu
     LEFT JOIN lga l ON l.lga_id = pu.lga_id
     WHERE pu.lga_id IN (SELECT lga_id FROM lga WHERE state_id = 25)
       AND pu.uniqueid IN (SELECT polling_unit_uniqueid FROM announced_pu_results)
     ORDER BY pu.uniqueid"
)->fetchAll();

$selectedId = filter_input(INPUT_GET, 'polling_unit', FILTER_VALIDATE_INT) ?: 0;
$unit = null;
$results = [];

if ($selectedId > 0) {
    $statement = $db->prepare(
        "SELECT pu.uniqueid, pu.polling_unit_number, pu.polling_unit_name, l.lga_name
         FROM polling_unit pu
         LEFT JOIN lga l ON l.lga_id = pu.lga_id
         WHERE pu.uniqueid = :id"
    );
    $statement->execute(['id' => $selectedId]);
    $unit = $statement->fetch();

    $statement = $db->prepare(
        "SELECT party_abbreviation, party_score
         FROM announced_pu_results
         WHERE polling_unit_uniqueid = :id
         ORDER BY party_score DESC, party_abbreviation"
    );
    $statement->execute(['id' => (string) $selectedId]);
    $results = $statement->fetchAll();
}

page_start('Polling unit results');
?>
<div class="page-heading">
    <p class="eyebrow">Question 1</p>
    <h1>Polling unit results</h1>
    <p>Select a polling unit to view its announced scores.</p>
</div>
<section class="panel">
    <form method="get" class="inline-form">
        <label for="polling_unit">Polling unit</label>
        <select id="polling_unit" name="polling_unit" required>
            <option value="">Choose a polling unit</option>
            <?php foreach ($units as $option): ?>
                <option value="<?= (int) $option['uniqueid'] ?>" <?= $selectedId === (int) $option['uniqueid'] ? 'selected' : '' ?>>
                    <?= e((string) $option['uniqueid'] . ' · ' . ($option['polling_unit_name'] ?: $option['polling_unit_number'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Show results</button>
    </form>
</section>
<?php if ($unit): ?>
    <section class="panel result-panel">
        <div class="section-title">
            <div>
                <p class="eyebrow">Polling unit #<?= (int) $unit['uniqueid'] ?></p>
                <h2><?= e((string) ($unit['polling_unit_name'] ?: $unit['polling_unit_number'])) ?></h2>
                <p><?= e((string) ($unit['lga_name'] ?: 'Delta State')) ?></p>
            </div>
            <strong><?= number_format(array_sum(array_column($results, 'party_score'))) ?> total votes</strong>
        </div>
        <?php if ($results): ?>
            <div class="table-wrap"><table><thead><tr><th>Party</th><th class="number">Score</th></tr></thead><tbody>
            <?php foreach ($results as $result): ?>
                <tr><td><span class="party-dot"></span><?= e((string) $result['party_abbreviation']) ?></td><td class="number"><?= number_format((int) $result['party_score']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?><p class="empty">No announced results were found for this polling unit.</p><?php endif; ?>
    </section>
<?php elseif ($selectedId > 0): ?>
    <p class="alert">That polling unit could not be found.</p>
<?php endif; ?>
<?php page_end(); ?>
