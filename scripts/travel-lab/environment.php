<?php

declare(strict_types=1);

/**
 * Loads only the explicitly marked, generated lab environment.
 *
 * @return array<string, string>
 */
function travelLabEnvironment(bool $testing = false): array
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
    if (($values['G7_ENV_PRIORITY'] ?? null) !== 'true') {
        throw new RuntimeException('Lab environment must own mail/queue/storage configuration.');
    }
    if (is_file($root.'/storage/installer/runtime.php') || is_file($root.'/bootstrap/cache/config.php')) {
        throw new RuntimeException('Installer runtime/config cache must be absent in this isolated checkout.');
    }
    $environment = getenv();
    foreach (array_keys($environment) as $key) {
        if (str_starts_with($key, 'DB_') || $key === 'MYSQL_ATTR_SSL_CA' || str_starts_with($key, 'INSTALLER_ADMIN_') || (str_starts_with($key, 'APP_') && str_ends_with($key, '_CACHE'))) {
            unset($environment[$key]);
        }
    }

    return array_merge($environment, $values, ['APP_ENV' => $testing ? 'testing' : 'local']);
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
