<?php

namespace Modules\Raonslab\TravelLab\Services;

/**
 * LAB 캠페인 합성 공개 문구 (RAON 실증 가정, 고객 자료 아님).
 *
 * 가격·할인·재고·연락처·외부 링크·이미지를 담지 않는다. content_mode=text 로 저장된다.
 * 실제 상품과 금액은 고객 화면이 기존 카탈로그 API 로 서버에서 받아 그린다.
 */
final class TravelCampaignSeedContent
{
    /**
     * @return array{title: array{ko: string, en: string}, content: array{ko: string, en: string}}|null
     */
    public static function for(string $slotKey): ?array
    {
        return match ($slotKey) {
            'autumn-escape' => [
                'title' => ['ko' => '가을, 숲으로 떠나는 느린 여행', 'en' => 'A slow autumn escape into the woods'],
                'content' => [
                    'ko' => "선선한 바람이 부는 계절, 숲길과 계곡을 천천히 걷는 일정을 모았어요.\n\n일정과 출발일은 상품마다 다르니 상세 화면에서 확인해 주세요.\n이 화면의 상담 요청은 테스트로만 처리돼요.",
                    'en' => "When the air turns crisp, wander forest trails and valleys at an easy pace.\n\nItineraries and departures differ by trip, so please check each detail page.\nRequests made here are test-only.",
                ],
            ],
            'weekend-reset' => [
                'title' => ['ko' => '주말 리셋, 쉼이 있는 짧은 여행', 'en' => 'Weekend reset: short trips to unwind'],
                'content' => [
                    'ko' => "바쁜 한 주 뒤에 몸과 마음을 쉬게 하는 웰니스 여행을 모았어요.\n\n온천, 산책, 조용한 숙소처럼 쉼에 집중한 일정을 살펴보세요.\n이 화면의 상담 요청은 테스트로만 처리돼요.",
                    'en' => "Wellness trips to rest body and mind after a busy week.\n\nBrowse itineraries built around hot springs, gentle walks and quiet stays.\nRequests made here are test-only.",
                ],
            ],
            default => null,
        };
    }
}
