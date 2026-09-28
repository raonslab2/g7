<?php

namespace Modules\Raonslab\Product\Http\Controllers\Admin;

use App\Enums\PermissionType;
use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Exceptions\InvalidStatusTransitionException;
use Modules\Raonslab\Product\Http\Requests\Admin\AddConsultationNoteRequest;
use Modules\Raonslab\Product\Http\Requests\Admin\ConsultationListRequest;
use Modules\Raonslab\Product\Http\Requests\Admin\UpdateConsultationStatusRequest;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Services\ConsultationService;

class ConsultationController extends AdminBaseController
{
    private const MANAGE_PERMISSION = 'raonslab-product.consultations.manage';

    public function __construct(private ConsultationService $consultationService)
    {
        parent::__construct();
    }

    public function index(ConsultationListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = isset($validated['status']) ? ConsultationStatus::from($validated['status']) : null;
        $paginator = $this->consultationService->paginate($status, (int) ($validated['per_page'] ?? 20));

        return $this->success('common.success', [
            'data' => $paginator->getCollection()
                ->map(fn (Consultation $consultation): array => $this->listData($consultation))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'abilities' => $this->abilities($request),
        ]);
    }

    public function show(Request $request, Consultation $consultation): JsonResponse
    {
        return $this->success('common.success', [
            ...$this->detailData($this->consultationService->detail($consultation)),
            'abilities' => $this->abilities($request),
        ]);
    }

    public function note(AddConsultationNoteRequest $request, Consultation $consultation): JsonResponse
    {
        $updated = $this->consultationService->addNote(
            $consultation,
            $request->validated()['note'],
            $request->user(),
        );

        return $this->success('common.success', [
            ...$this->detailData($updated),
            'abilities' => $this->abilities($request),
        ]);
    }

    public function status(UpdateConsultationStatusRequest $request, Consultation $consultation): JsonResponse
    {
        $validated = $request->validated();

        try {
            $updated = $this->consultationService->transition(
                $consultation,
                ConsultationStatus::from($validated['status']),
                $validated['close_outcome'] ?? null,
                $request->user(),
            );
        } catch (InvalidStatusTransitionException) {
            return $this->error('common.validation_failed', 422, [
                'status' => [__('common.validation_failed')],
            ]);
        }

        return $this->success('common.success', [
            ...$this->detailData($updated),
            'abilities' => $this->abilities($request),
        ]);
    }

    /**
     * 화면이 변경 컨트롤을 노출할지 판단하는 서버 판정값입니다.
     * 라우트 권한 미들웨어(`permission:admin,...manage`)와 같은 식별자·타입·판정기로 계산합니다.
     *
     * @return array{can_manage: bool}
     */
    private function abilities(Request $request): array
    {
        return [
            'can_manage' => $request->user()?->hasPermission(self::MANAGE_PERMISSION, PermissionType::Admin) === true,
        ];
    }

    /** @return array<string, mixed> */
    private function listData(Consultation $consultation): array
    {
        return [
            'reference' => $consultation->reference,
            'contact_name' => $consultation->contact_name,
            'email' => $consultation->email,
            'company' => $consultation->company,
            'service_interest' => $consultation->service_interest,
            'status' => $consultation->status->value,
            'mail_status' => $consultation->mail_status->value,
            'received_at' => $consultation->created_at?->toIso8601String(),
            'updated_at' => $consultation->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function detailData(Consultation $consultation): array
    {
        return [
            ...$this->listData($consultation),
            'next_status' => $consultation->status->next()?->value,
            'phone' => $consultation->phone,
            'message' => $consultation->message,
            'privacy_consent_version' => $consultation->privacy_consent_version,
            'privacy_consented_at' => $consultation->privacy_consented_at?->toIso8601String(),
            'close_outcome' => $consultation->close_outcome,
            'mail_attempted_at' => $consultation->mail_attempted_at?->toIso8601String(),
            'mail_sent_at' => $consultation->mail_sent_at?->toIso8601String(),
            'history' => $consultation->histories->map(fn ($history): array => [
                'id' => $history->id,
                'event_type' => $history->event_type->value,
                'from_status' => $history->from_status?->value,
                'to_status' => $history->to_status?->value,
                'note' => $history->note,
                'close_outcome' => $history->close_outcome,
                'actor' => $history->actor ? [
                    'id' => $history->actor->id,
                    'name' => $history->actor->name,
                ] : null,
                'created_at' => $history->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
