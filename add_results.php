<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';

$db = database();
$units = $db->query(
    "SELECT pu.uniqueid, pu.polling_unit_number, pu.polling_unit_name, l.lga_name
     FROM polling_unit pu LEFT JOIN lga l ON l.lga_id = pu.lga_id
     WHERE pu.lga_id IN (SELECT lga_id FROM lga WHERE state_id = 25)
       AND NOT EXISTS (
           SELECT 1 FROM announced_pu_results existing
           WHERE existing.polling_unit_uniqueid = CAST(pu.uniqueid AS CHAR)
       )
     ORDER BY pu.uniqueid"
)->fetchAll();
$parties = $db->query("SELECT partyid FROM party ORDER BY id")->fetchAll();
$errors = [];
$saved = isset($_GET['saved']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unitId = filter_input(INPUT_POST, 'polling_unit', FILTER_VALIDATE_INT) ?: 0;
    $unitExists = false;
    foreach ($units as $unit) {
        if ((int) $unit['uniqueid'] === $unitId) {
            $unitExists = true;
            break;
        }
    }
    if (!$unitExists) {
        $errors[] = 'Choose a valid Delta State polling unit.';
    }

    $scores = [];
    foreach ($parties as $party) {
        $partyId = (string) $party['partyid'];
        $value = filter_input(INPUT_POST, 'score_' . $partyId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($value === false || $value === null) {
            $errors[] = 'Enter a whole number score for ' . $partyId . '.';
        } else {
            $scores[$partyId] = (int) $value;
        }
    }

    if (!$errors) {
        $insert = $db->prepare(
            "INSERT INTO announced_pu_results
             (polling_unit_uniqueid, party_abbreviation, party_score, entered_by_user, date_entered, user_ip_address)
             VALUES (:unit, :party, :score, :user, NOW(), :ip)"
        );
        $db->beginTransaction();
        try {
            foreach ($scores as $party => $score) {
                $insert->execute([
                    'unit' => (string) $unitId,
                    'party' => substr($party, 0, 4),
                    'score' => $score,
                    'user' => 'web user',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]);
            }
            $db->commit();
            redirect('add_results.php?saved=1');
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }
}

page_start('Enter polling unit results');
?>
<div class="page-heading">
    <p class="eyebrow">Question 3</p>
    <h1>Enter new polling-unit results</h1>
    <p>Choose a polling unit and provide a score for every party. Scores must be zero or greater.</p>
</div>
<?php if ($saved): ?><p class="success">Results saved successfully.</p><?php endif; ?>
<?php if ($errors): ?><div class="alert"><strong>Please check the form:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="panel">
    <form method="post">
        <label for="polling_unit">Polling unit</label>
        <select id="polling_unit" name="polling_unit" required>
            <option value="">Choose a polling unit</option>
            <?php foreach ($units as $unit): ?>
                <option value="<?= (int) $unit['uniqueid'] ?>" <?= (int) ($_POST['polling_unit'] ?? 0) === (int) $unit['uniqueid'] ? 'selected' : '' ?>>
                    <?= e((string) $unit['uniqueid'] . ' · ' . ($unit['polling_unit_name'] ?: $unit['polling_unit_number']) . ' · ' . $unit['lga_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="score-grid">
            <?php foreach ($parties as $party): $partyId = (string) $party['partyid']; ?>
                <div><label for="score_<?= e($partyId) ?>"><?= e($partyId) ?></label><input id="score_<?= e($partyId) ?>" name="score_<?= e($partyId) ?>" type="number" min="0" step="1" value="<?= e((string) ($_POST['score_' . $partyId] ?? '')) ?>" required></div>
            <?php endforeach; ?>
        </div>
        <button type="submit">Save all party results</button>
    </form>
</section>
<?php page_end(); ?>
