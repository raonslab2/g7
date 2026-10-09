<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 캠페인 목록 조회 요청.
 *
 * 목록은 레지스트리 두 슬롯만 열거한다. 호출자가 slug·검색어·접두사·ID 배열로 대상을 넓히는
 * 입력을 받지 않으며, 그런 입력이 오면 무시하지 않고 422 로 거절해 계약을 드러낸다.
 */
class CampaignListRequest extends FormRequest
{
    /** 대상 확장을 시도하는 입력 키 */
    public const FORBIDDEN_KEYS = ['slug', 'slugs', 'search', 'q', 'prefix', 'ids', 'filters', 'published', 'preview'];

    /**
     * 공개 API 이며 권한 판정이 없습니다 (발행 판정은 서비스 단일 지점).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_fill_keys(self::FORBIDDEN_KEYS, ['prohibited']);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['*.prohibited' => __('raonslab-travel_lab::campaigns.errors.selector_prohibited')];
    }
}
