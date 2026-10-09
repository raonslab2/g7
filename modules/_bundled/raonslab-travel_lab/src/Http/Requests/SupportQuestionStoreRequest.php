<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 1:1 문의 등록 요청.
 *
 * 첨부·비밀글·작성자 필드는 받지 않는다 — 비밀글은 서비스가 강제하고 작성자는 인증 사용자다.
 */
class SupportQuestionStoreRequest extends FormRequest
{
    public const TITLE_MAX = 200;

    public const CONTENT_MAX = 5000;

    /**
     * 인증은 라우트의 auth:sanctum 미들웨어가 담당합니다.
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
            'title' => ['required', 'string', 'min:2', 'max:'.self::TITLE_MAX],
            'content' => ['required', 'string', 'min:2', 'max:'.self::CONTENT_MAX],
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
