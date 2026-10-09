<?php

// This launcher never reads operational credentials or touches an external database.
$projectRoot = dirname(__DIR__, 4);
foreach ([
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('t', 32)),
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
    'SCOUT_DRIVER' => 'null',
    'INSTALLER_COMPLETED' => 'false',
    'APP_CONFIG_CACHE' => 'storage/framework/testing/travel-lab-config.php',
    'APP_ROUTES_CACHE' => 'storage/framework/testing/travel-lab-routes.php',
    'APP_PACKAGES_CACHE' => 'storage/framework/testing/travel-lab-packages.php',
    'APP_SERVICES_CACHE' => 'storage/framework/testing/travel-lab-services.php',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}

$loader = require $projectRoot.'/vendor/autoload.php';
$canonicalNamespaces = [];
foreach (['sirsoft-ecommerce', 'raonslab-travel_lab'] as $identifier) {
    $path = $projectRoot.'/modules/_bundled/'.$identifier;
    $composer = json_decode(file_get_contents($path.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $relative) {
        $loader->addPsr4($namespace, $path.'/'.$relative, true);
        $canonicalNamespaces[$namespace] = $path.'/'.$relative;
    }
    foreach ($composer['autoload']['files'] ?? [] as $relative) {
        require_once $path.'/'.$relative;
    }
}
$loader->addPsr4('Modules\\Raonslab\\TravelLab\\Tests\\', __DIR__, true);
// Core bootstrap may register an installed extension src_classmap, which takes
// priority over Composer PSR-4. A test-only loader pins the source under review
// even when the isolated preview has an older installed extension copy.
spl_autoload_register(static function (string $class) use ($canonicalNamespaces): void {
    foreach ($canonicalNamespaces as $namespace => $directory) {
        if (! str_starts_with($class, $namespace)) {
            continue;
        }
        $file = $directory.'/'.str_replace('\\', '/', substr($class, strlen($namespace))).'.php';
        if (is_file($file)) {
            require_once $file;

            return;
        }
    }
}, true, true);
