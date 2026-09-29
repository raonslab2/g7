<?php

namespace Modules\Raonslab\Product\Listeners;

use App\Contracts\Extension\HookListenerInterface;

/**
 * 제품 홈을 대체하는 동안 봇 렌더러의 title/description도 같은 소유 설정으로 맞춥니다.
 *
 * 봇·정적 렌더는 모듈 JS(접수 상태 확인·상담 양식)를 실행하지 않으므로, 홈 렌더 컨텍스트에
 * `_local.raonStaticClosedFallback` 을 표시해 레이아웃이 닫힘 행동 한 벌과 정적 닫힘 안내만 싣게 한다.
 * 사람 화면(SPA)에는 이 값이 없어 기존 상태 전환이 그대로 동작한다.
 */
class ApplyHomeSeoMeta implements HookListenerInterface
{
    /** @var array<string, mixed>|null */
    private static ?array $pageMeta = null;

    public static function getSubscribedHooks(): array
    {
        return [
            'core.seo.filter_meta' => [
                'method' => 'applyHomeMeta',
                'priority' => 10,
                'type' => 'filter',
            ],
            'core.seo.filter_context' => [
                'method' => 'markHomeStaticContext',
                'priority' => 10,
                'type' => 'filter',
            ],
        ];
    }

    public function handle(...$args): void
    {
        // 필터는 명시 메서드가 처리합니다.
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $hookMeta
     * @return array<string, mixed>
     */
    public function applyHomeMeta(array $meta, array $hookMeta): array
    {
        if (($hookMeta['layoutName'] ?? null) !== 'home') {
            return $meta;
        }

        $locale = ($hookMeta['locale'] ?? 'ko') === 'en' ? 'en' : 'ko';
        $localized = $this->pageMeta()['home'][$locale] ?? null;

        if (! is_array($localized)) {
            return $meta;
        }

        $title = $localized['title'] ?? '';
        $description = $localized['description'] ?? '';
        if (! is_string($title) || $title === '' || ! is_string($description) || $description === '') {
            return $meta;
        }

        $meta['title'] = $title;
        $meta['description'] = $description;
        $meta['og'] = is_array($meta['og'] ?? null) ? $meta['og'] : [];
        $meta['og']['title'] = $title;
        $meta['og']['description'] = $description;
        $meta['twitter'] = is_array($meta['twitter'] ?? null) ? $meta['twitter'] : [];
        $meta['twitter']['title'] = $title;
        $meta['twitter']['description'] = $description;

        return $meta;
    }

    /**
     * 홈 봇 렌더에서만 정적 닫힘 표시를 켭니다. 다른 레이아웃의 컨텍스트는 그대로 돌려줍니다.
     *
     * @param  array<string, mixed>  $context  SEO 렌더 컨텍스트
     * @param  array<string, mixed>  $hookMeta  레이아웃명·로케일 등 훅 메타
     * @return array<string, mixed>
     */
    public function markHomeStaticContext(array $context, array $hookMeta = []): array
    {
        if (($hookMeta['layoutName'] ?? null) !== 'home') {
            return $context;
        }

        $local = is_array($context['_local'] ?? null) ? $context['_local'] : [];
        $local['raonStaticClosedFallback'] = true;
        $context['_local'] = $local;

        return $context;
    }

    /** @return array<string, mixed> */
    private function pageMeta(): array
    {
        if (self::$pageMeta !== null) {
            return self::$pageMeta;
        }

        $path = dirname(__DIR__, 2).'/resources/seo-config.json';
        if (! is_file($path)) {
            return self::$pageMeta = [];
        }

        $contents = file_get_contents($path);
        $config = $contents === false ? null : json_decode($contents, true);

        return self::$pageMeta = is_array($config['page_meta'] ?? null)
            ? $config['page_meta']
            : [];
    }
}
