<?php

declare(strict_types=1);

/** Native installer-only cache bootstrap; normal runtimes keep database admission locks. */
function travelLabInitialMigrationEnvironment(PDO $pdo, array $environment, string $root): array
{
    $schema = $environment['DB_WRITE_DATABASE'] ?? '';
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql'
        || ! in_array($schema, ['req81_travel_lab', 'req81_travel_lab_test'], true)
        || ($environment['TRAVEL_LAB_ISOLATED'] ?? null) !== '1'
        || ($environment['CACHE_STORE'] ?? null) !== 'database') {
        throw new RuntimeException('Marked native database-cache installation scope required.');
    }
    foreach (['DB_CONNECTION' => 'mysql', 'DB_PREFIX' => 'g7_', 'G7_ENV_PRIORITY' => 'true',
        'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'FILESYSTEM_DISK' => 'local',
        'DB_CACHE_CONNECTION' => 'mysql', 'DB_CACHE_LOCK_CONNECTION' => 'mysql',
        'DB_CACHE_TABLE' => 'cache', 'DB_CACHE_LOCK_TABLE' => 'cache_locks'] as $key => $value) {
        if (($environment[$key] ?? null) !== $value) {
            throw new RuntimeException('Native installer environment escaped its isolation contract.');
        }
    }
    foreach (['DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA', 'CACHE_LIMITER'] as $key) {
        if (! empty($environment[$key])) {
            throw new RuntimeException('Native installer override forbidden.');
        }
    }
    foreach (['DB_WRITE_DATABASE', 'DB_READ_DATABASE', 'DB_DATABASE'] as $key) {
        if (($environment[$key] ?? null) !== $schema) {
            throw new RuntimeException('Migration connection schema mismatch.');
        }
    }
    foreach (['WRITE', 'READ'] as $direction) {
        if (($environment['DB_'.$direction.'_HOST'] ?? null) !== '127.0.0.1'
            || ($environment['DB_'.$direction.'_PORT'] ?? null) !== '3306'
            || ($environment['DB_'.$direction.'_USERNAME'] ?? null) !== 'req81_travel') {
            throw new RuntimeException('Migration connection escaped the isolated account.');
        }
    }
    $identity = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
    if ($identity['db'] !== $schema || $identity['account'] !== 'req81_travel@127.0.0.1') {
        throw new RuntimeException('Live native installer connection escaped its scope.');
    }
    $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn();
    if ($tables !== 0) {
        return $environment; // Existing installations, including incomplete schemas, never bypass DB cache.
    }
    if (($environment['INSTALLER_COMPLETED'] ?? null) !== 'false') {
        throw new RuntimeException('Empty-cache bootstrap requires an incomplete native installation.');
    }
    foreach (['ROUTINES' => 'ROUTINE_SCHEMA', 'EVENTS' => 'EVENT_SCHEMA', 'TRIGGERS' => 'TRIGGER_SCHEMA'] as $table => $column) {
        if ((int) $pdo->query('SELECT COUNT(*) FROM information_schema.'.$table.' WHERE '.$column.' = DATABASE()')->fetchColumn() !== 0) {
            throw new RuntimeException('Empty bootstrap cannot preserve pre-existing SQL objects.');
        }
    }
    foreach (['modules', 'templates', 'plugins'] as $type) {
        foreach (glob($root.'/'.$type.'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            if (! in_array(basename($directory), ['_bundled', '_pending'], true)) {
                throw new RuntimeException('Installed extensions cannot use empty-cache bootstrap.');
            }
        }
    }

    // CoreServiceProvider reads cache before the first migration can create cache tables.
    // Only the initial native migration process receives array; no environment file is rewritten.
    return array_replace($environment, ['CACHE_STORE' => 'array']);
}
