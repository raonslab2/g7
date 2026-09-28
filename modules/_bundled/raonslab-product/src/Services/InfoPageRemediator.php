<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use Modules\Raonslab\Product\Exceptions\InfoPageRemediationConflict;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Models\PageVersion;
use Modules\Sirsoft\Page\Services\PageService;
use RuntimeException;

/**
 * 운영자 전용 일회성 도구: 그대로 남은 G7 샘플 Page 4종(about/faq/contact/refund)을
 * 승인된 외부 ko/en payload 로 제자리 교체한다.
 *
 * - 하나의 외부 transaction 안에서 4개 행을 모두 잠그고 분류한 뒤에만 쓴다. 하나라도 충돌이면 전부 중단한다.
 * - 쓰기는 PageService::updatePage() 로만 한다(버전 스냅샷·훅·권한 스코프 유지). raw SQL·Model save 없음.
 * - 입력은 content pack 의 `pages` 만이다. envelope·파일 SHA-256·base_commit 은 NativePageContentPack 이 판정한다.
 * - 발행 상태는 바꾸지 않는다. page 의 `published` 는 true 만 허용하는 사전 조건 선언이며 updatePage 로 넘기지 않는다.
 * - module install/update 가 호출하지 않는다. 명시적 CLI 명령에서만 실행된다.
 */
class InfoPageRemediator
{
    public const FINGERPRINT_NAMESPACE = 'g7-page-sample-v1';

    public const REQUIRED_VERSION = 1;

    /**
     * SOURCE_FINGERPRINTS 를 측정한 감사 기준 commit. content pack 의 base_commit 은 이 값이어야 한다
     * (다른 기준에서 만든 pack 은 다른 원문을 전제했을 수 있다).
     */
    public const AUDITED_BASE_COMMIT = '390cdc7a379e1f2b9c8e3991b241edcc59dbdd71';

    /**
     * 기술 감사(2026-09-28)가 운영 DB 의 v1 스냅샷에서 계산하고, sirsoft-page PageSeeder 원문으로 재현한 지문.
     *
     * @var array<string, string>
     */
    public const SOURCE_FINGERPRINTS = [
        'about' => 'a422a20f948b0680fbc0e2318990fdb1f1be821ad546af6ec9fcfa04f8718338',
        'faq' => '7d1af3c3b51fe92fc17764aab13db4c56899ff1de71cb2bd51c159f5f8e7979c',
        'contact' => '789463ce263d33a2854ab522f71e632905bb000f53a91b434eae1731f942eadd',
        'refund' => '473df9e9840095d678a60f6959635aed8021720b2da9bd527ee8f52372f7e5fc',
    ];

    private const TITLE_MAX_LENGTH = 255;

    private const CONTENT_MAX_LENGTH = 16777215;

    /** 공개 문서에 남으면 안 되는 샘플·개발 표식 */
    private const FORBIDDEN_PATTERNS = [
        '/입력하세요/u',
        '/\b(DEMO|MOCK|SANDBOX|TEST)\b/',
        '/그누보드7에 오신 것을 환영합니다/u',
    ];

    public function __construct(
        private PageService $pageService,
        private ConnectionInterface $connection,
    ) {}

    /**
     * 비교에 쓰는 의미 지문. slug·ko/en 제목·ko/en 본문·content_mode·평면 SEO 3필드를
     * `길이:값` 으로 이어 SHA-256 을 계산한다(감사의 SQL 식과 동일).
     *
     * @param  array{title?: mixed, content?: mixed, content_mode?: mixed, seo_meta?: mixed}  $page
     */
    public static function fingerprint(string $slug, array $page): string
    {
        $title = is_array($page['title'] ?? null) ? $page['title'] : [];
        $content = is_array($page['content'] ?? null) ? $page['content'] : [];
        $seo = is_array($page['seo_meta'] ?? null) ? $page['seo_meta'] : [];

        $parts = [
            $slug,
            (string) ($title['ko'] ?? ''),
            (string) ($title['en'] ?? ''),
            (string) ($content['ko'] ?? ''),
            (string) ($content['en'] ?? ''),
            (string) ($page['content_mode'] ?? ''),
            (string) ($seo['title'] ?? ''),
            (string) ($seo['description'] ?? ''),
            (string) ($seo['keywords'] ?? ''),
        ];

        return hash('sha256', self::FINGERPRINT_NAMESPACE.'|'.implode('|', array_map(
            fn (string $value): string => strlen($value).':'.$value,
            $parts,
        )));
    }

