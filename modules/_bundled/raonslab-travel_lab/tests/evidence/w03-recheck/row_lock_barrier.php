<?php

declare(strict_types=1);

/*
 * W03 recheck (nonauthor) row-lock barrier for real HTTP overlap.
 *
 * Usage: php row_lock_barrier.php <parent-root> <departure|user|inquiry> <id> <run-prefix> <allowed-user-ids-csv>
 * Protocol (stdin/stdout, one line each): prints LOCKED -> reads OBSERVE -> prints JSON facts
 * -> reads RELEASE -> commits -> prints RELEASED.
 *
 * The connection uses the parent's marked lab .env, loaded in memory by the parent's own
 * scripts/travel-lab/environment.php allowlist. Credentials are never printed or passed on a
 * command line. The script locks only one row it is allowed to lock: a departure of a product whose
 * SKU starts with this run's prefix, or one of the synthetic review users.
 * The output contains only process IDs, command, time and state, plus booleans. It never includes SQL text, credentials or contact data.
 */

[$self, $parentRoot, $kind, $id, $runPrefix, $allowedUsers] = $argv + [null, null, null, null, null, ''];
if (! in_array($kind, ['departure', 'user', 'inquiry'], true) || ! ctype_digit((string) $id) || ! preg_match('/^W03R-[A-Za-z0-9-]+$/', (string) $runPrefix)) {
    fwrite(STDERR, "invalid arguments\n");
    exit(2);
}
require $parentRoot.'/scripts/travel-lab/environment.php';
$env = travelLabEnvironment(false);
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_WRITE_HOST'], $env['DB_WRITE_PORT'], $env['DB_WRITE_DATABASE']),
    $env['DB_WRITE_USERNAME'],
    $env['DB_WRITE_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
unset($env);
$identity = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS cu, CONNECTION_ID() AS cid, VERSION() AS v')->fetch();
$account = explode('@', (string) $identity['cu'])[0];
if ($identity['db'] !== 'req81_travel_lab' || $account !== 'req81_travel') {
    fwrite(STDERR, "refusing non-lab database/account\n");
    exit(3);
}
$id = (int) $id;
if ($kind === 'departure') {
    $owned = $pdo->prepare('SELECT d.id FROM g7_travel_lab_departures d JOIN g7_ecommerce_products p ON p.id = d.product_id WHERE d.id = ? AND p.sku LIKE ?');
    $owned->execute([$id, $runPrefix.'%']);
    $table = 'g7_travel_lab_departures';
} elseif ($kind === 'inquiry') {
    $users = array_map('intval', explode(',', (string) $allowedUsers));
    $owned = $pdo->prepare('SELECT id FROM g7_travel_lab_inquiries WHERE id = ? AND user_id IN ('.implode(',', $users).')');
    $owned->execute([$id]);
    $table = 'g7_travel_lab_inquiries';
} else {
    if (! in_array($id, array_map('intval', explode(',', (string) $allowedUsers)), true)) {
        fwrite(STDERR, "user is not a synthetic review user\n");
        exit(4);
    }
    $owned = $pdo->prepare('SELECT id FROM g7_users WHERE id = ?');
    $owned->execute([$id]);
    $table = 'g7_users';
}
if (! $owned->fetch()) {
    fwrite(STDERR, "row is not owned by this run\n");
    exit(5);
}
$pdo->query('SET SESSION innodb_lock_wait_timeout = 50');
$pdo->beginTransaction();
$lock = $pdo->prepare("SELECT id FROM {$table} WHERE id = ? FOR UPDATE");
$lock->execute([$id]);
$lockedAt = microtime(true);
echo json_encode(['event' => 'LOCKED', 'barrier_connection_id' => (int) $identity['cid'], 'db' => $identity['db'], 'account' => $account, 'server_version' => $identity['v'], 'table' => $table, 'row_id' => $id, 'at' => $lockedAt]), "\n";
flush();

$line = trim((string) fgets(STDIN));
if ($line !== 'OBSERVE') {
    $pdo->rollBack();
    exit(6);
}
$innodbVisible = true;
$deadline = microtime(true) + 20;
$facts = [];
do {
    usleep(150000);
    $rows = $pdo->query('SELECT ID, COMMAND, TIME, STATE, INFO FROM information_schema.PROCESSLIST WHERE DB = DATABASE() AND ID <> CONNECTION_ID()')->fetchAll();
    $waits = [];
    if ($innodbVisible) {
        try {
            foreach ($pdo->query("SELECT trx_mysql_thread_id AS tid, trx_state AS st FROM information_schema.INNODB_TRX WHERE trx_state = 'LOCK WAIT'")->fetchAll() as $trx) {
                $waits[(int) $trx['tid']] = true;
            }
        } catch (PDOException) {
            $innodbVisible = false;
        }
    }
    $facts = [];
    foreach ($rows as $row) {
        // The SQL text is only inspected in memory to identify the waited table; it is never printed.
        $info = (string) ($row['INFO'] ?? '');
        $targetsLockedTable = $info !== '' && stripos($info, $table) !== false && stripos($info, 'for update') !== false;
        $facts[] = [
            'connection_id' => (int) $row['ID'],
            'command' => $row['COMMAND'],
            'time_s' => (int) $row['TIME'],
            'state' => $row['STATE'],
            'innodb_lock_wait' => isset($waits[(int) $row['ID']]),
            'statement_targets_locked_table_for_update' => $targetsLockedTable,
        ];
    }
    // Laravel uses native prepared statements, so a waiting statement shows COMMAND=Execute.
    $blocked = array_values(array_filter($facts, fn ($f) => in_array($f['command'], ['Query', 'Execute'], true) && $f['statement_targets_locked_table_for_update'] && ($f['innodb_lock_wait'] || ! $innodbVisible)));
    if (count($blocked) >= 2 && ! isset($firstSeen)) {
        $firstSeen = ['at_s' => round(microtime(true) - $lockedAt, 3), 'blocked' => $blocked];
    }
    // Keep holding at least 3s after first detection, then report the later sample: the same connections must still wait.
} while ((! isset($firstSeen) || microtime(true) - $lockedAt < $firstSeen['at_s'] + 3) && microtime(true) < $deadline);
echo json_encode([
    'event' => 'OBSERVED',
    'held_for_s' => round(microtime(true) - $lockedAt, 3),
    'innodb_trx_visible' => $innodbVisible,
    'first_detection' => $firstSeen ?? null,
    'still_blocked_after_hold' => isset($firstSeen) && array_column($firstSeen['blocked'], 'connection_id') == array_column($blocked, 'connection_id'),
    'blocked_distinct_connection_ids' => array_values(array_unique(array_column($blocked, 'connection_id'))),
    'blocked' => $blocked,
    'other_lab_connections' => count($facts),
    'lab_connection_facts' => $facts,
]), "\n";
flush();

$line = trim((string) fgets(STDIN));
$pdo->commit();
echo json_encode(['event' => 'RELEASED', 'at' => microtime(true), 'command' => $line]), "\n";
flush();
