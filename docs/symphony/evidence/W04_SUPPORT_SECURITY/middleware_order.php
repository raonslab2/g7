<?php

// Pure native priority check. No Application boot, dotenv, DB or cache access.
require dirname(__DIR__, 4).'/vendor/autoload.php';

$defaults = (new ReflectionClass(Illuminate\Foundation\Http\Kernel::class))->getDefaultProperties();
$declared = [
    Illuminate\Routing\Middleware\SubstituteBindings::class,
    App\Http\Middleware\OptionalSanctumMiddleware::class,
    Illuminate\Routing\Middleware\ThrottleRequests::class.':600,1,travel-lab-support-public:',
];
$resolved = (new Illuminate\Routing\SortedMiddleware($defaults['middlewarePriority'], $declared))->all();
echo json_encode([
    'source_sha' => '598a89fff702d51c1405f1a5952d95ab1d2651f4',
    'scope' => 'pure native middleware sorting, not installed cache inspection',
    'declared' => $declared,
    'resolved' => $resolved,
    'throttle_precedes_optional_auth' => array_search($declared[2], $resolved) < array_search($declared[1], $resolved),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