    /**
     * @param  array<string, mixed>  $pages  content pack 의 pages (slug => page)
     * @return array<string, string> slug => already_applied|would_update|updated
     *
     * @throws InvalidArgumentException pages 가 계약을 어기면
     * @throws InfoPageRemediationConflict 사전 조건이 하나라도 맞지 않으면(쓰기 없음)
     */
    public function remediate(array $pages, Authenticatable $actor, bool $dryRun = false): array
    {
        $targets = $this->validatedPages($pages);

        return $this->connection->transaction(function () use ($targets, $actor, $dryRun): array {
            $rows = Page::query()
                ->whereIn('slug', array_keys($targets))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('slug');

            $plan = [];
            $conflicts = [];
            foreach ($targets as $slug => $target) {
                $page = $rows->get($slug);
                if (! $page) {
                    $conflicts[$slug] = 'missing';

                    continue;
                }

                $current = self::fingerprint($slug, $this->pageFields($page));
                if (! $page->published) {
                    $conflicts[$slug] = 'not_published';
                } elseif ($current === self::fingerprint($slug, $target)) {
                    $plan[$slug] = 'already_applied';
                } elseif ((int) $page->current_version !== self::REQUIRED_VERSION) {
                    $conflicts[$slug] = 'unexpected_version:'.$page->current_version;
                } elseif (! hash_equals(self::SOURCE_FINGERPRINTS[$slug], $current)) {
                    $conflicts[$slug] = 'unexpected_source_fingerprint';
                } else {
                    $plan[$slug] = $dryRun ? 'would_update' : 'eligible';
                }
            }

            if ($conflicts !== []) {
                throw new InfoPageRemediationConflict($conflicts);
            }

            if ($dryRun) {
                return $plan;
            }

            $results = [];
            foreach ($plan as $slug => $status) {
                if ($status === 'already_applied') {
                    $results[$slug] = $status;

                    continue;
                }

                $page = $rows->get($slug);
                $this->pageService->updatePage($page, $targets[$slug]);
                $this->assertApplied($slug, $page->id, $targets[$slug], $actor);
                $results[$slug] = 'updated';
            }

            return $results;
        });
    }

    /** 쓰기 직후 같은 transaction 안에서 결과를 재확인한다. 어긋나면 예외로 전체를 되돌린다. */
    private function assertApplied(string $slug, int $pageId, array $target, Authenticatable $actor): void
    {
        $page = Page::query()->findOrFail($pageId);
        $expectedVersion = self::REQUIRED_VERSION + 1;
        $version = PageVersion::query()
            ->where('page_id', $pageId)
            ->where('version', $expectedVersion)
            ->first();

        $expected = self::fingerprint($slug, $target);
        $failures = array_filter([
            'version' => (int) $page->current_version !== $expectedVersion,
            'published' => ! $page->published,
            'updated_by' => (int) $page->updated_by !== (int) $actor->getAuthIdentifier(),
            'fields' => self::fingerprint($slug, $this->pageFields($page)) !== $expected,
            'snapshot' => ! $version
                || (int) $version->created_by !== (int) $actor->getAuthIdentifier()
                || self::fingerprint($slug, $this->pageFields($version)) !== $expected,
        ]);

        if ($failures !== []) {
            throw new RuntimeException("Page [{$slug}] post-write verification failed: ".implode(',', array_keys($failures)));
        }
    }

    /** @return array{title: mixed, content: mixed, content_mode: mixed, seo_meta: mixed} */
    private function pageFields(Page|PageVersion $record): array
    {
        return [
            'title' => $record->title,
            'content' => $record->content,
            'content_mode' => $record->content_mode,
            'seo_meta' => $record->seo_meta,
        ];
    }

