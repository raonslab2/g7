<?php

namespace Modules\Raonslab\TravelLab\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Inquiry;

interface WorkflowInquiryRepositoryInterface
{
    public function byKey(int $userId, string $key, bool $lock = false): ?Inquiry;

    public function find(int $id, ?int $userId = null, bool $lock = false): ?Inquiry;

    public function paginate(?int $userId, int $perPage, int $page, ?User $actor = null, ?InquiryStatus $status = null): LengthAwarePaginator;

    public function create(array $attributes, array $items): Inquiry;

    public function update(Inquiry $inquiry, array $attributes): Inquiry;

    public function appendEvent(Inquiry $inquiry, int $actorId, ?InquiryStatus $from, InquiryStatus $to, ?string $note = null): void;
}
