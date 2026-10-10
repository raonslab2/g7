<?php

declare(strict_types=1);

/**
 * Loads only the explicitly marked, generated lab environment.
 *
 * @return array<string, string>
 */
function travelLabEnvironment(bool $testing = false, bool $allowGeneratedConfigForCleanup = false): array
{
    $root = dirname(__DIR__, 2);
    require_once $root.'/vendor/autoload.php';
    $path = $root.($testing ? '/.env.testing' : '/.env');
    if (! is_file($path)) {
        throw new RuntimeException('Run php scripts/travel-lab/setup.php first.');
    }
    // Check ownership before parsing any potentially unrelated environment.
    $contents = file_get_contents($path);
    if (! preg_match('/^TRAVEL_LAB_ISOLATED=1$/m', $contents)) {
        throw new RuntimeException('Refusing an environment without TRAVEL_LAB_ISOLATED=1.');
    }
    $values = Dotenv\Dotenv::parse($contents);
    $expected = $testing ? 'req81_travel_lab_test' : 'req81_travel_lab';
    foreach (['DB_WRITE_DATABASE', 'DB_READ_DATABASE', 'DB_DATABASE'] as $key) {
        if (($values[$key] ?? null) !== $expected) {
            throw new RuntimeException('Refusing database outside the lab allowlist: '.$key);
        }
    }
    foreach (['DB_WRITE_HOST', 'DB_READ_HOST'] as $key) {
        if (($values[$key] ?? null) !== '127.0.0.1') {
            throw new RuntimeException('Only local lab database connections are permitted.');
        }
    }
    foreach (['DB_WRITE_USERNAME', 'DB_READ_USERNAME'] as $key) {
        if (($values[$key] ?? null) !== 'req81_travel') {
            throw new RuntimeException('Only the scoped lab database user is permitted.');
        }
    }
    foreach (['DB_WRITE_PORT', 'DB_READ_PORT'] as $key) {
        if (($values[$key] ?? null) !== '3306') {
            throw new RuntimeException('Only the scoped local DB port is permitted.');
        }
    }
    foreach (['DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA'] as $key) {
        if (! empty($values[$key])) {
            throw new RuntimeException('Connection override is forbidden: '.$key);
        }
    }
    if (($values['DB_CONNECTION'] ?? null) !== 'mysql' || ($values['DB_PREFIX'] ?? null) !== 'g7_') {
        throw new RuntimeException('Lab requires the canonical MySQL connection and g7_ table prefix.');
    }
    if (($values['MAIL_MAILER'] ?? null) !== 'array' || ($values['QUEUE_CONNECTION'] ?? null) !== 'sync') {
        throw new RuntimeException('Lab requires array mail and synchronous queue.');
    }
    if (($values['FILESYSTEM_DISK'] ?? null) !== 'local') {
        throw new RuntimeException('Lab requires local file storage.');
    }
    if (! $testing && (($values['CACHE_STORE'] ?? null) !== 'database'
        || ($values['DB_CACHE_CONNECTION'] ?? null) !== 'mysql'
        || ($values['DB_CACHE_LOCK_CONNECTION'] ?? null) !== 'mysql'
        || ($values['DB_CACHE_TABLE'] ?? null) !== 'cache'
        || ($values['DB_CACHE_LOCK_TABLE'] ?? null) !== 'cache_locks'
        || ! empty($values['CACHE_LIMITER']))) {
        throw new RuntimeException('Lab requires native scoped database cache and admission locks.');
    }
    if (($values['G7_ENV_PRIORITY'] ?? null) !== 'true') {
        throw new RuntimeException('Lab environment must own mail/queue/storage configuration.');
    }
    if (is_file($root.'/storage/installer/runtime.php') || (! $allowGeneratedConfigForCleanup && is_file($root.'/bootstrap/cache/config.php'))) {
        throw new RuntimeException('Installer runtime/config cache must be absent in this isolated checkout.');
    }
    $environment = getenv();
    foreach (array_keys($environment) as $key) {
        if (str_starts_with($key, 'DB_') || $key === 'MYSQL_ATTR_SSL_CA' || str_starts_with($key, 'INSTALLER_ADMIN_') || (str_starts_with($key, 'APP_') && str_ends_with($key, '_CACHE'))) {
            unset($environment[$key]);
        }
    }

    return array_merge($environment, $values, ['APP_ENV' => $testing ? 'testing' : 'local'],
        $testing ? ['CACHE_STORE' => 'array'] : []);
}

/** Remove only this marked checkout's generated config after native lifecycle work. */
function travelLabClearGeneratedConfig(): void
{
    travelLabEnvironment(false, true); // All DB/egress checks still apply; installer override remains forbidden.
    $path = dirname(__DIR__, 2).'/bootstrap/cache/config.php';
    if (is_link($path)) {
        throw new RuntimeException('Refusing a symlinked configuration cache.');
    }
    if (is_file($path) && ! unlink($path)) {
        throw new RuntimeException('Could not clear this checkout generated configuration cache.');
    }
}

/** Native G7 lifecycle commands optimize config; a lab must not retain that snapshot. */
function travelLabLifecycleProcess(array $command, array $environment): int
{
    try {
        return travelLabProcess($command, $environment);
    } finally {
        travelLabClearGeneratedConfig();
    }
}

/**
 * @param  list<string>  $command
 * @param  array<string, string>  $environment
 */
function travelLabProcess(array $command, array $environment): int
{
    $process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, dirname(__DIR__, 2), $environment);
    if (! is_resource($process)) {
        throw new RuntimeException('Could not start lab command.');
    }

    return proc_close($process);
}

/** Apply the already-validated environment to a process that bootstraps Laravel. */
function travelLabApplyEnvironment(array $environment): void
{
    $keys = array_unique(array_merge(array_keys(getenv()), array_keys($_ENV), array_keys($_SERVER)));
    foreach ($keys as $key) {
        if (! array_key_exists($key, $environment) && (
            str_starts_with($key, 'DB_') || $key === 'MYSQL_ATTR_SSL_CA'
            || str_starts_with($key, 'INSTALLER_ADMIN_')
            || (str_starts_with($key, 'APP_') && str_ends_with($key, '_CACHE'))
        )) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
    foreach ($environment as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

/** Local administrative SQL; never logs statements, output or credential material. */
function travelLabAdminSql(string $sql): string
{
    $process = proc_open(['sudo', '-n', 'mysql', '--protocol=socket', '-uroot', '--batch', '--skip-column-names'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Local administrative socket unavailable.');
    }
    fwrite($pipes[0], $sql);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Local scoped database operation failed; no external fallback is permitted.');
    }

    return $output;
}
