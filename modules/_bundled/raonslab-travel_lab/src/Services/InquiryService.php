<?php

namespace Modules\Raonslab\TravelLab\Services;

use App\Enums\PermissionType;
use App\Helpers\PermissionHelper;
use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowInquiryRepositoryInterface;

/** 테스트 문의 스냅샷과 모의 정원을 원자적으로 관리한다. 주문·결제·알림을 만들지 않는다. */
class InquiryService
{
    public function __construct(
        private WorkflowInquiryRepositoryInterface $inquiries,
        private WorkflowCartRepositoryInterface $carts,
        private TravelCartService $travelCart,
    ) {}

    public function submit(int $userId, array $cartIds, array $contact, string $key): Inquiry
    {
        // 재시도에서 삭제된 카트를 다시 읽지 않는다. 선택 순서와 연락처 키 순서는 의미가 없다.
        $cartIds = array_map('intval', $cartIds);
        sort($cartIds, SORT_NUMERIC);
        $contact = array_filter(array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $contact), static fn ($value) => $value !== null && $value !== '');
        ksort($contact);
        $payloadHash = hash('sha256', json_encode(['cart_ids' => $cartIds, 'contact' => $contact], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($userId, $cartIds, $contact, $key, $payloadHash) {
            // 같은 사용자의 submit/cancel은 사용자 행에서 직렬화된다. UNIQUE(user_id,key)도 유지한다.
            // 이 transaction의 첫 일반 조회는 사용자 잠금을 얻은 뒤라 직전 승자의 commit을 읽는다.
            // 없는 키를 FOR UPDATE로 읽으면 다른 사용자의 INSERT까지 막는 gap lock이 생긴다.
            $this->requireUser($userId, true);
            $existing = $this->inquiries->byKey($userId, $key);
            if ($existing) {
                if (! hash_equals($existing->payload_hash, $payloadHash)) {
                    $this->fail('idempotency_conflict');
                }

                return $existing;
            }
            $selected = $this->carts->carts($userId, $cartIds, true);
            if ($cartIds === [] || count($cartIds) !== count(array_unique($cartIds)) || $selected->count() !== count($cartIds)) {
                $this->fail('cart_changed');
            }
            $departures = $this->carts->departuresForOptions($selected->pluck('product_option_id')->all(), true);
            $snapshot = $this->travelCart->calculate($userId, $selected, $departures, true);
            $items = [];
            $byId = $departures->keyBy('id');
            foreach ($snapshot['items'] as $item) {
                if (! $this->carts->reserve($byId->get($item['departure_id']), $item['quantity'])) {
                    $this->fail('capacity_unavailable');
                }
                unset($item['id'], $item['return_date'], $item['available'], $item['unavailable_reason']);
                $items[] = $item;
            }
            $inquiry = $this->inquiries->create([
                'user_id' => $userId,
                'idempotency_key' => $key,
                'payload_hash' => $payloadHash,
                'status' => InquiryStatus::TEST_INQUIRY,
                'total_amount' => $snapshot['totals']['final_amount'],
                'currency_code' => $snapshot['currency_code'],
                'contact' => $contact,
                'calculation_snapshot' => $snapshot['calculation_snapshot'],
            ], $items);
            $this->inquiries->appendEvent($inquiry, $userId, null, InquiryStatus::TEST_INQUIRY);
            $this->travelCart->removeSelected($userId, $cartIds);

            return $inquiry;
        }, 3);
    }

    public function listOwn(int $userId, int $perPage = 20, int $page = 1, ?InquiryStatus $status = null): LengthAwarePaginator
    {
        $this->requireUser($userId);

        return $this->inquiries->paginate($userId, $perPage, $page, status: $status);
    }

    public function findOwn(int $userId, int $inquiryId): Inquiry
    {
        $this->requireUser($userId);
        $inquiry = $this->inquiries->find($inquiryId, $userId);
        if (! $inquiry) {
            $this->fail('not_found', 404);
        }

        return $inquiry;
    }

    public function cancel(int $userId, int $inquiryId): Inquiry
    {
        return DB::transaction(function () use ($userId, $inquiryId) {
            $this->requireUser($userId, true);
            $inquiry = $this->inquiries->find($inquiryId, $userId, true);
            if (! $inquiry) {
                $this->fail('not_found', 404);
            }

            return $this->applyTransition($inquiry, InquiryStatus::CANCELLED, $userId);
        }, 3);
    }

