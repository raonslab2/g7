<?php
// Adapted (req_caef46f3) from failed historical reviewer w04r/fixtures.php (readonly source sha256 374b7ffe...). Corrections:
// persisted vendor_mode must equal bundled for every installed module (hard gate, closes W04-I02/historical reviewer auto FAIL).
// W04 repaired-install review helpers. Loaded only by fresh-install.php inside a validated snapshot boundary.
// Fixture modifications are disposable, own-checkout only, restored byte-exact, and never part of the product PASS.

const W04R_BUNDLE_SHA = '713f98578a866fada6782d11f8c80ff13037f1faf0edaaa55f004ddc4e80d1b9';

function w04rGitClean(string $path): bool
{
    $root = dirname(__DIR__, 3);
    $out = shell_exec('cd '.escapeshellarg($root).' && git status --porcelain --ignored -- '.escapeshellarg($path).' 2>&1');

    return trim((string) $out) === '';
}

function w04rModuleRow(string $id): ?array
{
    $pdo = w04Pdo();
    if ((int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'g7_modules'")->fetchColumn() === 0) { return null; }
    $stmt = $pdo->prepare('SELECT * FROM g7_modules WHERE identifier = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/** Missing/corrupt dependency archive: native install must fail before entry/DDL/registration. */
function w04rBundleFixture(string $kind): void
{
    global $private, $evidence, $root;
    $source = $root.'/modules/_bundled/sirsoft-ecommerce';
    $zip = $source.'/vendor-bundle.zip';
    if (hash_file('sha256', $zip) !== W04R_BUNDLE_SHA || ! w04rGitClean('modules/_bundled/sirsoft-ecommerce')) { throw new RuntimeException('Pinned bundle source differs before fixture.'); }
    $mode = fileperms($zip) & 0777;
    $backup = $private.'/fixture-'.$kind.'.zip';
    if (! copy($zip, $backup) || ! chmod($backup, 0600)) { throw new RuntimeException('Fixture backup failed.'); }
    $before = w04Measure(w04Pdo());
    $active = $root.'/modules/sirsoft-ecommerce';
    $status = null;
    $restored = false;
    try {
        if ($kind === 'missing') {
            if (! unlink($zip)) { throw new RuntimeException('Fixture removal failed.'); }
        } else {
            $bytes = file_get_contents($zip);
            $offset = intdiv(strlen($bytes), 2);
            for ($i = 0; $i < 64; $i++) { $bytes[$offset + $i] = chr(ord($bytes[$offset + $i]) ^ 0xFF); }
            file_put_contents($zip, $bytes);
            if (hash_file('sha256', $zip) === W04R_BUNDLE_SHA || filesize($zip) !== filesize($backup)) { throw new RuntimeException('Corrupt fixture not applied.'); }
        }
        $name = 'fixture-'.$kind;
        w04fQuiesce();
        $status = w04fChild([PHP_BINARY, __DIR__.'/guarded-artisan.php', 'module:install', 'sirsoft-ecommerce', '--vendor-mode=bundled', '--no-interaction'], $private.'/'.$name.'.txt');
    } finally {
        if (is_file($zip)) { unlink($zip); }
        copy($backup, $zip);
        chmod($zip, $mode);
        $restored = hash_file('sha256', $zip) === W04R_BUNDLE_SHA;
    }
    $after = w04Measure(w04Pdo());
    $out = file_get_contents($private.'/fixture-'.$kind.'.txt').file_get_contents($private.'/fixture-'.$kind.'.txt.stderr');
    $markers = [];
    foreach (['vendor-bundle.zip 파일을 찾을 수 없습니다', '무결성 검증에 실패했습니다', ':expected', ':actual'] as $marker) { $markers[$marker] = str_contains($out, $marker); }
    $result = [
        'fixture' => $kind,
        'native_command' => ['module:install', 'sirsoft-ecommerce', '--vendor-mode=bundled', '--no-interaction'],
        'native_exit' => $status,
        'failed_closed' => $status !== 0,
        'schema_rows_ddl_unchanged' => $before === $after,
        'full_inventory_comparison' => w04fCompare($before,$after,['g7_cache']),
        'noncache_rows_ddl_unchanged'=>w04fCompare($before,$after,['g7_cache'])['all_nonallowed_tables_equal'],
        'table_count' => $after['table_count'], 'row_count' => $after['row_count'],
        'module_registration_row' => w04rModuleRow('sirsoft-ecommerce') !== null,
        'active_directory_absent' => ! file_exists($active),
        'pending_directory_absent' => ! file_exists($root.'/modules/_pending/sirsoft-ecommerce'),
        'safe_output_markers' => $markers,
        'output_sha256' => hash('sha256', $out),
        'source_restored_sha256' => hash_file('sha256', $zip),
        'source_restored_exact' => $restored,
        'bundled_source_git_clean' => w04rGitClean('modules/_bundled/sirsoft-ecommerce'),
    ];
    w04Save($evidence.'/fixture-'.$kind.'.json', $result);

    echo 'fixture-'.$kind.' exit='.$status.' unchanged='.json_encode($result['schema_rows_ddl_unchanged'])."\n";
    if (! $result['failed_closed'] || ! $result['noncache_rows_ddl_unchanged'] || $result['module_registration_row'] || ! $result['active_directory_absent'] || ! $result['pending_directory_absent'] || ! $restored || ! $result['bundled_source_git_clean']) {
        throw new RuntimeException('Dependency failure fixture did not fail closed: '.$kind);
    }
    unlink($backup);
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
