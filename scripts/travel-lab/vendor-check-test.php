<?php

declare(strict_types=1);

// Real native bundle extraction into a private disposable fixture; no application/DB boot.
require __DIR__.'/vendor-check.php';

use App\Extension\Vendor\EnvironmentDetector;
use App\Extension\Vendor\Exceptions\VendorInstallException;
use App\Extension\Vendor\VendorBundleInstaller;
use App\Extension\Vendor\VendorInstallContext;
use App\Extension\Vendor\VendorIntegrityChecker;
use App\Extension\Vendor\VendorMode;
use App\Extension\Vendor\VendorResolver;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Psr\Log\NullLogger;

umask(0077);
$root = dirname(__DIR__, 2);
$fixture = $root.'/storage/framework/testing/vendor-check-'.bin2hex(random_bytes(6));
$files = new Filesystem;
$container = new Container;
Container::setInstance($container);
$container->instance('files', $files);
$container->instance('log', new NullLogger);
$container->instance('translator', new Translator(new ArrayLoader, 'en'));
Facade::setFacadeApplication($container);
$results = [];
$exitStatus = 0;
$assert = static function (bool $condition, string $case) use (&$results): void {
    if (! $condition) {
        throw new RuntimeException('Fixture check failed: '.$case);
    }
    $results[$case] = 'PASS';
};
$blocked = static function (callable $probe): bool {
    try {
        $probe();

        return false;
    } catch (RuntimeException) {
        return true;
    }
};
try {
    $files->makeDirectory($fixture.'/source/src/Helpers', 0700, true);
    foreach (['composer.json', 'composer.lock', 'vendor-bundle.json', 'vendor-bundle.zip', 'src/Helpers/helpers.php'] as $path) {
        $files->copy($root.'/modules/_bundled/sirsoft-ecommerce/'.$path, $fixture.'/source/'.$path);
    }
    $assert($blocked(fn () => travelLabVerifyEcommerceVendor($fixture.'/source')), 'missing_installed_autoload_rejected_before_install');
    $bundle = travelLabVerifyEcommerceBundle($fixture.'/source');
    $assert($bundle['package_count'] === 1, 'actual_native_bundle_integrity');
    $checker = new VendorIntegrityChecker;
    $resolver = new VendorResolver(new EnvironmentDetector, $checker, new VendorBundleInstaller($checker));
    // No Composer executor is supplied: any accidental Composer path must fail.
    $installed = $resolver->install(new VendorInstallContext('module', 'sirsoft-ecommerce', $fixture.'/source', $fixture.'/source', VendorMode::Bundled));
    $assert($installed->mode === VendorMode::Bundled && $installed->packageCount === 1, 'actual_native_resolver_offline_bundle_install');
    $vendor = travelLabVerifyEcommerceVendor($fixture.'/source');
    $assert($vendor['version'] === '4.19.0' && $vendor['html_sanitization'], 'actual_installed_library_origin_lock_and_html');
    $files->makeDirectory($fixture.'/missing', 0700, true);
    $assert($blocked(fn () => travelLabVerifyEcommerceVendor($fixture.'/missing')), 'globally_loaded_library_cannot_mask_missing_module_vendor');
    $files->copyDirectory($fixture.'/source', $fixture.'/corrupt');
    file_put_contents($fixture.'/corrupt/vendor-bundle.zip', 'invalid', FILE_APPEND);
    $assert($blocked(fn () => travelLabVerifyEcommerceBundle($fixture.'/corrupt')), 'corrupted_bundle_preflight_rejected');
    try {
        $resolver->install(new VendorInstallContext('module', 'sirsoft-ecommerce', $fixture.'/corrupt', $fixture.'/corrupt', VendorMode::Bundled));
        $assert(false, 'native_resolver_corrupted_bundle_rejected');
    } catch (VendorInstallException) {
        $assert(true, 'native_resolver_corrupted_bundle_rejected');
    }
    $files->deleteDirectory($fixture.'/source/vendor');
    $assert($blocked(fn () => travelLabVerifyEcommerceVendor($fixture.'/source')), 'removed_vendor_rejected_despite_in_process_class_cache');
    echo json_encode(['scope' => 'pure filesystem/native vendor fixture; no DB, Laravel boot, network or installed module mutation', 'results' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: vendor fixture ('.$error::class.').'.PHP_EOL);
    $exitStatus = 1;
} finally {
    $files->deleteDirectory($fixture);
}
exit($exitStatus);
