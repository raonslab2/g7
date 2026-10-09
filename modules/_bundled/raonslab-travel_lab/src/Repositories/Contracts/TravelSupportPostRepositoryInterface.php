<?php

namespace Modules\Raonslab\TravelLab\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;

/**
 * 여행 고객지원 게시판(board_posts) 조회 계약.
 *
 * 게시판 모듈의 PostRepository 는 공지 우선 정렬·답글 병합 등 게시판 화면 규칙을
 * 적용하므로, 작성자 격리가 필요한 문의 목록은 이 계약이 board_id·user_id 스코프를
 * 직접 where 절로 고정한다 (스코프 SSoT).
 */
interface TravelSupportPostRepositoryInterface
{
    /**
     * 공개 채널(공지/FAQ)의 게시 상태·비밀글 아님·최상위 글을 최신순으로 페이지네이션합니다.
     */
    public function paginatePublic(int $boardId, int $perPage, int $page): LengthAwarePaginator;

    /**
     * 문의 목록을 페이지네이션합니다.
     *
     * @param  int|null  $authorId  null 이면 전체(관리자), 값이 있으면 그 작성자의 글만
     */
    public function paginateQuestions(int $boardId, ?int $authorId, int $perPage, int $page): LengthAwarePaginator;

    /**
     * 게시판 안의 게시 상태 최상위 글 한 건을 찾습니다 (다른 게시판 글은 null).
     */
    public function findInBoard(int $boardId, int $postId): ?Post;

    /**
     * 프로비저너가 심은 콘텐츠를 provenance 키로 찾습니다 (재실행 멱등성).
     */
    public function findByProvenanceKey(int $boardId, string $provenanceKey): ?Post;

    /**
     * 게시판 권한 키별로 부여된 역할 identifier 목록을 반환합니다.
     *
     * 권한 행이 없으면 null 이다 — 게시판 모듈은 권한 행 부재와 역할 0개를 모두
     * "전체 허용" 으로 해석하므로, 호출자는 null 과 빈 배열을 모두 개방 상태로 다뤄야 한다.
     * 문의 수정 쓰기는 이 계약에 두지 않는다 — 게시판 PostService::updatePost() 만 사용한다
     * (훅·활동 로그·캐시 무효화를 우회하는 직접 save 경로 금지).
     *
     * @param  array<int, string>  $permissionKeys  `admin.posts.read` 형태의 게시판 권한 키
     * @return array<string, array<int, string>|null>
     */
    public function boardPermissionRoles(string $boardSlug, array $permissionKeys): array;

    /**
     * 문의에 달린 게시 상태 답변(댓글)을 작성 순으로 최대 $limit 건 반환합니다.
     *
     * @return Collection<int, Comment>
     */
    public function answersFor(int $boardId, int $postId, int $limit): Collection;
}
