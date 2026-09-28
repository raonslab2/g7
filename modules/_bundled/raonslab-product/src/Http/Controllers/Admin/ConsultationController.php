<?php

namespace Modules\Raonslab\Product\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Exceptions\InvalidStatusTransitionException;
use Modules\Raonslab\Product\Http\Requests\Admin\AddConsultationNoteRequest;
use Modules\Raonslab\Product\Http\Requests\Admin\ConsultationListRequest;
use Modules\Raonslab\Product\Http\Requests\Admin\UpdateConsultationStatusRequest;
use Modules\Raonslab\Product\Models\Consultation;

class ConsultationController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @deprecated 상담 처리는 sirsoft-board 관리자 게시판에서 수행합니다.
     */
    public function deprecated(): JsonResponse
    {
        return $this->error('common.failed', 410, [
            'deprecated' => true,
            'board_slug' => (string) config('raonslab-product-consultations.board_slug'),
            'admin_path' => '/admin/board/'.rawurlencode((string) config('raonslab-product-consultations.board_slug')),
            'read_only_legacy' => true,
        ]);
    }

    public function index(ConsultationListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = isset($validated['status']) ? ConsultationStatus::from($validated['status']) : null;
        $paginator = $this->consultationService->paginate($status, (int) ($validated['per_page'] ?? 20));
        $paginator->through(fn (Consultation $consultation): array => $this->listData($consultation));

        return $this->success('common.success', $paginator);
    }

    public function show(Consultation $consultation): JsonResponse
    {
        return $this->success('common.success', $this->detailData($this->consultationService->detail($consultation)));
    }

    public function note(AddConsultationNoteRequest $request, Consultation $consultation): JsonResponse
    {
        $updated = $this->consultationService->addNote(
            $consultation,
            $request->validated()['note'],
            $request->user(),
        );

        return $this->success('common.success', $this->detailData($updated));
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

        return $this->success('common.success', $this->detailData($updated));
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