    public function listAdmin(int $actorId, int $perPage = 20, int $page = 1, ?InquiryStatus $status = null): LengthAwarePaginator
    {
        $actor = $this->requireAdmin($actorId, 'read');

        return $this->inquiries->paginate(null, $perPage, $page, $actor, $status);
    }

    public function findAdmin(int $actorId, int $inquiryId): Inquiry
    {
        $actor = $this->requireAdmin($actorId, 'read');
        $inquiry = $this->inquiries->find($inquiryId);
        if (! $inquiry) {
            $this->fail('not_found', 404);
        }
        $this->checkAdminScope($actor, $inquiry, 'read');

        return $inquiry;
    }

    public function transition(int $actorId, int $inquiryId, InquiryStatus $status, ?string $adminNote = null, bool $noteProvided = false): Inquiry
    {
        $noteProvided = $noteProvided || $adminNote !== null;
        $adminNote = $adminNote === null ? null : trim($adminNote);

        return DB::transaction(function () use ($actorId, $inquiryId, $status, $adminNote, $noteProvided) {
            $actor = $this->requireAdmin($actorId, 'update');
            $inquiry = $this->inquiries->find($inquiryId, lock: true);
            if (! $inquiry) {
                $this->fail('not_found', 404);
            }
            $this->checkAdminScope($actor, $inquiry, 'update');

            return $this->applyTransition($inquiry, $status, $actorId, $adminNote, $noteProvided);
        }, 3);
    }

    private function applyTransition(Inquiry $inquiry, InquiryStatus $next, int $actorId, ?string $adminNote = null, bool $noteProvided = false): Inquiry
    {
        $current = $inquiry->status instanceof InquiryStatus ? $inquiry->status : InquiryStatus::from($inquiry->status);
        if ($current === $next) {
            // 같은 상태의 메모 저장은 감사하지만 동일 메모의 재전송은 쓰지 않는다.
            if ($noteProvided && $inquiry->admin_note !== $adminNote) {
                $inquiry = $this->inquiries->update($inquiry, ['admin_note' => $adminNote]);
                $this->inquiries->appendEvent($inquiry, $actorId, $current, $next, $adminNote);
            }

            return $inquiry;
        }
        if (! in_array($next, $current->allowedNext(), true)) {
            $this->fail('invalid_transition');
        }
        if (in_array($next, [InquiryStatus::DECLINED, InquiryStatus::CANCELLED], true)) {
            $quantities = $inquiry->items->groupBy('departure_id')->map(fn ($items) => (int) $items->sum('quantity'));
            $departures = $this->carts->departuresByIds($quantities->keys()->all(), true);
            if ($departures->count() !== $quantities->count()) {
                $this->fail('capacity_inconsistent');
            }
            foreach ($departures as $departure) {
                if (! $this->carts->release($departure, $quantities->get($departure->id))) {
                    $this->fail('capacity_inconsistent');
                }
            }
        }
        $attributes = ['status' => $next];
        if ($noteProvided) {
            $attributes['admin_note'] = $adminNote;
        }
        $inquiry = $this->inquiries->update($inquiry, $attributes);
        $this->inquiries->appendEvent($inquiry, $actorId, $current, $next, $noteProvided ? $adminNote : null);

        return $inquiry;
    }

    private function requireUser(int $userId, bool $lock = false): User
    {
        $user = $this->carts->user($userId, $lock);
        if (! $user) {
            $this->fail('unauthorized', 401);
        }

        return $user;
    }

    private function requireAdmin(int $actorId, string $operation): User
    {
        $actor = $this->requireUser($actorId);
        if (! $actor->isAdmin() || ! $actor->hasPermission('raonslab-travel_lab.inquiries.'.$operation, PermissionType::Admin)) {
            $this->fail('forbidden', 403);
        }

        return $actor;
    }

    private function checkAdminScope(User $actor, Inquiry $inquiry, string $operation): void
    {
        if (! PermissionHelper::checkScopeAccess($inquiry, 'raonslab-travel_lab.inquiries.'.$operation, $actor)) {
            $this->fail('forbidden', 403);
        }
    }

    private function fail(string $key, int $status = 409): never
    {
        throw new HttpResponseException(ResponseHelper::error('raonslab-travel_lab::workflow.'.$key, $status));
    }
}
