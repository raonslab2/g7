<?php
// Import verified backup settings through the native service, without printing values.
// Default is read-only. Keep AWS URL, infrastructure and generated timestamps.
$source = $argv[1] ?? '';
$apply = ($argv[2] ?? '') === '--apply';
if (! is_dir($source)) {
    fwrite(STDERR, "Usage: php import-core-settings.php SETTINGS_DIRECTORY [--apply]\n");
    exit(2);
}
$root = '/srv/g7/current';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = $app->make(App\Services\SettingsService::class);
$categories = ['general', 'mail', 'pagination', 'seo', 'upload', 'cache', 'identity', 'notifications', 'core_update'];
$preserved = ['general.site_url', 'seo.sitemap_last_updated_at'];
$changes = [];
foreach ($categories as $category) {
    $file = $source.'/'.$category.'.json';
    $settings = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    foreach ($settings as $name => $value) {
        $key = $category.'.'.$name;
        if ($name === '_meta' || in_array($key, $preserved, true)) {
            continue;
        }
        if ($service->getSetting($key) !== $value) {
            $changes[] = $key;
            if ($apply && ! $service->setSetting($key, $value)) {
                throw new RuntimeException('Setting write failed: '.$key);
            }
        }
    }
}
echo json_encode(['applied' => $apply, 'changed_keys' => $changes, 'preserved_keys' => $preserved], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
