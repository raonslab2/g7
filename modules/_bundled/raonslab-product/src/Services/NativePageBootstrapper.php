<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * Creates the approved RAON native Page set without ever updating an existing slug.
 */
class NativePageBootstrapper
{
    private const CONTENT_MAX_LENGTH = 16777215;

    private const TITLE_MAX_LENGTH = 255;

    public const SLUGS = [
        'service',
        'cases',
        'technology',
        'privacy',
        'terms',
        'ai-workspace-policy',
        'open-source',
    ];

    /** @var array<int, string> */
    private array $supportedLocales;

    /**
     * @param  array<int, string>|null  $supportedLocales  Test seam; production uses the active G7 locales.
     */
    public function __construct(
        private PageService $pageService,
        private ConnectionInterface $connection,
        ?array $supportedLocales = null,
    ) {
        $this->supportedLocales = array_values(array_filter(
            $supportedLocales ?? config('app.supported_locales', ['ko', 'en']),
            fn (mixed $locale): bool => is_string($locale)
                && preg_match('/^[a-z]{2}(?:-[A-Za-z]{2})?$/', $locale) === 1,
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public function bootstrap(array $payload, bool $dryRun = false): array
    {
        $pages = $this->validatedPages($payload);

        return $this->connection->transaction(function () use ($pages, $dryRun): array {
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
        });
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

            if (array_diff(array_keys($page), ['title', 'content', 'content_mode', 'published', 'seo_meta'])) {
                throw new InvalidArgumentException("Page payload [{$slug}] contains an unsupported field.");
            }

            $title = $this->localizedStrings(
                $page['title'] ?? null,
                $slug,
                'title',
                self::TITLE_MAX_LENGTH,
            );
            $content = $this->localizedStrings(
                $page['content'] ?? null,
                $slug,
                'content',
                self::CONTENT_MAX_LENGTH,
            );
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

    /** @return array<string, string> */
    private function localizedStrings(mixed $value, string $slug, string $field, int $maxLength): array
    {
        if (! is_array($value) || array_diff(array_keys($value), $this->supportedLocales)) {
            throw new InvalidArgumentException("Page payload [{$slug}.{$field}] contains an unsupported locale.");
        }

        foreach (['ko', 'en'] as $requiredLocale) {
            if (! array_key_exists($requiredLocale, $value)) {
                throw new InvalidArgumentException("Page payload [{$slug}.{$field}.{$requiredLocale}] is required.");
            }
        }

        foreach ($value as $locale => $localizedValue) {
            if (! is_string($localizedValue) || trim($localizedValue) === '' || mb_strlen($localizedValue) > $maxLength) {
                throw new InvalidArgumentException("Page payload [{$slug}.{$field}.{$locale}] is invalid.");
            }
        }

        return $value;
    }
}
