<?php

/** 격리 SQLite 와 실제 HTTP 커널·native PageService 응답으로 캠페인 API 문서를 native 생성기로 만듭니다. */
require __DIR__.'/bootstrap.php';

use App\Extension\Testing\ExtensionTestAllowlist;
use App\Support\ApiDoc\ApiDocScaffolder;
use App\Support\ApiDoc\ApiRouteInventory;
use App\Support\ApiDoc\FormRequestIntrospector;
use App\Support\ApiDoc\ResponseSchemaInferrer;
use Illuminate\Support\Carbon;
use Modules\Raonslab\TravelLab\Tests\CampaignTestCase;

class TravelCampaignDocumentationHarness extends CampaignTestCase
{
    /** 응답 필드 설명 (네이티브 생성기 commentMap) */
    private const FIELD_COMMENTS = [
        'items' => '발행된 슬롯 목록 (레지스트리 순서, 최대 2건, 페이지네이션 없음)',
        'slug' => 'native Page slug = 고정 슬롯 주소',
        'kind' => '항상 campaign',
        'theme' => '슬롯의 여행 테마 enum (nature/wellness)',
        'title' => '현재 로케일 제목 (native fallback 규칙)',
        'excerpt' => '본문에서 만든 평문 발췌 (태그·엔티티 제거, 최대 140자)',
        'current_version' => 'native Page 현재 버전 번호',
        'published_at' => '발행 시각 (사용자 시간대)',
        'path' => '고객 상세 경로',
        'catalog_query' => '기존 카탈로그 API 로 그대로 넘기는 서버 고정 필터',
        'art_variant' => '템플릿 ScenicArt 장면 이름',
        'content' => '현재 로케일 본문 원문 (고객 화면이 서식 전용으로 정제해 표시)',
        'content_mode' => 'text 또는 html',
        'updated_at' => 'Page 최종 수정 시각 (사용자 시간대)',
        'sort' => '카탈로그 정렬 enum',
    ];

    /** 엔드포인트 설명 (생성 블록 밖 사람 서술 자리) */
    private const ROUTE_NOTES = [
        'index' => '홈 기획전 카드와 `/travel/campaigns` 목록이 씁니다. 발행된 슬롯만 담으며 초안·부재 슬롯은 빠집니다. 빈 배열은 정상 응답(진행 중 기획전 없음)이고 대체 문구를 만들지 않습니다.',
        'show' => '`/travel/campaigns/{slug}` 와 `/page/{slug}` 별칭이 씁니다. 레지스트리 slug 이고 발행 상태일 때만 200 이며, 그 밖은 열람자와 무관하게 404 입니다. `catalog_query` 로 기존 `GET /catalog` 를 불러 실제 상품·금액을 보여 줍니다.',
    ];

    public function documentation(): void {}

