<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

require __DIR__.'/live-bootstrap.php';

// Reproduce disposable-worktree env loss only on the marked synthetic lab, before preview/review.
$root = dirname(__DIR__, 2);
$directory = $root.'/storage/framework/testing/travel-live-env-recovery-'.bin2hex(random_bytes(6));
$moved = [];
$exitCode = 0;
try {
    travelLabApp();
    $beforeIds = DB::table('users')->orderBy('id')->pluck('id')->all();
    $before = [];
    foreach (['travel_lab_departures', 'travel_lab_inquiries', 'travel_lab_inquiry_items', 'travel_lab_inquiry_events'] as $table) {
        $before[$table] = hash('sha256', json_encode(DB::table($table)->orderBy('id')->get()->toArray(), JSON_THROW_ON_ERROR));
    }
    mkdir($directory, 0700, true);
    foreach (['.env', '.env.testing'] as $file) {
        travelLabCheck(is_file($root.'/'.$file) && ! is_link($root.'/'.$file), 'Only existing generated regular environment files can be backed up.');
        travelLabCheck(rename($root.'/'.$file, $directory.'/'.$file), 'Could not privately preserve generated environment.');
        chmod($directory.'/'.$file, 0600);
        $moved[] = $file;
    }
    // No Laravel operation in this parent process after rotation: its connection is stale.
    travelLabCheck(travelLabProcess([PHP_BINARY, 'scripts/travel-lab/setup.php'], getenv()) === 0, 'Fresh environment/scoped account recovery failed.');
    $environment = travelLabEnvironment();
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=req81_travel_lab', 'req81_travel', $environment['DB_WRITE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $afterIds = array_map('intval', $pdo->query('SELECT id FROM g7_users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    travelLabCheck(array_map('intval', $beforeIds) === $afterIds, 'Environment recovery replaced existing user IDs.');
    foreach ($before as $table => $hash) {
        $rows = $pdo->query('SELECT * FROM `g7_'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_OBJ);
        travelLabCheck(hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)) === $hash, 'Environment recovery changed travel records.');
    }
    foreach ($moved as $file) {
        unlink($directory.'/'.$file);
    }
    rmdir($directory);
    $moved = [];
    echo 'PASS_IMPLEMENTER_ENV_LOSS_RECOVERY: fresh DB/admin credentials work; all user IDs and travel record digests preserved. New APP_KEY invalidates old sessions; only synthetic lab records are covered.'.PHP_EOL;
} catch (Throwable $error) {
    if ($moved !== []) {
        foreach ($moved as $file) {
            if (is_file($directory.'/'.$file)) {
                rename($directory.'/'.$file, $root.'/'.$file);
            }
        }
        // Restore authentication to the privately retained secrets, through the same scoped setup.
        if (travelLabProcess([PHP_BINARY, 'scripts/travel-lab/setup.php'], getenv()) !== 0) {
            fwrite(STDERR, 'BLOCKED: restored environment requires scoped setup recovery; do not use preview.'.PHP_EOL);
        }
    }
    fwrite(STDERR, 'FAIL: '.$error->getMessage().PHP_EOL);
    $exitCode = 1;
}
exit($exitCode);
