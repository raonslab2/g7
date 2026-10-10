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

    /** 허용된 테스트 상태 전이의 단일 정의입니다. */
    public function allowedNext(): array
    {
        return match ($this) {
            self::TEST_INQUIRY => [self::UNDER_REVIEW, self::DECLINED, self::CANCELLED],
            self::UNDER_REVIEW => [self::TEST_ACCEPTED, self::DECLINED, self::CANCELLED],
            self::TEST_ACCEPTED => [self::CANCELLED],
            self::DECLINED, self::CANCELLED => [],
        };
    }

    public function canCancel(): bool
    {
        return in_array(self::CANCELLED, $this->allowedNext(), true);
    }

    public function label(): string
    {
        return __('raonslab-travel_lab::enums.inquiry_status.'.$this->value);
    }
}
