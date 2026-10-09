<?php

namespace Modules\Raonslab\TravelLab\Database\Seeders;

use App\Concerns\Seeder\HasTranslatableSeeder;
use App\Contracts\Seeder\TranslatableSeederInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** 고정 SKU와 날짜로 재실행 가능한 합성 여행 카탈로그입니다. */
class SyntheticCatalogSeeder extends Seeder implements TranslatableSeederInterface
{
    use HasTranslatableSeeder;

    public function getExtensionIdentifier(): string
    {
        return 'raonslab-travel_lab';
    }

    public function getTranslatableEntity(): string
    {
        return 'travel_catalog';
    }

    public function getMatchKey(): string
    {
        return 'sku';
    }

    public function getDefaults(): array
    {
        $definitions = [
            ['jeju', 'nature', 3, 189000, '제주 바다와 오름', 'Jeju sea and volcanic hills'],
            ['jeju', 'wellness', 4, 329000, '제주 숲 속 쉼', 'Rest in Jeju forests'],
            ['gangwon', 'nature', 2, 159000, '강원 산과 호수', 'Gangwon mountains and lakes'],
            ['gangwon', 'wellness', 3, 249000, '강원 조용한 산책', 'Gentle walks in Gangwon'],
            ['busan', 'culture', 2, 179000, '부산 골목과 바다', 'Busan lanes and sea'],
            ['busan', 'city', 3, 219000, '부산 도시 탐험', 'Explore Busan city'],
            ['seoul', 'culture', 2, 139000, '서울 궁궐 산책', 'Seoul palace walks'],
            ['seoul', 'city', 1, 79000, '서울 하루 발견', 'Discover Seoul in a day'],
        ];
        $rows = [];
        foreach ($definitions as $index => [$region, $theme, $days, $price, $ko, $en]) {
            $dates = [];
            $anchor = Carbon::parse(config('raonslab-travel_lab.catalog.sample_departure_anchor', '2026-11-01'))->addDays($index);
            for ($offset = 0; $offset < 3; $offset++) {
                $start = $anchor->copy()->addWeeks($offset);
                $dates[] = ['code' => 'TRAVEL-LAB-'.($offset + 1), 'departure_date' => $start->toDateString(), 'return_date' => $start->copy()->addDays($days - 1)->toDateString(), 'capacity' => 24, 'adjustment' => $offset * 20000];
            }
            $itinerary = [];
            for ($day = 1; $day <= $days; $day++) {
                $itinerary[] = ['day' => $day, 'title' => ['ko' => $ko.' '.$day.'일차', 'en' => $en.' day '.$day]];
            }
            $rows[] = [
                'sku' => 'TRAVEL-LAB-SYNTHETIC-'.strtoupper($region.'-'.$theme),
                'region' => $region, 'theme' => $theme, 'duration_days' => $days, 'price' => $price,
                'name' => ['ko' => $ko.' (테스트)', 'en' => $en.' (test)'],
                'summary' => ['ko' => '실제 예약이 아닌 합성 여행 탐색 데이터입니다.', 'en' => 'Synthetic travel discovery data; this is not a real booking.'],
                'itinerary' => $itinerary, 'departures' => $dates,
            ];
        }

        return $rows;
    }

    public function run(): void
    {
        app(SyntheticCatalogHelper::class)->sync($this->resolveTranslatedDefaults());
        $this->command?->info('트래블 랩 합성 카탈로그 생성 완료 (기존 데이터 보존)');
    }
}
