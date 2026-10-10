<?php

namespace Modules\Raonslab\TravelLab\Http\Controllers\Api;

use App\Http\Controllers\Api\Base\PublicBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Http\Requests\SupportListRequest;
use Modules\Raonslab\TravelLab\Http\Requests\SupportQuestionStoreRequest;
use Modules\Raonslab\TravelLab\Http\Requests\SupportQuestionUpdateRequest;
use Modules\Raonslab\TravelLab\Http\Resources\SupportAnswerResource;
use Modules\Raonslab\TravelLab\Http\Resources\SupportPostCollection;
use Modules\Raonslab\TravelLab\Http\Resources\SupportPostResource;
use Modules\Raonslab\TravelLab\Services\TravelSupportService;

/**
 * 여행 고객지원 API.
 *
 * 공지/FAQ 는 공개(optional.sanctum), 1:1 문의는 auth:sanctum 라우트 미들웨어가 인증을 강제한다.
 * 문의의 작성자·관리자 격리는 TravelSupportService 가 단일 지점에서 판정한다.
 */
class SupportController extends PublicBaseController
{
    public function __construct(
        private TravelSupportService $supportService,
    ) {
        parent::__construct();
    }

    /**
     * 공지사항 목록.
     */
    public function notices(SupportListRequest $request): JsonResponse
    {
        return $this->publicList(TravelSupportChannel::Notices, $request);
    }

    /**
     * 공지사항 상세.
     */
    public function notice(int $id): JsonResponse
    {
        return $this->publicShow(TravelSupportChannel::Notices, $id);
    }

    /**
     * FAQ 목록.
     */
    public function faqs(SupportListRequest $request): JsonResponse
    {
        return $this->publicList(TravelSupportChannel::Faqs, $request);
    }

    /**
     * FAQ 상세.
     */
    public function faq(int $id): JsonResponse
    {
        return $this->publicShow(TravelSupportChannel::Faqs, $id);
    }

    /**
     * 내 문의 목록 (support.read 관리자는 전체).
     */
    public function questions(SupportListRequest $request): JsonResponse
    {
        try {
            $page = $this->supportService->listQuestions($request->user(), $request->perPage(), $request->page());
        } catch (TravelSupportException $e) {
            return $this->domainError($e);
        }

        return $this->success(
            'raonslab-travel_lab::support.messages.questions_loaded',
            SupportPostCollection::forChannel($page, TravelSupportChannel::Questions)->toArray($request),
        );
    }

    /**
     * 문의 등록.
     */
    public function storeQuestion(SupportQuestionStoreRequest $request): JsonResponse
    {
        try {
            $post = $this->supportService->createQuestion($request->user(), $request->validated(), $request->ip());
        } catch (TravelSupportException $e) {
            return $this->domainError($e);
        }

        return $this->success(
            'raonslab-travel_lab::support.messages.question_created',
            SupportPostResource::forChannel($post, TravelSupportChannel::Questions)->toArray($request),
            201,
        );
    }

    /**
     * 문의 상세 (작성자 또는 support.read 관리자).
     */
    public function showQuestion(int $id): JsonResponse
    {
        try {
            $result = $this->supportService->showQuestion(request()->user(), $id);
        } catch (TravelSupportException $e) {
            return $this->domainError($e);
        }

        $authorId = $result['post']->user_id !== null ? (int) $result['post']->user_id : null;
        $answers = $result['answers']->map(function ($comment) use ($authorId): array {
            $resource = new SupportAnswerResource($comment);
            $resource->questionAuthorId = $authorId;

            return $resource->toArray(request());
        })->values()->all();

        return $this->success('raonslab-travel_lab::support.messages.question_loaded', [
            ...SupportPostResource::forChannel($result['post'], TravelSupportChannel::Questions)->toArray(request()),
            'answers_count' => count($answers),
            'answers' => $answers,
        ]);
    }

    /**
     * 문의 수정 (작성자 또는 support.update 관리자).
     */
    public function updateQuestion(SupportQuestionUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $post = $this->supportService->updateQuestion($request->user(), $id, $request->validated());
        } catch (TravelSupportException $e) {
            return $this->domainError($e);
        }

        return $this->success(
            'raonslab-travel_lab::support.messages.question_updated',
            SupportPostResource::forChannel($post, TravelSupportChannel::Questions)->toArray($request),
        );
    }

    private function publicList(TravelSupportChannel $channel, SupportListRequest $request): JsonResponse
    {
        try {
            $page = $this->supportService->listPublic($channel, $request->perPage(), $request->page());
        } catch (TravelSupportException $e) {
            return $this->domainError($e);
        }

        return $this->success(
            'raonslab-travel_lab::support.messages.'.$channel->value.'_loaded',
            SupportPostCollection::forChannel($page, $channel)->toArray($request),
        );
    }

    private function publicShow(TravelSupportChannel $channel, int $id): JsonResponse
    {
        try {
            $post = $this->supportService->showPublic($channel, $id);
        } catch (TravelSupportException $e) {
            return $this->domainError($e);
        }

        return $this->success(
            'raonslab-travel_lab::support.messages.post_loaded',
            SupportPostResource::forChannel($post, $channel)->toArray(request()),
        );
    }

    /**
     * 도메인 예외를 키 기반 응답으로 변환합니다 (원문을 키 자리에 넘기지 않는다).
     */
    private function domainError(TravelSupportException $e): JsonResponse
    {
        return $this->error($e->getMessageKey(), $e->getStatus(), null, $e->getMessageParams());
    }
}
