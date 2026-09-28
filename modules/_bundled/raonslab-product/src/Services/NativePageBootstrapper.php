<?php

namespace Modules\Raonslab\Product\Services;

use InvalidArgumentException;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * Creates the approved RAON native Page set without ever updating an existing slug.
 */
class NativePageBootstrapper
{
    public const SLUGS = [
        'service',
        'cases',
        'technology',
        'privacy',
        'terms',
        'ai-workspace-policy',
        'open-source',
    ];

    public function __construct(private PageService $pageService) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public function bootstrap(array $payload, bool $dryRun = false): array
    {
        $pages = $this->validatedPages($payload);
        $results = [];

        foreach ($pages as $slug => $page) {
            if ($this->pageService->slugExists($slug)) {
                $results[$slug] = 'preserved_existing';

                continue;
            }

            if ($dryRun) {
                $results[$slug] = 'would_create';

                continue;
            }

            $this->pageService->createPage(['slug' => $slug, ...$page]);
            $results[$slug] = 'created';
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array<string, mixed>>
     */
    private function validatedPages(array $payload): array
    {
        $actualSlugs = array_keys($payload);
        sort($actualSlugs);
        $expectedSlugs = self::SLUGS;
        sort($expectedSlugs);

        if ($actualSlugs !== $expectedSlugs) {
            throw new InvalidArgumentException('The payload must contain exactly the seven approved native Page slugs.');
        }

        $pages = [];
        foreach (self::SLUGS as $slug) {
            $page = $payload[$slug];
            if (! is_array($page)) {
                throw new InvalidArgumentException("Page payload [{$slug}] must be an object.");
            }

            $title = $this->localizedStrings($page['title'] ?? null, $slug, 'title', allowEmpty: false);
            $content = $this->localizedStrings($page['content'] ?? null, $slug, 'content', allowEmpty: false);
            $mode = $page['content_mode'] ?? 'html';
            if (! in_array($mode, ['html', 'text'], true)) {
                throw new InvalidArgumentException("Page payload [{$slug}.content_mode] is invalid.");
            }
            if (! isset($page['published']) || ! is_bool($page['published'])) {
                throw new InvalidArgumentException("Page payload [{$slug}.published] must be boolean.");
            }

            $seoMeta = $page['seo_meta'] ?? null;
            if ($seoMeta !== null) {
                if (! is_array($seoMeta)) {
                    throw new InvalidArgumentException("Page payload [{$slug}.seo_meta] must be an object or null.");
                }
                foreach (['title' => 255, 'description' => 500, 'keywords' => 500] as $field => $max) {
                    if (array_key_exists($field, $seoMeta)
                        && (! is_string($seoMeta[$field]) || mb_strlen($seoMeta[$field]) > $max)) {
                        throw new InvalidArgumentException("Page payload [{$slug}.seo_meta.{$field}] is invalid.");
                    }
                }
                if (array_diff(array_keys($seoMeta), ['title', 'description', 'keywords'])) {
                    throw new InvalidArgumentException("Page payload [{$slug}.seo_meta] contains an unsupported field.");
                }
            }

            $pages[$slug] = [
                'title' => $title,
                'content' => $content,
                'content_mode' => $mode,
                'published' => $page['published'],
                'seo_meta' => $seoMeta,
            ];
        }

        return $pages;
    }

    /** @return array{ko: string, en: string} */
    private function localizedStrings(mixed $value, string $slug, string $field, bool $allowEmpty): array
    {
        if (! is_array($value) || array_diff(array_keys($value), ['ko', 'en'])) {
            throw new InvalidArgumentException("Page payload [{$slug}.{$field}] must contain only ko and en strings.");
        }

        foreach (['ko', 'en'] as $locale) {
            if (! array_key_exists($locale, $value) || ! is_string($value[$locale])
                || (! $allowEmpty && trim($value[$locale]) === '')) {
                throw new InvalidArgumentException("Page payload [{$slug}.{$field}.{$locale}] is invalid.");
            }
        }

        return ['ko' => $value['ko'], 'en' => $value['en']];
    }
}
