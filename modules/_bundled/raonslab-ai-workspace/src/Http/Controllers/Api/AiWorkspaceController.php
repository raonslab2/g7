<?php

namespace Modules\Raonslab\Ai\Workspace\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Raonslab\Ai\Workspace\Exceptions\AiGcsException;
use Modules\Raonslab\Ai\Workspace\Http\Requests\FollowUpAiRequest;
use Modules\Raonslab\Ai\Workspace\Http\Requests\SubmitAiRequest;
use Modules\Raonslab\Ai\Workspace\Services\AiWorkspaceService;

class AiWorkspaceController extends Controller
{
    public function __construct(private readonly AiWorkspaceService $workspace) {}

    public function capabilities(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->workspace->capabilities($request->user()));
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->workspace->list($request->user()));
    }

    public function store(SubmitAiRequest $request): JsonResponse
    {
        return $this->respond(
            fn () => $this->workspace->submit($request->user(), $request->validated()),
            202
        );
    }

    public function show(Request $request, string $requestId): JsonResponse
    {
        return $this->respond(fn () => $this->workspace->detail($request->user(), $requestId));
    }

    public function followUp(FollowUpAiRequest $request, string $requestId): JsonResponse
    {
        return $this->respond(
            fn () => $this->workspace->followUp($request->user(), $requestId, $request->validated()),
            202
        );
    }

    public function resume(FollowUpAiRequest $request, string $requestId): JsonResponse
    {
        return $this->respond(
            fn () => $this->workspace->resume($request->user(), $requestId, $request->validated()),
            202
        );
    }

    private function respond(callable $callback, int $status = 200): JsonResponse
    {
        try {
            return response()->json(['data' => $callback()], $status);
        } catch (ModelNotFoundException) {
            return response()->json([
                'code' => 'AI_REQUEST_NOT_FOUND',
                'message' => '요청을 찾을 수 없거나 접근 권한이 없습니다.',
            ], 404);
        } catch (AiGcsException $exception) {
            return response()->json([
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ], $exception->status);
        } catch (\UnexpectedValueException) {
            return response()->json([
                'code' => 'AIGCS_INVALID_RESPONSE',
                'message' => 'AI 서비스가 올바른 응답을 반환하지 않았습니다.',
            ], 502);
        }
    }
}
