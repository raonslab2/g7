<?php

namespace Modules\Raonslab\TravelLab\Services;

use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;

/**
 * LAB 고객지원 게시판의 합성 공지/FAQ 원문.
 *
 * 모든 문구는 이 모듈을 위해 새로 작성한 합성 콘텐츠이며, 실제 상품·가격·예약·결제를
 * 약속하지 않는다. 키는 provenance 식별자의 일부이므로 바꾸면 재실행 시 새 글로 취급된다.
 */
final class TravelSupportSeedContent
{
    /**
     * 채널의 합성 콘텐츠를 반환합니다. 문의 채널은 합성 콘텐츠를 심지 않는다.
     *
     * @return array<string, array{title: string, content: string, is_notice?: bool}>
     */
    public static function for(TravelSupportChannel $channel): array
    {
        return match ($channel) {
            TravelSupportChannel::Notices => [
                'lab-scope' => [
                    'title' => '[LAB] 여행 연구소 테스트 운영 안내',
                    'content' => "여행 연구소는 여행 상품 화면과 문의 흐름을 검증하기 위한 테스트 공간입니다.\n표시되는 일정·인원·금액은 모두 예시이며 실제 예약이나 결제로 이어지지 않습니다.",
                    'is_notice' => true,
                ],
                'no-payment' => [
                    'title' => '[LAB] 결제·환불 기능은 동작하지 않습니다',
                    'content' => "테스트 기간에는 결제, 환불, 예약 확정 기능을 제공하지 않습니다.\n문의 상태가 '테스트 접수'로 바뀌어도 실제 계약이 성립하지 않습니다.",
                ],
                'data-reset' => [
                    'title' => '[LAB] 테스트 데이터 정리 일정 안내',
                    'content' => "테스트 중 등록된 문의와 상담 기록은 검증이 끝나면 정리될 수 있습니다.\n개인정보나 실제 여행 계획은 입력하지 말아 주세요.",
                ],
            ],
            TravelSupportChannel::Faqs => [
                'how-to-book' => [
                    'title' => '출발일과 인원은 어떻게 고르나요?',
                    'content' => "상품 상세에서 출발일(상품 옵션)을 고르고 인원 수를 수량으로 입력합니다.\nLAB 환경에서는 선택 결과가 문의 접수로만 기록됩니다.",
                ],
                'price-source' => [
                    'title' => '표시 금액은 어디에서 오나요?',
                    'content' => "금액은 이커머스 상품과 옵션에 등록된 값을 그대로 보여 줍니다.\n여행 연구소는 별도의 가격을 계산하거나 저장하지 않습니다.",
                ],
                'question-privacy' => [
                    'title' => '1:1 문의는 누가 볼 수 있나요?',
                    'content' => "1:1 문의는 작성한 회원 본인과 권한을 가진 관리자만 볼 수 있습니다.\n다른 회원에게는 목록과 상세 모두 표시되지 않습니다.",
                ],
                'notifications' => [
                    'title' => '문의하면 메일이나 문자로 연락이 오나요?',
                    'content' => "LAB 환경에서는 메일·문자·알림을 보내지 않습니다.\n답변은 로그인 후 내 문의 상세에서 확인합니다.",
                ],
            ],
            TravelSupportChannel::Questions => [],
        };
    }
}
