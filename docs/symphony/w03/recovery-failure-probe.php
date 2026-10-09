<?php

declare(strict_types=1);

// 실제 DB/CLI/환경파일을 사용하지 않는 고정 소스 catch/finally 제어흐름 재현.
$w03Root = dirname(__DIR__, 3);
$source = file_get_contents($w03Root.'/scripts/travel-lab/live-recovery.php');
if (hash('sha256', $source) !== '24cb60481c6d5814eb19aa48a6f60202f25a1e531f0f5a76b9ee27bc8c93793d') {
    throw new RuntimeException('Pinned recovery source hash mismatch.');
}
$start = strpos($source, '} catch (Throwable $error) {');
$end = strpos($source, 'exit($exitCode);', $start);
if ($start === false || $end === false) {
    throw new RuntimeException('Pinned recovery catch/finally region missing.');
}
$directory = $w03Root.'/storage/framework/testing/w03-recovery-controlflow-'.bin2hex(random_bytes(6));
mkdir($directory, 0700, true);
$dump = $directory.'/test.sql';
$options = $directory.'/mysql.cnf';
file_put_contents($dump, 'SYNTHETIC CONTROL-FLOW FIXTURE; NOT SQL');
file_put_contents($options, 'SYNTHETIC; NO CREDENTIALS');
chmod($dump, 0600);
chmod($options, 0600);
$damaged = true;
$exitCode = 0;
$w03Imports = 0;
function travelLabMysqlTool(array $command, string $input, string $output): void
{
    // 성공한 mysql 종료만 모사한다. 명령은 실행하지 않는다.
    $GLOBALS['w03Imports']++;
}
eval("try { throw new RuntimeException('Restored synthetic testing records differ from the pre-rollback digest.');\n"
    .substr($source, $start, $end - $start));
if ($w03Imports !== 1 || $damaged !== false || is_file($dump) || is_dir($directory) || $exitCode !== 1) {
    throw new RuntimeException('Pinned source did not reproduce the documented failure path.');
}
echo json_encode(['status' => 'REPRODUCED_W03_P2_UNVERIFIED_FALLBACK_SNAPSHOT_DELETION',
    'scope' => 'Exact source catch/finally only, successful importer stub; no DB/env/CLI',
    'failed_check' => 'pre-rollback digest mismatch', 'fallback_imports' => $w03Imports,
    'snapshot_retained' => false, 'fallback_digest_checked' => false,
    'native_restore_failure_reproduction' => 'NOT_RUN'], JSON_PRETTY_PRINT).PHP_EOL;
