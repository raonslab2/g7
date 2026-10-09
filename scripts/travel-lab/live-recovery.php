<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/live-bootstrap.php';

/** Round-trip only the disposable testing schema; a dump is never a public artifact. */
function travelLabTestDigest(): array
{
    $result = [];
    foreach (['users', 'ecommerce_products', 'ecommerce_product_options', 'travel_lab_products', 'travel_lab_departures', 'travel_lab_inquiries', 'travel_lab_inquiry_items', 'travel_lab_inquiry_events'] as $table) {
        if (Schema::hasTable($table)) {
            $rows = DB::table($table)->orderBy('id')->get()->toArray();
            $result[$table] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
        }
    }

    return $result;
}

function travelLabMysqlTool(array $command, string $input, string $output): void
{
    $process = proc_open($command, [0 => ['file', $input, 'r'], 1 => ['file', $output, 'w'], 2 => ['pipe', 'w']], $pipes);
    travelLabCheck(is_resource($process), 'MySQL recovery tool could not start.');
    stream_get_contents($pipes[2]); // Never echo raw SQL/credential diagnostics.
    fclose($pipes[2]);
    travelLabCheck(proc_close($process) === 0, 'Scoped MySQL recovery tool failed.');
}

$directory = dirname(__DIR__, 2).'/storage/framework/testing/travel-live-recovery-'.bin2hex(random_bytes(6));
$damaged = false;
$exitCode = 0;
try {
    travelLabApp(true);
    travelLabCheck(Schema::hasTable('travel_lab_inquiries'), 'Run the native MySQL test before recovery verification.');
    mkdir($directory, 0700, true);
    $environment = travelLabEnvironment(true);
    $options = $directory.'/mysql.cnf';
    file_put_contents($options, "[client]\nhost=127.0.0.1\nport=3306\nuser=req81_travel\npassword=".$environment['DB_WRITE_PASSWORD']."\n");
    chmod($options, 0600);
    $dump = $directory.'/test.sql';
    $before = travelLabTestDigest();
    travelLabMysqlTool(['mysqldump', '--defaults-extra-file='.$options, '--single-transaction', '--no-tablespaces', '--skip-comments', 'req81_travel_lab_test'], '/dev/null', $dump);
    chmod($dump, 0600);
    $dumpHash = hash_file('sha256', $dump);
    $paths = glob(dirname(__DIR__, 2).'/modules/_bundled/raonslab-travel_lab/database/migrations/*.php');
    $damaged = true;
    travelLabCheck(Artisan::call('migrate:rollback', [
        '--path' => [dirname(__DIR__, 2).'/modules/_bundled/raonslab-travel_lab/database/migrations'],
        '--realpath' => true, '--step' => count($paths), '--force' => true,
    ]) === 0, 'Travel migration rollback failed.');
    travelLabCheck(! Schema::hasTable('travel_lab_inquiries'), 'Travel migration rollback did not drop its own inquiry table.');
    travelLabCheck(DB::table('users')->count() === $before['users']['count'], 'Core users changed during travel rollback.');
    travelLabCheck(Artisan::call('migrate', [
        '--path' => [dirname(__DIR__, 2).'/modules/_bundled/raonslab-travel_lab/database/migrations'],
        '--realpath' => true, '--force' => true,
    ]) === 0, 'Travel migration replay failed.');
    travelLabCheck(Schema::hasTable('travel_lab_inquiries'), 'Travel migration replay did not restore schema.');
    travelLabMysqlTool(['mysql', '--defaults-extra-file='.$options, '--database=req81_travel_lab_test'], $dump, $directory.'/restore-output');
    DB::purge();
    travelLabCheck(travelLabTestDigest() === $before, 'Restored synthetic testing records differ from the pre-rollback digest.');
    $damaged = false;
    echo json_encode(['status' => 'PASS_IMPLEMENTER_MYSQL_ROLLBACK_RESTORE',
        'source_sha' => trim(shell_exec('git rev-parse HEAD')), 'db' => 'req81_travel_lab_test',
        'private_dump_sha256' => $dumpHash, 'restored_digests' => $before], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    if ($damaged && isset($dump, $options) && is_file($dump)) {
        try {
            travelLabMysqlTool(['mysql', '--defaults-extra-file='.$options, '--database=req81_travel_lab_test'], $dump, $directory.'/restore-output');
            DB::purge();
            travelLabCheck(travelLabTestDigest() === $before, 'Fallback restore differs from the pre-rollback digest.');
            $damaged = false;
            fwrite(STDERR, 'RECOVERED: testing schema restored and all selected-table digests verified after a failed check.'.PHP_EOL);
        } catch (Throwable $restoreError) {
            fwrite(STDERR, 'BLOCKED: testing restore verification failed ('.get_class($restoreError).'); retained private recovery dump required.'.PHP_EOL);
        }
    }
    // Query/tool exception messages can contain SQL or credentials; classify only.
    fwrite(STDERR, 'FAIL: testing rollback/replay or restore verification failed ('.get_class($error).').'.PHP_EOL);
    $exitCode = 1;
} finally {
    // Retain the private snapshot only if recovery itself failed.
    if (! $damaged && is_dir($directory)) {
        foreach (glob($directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}
exit($exitCode);
