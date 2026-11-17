<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';

$db = database();
$lgas = $db->query("SELECT lga_id, lga_name FROM lga WHERE state_id = 25 ORDER BY lga_name")->fetchAll();
$selectedLga = filter_input(INPUT_GET, 'lga', FILTER_VALIDATE_INT) ?: 0;
$lgaName = '';
$results = [];

if ($selectedLga > 0) {
    $statement = $db->prepare("SELECT lga_name FROM lga WHERE lga_id = :id AND state_id = 25");
    $statement->execute(['id' => $selectedLga]);
    $lgaName = (string) ($statement->fetchColumn() ?: '');

    if ($lgaName !== '') {
        $statement = $db->prepare(
            "SELECT r.party_abbreviation, SUM(r.party_score) AS total_score
             FROM announced_pu_results r
             INNER JOIN polling_unit pu ON pu.uniqueid = CAST(r.polling_unit_uniqueid AS UNSIGNED)
             WHERE pu.lga_id = :id
             GROUP BY r.party_abbreviation
             ORDER BY total_score DESC, r.party_abbreviation"
        );
        $statement->execute(['id' => $selectedLga]);
        $results = $statement->fetchAll();
    }
}

page_start('LGA totals');
?>
<div class="page-heading">
    <p class="eyebrow">Question 2</p>
    <h1>Local government totals</h1>
    <p>Totals are calculated from the announced polling-unit results, not the announced LGA table.</p>
</div>
<section class="panel">
    <form method="get" class="inline-form">
        <label for="lga">Local government</label>
        <select id="lga" name="lga" required>
            <option value="">Choose an LGA</option>
            <?php foreach ($lgas as $lga): ?>
                <option value="<?= (int) $lga['lga_id'] ?>" <?= $selectedLga === (int) $lga['lga_id'] ? 'selected' : '' ?>><?= e((string) $lga['lga_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Calculate total</button>
    </form>
</section>
<?php if ($lgaName !== ''): ?>
    <section class="panel result-panel">
        <div class="section-title"><div><p class="eyebrow">Delta State</p><h2><?= e($lgaName) ?></h2></div><strong><?= number_format(array_sum(array_column($results, 'total_score'))) ?> total votes</strong></div>
        <?php if ($results): ?>
            <div class="table-wrap"><table><thead><tr><th>Party</th><th class="number">Summed score</th></tr></thead><tbody>
            <?php foreach ($results as $result): ?><tr><td><span class="party-dot"></span><?= e((string) $result['party_abbreviation']) ?></td><td class="number"><?= number_format((int) $result['total_score']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?><p class="empty">No polling-unit results are available for this LGA.</p><?php endif; ?>
    </section>
<?php elseif ($selectedLga > 0): ?><p class="alert">That LGA was not found in Delta State.</p><?php endif; ?>
<?php page_end(); ?>
