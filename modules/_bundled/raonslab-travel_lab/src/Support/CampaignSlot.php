<?php

namespace Modules\Raonslab\TravelLab\Support;

use Modules\Raonslab\TravelLab\Enums\CatalogSort;
use Modules\Raonslab\TravelLab\Enums\Theme;

/**
 * 캠페인 고정 슬롯 하나 (불변 Value Object).
 *
 * slug 는 native Page 주소이고, theme/sort 는 기존 카탈로그 API 가 받는 허용 필터다.
 * 고객 화면은 이 서버 값만으로 카탈로그를 조회한다.
 */
final class CampaignSlot
{
    /** 고객 캠페인 상세 경로 접두사 */
    public const PATH_PREFIX = '/travel/campaigns/';

    public function __construct(
        public readonly string $key,
        public readonly string $slug,
        public readonly Theme $theme,
        public readonly CatalogSort $sort,
        public readonly string $artVariant,
    ) {}

    /**
     * 고객 상세 경로.
     */
    public function path(): string
    {
        return self::PATH_PREFIX.$this->slug;
    }

    /**
     * 기존 카탈로그 API 로 그대로 넘길 고정 필터.
     *
     * @return array{theme: string, sort: string}
     */
    public function catalogQuery(): array
    {
        return ['theme' => $this->theme->value, 'sort' => $this->sort->value];
    }
}
