<?php

declare(strict_types=1);
use App\Extension\Vendor\VendorIntegrityChecker;

// Dependency-only preflight: no Laravel boot, environment file or DB access.
require_once dirname(__DIR__, 2).'/vendor/autoload.php';

/** @return array{bundle_sha256: string, package_count: int} */
function travelLabVerifyEcommerceBundle(string $modulePath): array
{
    $result = (new VendorIntegrityChecker)->verify($modulePath);
    if (! $result->valid) {
        throw new RuntimeException('Ecommerce bundled dependency integrity failed: '.implode(', ', $result->errors));
    }

    return ['bundle_sha256' => (string) $result->meta['zip_sha256'], 'package_count' => (int) $result->meta['package_count']];
}

/** @return array{package: string, version: string, html_sanitization: bool} */
function travelLabVerifyEcommerceVendor(string $modulePath): array
{
    $module = realpath($modulePath);
    $vendor = $module === false ? false : realpath($module.'/vendor');
    $autoload = $modulePath.'/vendor/autoload.php';
    if ($module === false || $vendor !== $module.'/vendor' || is_link($autoload) || ! is_file($autoload)) {
        throw new RuntimeException('Installed ecommerce vendor/autoload.php missing or outside the module. Run the native bundled installation repair; do not continue to activation or seed.');
    }
    require_once $autoload;
    foreach ([HTMLPurifier::class, HTMLPurifier_Config::class] as $class) {
        if (! class_exists($class)) {
            throw new RuntimeException('Installed ecommerce dependency class is unavailable.');
        }
        $source = realpath((new ReflectionClass($class))->getFileName());
        if ($source === false || ! str_starts_with($source, $vendor.'/ezyang/htmlpurifier/library/')) {
            throw new RuntimeException('Ecommerce dependency was resolved outside its installed vendor directory.');
        }
    }
    $lock = json_decode(file_get_contents($module.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
    $packages = array_values(array_filter($lock['packages'] ?? [], fn (array $package): bool => $package['name'] === 'ezyang/htmlpurifier'));
    if (count($packages) !== 1 || ltrim($packages[0]['version'], 'v') !== HTMLPurifier::VERSION) {
        throw new RuntimeException('Installed ecommerce HTMLPurifier differs from the locked dependency version.');
    }
    $config = HTMLPurifier_Config::createDefault();
    $config->set('Cache.DefinitionImpl', null);
    $purified = (new HTMLPurifier($config))->purify('<p><strong>Travel Lab</strong><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(1)">test</a></p>');
    if (! str_contains($purified, '<strong>Travel Lab</strong>') || preg_match('/<script|javascript:|onclick\s*=/i', $purified)) {
        throw new RuntimeException('Installed ecommerce HTMLPurifier runtime check failed.');
    }

    return ['package' => 'ezyang/htmlpurifier', 'version' => HTMLPurifier::VERSION, 'html_sanitization' => true];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        $bundled = ($argv[1] ?? '') === '--bundled';
        if (count($argv) > ($bundled ? 2 : 1)) {
            throw new RuntimeException('Use vendor-check.php with optional --bundled only.');
        }
        $root = dirname(__DIR__, 2);
        $result = $bundled
            ? travelLabVerifyEcommerceBundle($root.'/modules/_bundled/sirsoft-ecommerce')
            : travelLabVerifyEcommerceVendor($root.'/modules/sirsoft-ecommerce');
        echo json_encode(['status' => 'PASS dependency-only check', ...$result], JSON_THROW_ON_ERROR).PHP_EOL;
    } catch (Throwable $error) {
        fwrite(STDERR, 'BLOCKED: ecommerce dependency preflight failed ('.$error::class.'); no activation/seed/runtime PASS.'.PHP_EOL);
        exit(1);
    }
}
