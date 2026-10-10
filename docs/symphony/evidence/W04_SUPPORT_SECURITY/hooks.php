<?php

// Canonical source declaration only. No Laravel boot or live hook-cache claim.
$root = dirname(__DIR__, 4);
$loader = require $root.'/vendor/autoload.php';
$loader->addPsr4('Modules\\Raonslab\\TravelLab\\', $root.'/modules/_bundled/raonslab-travel_lab/src');
require $root.'/modules/_bundled/raonslab-travel_lab/module.php';
$module = new Modules\Raonslab\TravelLab\Module;
$output = [];
foreach ($module->getHookListeners() as $class) {
    $reflection = new ReflectionClass($class);
    $output[] = [
        'class' => $class,
        'source' => substr($reflection->getFileName(), strlen($root) + 1),
        'sha256' => hash_file('sha256', $reflection->getFileName()),
        'subscriptions' => $class::getSubscribedHooks(),
    ];
}
echo json_encode([
    'source_sha' => '598a89fff702d51c1405f1a5952d95ab1d2651f4',
    'scope' => 'canonical declaration; installed hooks not inspected',
    'listener_count' => count($output),
    'subscription_count' => array_sum(array_map(fn ($x) => count($x['subscriptions']), $output)),
    'listeners' => $output,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
