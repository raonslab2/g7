<?php

// 실패한 native 사전 검증만 관찰한다. wipe/import/재시도는 호출하지 않는다.
declare(strict_types=1);
require __DIR__.'/recovery-common.php';
require __DIR__.'/runtime-bootstrap.php';
$phase = 'readonly_state';
try {
    require_once dirname(__DIR__, 3).'/vendor/autoload.php';
    $orphans = recoveryOrphans();
    $pdo = w04Pdo();
    $connection = recoveryConnection($pdo);
    $inventory = w04Measure($pdo);
    $pdo = null;
    w04Save(__DIR__.'/evidence/blocked-current.json', $inventory);
    $phase = 'readonly_native_preflight';
    w04App();
    w04Save(__DIR__.'/evidence/blocked-diagnostic.json', ['phase' => $phase, 'preflight' => 'PASS',
        'connection' => $connection, 'orphans' => $orphans, 'database_mutations' => 0]);
} catch (Throwable $error) {
    $result = ['phase' => $phase, 'class' => get_class($error), 'source_file' => basename($error->getFile()),
        'source_line' => $error->getLine(), 'message_sha256' => hash('sha256', $error->getMessage()),
        'database_mutations' => 0, 'automatic_retry' => false];
    if (isset($inventory)) {
        $result['current_table_count'] = $inventory['table_count'];
        $result['current_row_count'] = $inventory['row_count'];
        $result['current_inventory_digest'] = recoveryDigest($inventory);
        $result['connection'] = $connection;
        $result['orphans'] = $orphans;
    }
    w04Save(__DIR__.'/evidence/blocked-diagnostic.json', $result);
    echo json_encode($result)."\n";
    exit(1);
}
