<?php

namespace Modules\Raonslab\Product\Listeners;

use App\Contracts\Extension\HookListenerInterface;

/**
 * 제품 홈을 대체하는 동안 봇 렌더러의 title/description도 같은 소유 설정으로 맞춥니다.
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
