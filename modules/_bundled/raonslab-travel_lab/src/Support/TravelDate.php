<?php

namespace Modules\Raonslab\TravelLab\Support;

use Carbon\CarbonImmutable;

/** 여행의 날짜 판정은 앱/서버 시간대와 분리된 사업 시간대를 따른다. */
final class TravelDate
{
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('raonslab-travel_lab.catalog.business_timezone', 'Asia/Seoul'))->startOfDay();
    }
}
