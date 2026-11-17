<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';

page_start('Election results dashboard');
?>
<section class="hero">
    <p class="eyebrow">2011 election data</p>
    <h1>Delta State results dashboard</h1>
    <p>Explore announced polling-unit results, calculate LGA totals, or enter results for a new polling unit.</p>
</section>
<section class="card-grid">
    <a class="feature-card" href="polling_unit.php">
        <span class="card-number">01</span>
        <h2>Polling unit results</h2>
        <p>Look up every party score for an individual polling unit.</p>
        <span class="card-link">View results &rarr;</span>
    </a>
    <a class="feature-card" href="lga_results.php">
        <span class="card-number">02</span>
        <h2>LGA totals</h2>
        <p>See summed results for all polling units under a selected local government.</p>
        <span class="card-link">Calculate totals &rarr;</span>
    </a>
    <a class="feature-card" href="add_results.php">
        <span class="card-number">03</span>
        <h2>Enter new results</h2>
        <p>Store scores for every party at a new polling unit.</p>
        <span class="card-link">Enter results &rarr;</span>
    </a>
</section>
<?php page_end(); ?>