    /**
     * @param  array<string, mixed>  $pages  content pack 의 pages
     * @return array<string, array{title: array<string, string>, content: array<string, string>, content_mode: string, seo_meta: array<string, string>}>
     */
    private function validatedPages(array $pages): array
    {
        $actualSlugs = array_keys($pages);
        $expectedSlugs = array_keys(self::SOURCE_FINGERPRINTS);
        sort($actualSlugs);
        sort($expectedSlugs);
        if ($actualSlugs !== $expectedSlugs) {
            throw new InvalidArgumentException('The pages must contain exactly: '.implode(', ', array_keys(self::SOURCE_FINGERPRINTS)).'.');
        }

        $targets = [];
        foreach (array_keys(self::SOURCE_FINGERPRINTS) as $slug) {
            $page = $pages[$slug];
            if (! is_array($page)) {
                throw new InvalidArgumentException("Page payload [{$slug}] must be an object.");
            }
            if (array_diff(array_keys($page), ['title', 'content', 'content_mode', 'published', 'seo_meta'])) {
                throw new InvalidArgumentException("Page payload [{$slug}] contains an unsupported field.");
            }
            // 발행 상태는 바꾸지 않는다. 선언이 있다면 현재 상태(발행)와 같아야 한다.
            if (array_key_exists('published', $page) && $page['published'] !== true) {
                throw new InvalidArgumentException("Page payload [{$slug}.published] may only declare true; publish state is never changed.");
            }
            if (($page['content_mode'] ?? null) !== 'html') {
                throw new InvalidArgumentException("Page payload [{$slug}.content_mode] must be html.");
            }

            $targets[$slug] = [
                'title' => $this->localized($page['title'] ?? null, $slug, 'title', self::TITLE_MAX_LENGTH),
                'content' => $this->localized($page['content'] ?? null, $slug, 'content', self::CONTENT_MAX_LENGTH),
                'content_mode' => 'html',
                'seo_meta' => $this->seoMeta($page['seo_meta'] ?? null, $slug),
            ];

            if (hash_equals(self::SOURCE_FINGERPRINTS[$slug], self::fingerprint($slug, $targets[$slug]))) {
                throw new InvalidArgumentException("Page payload [{$slug}] is identical to the untouched sample.");
            }
        }

        return $targets;
    }

    /** @return array<string, string> */
    private function localized(mixed $value, string $slug, string $field, int $maxLength): array
    {
        if (! is_array($value) || array_keys($value) !== array_values(array_intersect(array_keys($value), ['ko', 'en']))
            || ! array_key_exists('ko', $value) || ! array_key_exists('en', $value)) {
            throw new InvalidArgumentException("Page payload [{$slug}.{$field}] must contain exactly ko and en.");
        }

        $result = [];
        foreach (['ko', 'en'] as $locale) {
            $text = $value[$locale];
            if (! is_string($text) || trim($text) === '' || mb_strlen($text) > $maxLength) {
                throw new InvalidArgumentException("Page payload [{$slug}.{$field}.{$locale}] is invalid.");
            }
            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                if (preg_match($pattern, $text) === 1) {
                    throw new InvalidArgumentException("Page payload [{$slug}.{$field}.{$locale}] contains sample or development wording.");
                }
            }
            $result[$locale] = $text;
        }

        return $result;
    }

    /** @return array<string, string> */
    private function seoMeta(mixed $value, string $slug): array
    {
        if (! is_array($value) || array_diff(array_keys($value), ['title', 'description', 'keywords'])) {
            throw new InvalidArgumentException("Page payload [{$slug}.seo_meta] must be a flat object of title, description and optional keywords.");
        }

        foreach (['title' => 255, 'description' => 500, 'keywords' => 500] as $field => $max) {
            $required = $field !== 'keywords';
            if (! array_key_exists($field, $value)) {
                if ($required) {
                    throw new InvalidArgumentException("Page payload [{$slug}.seo_meta.{$field}] is required.");
                }

                continue;
            }
            if (! is_string($value[$field]) || ($required && trim($value[$field]) === '') || mb_strlen($value[$field]) > $max) {
                throw new InvalidArgumentException("Page payload [{$slug}.seo_meta.{$field}] is invalid.");
            }
        }

        return $value;
    }
}
