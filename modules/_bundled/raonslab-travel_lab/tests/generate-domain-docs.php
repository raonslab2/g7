<?php

/** 격리 DB와 실제 HTTP 커널 응답으로 네이티브 G7 API 문서 생성기를 실행합니다. */
require __DIR__.'/bootstrap.php';

use App\Enums\PermissionType;
use App\Extension\Testing\ExtensionTestAllowlist;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiDoc\ApiDocScaffolder;
use App\Support\ApiDoc\ApiRouteInventory;
use App\Support\ApiDoc\FormRequestIntrospector;
use App\Support\ApiDoc\ResponseSchemaInferrer;
use Illuminate\Support\Carbon;
use Modules\Raonslab\TravelLab\Tests\ModuleTestCase;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

class TravelCatalogDocumentationHarness extends ModuleTestCase
{
    public function documentation(): void {}

    public function generate(): void
    {
        $this->setUp();
        try {
            [$travel, $departure] = $this->createTravel();
            $product = $travel->product_id;
            [$unmapped, $unmappedDeparture] = $this->createTravel();
            $unmappedProduct = $unmapped->product_id;
            $unmappedDeparture->delete();
            $unmapped->delete();
            $extra = ProductOption::create([
                'product_id' => $product, 'option_code' => 'DOC-DEPARTURE',
                'option_name' => ['ko' => '문서 출발', 'en' => 'Documentation departure'],
                'option_values' => [],
                'stock_quantity' => 20, 'price_adjustment' => 20000, 'is_active' => true,
            ]);
            $admin = User::create(['name' => 'API documentation fixture', 'email' => 'travel-doc@example.test', 'password' => 'synthetic-password']);
            $role = Role::create(['identifier' => 'travel-api-doc', 'name' => ['ko' => '문서 테스트', 'en' => 'Documentation test']]);
            $admin->roles()->attach($role->id);
            foreach (['admin.access', 'raonslab-travel_lab.catalog.read', 'raonslab-travel_lab.catalog.update'] as $identifier) {
                $permission = Permission::create(['identifier' => $identifier, 'name' => ['ko' => $identifier, 'en' => $identifier], 'type' => PermissionType::Admin]);
                $role->permissions()->attach($permission->id);
            }
            $token = $admin->createToken('isolated-api-documentation')->plainTextToken;
            $inventory = $this->app->make(ApiRouteInventory::class);
            $scaffolder = $this->app->make(ApiDocScaffolder::class);
            $introspector = $this->app->make(FormRequestIntrospector::class);
            $inferrer = $this->app->make(ResponseSchemaInferrer::class);
            $sections = [];
            $keys = [];
            foreach ($inventory->collect('module:raonslab-travel_lab') as $route) {
                $uri = str_replace(['{product}', '{departure}'], [(string) $product, (string) $departure->id], $route['uri']);
                $payload = [];
                if (str_contains($route['name'], '.admin.')) {
                    $this->withHeader('Authorization', 'Bearer '.$token);
                } else {
                    $this->flushHeaders();
                }
                $isRegistration = str_ends_with($route['name'], '.admin.catalog.store');
                if ($isRegistration) {
                    $payload = ['product_id' => $unmappedProduct, 'region' => 'jeju', 'theme' => 'nature', 'duration_days' => 3,
                        'summary' => ['ko' => '합성 문서 상품', 'en' => 'Synthetic documentation trip'],
                        'itinerary' => [['day' => 1, 'title' => ['ko' => '바다', 'en' => 'Sea']]]];
                } elseif (in_array($route['method'], ['PUT', 'POST'], true)) {
                    $payload = ['product_option_id' => $route['method'] === 'POST' ? $extra->id : $departure->product_option_id, 'departure_date' => '2026-11-01', 'return_date' => '2026-11-03', 'capacity' => 20, 'is_active' => true];
                } elseif ($route['method'] === 'PATCH') {
                    $payload = ['published' => true];
                }
                $response = $this->json($route['method'], $uri, $payload);
                $expectedStatus = $isRegistration ? 201 : 200;
                if ($response->status() !== $expectedStatus) {
                    throw new RuntimeException('Isolated documentation probe failed: '.$route['name'].' status '.$response->status());
                }
                $body = $response->json();
                $requestMetadata = $introspector->introspect($route['controller'], $route['controller_method']);
                // 네이티브 introspector는 prohibited를 일반 입력처럼 표현하므로 전송 가능한 항목만 예시로 만듭니다.
                $prohibited = $isRegistration ? ['reserved', 'unit_price'] : ['reserved', 'product_id', 'unit_price'];
                $requestMetadata['params'] = array_values(array_filter($requestMetadata['params'], fn ($parameter) => ! in_array($parameter['name'], $prohibited, true)));
                foreach ($requestMetadata['params'] as &$parameter) {
                    if ($parameter['name'] === 'sort') {
                        $parameter['type'] = 'string';
                        $parameter['allowed'] = 'recommended, price_asc, price_desc, departure_asc';
                    }
                }
                unset($parameter);
                $section = $scaffolder->endpointSection(
                    $route,
                    $requestMetadata,
                    $inferrer->infer($body),
                    ['status' => $expectedStatus, 'body' => $body, 'resolved_uri' => $uri, 'base_url' => 'http://localhost'],
                );
                $example = "```http\n".$route['method'].' '.$uri." HTTP/1.1\nHost: api.example.com\nAccept: application/json";
                if (str_contains($route['name'], '.admin.')) {
                    $example .= "\nAuthorization: Bearer {YOUR_TOKEN}";
                }
                if ($payload !== []) {
                    $example .= "\nContent-Type: application/json\n\n".json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $example .= "\n```";
                $section = preg_replace('/(?<=\*\*요청 예시\*\*\n\n)```http[\s\S]*?```/', $example, $section, 1);
                $sections[] = $section;
                $keys[] = $scaffolder->generatedKey($route);
            }
            $path = dirname(__DIR__).'/docs/api';
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $header = "# 여행 카탈로그 API\n\n격리 SQLite DB와 실제 HTTP 커널에서 수집한 응답입니다. 운영 서버 실측이 아닙니다.\n\n[계약과 사용 예시](../domain-api.md) · [격리 생성기](../../tests/generate-domain-docs.php)\n\n";
            file_put_contents($path.'/catalog.md', rtrim($scaffolder->mergeDocument(is_file($path.'/catalog.md') ? file_get_contents($path.'/catalog.md') : null, $header, $sections, $keys))."\n");
            // A catalogue-only probe must preserve the other runtime API domains.
            $entries = [];
            foreach (glob($path.'/*.md') as $document) {
                $count = preg_match_all('/<!-- @generated:start:api\.[^\s]+ -->/', file_get_contents($document));
                if ($count > 0) {
                    $entries[] = ['domain' => pathinfo($document, PATHINFO_FILENAME), 'file' => basename($document), 'count' => $count];
                }
            }
            $existingIndex = is_file($path.'/README.md') ? file_get_contents($path.'/README.md') : null;
            file_put_contents($path.'/README.md', rtrim($scaffolder->readmeIndex('모듈 `raonslab-travel_lab`', $entries, $existingIndex))."\n");
            echo 'Generated '.count($keys)." catalog endpoints from real isolated responses.\n";
        } finally {
            // 단독 생성기에는 PHPUnit runner가 없으므로 runner 전용 teardown을 호출하지 않습니다.
            Carbon::setTestNow();
            ExtensionTestAllowlist::reset();
        }
    }
}

(new TravelCatalogDocumentationHarness('documentation'))->generate();
