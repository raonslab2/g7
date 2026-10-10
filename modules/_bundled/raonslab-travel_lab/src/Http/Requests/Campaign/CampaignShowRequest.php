<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Raonslab\TravelLab\Support\CampaignRegistry;

/**
 * 캠페인 상세 조회 요청.
 *
 * 라우트 제약과 같은 slug 형식을 경로 파라미터에 재검증한다. 형식이 맞아도 레지스트리 등록 여부는
 * 서비스가 별도로 판정한다(접두사가 같다고 열리지 않는다). 미리보기 입력은 받지 않는다.
 */
class CampaignShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => (string) $this->route('slug')]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'regex:/^'.CampaignRegistry::SLUG_PATTERN.'$/'],
            'preview' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('raonslab-travel_lab::campaigns.errors.slug_invalid'),
            'preview.prohibited' => __('raonslab-travel_lab::campaigns.errors.selector_prohibited'),
        ];
    }

    public function slug(): string
    {
        return (string) $this->validated('slug');
    }
}
