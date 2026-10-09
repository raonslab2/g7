<?php

declare(strict_types=1);
require __DIR__.'/environment.php';

try {
    $environment = travelLabEnvironment();
    $root = dirname(__DIR__, 2);
    if (travelLabProcess([PHP_BINARY, __DIR__.'/vendor-check.php', '--bundled'], $environment) !== 0) {
        throw new RuntimeException('Bundled ecommerce dependencies failed preflight; no extension lifecycle started.');
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=req81_travel_lab', 'req81_travel', $environment['DB_WRITE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $commands = [];
    foreach (['sirsoft-board', 'sirsoft-page', 'sirsoft-ecommerce', 'raonslab-travel_lab'] as $module) {
        if (! is_file($root.'/modules/_bundled/'.$module.'/module.json')) {
            throw new RuntimeException('Pending bundled module: '.$module);
        }
        if ($module === 'raonslab-travel_lab') {
            foreach (['module.php', 'composer.json', 'src/routes/api.php', 'src/routes/catalog.php', 'src/routes/workflow.php', 'src/routes/support.php'] as $artifact) {
                if (! is_file($root.'/modules/_bundled/'.$module.'/'.$artifact)) {
                    throw new RuntimeException('Pending bundled travel artifact: '.$artifact);
                }
            }
        }
        $statement = $pdo->prepare('SELECT status FROM g7_modules WHERE identifier = ?');
        $statement->execute([$module]);
        $status = $statement->fetchColumn();
        if ($status === false) {
            $commands[] = ['module:install', $module, '--vendor-mode=bundled', '--no-interaction'];
        }
        if ($module === 'sirsoft-ecommerce') {
            // Verify a real installed library even when an earlier installation returned success.
            $commands[] = ['@ecommerce-vendor-check'];
        }
        if ($status !== 'active') {
            $commands[] = ['module:activate', $module, '--no-interaction'];
        }
    }
    foreach (['sirsoft-admin_basic', 'raonslab-travel_lab'] as $template) {
        if (! is_file($root.'/templates/_bundled/'.$template.'/template.json')) {
            throw new RuntimeException('Pending bundled template: '.$template);
        }
        $statement = $pdo->prepare('SELECT status FROM g7_templates WHERE identifier = ?');
        $statement->execute([$template]);
        $status = $statement->fetchColumn();
        if ($status === false) {
            $commands[] = ['template:install', $template, '--no-interaction'];
        }
        if ($status !== 'active') {
            $commands[] = ['template:activate', $template, '--no-interaction'];
        }
    }
    foreach ($commands as $command) {
        if ($command === ['@ecommerce-vendor-check']) {
            if (travelLabProcess([PHP_BINARY, __DIR__.'/vendor-check.php'], $environment) !== 0) {
                throw new RuntimeException('Installed ecommerce dependency check failed; activation/sample/support provisioning refused.');
            }

            continue;
        }
        if (travelLabLifecycleProcess([PHP_BINARY, 'artisan', ...$command], $environment) !== 0) {
            throw new RuntimeException('Extension command failed: '.implode(' ', $command));
        }
    }
    if (travelLabLifecycleProcess([PHP_BINARY, 'artisan', 'module:seed', 'raonslab-travel_lab', '--sample', '--no-interaction'], $environment) !== 0) {
        throw new RuntimeException('Travel module seed failed.');
    }
    if (travelLabLifecycleProcess([PHP_BINARY, 'artisan', 'raonslab-travel_lab:support-provision', '--lab-confirm', '--no-interaction'], $environment) !== 0) {
        throw new RuntimeException('Explicit lab support provisioning failed.');
    }
    echo 'PASS: extensions installed/activated, explicit sample seed and lab support provisioning executed.'.PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}
