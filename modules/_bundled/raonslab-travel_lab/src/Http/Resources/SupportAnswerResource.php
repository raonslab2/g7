<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

/**
 * 문의 답변(게시판 댓글) 리소스.
 *
 * 답변자의 계정 정보(user_id·이메일·이름)는 내보내지 않는다. 답변이 문의 작성자 본인의
 * 추가 댓글인지(`is_author`) 운영자 답변인지만 구분한다.
 */
class SupportAnswerResource extends BaseApiResource
{
    /** 문의 작성자 ID (컨트롤러가 지정) */
    public ?int $questionAuthorId = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'is_author' => $this->questionAuthorId !== null && (int) $this->user_id === $this->questionAuthorId,
            'content' => (string) $this->content,
            'created_at' => $this->created_at ? $this->formatDateTimeStringForUser($this->created_at) : null,
        ];
    }
}
