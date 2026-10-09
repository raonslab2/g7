<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Models\InquiryEvent;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowInquiryRepositoryInterface;

class WorkflowInquiryRepository implements WorkflowInquiryRepositoryInterface
{
    public function byKey(int $userId, string $key, bool $lock = false): ?Inquiry
    {
        $query = Inquiry::query()->where('user_id', $userId)->where('idempotency_key', $key);

        return ($lock ? $query->lockForUpdate() : $query)->with(['items', 'events'])->first();
    }

    public function find(int $id, ?int $userId = null, bool $lock = false): ?Inquiry
    {
        $query = Inquiry::query()->whereKey($id);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return ($lock ? $query->lockForUpdate() : $query)->with(['items', 'events'])->first();
    }

    public function paginate(?int $userId, int $perPage, int $page, ?User $actor = null, ?InquiryStatus $status = null): LengthAwarePaginator
    {
        $query = Inquiry::query()->with('items')->orderByDesc('id');
        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($actor !== null) {
            PermissionHelper::applyPermissionScope($query, 'raonslab-travel_lab.inquiries.read', $actor);
        }
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->paginate($perPage, [
            'id', 'user_id', 'status', 'total_amount', 'currency_code', 'contact',
            'admin_note', 'created_at', 'updated_at',
        ], 'page', $page);
    }

    public function create(array $attributes, array $items): Inquiry
    {
        $inquiry = Inquiry::query()->create($attributes);
        $inquiry->items()->createMany($items);

        return $inquiry->load('items');
    }

    public function update(Inquiry $inquiry, array $attributes): Inquiry
    {
        $inquiry->fill($attributes)->save();

        return $inquiry->load(['items', 'events']);
    }

    public function appendEvent(Inquiry $inquiry, int $actorId, ?InquiryStatus $from, InquiryStatus $to, ?string $note = null): void
    {
        InquiryEvent::query()->create([
            'inquiry_id' => $inquiry->id,
            'actor_id' => $actorId,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note,
            'created_at' => now(),
        ]);
        $inquiry->load('events');
    }
}
