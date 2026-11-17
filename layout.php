<?php
declare(strict_types=1);

function page_start(string $title): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | Delta Results</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="index.php"><span>DR</span> Delta Results</a>
            <nav>
                <a href="polling_unit.php">Polling unit</a>
                <a href="lga_results.php">LGA totals</a>
                <a href="add_results.php">Enter results</a>
            </nav>
        </div>
    </header>
    <main class="container">
    <?php
}

function page_end(): void
{
    ?>
    </main>
    <footer class="container footer">Bincom programming test &middot; Delta State election results</footer>
    </body>
    </html>
    <?php
}
