<?php
/** Reviewed inherited dependency checks; missing/corrupt mutations not rerun in this Request. */
const W04R_BUNDLE_SHA = '713f98578a866fada6782d11f8c80ff13037f1faf0edaaa55f004ddc4e80d1b9';

function w04rModuleRow(string $id): ?array
{
    $pdo = w04Pdo();
    if ((int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'g7_modules'")->fetchColumn() === 0) { return null; }
    $stmt = $pdo->prepare('SELECT * FROM g7_modules WHERE identifier = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/** After ordinary install and BEFORE activation/sample/support: actual installed dependency. */
function w04rDependencyGate(): void
{
    global $private, $evidence, $root;
    $module = $root.'/modules/sirsoft-ecommerce';
    // Fresh PHP process: package dependency-only check of the actual installed vendor (no Laravel/DB).
    $check = w04fChild([PHP_BINARY, $root.'/scripts/travel-lab/vendor-check.php'], $private.'/vendor-check-installed.txt');
    $bundled = w04fChild([PHP_BINARY, $root.'/scripts/travel-lab/vendor-check.php', '--bundled'], $private.'/vendor-check-bundled.txt');
    $row = w04rModuleRow('sirsoft-ecommerce');
    $manifest = json_decode(file_get_contents($module.'/vendor-bundle.json'), true, flags: JSON_THROW_ON_ERROR);
    $lock = json_decode(file_get_contents($module.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
    $autoloadCache = $root.'/bootstrap/cache/autoload-extensions.php';
    $vendorAutoloads = is_file($autoloadCache) ? ((require $autoloadCache)['vendor_autoloads'] ?? []) : [];
    $installedJson = $module.'/vendor/composer/installed.json';
    $installed = is_file($installedJson) ? json_decode(file_get_contents($installedJson), true, flags: JSON_THROW_ON_ERROR) : [];
    $names = array_map(fn ($p) => $p['name'].'@'.$p['version'], $installed['packages'] ?? []);
    $result = [
        'vendor_check_installed_exit' => $check,
        'vendor_check_installed_output' => trim(file_get_contents($private.'/vendor-check-installed.txt')),
        'vendor_check_bundled_exit' => $bundled,
        'vendor_check_bundled_output' => trim(file_get_contents($private.'/vendor-check-bundled.txt')),
        'installed_vendor_autoload' => is_file($module.'/vendor/autoload.php') && ! is_link($module.'/vendor/autoload.php'),
        'installed_vendor_packages' => $names,
        'manifest_zip_sha256' => $manifest['zip_sha256'],
        'manifest_matches_pinned' => $manifest['zip_sha256'] === W04R_BUNDLE_SHA,
        'installed_zip_sha256' => is_file($module.'/vendor-bundle.zip') ? hash_file('sha256', $module.'/vendor-bundle.zip') : null,
        'lock_htmlpurifier' => array_values(array_map(fn ($p) => $p['version'], array_filter($lock['packages'], fn ($p) => $p['name'] === 'ezyang/htmlpurifier'))),
        'registered_vendor_mode' => $row['vendor_mode'] ?? null,
        'registered_status' => $row['status'] ?? null,
        'autoload_cache_vendor_autoloads' => $vendorAutoloads,
        'phase' => 'after native module:install, before module:activate / sample seed / support provision',
    ];
    $pass = $check === 0 && $bundled === 0 && $result['installed_vendor_autoload'] && $result['manifest_matches_pinned'] && in_array('modules/sirsoft-ecommerce/vendor/autoload.php', $vendorAutoloads, true) && $row !== null;
    // historical reviewer finding W04-I02 (auto persisted). Fixed source fa552317 must persist the exact enum string 'bundled'.
    $result['persisted_vendor_mode_matches_request'] = ($row['vendor_mode'] ?? null) === 'bundled';
    $pass = $pass && $result['persisted_vendor_mode_matches_request'];
    $result['status'] = $pass ? 'PASS' : 'FAIL';
    w04Save($evidence.'/dependency-gate.json', $result);
    echo 'dependency-gate '.$result['status']."\n";
    if (! $pass) { throw new RuntimeException('Installed ecommerce dependency gate failed before activation.'); }
}
