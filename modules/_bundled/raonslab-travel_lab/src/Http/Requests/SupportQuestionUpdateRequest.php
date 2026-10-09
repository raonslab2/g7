<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 1:1 문의 수정 요청 (제목·본문만).
 */
class SupportQuestionUpdateRequest extends FormRequest
{
    /**
     * 작성자/관리자 판정은 서비스가 수행합니다 (거부 시 404).
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
        return [
            'title' => ['required_without:content', 'string', 'min:2', 'max:'.SupportQuestionStoreRequest::TITLE_MAX],
            'content' => ['required_without:title', 'string', 'min:2', 'max:'.SupportQuestionStoreRequest::CONTENT_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('raonslab-travel_lab::support.attributes.title'),
            'content' => __('raonslab-travel_lab::support.attributes.content'),
        ];
    }
}
