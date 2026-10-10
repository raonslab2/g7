<?php

declare(strict_types=1);

/*
 * W04 atomic contention (nonauthor, fixed fa552317) — guarded APP PDO access. Derived from the earlier nonauthor w04-final-security guard; extended for DatabaseStore counter/lock and idempotency-gap reads for the isolated travel lab.
 *
 * Usage: php db_guard.php <private-dir> <mode> [json-args]
 *   modes: identity | tables | query <json {"name":..., "params":{...}}> | barrier <json> | own-token-expire <json> | own-token-delete <json>
 *
 * Guards (all must hold before any other statement): database-access.json is 0600 inside a 0700 directory, its
 * source_sha is the fixed review SHA, host is loopback, schema is exactly req81_travel_lab with prefix g7_, and the
 * live SELECT DATABASE()/CURRENT_USER() match. The credential is never printed, logged or put on a command line.
 * Only fixed, named, parameterised statements exist below; no free SQL input is accepted. Output: counts, integer IDs,
 * statuses, amounts and booleans — never contact data, token values, emails, post bodies or SQL text.
 */

const REVIEW_SHA = 'fa5523175ac494cfbd13bbf89bf06b3ec91835a6';
const SCHEMA = 'req81_travel_lab';
const ACCOUNT = 'req81_travel';

[$self, $dir, $mode, $json] = $argv + [null, null, null, '{}'];
$file = rtrim((string) $dir, '/').'/database-access.json';
$fail = function (string $why, int $code = 3) {
    fwrite(STDERR, json_encode(['guard_error' => $why])."\n");
    exit($code);
};
if (! is_file($file) || (fileperms($file) & 0777) !== 0600 || (fileperms(dirname($file)) & 0777) !== 0700) {
    $fail('private file/dir mode');
}
$cfg = json_decode((string) file_get_contents($file), true);
if (($cfg['source_sha'] ?? '') !== REVIEW_SHA || ($cfg['allowed_schema'] ?? '') !== SCHEMA || ($cfg['table_prefix'] ?? '') !== 'g7_'
    || ! in_array($cfg['host'] ?? '', ['127.0.0.1', 'localhost'], true) || (int) ($cfg['port'] ?? 0) !== 3306 || ($cfg['username'] ?? '') !== ACCOUNT) {
    $fail('handoff scope mismatch');
}
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], SCHEMA), $cfg['username'], $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
unset($cfg);
$id = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS cu, CONNECTION_ID() AS cid, VERSION() AS v, @@tx_isolation AS iso')->fetch();
if ($id['db'] !== SCHEMA || explode('@', (string) $id['cu'])[0] !== ACCOUNT) {
    $fail('live identity mismatch');
}
$args = json_decode((string) $json, true) ?? [];
$ints = fn (array $xs) => array_values(array_map('intval', $xs));
$in = fn (array $xs) => implode(',', array_fill(0, max(1, count($xs)), '?'));
$emit = fn ($x) => print(json_encode($x, JSON_UNESCAPED_SLASHES)."\n");
$identity = ['db' => $id['db'], 'account' => explode('@', (string) $id['cu'])[0], 'connection_id' => (int) $id['cid'], 'server_version' => $id['v'], 'isolation' => $id['iso']];

if ($mode === 'identity') {
    $emit($identity);
    exit(0);
}
if ($mode === 'tables') {
    $rows = $pdo->query("SELECT TABLE_NAME AS t, ENGINE AS e, TABLE_ROWS AS approx_rows FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME")->fetchAll();
    $emit(['identity' => $identity, 'tables' => $rows]);
    exit(0);
}

