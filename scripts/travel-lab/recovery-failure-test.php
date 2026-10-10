<?php

declare(strict_types=1);

// Execute the product's catch/finally text with stubs; no DB, SQL client or env.
final class TravelRecoveryFailureFakeDb
{
    public static int $purges = 0;

    public static function purge(): void
    {
        self::$purges++;
    }
}

// eval resolves the extracted DB alias as a global class; provide a local stub.
class_alias(TravelRecoveryFailureFakeDb::class, 'DB');

function travelLabMysqlTool(array $command, string $input, string $output): void
{
    $GLOBALS['recoveryImports']++;
    if ($GLOBALS['recoveryScenario'] === 'import_failure') {
        throw new RuntimeException('PRIVATE SQL TOOL DIAGNOSTIC MUST NOT ESCAPE');
    }
}

function travelLabTestDigest(): array
{
    $GLOBALS['recoveryDigests']++;
    if ($GLOBALS['recoveryScenario'] === 'digest_exception') {
        throw new RuntimeException('PRIVATE SQL QUERY MUST NOT ESCAPE');
    }

    return $GLOBALS['recoveryScenario'] === 'mismatch'
        ? ['users' => ['count' => 1, 'sha256' => 'different']]
        : $GLOBALS['recoveryBefore'];
}

function travelLabCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$sourcePath = $argv[1] ?? __DIR__.'/live-recovery.php';
$source = file_get_contents($sourcePath);
if ($source === false) {
    throw new RuntimeException('Recovery source unreadable.');
}
$start = strpos($source, '} catch (Throwable $error) {');
$end = strpos($source, 'exit($exitCode);', $start ?: 0);
travelLabCheck($start !== false && $end !== false, 'Recovery catch/finally region missing.');
$results = [];
foreach (['verified', 'mismatch', 'import_failure', 'digest_exception'] as $scenario) {
    $directory = dirname(__DIR__, 2).'/storage/framework/testing/travel-recovery-controlflow-'.bin2hex(random_bytes(6));
    mkdir($directory, 0700, true);
    $dump = $directory.'/test.sql';
    $options = $directory.'/mysql.cnf';
    file_put_contents($dump, 'SYNTHETIC CONTROL-FLOW FIXTURE; NOT SQL');
    file_put_contents($options, 'SYNTHETIC; NO CREDENTIALS');
    chmod($dump, 0600);
    chmod($options, 0600);
    $before = ['users' => ['count' => 1, 'sha256' => 'before'], 'travel_lab_inquiries' => ['count' => 2, 'sha256' => 'before-inquiries']];
    $GLOBALS['recoveryScenario'] = $scenario;
    $GLOBALS['recoveryBefore'] = $before;
    $GLOBALS['recoveryImports'] = 0;
    $GLOBALS['recoveryDigests'] = 0;
    DB::$purges = 0;
    $damaged = true;
    $exitCode = 0;
    eval("try { throw new RuntimeException('PRIVATE INITIAL EXCEPTION MUST NOT ESCAPE');\n".substr($source, $start, $end - $start));
    $retained = is_file($dump) && is_file($options) && is_dir($directory);
    $expectedRetained = $scenario !== 'verified';
    $expectedChecks = $scenario === 'import_failure' ? 0 : 1;
    $pass = $GLOBALS['recoveryImports'] === 1
        && DB::$purges === $expectedChecks
        && $GLOBALS['recoveryDigests'] === $expectedChecks
        && $damaged === $expectedRetained && $retained === $expectedRetained
        && $exitCode === 1;
    if ($retained) {
        $pass = $pass && (fileperms($directory) & 0777) === 0700
            && (fileperms($dump) & 0777) === 0600 && (fileperms($options) & 0777) === 0600;
    }
    $results[] = ['scenario' => $scenario, 'status' => $pass ? 'PASS' : 'FAIL', 'imports' => $GLOBALS['recoveryImports'],
        'purges' => DB::$purges, 'digest_checks' => $GLOBALS['recoveryDigests'], 'private_snapshot_retained' => $retained];
    // Only these synthetic probe fixtures are removed after checking retention.
    if (is_dir($directory)) {
        unlink($dump);
        unlink($options);
        rmdir($directory);
    }
}
$pass = ! in_array('FAIL', array_column($results, 'status'), true);
echo json_encode(['status' => $pass ? 'PASS_CONTROL_FLOW_ONLY' : 'FAIL_CONTROL_FLOW_ONLY',
    'source_sha256' => hash('sha256', $source), 'cases' => $results,
    'actual_mysql_restore' => 'NOT_RUN'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
exit($pass ? 0 : 1);
