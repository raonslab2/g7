<?php
/** 公開 extensions.php 조건의 동일 native empty-reference branch. */
require __DIR__.'/runtime-bootstrap.php';
$app=w04App();
Illuminate\Support\Facades\Auth::setUser(App\Models\User::where('email',w04Env()['INSTALLER_ADMIN_EMAIL'])->firstOrFail());
$service=app(Modules\Sirsoft\Ecommerce\Services\ShippingTypeService::class);
$custom=$service->createType(['code'=>'w04-custom-'.bin2hex(random_bytes(4)),'name'=>['ko'=>'W04 운영자 배송','en'=>'W04 operator shipping'],'category'=>'domestic','is_active'=>false,'sort_order'=>88]);
$service->updateType($custom->id,['name'=>['ko'=>'W04 운영자 보존','en'=>'W04 operator preserved'],'sort_order'=>89]);
$before=w04Measure(w04Pdo());
$count=Illuminate\Support\Facades\DB::table('ecommerce_shipping_types')->count();
if($count===0) { Illuminate\Support\Facades\Artisan::call('db:seed',['--class'=>Modules\Sirsoft\Ecommerce\Database\Seeders\ShippingTypeSeeder::class,'--force'=>true,'--no-interaction'=>true]); }
$after=w04Measure(w04Pdo());
if($count!==0 && $before!==$after) { throw new RuntimeException('Nonempty reference branch modified rows.'); }
w04Save(w04fEvidence('install').'/reference-preservation.json',['before_rows'=>$count,'seed_called'=>$count===0,'exact_inventory_if_nonempty'=>$before===$after,'native_class'=>'ShippingTypeSeeder','whole_ecommerce_sample'=>false,'custom_native_service_created'=>true,'custom_operator_fields_preserved'=>$custom->fresh()->sort_order===89]);
