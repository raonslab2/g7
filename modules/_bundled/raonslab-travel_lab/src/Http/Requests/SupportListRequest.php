<?php

namespace Modules\Raonslab\TravelLab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 고객지원 목록 조회 요청 (공지/FAQ/문의 공용).
 */
class SupportListRequest extends FormRequest
{
    /** 페이지당 최대 건수 */
    public const MAX_PER_PAGE = 50;

    /** 기본 페이지당 건수 */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * 권한 체크는 라우트 미들웨어와 서비스의 작성자 격리에서 수행합니다.
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
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
