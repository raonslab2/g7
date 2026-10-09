<?php

declare(strict_types=1);
require __DIR__.'/environment.php';

// Explicit recovery for failed initial MySQL DDL. Never rollback an applied/populated module.
try {
    $environment = travelLabEnvironment();
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=req81_travel_lab', 'req81_travel', $environment['DB_WRITE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $statement = $pdo->query("SELECT COUNT(*) FROM g7_migrations WHERE migration IN ('2026_10_09_000001_create_travel_lab_tables','2026_10_09_900001_add_inquiry_evidence')");
    travelLabPartialCheck((int) $statement->fetchColumn() === 0, 'Refusing recovery of an applied travel migration.');
    $tables = ['travel_lab_inquiry_events', 'travel_lab_inquiry_items', 'travel_lab_inquiries', 'travel_lab_departures', 'travel_lab_products'];
    $present = [];
    foreach ($tables as $table) {
        $query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $query->execute(['g7_'.$table]);
        if ((int) $query->fetchColumn() > 0) {
            travelLabPartialCheck((int) $pdo->query('SELECT COUNT(*) FROM `g7_'.$table.'`')->fetchColumn() === 0, 'Refusing recovery of populated travel tables.');
            $present[] = $table;
        }
    }
    foreach ($present as $table) {
        $pdo->exec('DROP TABLE `g7_'.$table.'`');
    }
    echo 'PASS: '.count($present).' empty unapplied travel tables removed in reverse FK order; no foreign-key guard disabled.'.PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}

function travelLabPartialCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
