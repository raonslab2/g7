<?php
require __DIR__.'/runtime-bootstrap.php';
$app=w04App();
$source=base_path('modules/_bundled/sirsoft-ecommerce');
$integrity=app(App\Extension\Vendor\VendorIntegrityChecker::class)->verify($source);
$missing=!class_exists('HTMLPurifier');
$failure=null; $missingSymbol=null;
try {
    // Resolve the actual installed native service; invoke only its purifier constructor.
    // No product/order/user write or foreign service call occurs.
    $service=app(Modules\Sirsoft\Ecommerce\Services\ProductService::class);
    (new ReflectionMethod($service,'createPurifier'))->invoke($service);
} catch (Throwable $e) { $failure=get_class($e); if (preg_match('/Class "(HTMLPurifier(?:_Config)?)" not found/',$e->getMessage(),$match)) { $missingSymbol=$match[1]; } }
$autoloads=require base_path('bootstrap/cache/autoload-extensions.php');
w04Save(__DIR__.'/evidence/vendor-mode.json',['source_sha'=>'7de0c4441b68b1c012dbf4a75114200e322051c9','requested_install_mode'=>'bundled','native_install_exit'=>0,'bundled_zip_sha256'=>hash_file('sha256',$source.'/vendor-bundle.zip'),'bundle_integrity_valid'=>$integrity->valid,'htmlpurifier_available'=>!$missing,'installed_vendor_autoload_count'=>count($autoloads['vendor_autoloads'] ?? []),'native_purifier_error_class'=>$failure,'missing_symbol'=>$missingSymbol,'service_origin'=>str_replace(base_path().'/','',(new ReflectionClass(Modules\Sirsoft\Ecommerce\Services\ProductService::class))->getFileName()),'status'=>$missing && $failure !== null?'REPRODUCED_INCOMPLETE_BUNDLED_DEPENDENCY_INSTALL':'NOT_REPRODUCED','scope'=>'Installed native ProductService purifier initialization only; no product write'] );
exit($missing && $failure !== null ? 0 : 1);
