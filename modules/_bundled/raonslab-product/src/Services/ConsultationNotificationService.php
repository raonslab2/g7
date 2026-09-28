<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Raonslab\Product\Enums\ConsultationMailStatus;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Repositories\Contracts\ConsultationRepositoryInterface;
use Throwable;

class ConsultationNotificationService
{
    public function __construct(
        private ConsultationRepositoryInterface $repository,
        private ConsultationConfigService $configService,
    ) {}

    public function canAttemptDelivery(): bool
    {
        $recipient = $this->configService->notificationRecipient();
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $mailer = (string) config('mail.default', '');
        $transport = (string) config("mail.mailers.{$mailer}.transport", '');

        return $mailer !== '' && ! in_array($transport, ['', 'log', 'array'], true);
    }

    public function deliver(Consultation $consultation): void
    {
        if (! $this->canAttemptDelivery()) {
            return;
        }

        $recipient = $this->configService->notificationRecipient();
        $reference = $consultation->reference;
        $adminUrl = rtrim((string) config('app.url'), '/').'/admin/consultations/'.rawurlencode($reference);

        try {
            Mail::raw(
                "A new business consultation was received.\nReference: {$reference}\nAdmin: {$adminUrl}",
                function ($message) use ($recipient, $reference): void {
                    $message->to($recipient)->subject("New consultation {$reference}");
                }
            );

            $this->repository->updateMailStatus($consultation, ConsultationMailStatus::Sent);
        } catch (Throwable) {
            try {
                $this->repository->updateMailStatus($consultation, ConsultationMailStatus::Failed);
            } catch (Throwable) {
                // 접수 트랜잭션은 이미 확정되었습니다. 후속 상태 기록 실패가 접수를 뒤집지 않습니다.
            }
        }
    }
}
