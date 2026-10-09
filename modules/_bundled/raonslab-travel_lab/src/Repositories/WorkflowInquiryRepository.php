<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowInquiryRepositoryInterface;

class WorkflowInquiryRepository implements WorkflowInquiryRepositoryInterface
{
    public function byKey(int $userId, string $key, bool $lock = false): ?Inquiry
    {
        $query = Inquiry::query()->where('user_id', $userId)->where('idempotency_key', $key);

        return ($lock ? $query->lockForUpdate() : $query)->with('items')->first();
    }

    public function find(int $id, ?int $userId = null, bool $lock = false): ?Inquiry
    {
        $query = Inquiry::query()->whereKey($id);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return ($lock ? $query->lockForUpdate() : $query)->with('items')->first();
    }

    public function paginate(?int $userId, int $perPage, int $page, ?User $actor = null): LengthAwarePaginator
    {
        $query = Inquiry::query()->with('items')->orderByDesc('id');
        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($actor !== null) {
            PermissionHelper::applyPermissionScope($query, 'raonslab-travel_lab.inquiries.read', $actor);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
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

        return $inquiry->load('items');
    }
}