/** Exact counts for a fixed allowlist of tables, globally and (when a user column exists) for given own users. */
function counts(PDO $pdo, array $users): array
{
    $spec = [
        'g7_ecommerce_orders' => 'user_id', 'g7_ecommerce_order_payments' => null, 'g7_ecommerce_order_options' => null,
        'g7_ecommerce_temp_orders' => 'user_id', 'g7_jobs' => null, 'g7_failed_jobs' => null, 'g7_notifications' => 'notifiable_id',
        'g7_travel_lab_inquiries' => 'user_id', 'g7_travel_lab_inquiry_items' => null, 'g7_travel_lab_inquiry_events' => 'actor_id',
        'g7_travel_lab_departures' => null, 'g7_ecommerce_carts' => 'user_id', 'g7_personal_access_tokens' => 'tokenable_id',
        'g7_activity_logs' => 'user_id', 'g7_notification_logs' => null, 'g7_mail_send_logs' => null,
    ];
    $present = array_column($pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(), 'TABLE_NAME');
    $out = [];
    foreach ($spec as $t => $col) {
        if (! in_array($t, $present, true)) {
            $out[$t] = 'absent';

            continue;
        }
        $row = ['all' => (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn()];
        if ($col !== null && $users) {
            $cols = array_column($pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'")->fetchAll(), 'COLUMN_NAME');
            if (in_array($col, $cols, true)) {
                $s = $pdo->prepare("SELECT COUNT(*) FROM `$t` WHERE `$col` IN (".implode(',', array_fill(0, count($users), '?')).')');
                $s->execute($users);
                $row['own'] = (int) $s->fetchColumn();
            }
        }
        $out[$t] = $row;
    }

    return $out;
}

if ($mode === 'query') {
    $p = $args['params'] ?? [];
    $users = $ints($p['users'] ?? []);
    switch ($args['name'] ?? '') {
        case 'counts':
            $emit(['identity' => $identity, 'counts' => counts($pdo, $users)]);
            break;
        case 'departures':
            $ids = $ints($p['ids']);
            $s = $pdo->prepare('SELECT d.id, d.product_id, d.product_option_id, d.capacity, d.reserved, d.is_active, d.departure_date, o.stock_quantity, o.price_adjustment, o.selling_price AS option_selling_price, p.selling_price AS product_selling_price
                FROM g7_travel_lab_departures d JOIN g7_ecommerce_product_options o ON o.id = d.product_option_id JOIN g7_ecommerce_products p ON p.id = d.product_id WHERE d.id IN ('.$in($ids).') ORDER BY d.id');
            $s->execute($ids);
            $emit(['departures' => $s->fetchAll()]);
            break;
        case 'inquiries':
            $s = $pdo->prepare('SELECT i.id, i.user_id, i.status, i.total_amount, i.currency_code, (SELECT COUNT(*) FROM g7_travel_lab_inquiry_items x WHERE x.inquiry_id = i.id) AS items,
                (SELECT COALESCE(SUM(x.quantity),0) FROM g7_travel_lab_inquiry_items x WHERE x.inquiry_id = i.id) AS qty, (SELECT COUNT(*) FROM g7_travel_lab_inquiry_events e WHERE e.inquiry_id = i.id) AS events
                FROM g7_travel_lab_inquiries i WHERE i.user_id IN ('.$in($users).') AND i.id >= ? ORDER BY i.id');
            $s->execute([...$users, (int) ($p['min_id'] ?? 0)]);
            $emit(['inquiries' => $s->fetchAll()]);
            break;
        case 'inquiry_items':
            $ids = $ints($p['ids']);
            $s = $pdo->prepare('SELECT x.inquiry_id, x.departure_id, x.product_option_id, x.quantity, x.unit_price, x.line_total FROM g7_travel_lab_inquiry_items x JOIN g7_travel_lab_inquiries i ON i.id = x.inquiry_id
                WHERE x.inquiry_id IN ('.$in($ids).') AND i.user_id IN ('.$in($users).') ORDER BY x.id');
            $s->execute([...$ids, ...$users]);
            $emit(['items' => $s->fetchAll()]);
            break;
        case 'tokens':
            // Metadata only: id, owner, name, created/expiry/last-used. Never the token hash.
            $s = $pdo->prepare('SELECT id, tokenable_id, name, created_at, expires_at, last_used_at FROM g7_personal_access_tokens WHERE tokenable_type LIKE ? AND tokenable_id IN ('.$in($users).') AND id >= ? ORDER BY id');
            $s->execute(['%User', ...$users, (int) ($p['min_id'] ?? 0)]);
            $emit(['tokens' => $s->fetchAll()]);
            break;
        case 'posts':
            // Native board posts by own users: id/board/secret/deleted/author/title-hash only, never content.
            $s = $pdo->prepare('SELECT p.id, p.board_id, p.user_id, p.is_secret, p.deleted_at IS NOT NULL AS deleted, SHA2(p.title, 256) AS title_sha256 FROM g7_board_posts p WHERE p.user_id IN ('.$in($users).') AND p.id >= ? ORDER BY p.id');
            $s->execute([...$users, (int) ($p['min_id'] ?? 0)]);
            $emit(['posts' => $s->fetchAll()]);
            break;
        case 'floors':
            $f = [];
            foreach (['g7_personal_access_tokens', 'g7_board_posts', 'g7_board_comments', 'g7_travel_lab_inquiries', 'g7_travel_lab_departures', 'g7_ecommerce_products', 'g7_ecommerce_product_options', 'g7_ecommerce_carts', 'g7_activity_logs', 'g7_ecommerce_orders', 'g7_ecommerce_order_payments', 'g7_ecommerce_temp_orders', 'g7_jobs', 'g7_failed_jobs', 'g7_mail_send_logs', 'g7_notification_logs', 'g7_board_attachments'] as $t) {
                try {
                    $f[$t] = (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM `$t`")->fetchColumn();
                } catch (PDOException $e) {
                    $f[$t] = 'unavailable:'.$e->getCode();
                }
            }
            $emit(['identity' => $identity, 'max_ids' => $f, 'at_utc' => gmdate('Y-m-d H:i:s'), 'innodb_deadlocks_global' => (int) ($pdo->query("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->fetch(PDO::FETCH_NUM)[1] ?? -1)]);
            break;
        case 'own_activity':
            // Native activity rows by own actors after a floor: action/loggable ids and whether title/content appear in the change set (booleans only).
            $s = $pdo->prepare('SELECT * FROM g7_activity_logs WHERE user_id IN ('.$in($users).') AND id > ? ORDER BY id');
            $s->execute([...$users, (int) ($p['min_id'] ?? 0)]);
            $outRows = [];
            foreach ($s->fetchAll() as $r) {
                $blob = json_encode($r);
                $outRows[] = ['id' => (int) $r['id'], 'user_id' => (int) $r['user_id'], 'action' => $r['action'] ?? null, 'loggable_type' => isset($r['loggable_type']) ? basename(str_replace('\\', '/', (string) $r['loggable_type'])) : null,
                    'loggable_id' => isset($r['loggable_id']) ? (int) $r['loggable_id'] : null,
                    'changes_mentions_title' => str_contains((string) ($r['changes'] ?? ''), '"title"') || str_contains((string) ($r['changes'] ?? ''), '\\"title\\"'),
                    'changes_mentions_content' => str_contains((string) ($r['changes'] ?? ''), '"content"'),
                    'contains_marker' => isset($p['marker']) && $p['marker'] !== '' ? str_contains($blob, (string) $p['marker']) : null,
                    'contains_body_marker' => isset($p['body_marker']) && $p['body_marker'] !== '' ? str_contains($blob, (string) $p['body_marker']) : null];
            }
            $emit(['activity' => $outRows]);
            break;
        case 'cache_rows':
            $present = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'g7_cache'")->fetchColumn();
            $n = $present ? (int) $pdo->query("SELECT COUNT(*) FROM g7_cache WHERE `key` LIKE '%travel-lab%'")->fetchColumn() : null;
            $emit(['g7_cache_table_present' => (bool) $present, 'travel_lab_rate_rows_in_db_cache' => $n]);
            break;
        case 'lock_visibility':
            $res = [];
            foreach (['performance_schema.data_lock_waits' => 'SELECT COUNT(*) FROM performance_schema.data_lock_waits', 'information_schema.INNODB_TRX' => 'SELECT COUNT(*) FROM information_schema.INNODB_TRX',
                'information_schema.INNODB_LOCK_WAITS' => 'SELECT COUNT(*) FROM information_schema.INNODB_LOCK_WAITS', 'INNODB_METRICS.lock_deadlocks' => "SELECT COUNT FROM information_schema.INNODB_METRICS WHERE NAME = 'lock_deadlocks'",
                'GLOBAL_STATUS.Innodb_deadlocks' => "SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'", 'ENGINE INNODB STATUS' => 'SHOW ENGINE INNODB STATUS'] as $k => $q) {
                try {
                    $r = $pdo->query($q)->fetch(PDO::FETCH_NUM);
                    $res[$k] = $k === 'ENGINE INNODB STATUS' ? ['visible' => true, 'latest_deadlock_section' => str_contains((string) ($r[2] ?? ''), 'LATEST DETECTED DEADLOCK')] : ['visible' => true, 'value' => $r[count($r) - 1] ?? null];
                } catch (PDOException $e) {
                    $res[$k] = ['visible' => false, 'error_class' => get_class($e), 'sqlstate' => $e->getCode(), 'driver_code' => $e->errorInfo[1] ?? null];
                }
            }
            $emit($res);
            break;
        case 'board':
            $s = $pdo->prepare('SELECT * FROM g7_boards WHERE slug = ?');
            $s->execute([(string) $p['slug']]);
            $b = $s->fetch() ?: [];
            $keep = array_filter($b, fn ($k) => preg_match('/^(id|slug|type|is_active|secret_mode|use_|max_|allow|.*file.*|.*attach.*|.*upload.*|.*notify.*|.*notification.*)$/', (string) $k), ARRAY_FILTER_USE_KEY);
            $emit(['board' => $keep]);
            break;
        case 'deadlocks':
            $emit(['innodb_deadlocks_global' => (int) ($pdo->query("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->fetch(PDO::FETCH_NUM)[1] ?? -1), 'at' => microtime(true)]);
            break;
        case 'schema':
            // Column and index NAMES only for the tables this review reasons about.
            $outS = [];
            foreach (['g7_travel_lab_inquiries', 'g7_cache', 'g7_cache_locks', 'g7_users'] as $t) {
                $cols = $pdo->prepare('SELECT COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
                $cols->execute([$t]);
                $idx = $pdo->prepare('SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX');
                $idx->execute([$t]);
                $eng = $pdo->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
                $eng->execute([$t]);
                $outS[$t] = ['engine' => $eng->fetchColumn(), 'columns' => $cols->fetchAll(), 'indexes' => $idx->fetchAll()];
            }
            $emit(['identity' => $identity, 'schema' => $outS, 'innodb_lock_wait_timeout_session' => (int) $pdo->query('SELECT @@innodb_lock_wait_timeout')->fetchColumn()]);
            break;
        case 'cache_keys':
            // Native DatabaseStore rows for the OWN actors' rate-limiter keys only: key = <cache prefix> . <throttle prefix> . sha1(user id).
            // Values are integers; lock rows report presence/expiry only (owner token never read).
            $prefixes = array_values(array_filter((array) ($p['prefixes'] ?? []), fn ($x) => is_string($x) && preg_match('/^travel-lab-[a-z-]+:$/', $x)));
            $now = time();
            $res = [];
            foreach ($users as $u) {
                foreach ($prefixes as $pre) {
                    $k = $pre.sha1((string) $u);
                    $row = function (string $suffix) use ($pdo) {
                        $s = $pdo->prepare('SELECT `key`, `value`, `expiration` FROM g7_cache WHERE `key` LIKE ? ORDER BY `key`');
                        $s->execute(['%'.$suffix]);

                        return $s->fetchAll();
                    };
                    $c = array_values(array_filter($row($k), fn ($r) => str_ends_with((string) $r['key'], $k)));
                    $t = array_values(array_filter($row($k.':timer'), fn ($r) => str_ends_with((string) $r['key'], $k.':timer')));
                    $lockName = 'travel-admission:'.hash('sha256', $k);
                    $ls = $pdo->prepare('SELECT `key`, `expiration` FROM g7_cache_locks WHERE `key` LIKE ?');
                    $ls->execute(['%'.$lockName]);
                    $locks = array_values(array_filter($ls->fetchAll(), fn ($r) => str_ends_with((string) $r['key'], $lockName)));
                    $val = $c ? (string) $c[0]['value'] : null;
                    $res[] = ['user_id' => $u, 'prefix' => $pre, 'counter_rows' => count($c), 'counter' => $val === null ? null : (preg_match('/^-?\d+$/', $val) ? (int) $val : (preg_match('/^i:(-?\d+);$/', $val, $mm) ? (int) $mm[1] : 'non-integer')),
                        'counter_expires_in_s' => $c ? (int) $c[0]['expiration'] - $now : null, 'counter_expired' => $c ? (int) $c[0]['expiration'] <= $now : null,
                        'cache_prefix_length' => $c ? strlen((string) $c[0]['key']) - strlen($k) : null, 'timer_rows' => count($t), 'timer_expires_in_s' => $t ? (int) $t[0]['expiration'] - $now : null,
                        'admission_lock_rows' => count($locks), 'admission_lock_expires_in_s' => $locks ? (int) $locks[0]['expiration'] - $now : null];
                }
            }
            $emit(['at' => microtime(true), 'keys' => $res]);
            break;
        case 'gap_layout':
            // Position of candidate (user_id, idempotency_key) values in the UNIQUE(user_id, idempotency_key) index (non-locking reads).
            // Neighbours are reported as owner class + id only; foreign keys are never output, own keys only as sha256 prefix.
            $pairs = (array) ($p['pairs'] ?? []);
            $outG = [];
            foreach ($pairs as [$u, $k]) {
                $u = (int) $u;
                if (! in_array($u, $users, true)) {
                    $fail('pair user not own', 4);
                }
                $pred = $pdo->prepare('SELECT id, user_id, idempotency_key FROM g7_travel_lab_inquiries WHERE (user_id < ?) OR (user_id = ? AND idempotency_key < ?) ORDER BY user_id DESC, idempotency_key DESC LIMIT 1');
                $pred->execute([$u, $u, (string) $k]);
                $succ = $pdo->prepare('SELECT id, user_id, idempotency_key FROM g7_travel_lab_inquiries WHERE (user_id > ?) OR (user_id = ? AND idempotency_key > ?) ORDER BY user_id ASC, idempotency_key ASC LIMIT 1');
                $succ->execute([$u, $u, (string) $k]);
                $dup = $pdo->prepare('SELECT COUNT(*) FROM g7_travel_lab_inquiries WHERE user_id = ? AND idempotency_key = ?');
                $dup->execute([$u, (string) $k]);
                $cls = function ($r) use ($users, $u) {
                    if (! $r) {
                        return ['edge' => true];
                    }

                    return ['inquiry_id' => (int) $r['id'], 'owner' => (int) $r['user_id'] === $u ? 'same_user' : (in_array((int) $r['user_id'], $users, true) ? 'other_own_actor' : 'foreign'),
                        'user_id_if_own' => in_array((int) $r['user_id'], $users, true) ? (int) $r['user_id'] : null];
                };
                $pr = $pred->fetch() ?: null;
                $sr = $succ->fetch() ?: null;
                $outG[] = ['user_id' => $u, 'key_sha256_12' => substr(hash('sha256', (string) $k), 0, 12), 'exists' => (int) $dup->fetchColumn(), 'predecessor' => $cls($pr), 'successor' => $cls($sr),
                    'gap_id' => substr(hash('sha256', ($pr['id'] ?? 'MIN').'|'.($sr['id'] ?? 'MAX')), 0, 12)];
            }
            $emit(['gaps' => $outG]);
            break;
        case 'inquiry_by_key':
            $s = $pdo->prepare('SELECT id, status, total_amount FROM g7_travel_lab_inquiries WHERE user_id = ? AND idempotency_key = ?');
            $s->execute([(int) ($users[0] ?? 0), (string) ($p['key'] ?? '')]);
            $emit(['rows' => $s->fetchAll()]);
            break;
        default:
            $fail('unknown query', 4);
    }
    exit(0);
}

if ($mode === 'barrier') {
    // Hold FOR UPDATE on one own departure/option/inquiry row; protocol: LOCKED -> OBSERVE -> OBSERVED json -> RELEASE -> RELEASED.
    $kind = (string) ($args['kind'] ?? '');
    $rid = (int) ($args['id'] ?? 0);
    $users = $ints($args['users'] ?? []);
    $owned = match ($kind) {
        'departure' => ['SELECT d.id FROM g7_travel_lab_departures d JOIN g7_ecommerce_products p ON p.id = d.product_id WHERE d.id = ? AND p.sku LIKE ?', [$rid, (string) $args['sku_prefix'].'%'], 'g7_travel_lab_departures'],
        'option' => ['SELECT o.id FROM g7_ecommerce_product_options o JOIN g7_ecommerce_products p ON p.id = o.product_id WHERE o.id = ? AND p.sku LIKE ?', [$rid, (string) $args['sku_prefix'].'%'], 'g7_ecommerce_product_options'],
        'inquiry' => ['SELECT id FROM g7_travel_lab_inquiries WHERE id = ? AND user_id IN ('.$in($users).')', [$rid, ...$users], 'g7_travel_lab_inquiries'],
        'cache' => ['SELECT `key` AS id FROM g7_cache WHERE `key` LIKE ?', ['%'.(string) ($args['limit_prefix'] ?? 'x').sha1((string) (int) ($users[0] ?? 0))], 'g7_cache'],
        default => $fail('bad kind', 4),
    };
    if (! preg_match('/^W04C-[A-Za-z0-9-]+$/', (string) ($args['sku_prefix'] ?? 'W04C-x'))) {
        $fail('bad prefix', 4);
    }
    if ($kind === 'cache' && ! preg_match('/^travel-lab-[a-z-]+:$/', (string) ($args['limit_prefix'] ?? ''))) {
        $fail('bad limit prefix', 4);
    }
    $chk = $pdo->prepare($owned[0]);
    $chk->execute($owned[1]);
    $ownedRow = $chk->fetch();
    if (! $ownedRow) {
        $fail('row not owned', 5);
    }
    $table = $owned[2];
    $lockCol = $kind === 'cache' ? '`key`' : 'id';
    $rid = $kind === 'cache' ? (string) $ownedRow['id'] : $rid;
    $pdo->query('SET SESSION innodb_lock_wait_timeout = 50');
    $pdo->beginTransaction();
    $pdo->prepare("SELECT $lockCol FROM `$table` WHERE $lockCol = ? FOR UPDATE")->execute([$rid]);
    $lockedAt = microtime(true);
    $emit(['event' => 'LOCKED', 'barrier_connection_id' => $identity['connection_id'], 'db' => $identity['db'], 'account' => $identity['account'], 'server_version' => $identity['server_version'], 'isolation' => $identity['isolation'], 'table' => $table, 'row_id' => $kind === 'cache' ? 'own-rate-counter' : $rid, 'at' => $lockedAt, 'innodb_deadlocks_global' => (int) ($pdo->query("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->fetch(PDO::FETCH_NUM)[1] ?? -1)]);
    flush();
    if (trim((string) fgets(STDIN)) !== 'OBSERVE') {
        $pdo->rollBack();
        exit(6);
    }
    $dlwVisible = true;
    $trxVisible = true;
    $samples = [];
    $first = null;
    $deadline = microtime(true) + (float) ($args['max_hold_s'] ?? 25);
    do {
        usleep(150000);
        $now = microtime(true);
        $waitOnMine = [];
        $waitOther = [];
        if ($dlwVisible) {
            try {
                // Which threads wait, and on whose lock (engine-level evidence), restricted to this schema.
                $q = $pdo->query('SELECT t.PROCESSLIST_ID AS waiter, bt.PROCESSLIST_ID AS blocker, w.REQUESTING_ENGINE_LOCK_ID AS r, dl.OBJECT_NAME AS obj
                    FROM performance_schema.data_lock_waits w JOIN performance_schema.threads t ON t.THREAD_ID = w.REQUESTING_THREAD_ID JOIN performance_schema.threads bt ON bt.THREAD_ID = w.BLOCKING_THREAD_ID
                    JOIN performance_schema.data_locks dl ON dl.ENGINE_LOCK_ID = w.REQUESTING_ENGINE_LOCK_ID WHERE dl.OBJECT_SCHEMA = DATABASE()');
                foreach ($q->fetchAll() as $w) {
                    if ((int) $w['blocker'] === $identity['connection_id']) {
                        $waitOnMine[(int) $w['waiter']] = $w['obj'];
                    } else {
                        $waitOther[(int) $w['waiter']] = ['blocker' => (int) $w['blocker'], 'obj' => $w['obj']];
                    }
                }
            } catch (PDOException) {
                $dlwVisible = false;
            }
        }
        $trxWait = [];
        if ($trxVisible) {
            try {
                foreach ($pdo->query("SELECT trx_mysql_thread_id AS tid FROM information_schema.INNODB_TRX WHERE trx_state = 'LOCK WAIT'")->fetchAll() as $t) {
                    $trxWait[(int) $t['tid']] = true;
                }
            } catch (PDOException) {
                $trxVisible = false;
            }
        }
        $rows = $pdo->query('SELECT ID, COMMAND, TIME, STATE, INFO FROM information_schema.PROCESSLIST WHERE DB = DATABASE() AND ID <> CONNECTION_ID()')->fetchAll();
        $facts = [];
        foreach ($rows as $r) {
            $info = (string) ($r['INFO'] ?? '');
            $facts[] = ['connection_id' => (int) $r['ID'], 'command' => $r['COMMAND'], 'time_s' => (int) $r['TIME'], 'state' => $r['STATE'],
                'statement_targets_locked_table_for_update' => $info !== '' && stripos($info, $table) !== false && stripos($info, 'for update') !== false,
                'statement_is_locking_read' => $info !== '' && stripos($info, 'for update') !== false,
                'innodb_lock_wait' => isset($trxWait[(int) $r['ID']]), 'waits_on_barrier' => isset($waitOnMine[(int) $r['ID']]), 'waits_on_other' => $waitOther[(int) $r['ID']] ?? null];
        }
        $onMine = array_values(array_filter($facts, fn ($f) => in_array($f['command'], ['Query', 'Execute'], true) && $f['statement_targets_locked_table_for_update'] && ($f['waits_on_barrier'] || (! $dlwVisible && ($f['innodb_lock_wait'] || ! $trxVisible)))));
        $waiting = array_values(array_filter($facts, fn ($f) => in_array($f['command'], ['Query', 'Execute'], true) && $f['statement_is_locking_read'] && ($f['waits_on_barrier'] || $f['waits_on_other'] !== null || (! $dlwVisible && ($f['innodb_lock_wait'] || ! $trxVisible)))));
        if (count($waiting) >= (int) ($args['min_waiters'] ?? 2) && $first === null) {
            $first = ['at_s' => round($now - $lockedAt, 3), 'on_barrier' => array_column($onMine, 'connection_id'), 'waiting' => $waiting];
        }
        $last = ['at_s' => round($now - $lockedAt, 3), 'on_barrier' => array_column($onMine, 'connection_id'), 'waiting' => $waiting];
        if (count($samples) < 400) {
            $samples[] = ['t' => round($now - $lockedAt, 2), 'on_barrier' => array_column($onMine, 'connection_id'), 'waiting' => array_column($waiting, 'connection_id')];
        }
    } while (($first === null || microtime(true) - $lockedAt < $first['at_s'] + (float) ($args['sustain_s'] ?? 3.2)) && microtime(true) < $deadline);
    $firstIds = $first ? array_column($first['waiting'], 'connection_id') : [];
    $lastIds = array_column($last['waiting'] ?? [], 'connection_id');
    sort($firstIds);
    sort($lastIds);
    $emit(['event' => 'OBSERVED', 'innodb_deadlocks_global' => (int) ($pdo->query("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->fetch(PDO::FETCH_NUM)[1] ?? -1), 'held_for_s' => round(microtime(true) - $lockedAt, 3), 'data_lock_waits_visible' => $dlwVisible, 'innodb_trx_visible' => $trxVisible,
        'first_detection' => $first, 'last_sample' => $last ?? null, 'same_waiters_sustained' => $first !== null && $firstIds === $lastIds,
        'sustained_s' => $first ? round(($last['at_s'] ?? 0) - $first['at_s'], 3) : 0, 'distinct_waiting_connection_ids' => $lastIds, 'samples' => $samples]);
    flush();
    $cmd = trim((string) fgets(STDIN));
    $pdo->commit();
    $emit(['event' => 'RELEASED', 'at' => microtime(true), 'command' => $cmd, 'innodb_deadlocks_global' => (int) ($pdo->query("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->fetch(PDO::FETCH_NUM)[1] ?? -1)]);
    exit(0);
}

if ($mode === 'own-token-expire' || $mode === 'own-token-delete') {
    // Only tokens issued by this run's own native login (id >= run floor, owner in own users, native auth-token name, created after run start).
    $users = $ints($args['users'] ?? []);
    $tid = (int) ($args['token_id'] ?? 0);
    $pdo->beginTransaction();
    $s = $pdo->prepare('SELECT id FROM g7_personal_access_tokens WHERE id = ? AND id >= ? AND tokenable_id IN ('.$in($users).') AND created_at >= ? FOR UPDATE');
    $s->execute([$tid, (int) $args['min_id'], ...$users, (string) $args['created_after']]);
    if (! $s->fetch()) {
        $pdo->rollBack();
        $fail('token not own-issued in this run', 5);
    }
    if ($mode === 'own-token-expire') {
        $u = $pdo->prepare('UPDATE g7_personal_access_tokens SET expires_at = ? WHERE id = ?');
        $u->execute([gmdate('Y-m-d H:i:s', time() - 3600), $tid]);
    } else {
        $u = $pdo->prepare('DELETE FROM g7_personal_access_tokens WHERE id = ?');
        $u->execute([$tid]);
    }
    $n = $u->rowCount();
    $pdo->commit();
    $emit(['mode' => $mode, 'token_id' => $tid, 'rows' => $n]);
    exit(0);
}
$fail('unknown mode', 4);
