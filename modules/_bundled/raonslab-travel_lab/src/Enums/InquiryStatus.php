<?php

namespace Modules\Raonslab\TravelLab\Enums;

/** 모든 상태는 테스트 문의이며 실제 예약 확정을 의미하지 않습니다. */
enum InquiryStatus: string
{
    case TEST_INQUIRY = 'TEST_INQUIRY';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case TEST_ACCEPTED = 'TEST_ACCEPTED';
    case DECLINED = 'DECLINED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return __('raonslab-travel_lab::enums.inquiry_status.'.$this->value);
    }
}