    public function generate(): void
    {
        $this->setUp();
        try {
            $admin = $this->pageAdmin();
            $this->nativeCreate($admin, self::AUTUMN, [
                'title' => ['ko' => '합성 가을 기획전', 'en' => 'Synthetic autumn campaign'],
                'content' => ['ko' => "합성 문서용 본문입니다.\n가격·링크·이미지는 없습니다.", 'en' => "Synthetic documentation body.\nNo prices, links or images."],
            ]);
            $this->nativeCreate($admin, self::WEEKEND, ['published' => false]);

            $inventory = $this->app->make(ApiRouteInventory::class);
            $scaffolder = $this->app->make(ApiDocScaffolder::class);
            $introspector = $this->app->make(FormRequestIntrospector::class);
            $inferrer = $this->app->make(ResponseSchemaInferrer::class);
            $sections = [];
            $keys = [];
            foreach ($inventory->collect('module:raonslab-travel_lab') as $route) {
                if (! str_contains($route['name'], '.campaigns.')) {
                    continue;
                }
                $uri = str_replace('{slug}', self::AUTUMN, $route['uri']);
                $response = $this->getJson($uri);
                if ($response->status() !== 200) {
                    throw new RuntimeException('Isolated documentation probe failed: '.$route['name'].' status '.$response->status());
                }
                $body = $response->json();
                $request = $introspector->introspect($route['controller'], $route['controller_method']);
                // prohibited 규칙은 보낼 수 있는 입력이 아니므로 예시에서 뺀다 (경로 slug 만 남는다).
                $isShow = str_ends_with($route['name'], '.campaigns.show');
                // 상세는 경로 slug 만, 목록은 전송 가능한 입력이 없다(대상 선택자는 모두 금지 = 422).
                $request['params'] = array_values(array_filter($request['params'], fn (array $p): bool => $isShow && $p['name'] === 'slug'));
                $sections[] = $scaffolder->endpointSection($route, $request, $inferrer->infer($body), ['status' => 200, 'body' => $body, 'resolved_uri' => $uri, 'base_url' => 'http://localhost'], self::FIELD_COMMENTS);
                $keys[] = $scaffolder->generatedKey($route);
            }
            if (count($keys) !== 2) {
                throw new RuntimeException('Expected exactly two campaign routes, got '.count($keys));
            }
            $path = dirname(__DIR__).'/docs/api';
            $header = "# 여행 기획전 API\n\n격리 SQLite DB 와 실제 HTTP 커널·native PageService 로 수집한 응답입니다(발행 1건 + 초안 1건). 운영 서버 실측이 아닙니다.\n\n"
                .'고정 두 슬롯(`config/campaigns.php`)만 열거합니다. 미발행·부재·레지스트리 밖 slug 는 비회원·회원·페이지 관리자 모두 404 이며 미리보기는 없습니다. '
                ."목록 조회에 `slug`·`slugs`·`search`·`q`·`prefix`·`ids`·`filters`·`published`·`preview` 를 보내면 422 입니다. 선택 인증 뒤 캠페인 전용 600/분 제한(`travel-lab-campaign-public:`)을 씁니다.\n\n"
                ."[계약과 사용 예시](../campaigns.md) · [격리 생성기](../../tests/generate-campaign-docs.php)\n\n";
            $document = $scaffolder->mergeDocument(is_file($path.'/campaigns.md') ? file_get_contents($path.'/campaigns.md') : null, $header, $sections, $keys);
            // 목록 FormRequest 는 대상 선택자를 prohibited 로만 갖는다 — 생성기 기본 '대표 에러 없음' 대신 실제 422 계약을 싣는다.
            $document = str_replace(
                '_대표 에러 없음 (공개 조회). <!-- TODO: 도메인 특이 에러가 있으면 보강 -->_',
                "| 상태코드 | 의미 | 발생 조건 |\n| --- | --- | --- |\n| 422 | Unprocessable Entity | `slug`·`slugs`·`search`·`q`·`prefix`·`ids`·`filters`·`published`·`preview` 중 하나라도 보낸 경우 (대상 선택 불가) |",
                $document,
            );
            foreach (self::ROUTE_NOTES as $name => $note) {
                $document = preg_replace(
                    '/(<!-- @generated:start:api\.modules\.raonslab-travel_lab\.campaigns\.'.$name.' -->[\s\S]*?<!-- @generated:end -->\s*\*\*설명\*\*) <!-- TODO:[^>]*-->/',
                    '$1 '.$note,
                    $document,
                    1,
                );
            }
            file_put_contents($path.'/campaigns.md', rtrim($document)."\n");
            $entries = [];
            foreach (glob($path.'/*.md') as $document) {
                $count = preg_match_all('/<!-- @generated:start:api\.[^\s]+ -->/', file_get_contents($document));
                if ($count > 0) {
                    $entries[] = ['domain' => pathinfo($document, PATHINFO_FILENAME), 'file' => basename($document), 'count' => $count];
                }
            }
            file_put_contents($path.'/README.md', rtrim($scaffolder->readmeIndex('모듈 `raonslab-travel_lab`', $entries, file_get_contents($path.'/README.md')))."\n");
            echo 'Generated '.count($keys)." campaign endpoints from real isolated responses.\n";
        } finally {
            Carbon::setTestNow();
            ExtensionTestAllowlist::reset();
        }
    }
}

(new TravelCampaignDocumentationHarness('documentation'))->generate();
