<?php

namespace Modules\Raonslab\Ai\Workspace\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\Ai\Workspace\Exceptions\AiGcsException;
use Modules\Raonslab\Ai\Workspace\Http\Requests\EventCursorRequest;
use Modules\Raonslab\Ai\Workspace\Services\AiWorkspaceService;
use Modules\Raonslab\Ai\Workspace\Services\CustomerSafeAiPayload;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiEventController extends Controller
{
    public function __construct(
        private readonly AiWorkspaceService $workspace,
        private readonly CustomerSafeAiPayload $customerSafe,
    ) {}

    public function __invoke(EventCursorRequest $request, string $requestId): StreamedResponse|JsonResponse
    {
        try {
            $stream = $this->workspace->events(
                $request->user(),
                $requestId,
                (int) ($request->validated()['after'] ?? 0)
            );
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
        }

        return response()->stream(function () use ($stream): void {
            foreach ($this->customerSafe->sanitizedSse($stream) as $frame) {
                echo $frame;
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
