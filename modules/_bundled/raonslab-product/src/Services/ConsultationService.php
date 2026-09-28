<?php

namespace Modules\Raonslab\Product\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Raonslab\Product\Enums\ConsultationHistoryType;
use Modules\Raonslab\Product\Enums\ConsultationMailStatus;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Exceptions\IdempotencyConflictException;
use Modules\Raonslab\Product\Exceptions\InvalidStatusTransitionException;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Repositories\Contracts\ConsultationRepositoryInterface;

class ConsultationService
{
    public function __construct(
        private ConsultationRepositoryInterface $repository,
        private ConsultationNotificationService $notificationService,
    ) {}

    public function submit(array $validated): ConsultationSubmissionResult
    {
        $keyHash = hash('sha256', (string) $validated['idempotency_key']);
        $payload = Arr::only($validated, [
            'contact_name', 'email', 'message', 'privacy_consent', 'privacy_consent_version',
            'company', 'phone', 'service_interest',
        ]);
        $payloadHash = hash_hmac('sha256', $this->canonicalJson($payload), (string) config('app.key'));

        try {
            $result = DB::transaction(function () use ($validated, $keyHash, $payloadHash): ConsultationSubmissionResult {
                $existing = $this->repository->findByIdempotencyHash($keyHash, true);
                if ($existing !== null) {
                    return $this->existingResult($existing, $payloadHash);
                }

                $consultation = $this->repository->create([
                    'reference' => 'RAON-'.Str::ulid(),
                    'idempotency_key_hash' => $keyHash,
                    'payload_hash' => $payloadHash,
                    'contact_name' => $validated['contact_name'],
                    'email' => $validated['email'],
                    'company' => $validated['company'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'service_interest' => $validated['service_interest'] ?? null,
                    'message' => $validated['message'],
                    'privacy_consent_version' => $validated['privacy_consent_version'],
                    'privacy_consented_at' => now(),
                    'status' => ConsultationStatus::New,
                    'mail_status' => $this->notificationService->canAttemptDelivery()
                        ? ConsultationMailStatus::Pending
                        : ConsultationMailStatus::NotConfigured,
                ]);

                $this->repository->addHistory([
                    'consultation_id' => $consultation->id,
                    'event_type' => ConsultationHistoryType::Created,
                    'to_status' => ConsultationStatus::New,
                ]);

                return new ConsultationSubmissionResult($consultation, true);
            }, 3);
        } catch (QueryException $exception) {
            // 두 동시 요청이 빈 키를 함께 관찰해도 unique 제약의 패자가 기존 행을 재사용합니다.
            $existing = $this->repository->findByIdempotencyHash($keyHash);
            if ($existing === null) {
                throw $exception;
            }

            $result = $this->existingResult($existing, $payloadHash);
        }

        if ($result->created) {
            $this->notificationService->deliver($result->consultation);
            $result->consultation->refresh();
        }

        return $result;
    }

    public function paginate(?ConsultationStatus $status, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($status, $perPage);
    }

    public function detail(Consultation $consultation): Consultation
    {
        return $this->repository->loadDetail($consultation);
    }

    public function addNote(Consultation $consultation, string $note, User $actor): Consultation
    {
        DB::transaction(function () use ($consultation, $note, $actor): void {
            $this->repository->addHistory([
                'consultation_id' => $consultation->id,
                'event_type' => ConsultationHistoryType::NoteAdded,
                'note' => $note,
                'actor_user_id' => $actor->id,
            ]);
        });

        return $this->repository->loadDetail($consultation->fresh());
    }

    public function transition(Consultation $consultation, ConsultationStatus $target, ?string $closeOutcome, User $actor): Consultation
    {
        return DB::transaction(function () use ($consultation, $target, $closeOutcome, $actor): Consultation {
            /** @var Consultation $locked */
            $locked = Consultation::query()->lockForUpdate()->findOrFail($consultation->id);
            $from = $locked->status;

            if ($from->next() !== $target) {
                throw new InvalidStatusTransitionException;
            }

            $updated = $this->repository->updateStatus($locked, $target, $closeOutcome);
            $this->repository->addHistory([
                'consultation_id' => $updated->id,
                'event_type' => ConsultationHistoryType::StatusChanged,
                'from_status' => $from,
                'to_status' => $target,
                'close_outcome' => $closeOutcome,
                'actor_user_id' => $actor->id,
            ]);

            return $this->repository->loadDetail($updated);
        });
    }

    private function existingResult(Consultation $existing, string $payloadHash): ConsultationSubmissionResult
    {
        if (! hash_equals($existing->payload_hash, $payloadHash)) {
            throw new IdempotencyConflictException;
        }

        return new ConsultationSubmissionResult($existing, false);
    }

    private function canonicalJson(array $payload): string
    {
        ksort($payload);

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
