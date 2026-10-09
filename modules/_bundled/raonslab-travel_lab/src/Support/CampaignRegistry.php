<?php

namespace Modules\Raonslab\TravelLab\Support;

use LogicException;
use Modules\Raonslab\TravelLab\Enums\CatalogSort;
use Modules\Raonslab\TravelLab\Enums\Theme;

/**
 * 여행 캠페인 고정 슬롯 레지스트리.
 *
 * `config/campaigns.php` 파일을 config() 저장소가 아니라 직접 읽는다. 런타임 config 덮어쓰기나
 * 요청 값으로 슬롯을 늘리거나 임의 Page slug 를 열 수 없게 하기 위해서다. 슬롯은 정확히 두 개이며
 * slug 접두사·theme/sort enum·아트 변형이 어긋나면 부팅 시점이 아니라 첫 사용 시 즉시 실패한다.
 */
final class CampaignRegistry
{
    /** 모든 캠페인 Page slug 가 공유하는 접두사 */
    public const SLUG_PREFIX = 'travel-lab-campaign-';

    /** 슬롯 slug 형식 (라우트 제약과 FormRequest 가 공유) */
    public const SLUG_PATTERN = 'travel-lab-campaign-[a-z0-9]+(?:-[a-z0-9]+)*';

    /** 레지스트리가 허용하는 슬롯 수 */
    public const SLOT_COUNT = 2;

    /** 템플릿 ScenicArt 가 그리는 장면 */
    private const ART_VARIANTS = ['coast', 'mountain', 'city', 'island', 'forest', 'desert', 'snow', 'lake'];

    /** @var array<string, CampaignSlot>|null slug => slot (레지스트리 순서) */
    private ?array $slots = null;

    /**
     * 레지스트리 순서대로 전체 슬롯.
     *
     * @return list<CampaignSlot>
     */
    public function all(): array
    {
        return array_values($this->load());
    }

    /**
     * 레지스트리에 등록된 slug 의 슬롯 (없으면 null).
     */
    public function find(string $slug): ?CampaignSlot
    {
        return $this->load()[$slug] ?? null;
    }

    /**
     * 레지스트리에 등록된 slug 인지.
     */
    public function has(string $slug): bool
    {
        return isset($this->load()[$slug]);
    }

    /**
     * 등록된 slug 목록 (레지스트리 순서).
     *
     * @return list<string>
     */
    public function slugs(): array
    {
        return array_keys($this->load());
    }

    /**
     * @return array<string, CampaignSlot>
     */
    private function load(): array
    {
        if ($this->slots !== null) {
            return $this->slots;
        }

        $definition = require dirname(__DIR__, 2).'/config/campaigns.php';
        $raw = $definition['slots'] ?? null;
        if (! is_array($raw) || count($raw) !== self::SLOT_COUNT) {
            throw new LogicException('Travel campaign registry must declare exactly '.self::SLOT_COUNT.' slots.');
        }

        $slots = [];
        foreach ($raw as $key => $slot) {
            $slug = (string) ($slot['slug'] ?? '');
            $art = (string) ($slot['art_variant'] ?? '');
            if (! is_string($key) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1
                || preg_match('/^'.self::SLUG_PATTERN.'$/', $slug) !== 1
                || isset($slots[$slug])
                || ! in_array($art, self::ART_VARIANTS, true)) {
                throw new LogicException('Travel campaign registry slot is malformed: '.$key);
            }

            $slots[$slug] = new CampaignSlot(
                key: $key,
                slug: $slug,
                theme: Theme::from((string) ($slot['theme'] ?? '')),
                sort: CatalogSort::from((string) ($slot['sort'] ?? '')),
                artVariant: $art,
            );
        }

        return $this->slots = $slots;
    }
}
