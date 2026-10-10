<?php
// Adapted (req_812d0334) from failed historical reviewer w04r/html-product.php (readonly sha256 f4f94b32...): own guard/paths only.
// W04 repaired-install review: actual installed native ProductService HTML create/update on own synthetic products.
// Requires the validated private snapshot boundary. No direct SQL write; reads verify persisted values only.
require __DIR__.'/guard.php';
require __DIR__.'/runtime-bootstrap.php';
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Sirsoft\Ecommerce\Services\ProductService;

$label = $argv[1] ?? '';
w04fRequireSnapshot($label);
$evidence = w04fEvidence('install');
try {
    $app = w04App();
    $root = base_path();
    // Dependency must come from the actual installed ecommerce vendor, never root/bundled source.
    $purifierFile = realpath((new ReflectionClass(HTMLPurifier::class))->getFileName());
    travelLabCheck(str_starts_with($purifierFile, $root.'/modules/sirsoft-ecommerce/vendor/ezyang/htmlpurifier/library/'), 'HTMLPurifier origin is not the installed ecommerce vendor.');
    $serviceFile = (new ReflectionClass(ProductService::class))->getFileName();
    travelLabCheck($serviceFile === $root.'/modules/sirsoft-ecommerce/src/Services/ProductService.php', 'ProductService is not the installed copy.');
    travelLabCheck(hash_file('sha256', $serviceFile) === hash_file('sha256', $root.'/modules/_bundled/sirsoft-ecommerce/src/Services/ProductService.php'), 'Installed ProductService bytes differ.');

    $super = App\Models\User::where('email', w04Env()['INSTALLER_ADMIN_EMAIL'])->firstOrFail();
    Auth::setUser($super);
    $catalog = app(Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface::class);
    $policy = $catalog->findSampleShippingPolicy();
    travelLabCheck($policy !== null, 'Native sample shipping policy missing.');
    $policyRefs = [];
    foreach ($policy->getAttributes() as $key => $value) {
        if (str_ends_with($key, '_id') || str_contains($key, 'carrier') || str_contains($key, 'type')) { $policyRefs[$key] = is_scalar($value) || $value === null ? $value : '[non-scalar]'; }
    }
    $products = app(ProductService::class);
    $run = bin2hex(random_bytes(5));
    $hostile = '<p>Safe <strong>bold</strong> <em>em</em> <a href="https://example.invalid/x" target="_blank">ok link</a></p>'
        .'<ul><li>item</li></ul><script>alert(1)</script>'
        .'<img src="https://example.invalid/a.png" alt="a" onerror="alert(2)">'
        .'<a href="javascript:alert(3)" onclick="alert(4)">bad</a><div onmouseover="alert(5)">hover</div>';
    $check = static function (string $html): array {
        return [
            'kept_strong' => str_contains($html, '<strong>bold</strong>'),
            'kept_em' => str_contains($html, '<em>em</em>'),
            'kept_https_link' => str_contains($html, 'href="https://example.invalid/x"'),
            'kept_list' => str_contains($html, '<li>item</li>'),
            'kept_img' => str_contains($html, 'src="https://example.invalid/a.png"'),
            'script_removed' => ! preg_match('/<script|alert\(1\)/i', $html),
            'event_handlers_removed' => ! preg_match('/\bon(error|click|mouseover)\s*=/i', $html),
            'javascript_url_removed' => ! preg_match('/javascript:/i', $html),
        ];
    };
    $results = [];
    $ids = [];
    foreach (['a', 'b'] as $n) {
        $product = $products->create([
            'product_code' => $products->generateUniqueCode(),
            'sku' => 'W04F-HTML-'.strtoupper($run.$n),
            'name' => ['ko' => 'W04R 합성 HTML 상품 '.$n, 'en' => 'W04R synthetic HTML product '.$n],
            'description' => ['ko' => $hostile, 'en' => $hostile],
            'description_mode' => 'html',
            'selling_price' => 15000, 'list_price' => 15000,
            'sales_status' => 'on_sale', 'display_status' => 'hidden', 'tax_status' => 'tax_free',
            'shipping_policy_id' => $policy->id, 'has_options' => false,
            'min_purchase_qty' => 1, 'max_purchase_qty' => 1,
        ]);
        $ids[] = $product->id;
        // Read back through a fresh query (no write) to prove the sanitized value was persisted.
        $stored = json_decode(DB::table('ecommerce_products')->where('id', $product->id)->value('description'), true, flags: JSON_THROW_ON_ERROR);
        $created = ['ko' => $check($stored['ko']), 'en' => $check($stored['en'])];
        $updateHtml = '<h2>Updated</h2><p><strong>bold</strong> <em>em</em></p><ul><li>item</li></ul>'
            .'<a href="https://example.invalid/x" target="_blank">ok link</a><img src="https://example.invalid/a.png" alt="a" onerror="alert(2)">'
            .'<script>alert(1)</script><a href="javascript:alert(3)" onclick="alert(4)">bad</a><div onmouseover="alert(5)">hover</div>';
        $products->update($product->fresh(), ['description' => ['ko' => $updateHtml, 'en' => $updateHtml], 'description_mode' => 'html']);
        $storedUpdate = json_decode(DB::table('ecommerce_products')->where('id', $product->id)->value('description'), true, flags: JSON_THROW_ON_ERROR);
        $updated = ['ko' => $check($storedUpdate['ko']) + ['update_marker' => str_contains($storedUpdate['ko'], '<h2>Updated</h2>')], 'en' => $check($storedUpdate['en'])];
        $price = DB::table('ecommerce_products')->where('id', $product->id)->first(['selling_price', 'list_price', 'shipping_policy_id', 'description_mode']);
        $results[$n] = ['created' => $created, 'updated' => $updated, 'persisted_mode' => $price->description_mode, 'persisted_selling_price' => (int) $price->selling_price, 'persisted_shipping_policy_matches' => (int) $price->shipping_policy_id === (int) $policy->id, 'stored_create_sha256' => hash('sha256', $stored['ko']), 'stored_update_sha256' => hash('sha256', $storedUpdate['ko'])];
    }
    $flat = [];
    array_walk_recursive($results, function ($v, $k) use (&$flat) { if (is_bool($v)) { $flat[] = $v; } });
    $pass = ! in_array(false, $flat, true) && array_column($results, 'persisted_mode') === ['html', 'html'] && array_column($results, 'persisted_selling_price') === [15000, 15000];
    w04Save($evidence.'/html-product.json', ['status' => $pass ? 'PASS' : 'FAIL', 'service_origin' => 'modules/sirsoft-ecommerce/src/Services/ProductService.php', 'service_sha256' => hash_file('sha256', $serviceFile), 'htmlpurifier_origin' => str_replace($root.'/', '', $purifierFile), 'htmlpurifier_version' => HTMLPurifier::VERSION, 'synthetic_products' => count($ids), 'shipping_policy_native_refs' => $policyRefs, 'results' => $results, 'direct_sql_writes' => 0]);
    echo ($pass ? 'PASS' : 'FAIL').' native ProductService HTML create/update products='.count($ids)."\n";
    exit($pass ? 0 : 1);
} catch (Throwable $e) {
    file_put_contents(base_path('storage/framework/testing/w04c-private/html-private-error.txt'), $e->getMessage()."\n".$e->getTraceAsString());
    w04Save($evidence.'/html-product-failure.json', ['class' => get_class($e)]);
    fwrite(STDERR, 'FAIL html product probe ('.get_class($e).")\n");
    exit(1);
}
