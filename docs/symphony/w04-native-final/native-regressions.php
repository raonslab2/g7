<?php
// Native regression batches (req_caef46f3). Each MySQL batch runs inside its own whole-TEST snapshot
// (snapshot.php <label> run ...) and is restored + exact-verified before the next batch starts.
// Individual counts are recorded per batch; no summing typos are carried from earlier reports.
require __DIR__.'/guard.php';
$root = w04fRoot();
w04fCompleted(false);
w04Env();
$batches = [
    'mysql-workflow' => ['scripts/travel-lab/LiveMysqlTest.php'],
    'support-api' => ['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportApiTest.php'],
    'support-provision' => ['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportProvisionerTest.php'],
    'support-notifications' => ['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportNotificationTest.php'],
    'board-secret' => ['modules/_bundled/sirsoft-board/tests/Feature/User/SecretPostCommentAccessTest.php'],
    'commerce-cart' => ['modules/_bundled/sirsoft-ecommerce/tests/Feature/Cart/CartQuantityAndPurchaseLimitTest.php'],
    'native-auth' => ['tests/Feature/Api/Auth/UserAuthControllerTest.php'],
    'installation' => ['--testsuite=Installation'],
    'installer-context' => ['tests/Unit/Support/InstallerContextTest.php'],
];
$progress = w04fEvidence('regressions').'/progress.json';
$results = is_file($progress) ? json_decode(file_get_contents($progress), true, flags: JSON_THROW_ON_ERROR) : [];
$only = array_slice($argv, 1);
if ($only !== []) { $batches = array_intersect_key($batches, array_flip($only)); }
foreach ($batches as $label => $args) {
    $snap = 'reg-'.$label;
    $log = $root.'/storage/framework/testing/w04f-private/regression-'.$label.'.log';
    $start = microtime(true);
    $status = w04fChild([PHP_BINARY, __DIR__.'/snapshot.php', $snap, 'run', PHP_BINARY, 'vendor/bin/phpunit', '--bootstrap', 'docs/symphony/w04-native-final/test-bootstrap.php', ...$args], $log);
    $native = w04fPrivate($snap).'/native.log';
    $output = is_file($native) ? file_get_contents($native) : '';
    $restore = is_file(__DIR__.'/evidence/'.$snap.'/result.json') ? json_decode(file_get_contents(__DIR__.'/evidence/'.$snap.'/result.json'), true) : null;
    preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $ok);
    preg_match('/Tests:\s*(\d+), Assertions:\s*(\d+)/', $output, $bad);
    preg_match('/Time:\s+([0-9:.]+), Memory:\s+([0-9.]+ MB)/', $output, $time);
    $lines = array_values(array_filter(explode("\n", $output), fn ($line) => preg_match('/^(PHPUnit |Runtime:|Time:|OK \(|Tests:|FAILURES!|ERRORS!|There (was|were) \d|W03:)/', $line)));
    $results[$label] = ['args' => $args, 'runner_exit' => $status, 'phpunit_exit' => $restore['child_exit'] ?? null,
        'tests' => (int) ($ok[1] ?? $bad[1] ?? 0), 'assertions' => (int) ($ok[2] ?? $bad[2] ?? 0), 'phpunit_time' => $time[1] ?? null, 'memory' => $time[2] ?? null,
        'wall_seconds' => round(microtime(true) - $start, 3), 'native_output_sha256' => hash('sha256', $output), 'summary' => $lines,
        'restored_exact' => ($restore['exact_tables_rows_ddl'] ?? false) === true && ($restore['import_exit'] ?? 1) === 0,
        'restored_baseline' => $restore['equals_original_baseline'] ?? false, 'restored_tables' => $restore['table_count'] ?? null, 'restored_rows' => $restore['row_count'] ?? null];
    if(($restore['exact_tables_rows_ddl']??false)===true) {
        w04fQuiesce();
        $independent=w04fChild([PHP_BINARY,__DIR__.'/measure.php','reg-'.$label],w04fPrivate('private').'/remeasure-'.$label.'.log');
        $record=json_decode(file_get_contents(__DIR__.'/evidence/measure/reg-'.$label.'.json'),true,flags:JSON_THROW_ON_ERROR);
        $results[$label]['independent_remeasure_exit']=$independent;
        $results[$label]['independent_remeasure_digest']=$record['digest'];
        if($independent!==0 || $record['digest']!==W04F_BASELINE_DIGEST) { throw new RuntimeException('Independent suite recovery remeasure failed.'); }
    }
    w04Save($progress, $results);
    echo $label.' runner_exit='.$status.' tests='.$results[$label]['tests'].' assertions='.$results[$label]['assertions'].' restored='.json_encode($results[$label]['restored_baseline'])."\n";
    if (is_file(w04fPrivate($snap).'/BLOCKED') || !$results[$label]['restored_baseline']) { fwrite(STDERR, "BLOCKED: restore not verified; stopping before next batch.\n"); exit(1); }
}
exit(in_array(true, array_map(fn ($r) => $r['runner_exit'] !== 0, $results), true) ? 1 : 0);
