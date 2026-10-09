# Travel Lab cart and test inquiry API

The contract is implemented by `src/routes/workflow.php`, Workflow FormRequests,
Resources, workflow repositories and native ecommerce services. Prefix:
`/api/modules/raonslab-travel_lab`. Every endpoint requires a real Sanctum Bearer
token and `Accept: application/json`. Admin endpoints also require the admin
role and the indicated scoped permission. These are **test inquiries**, never
real reservations, orders, payments or shipping confirmations.

## Endpoints

Route names have the automatic `api.modules.raonslab-travel_lab.` prefix.

| Method | URI | Name | Parameters | Success |
|---|---|---|---|---|
| GET | `/cart` | cart.index | none | 200 cart |
| POST | `/cart` | cart.store | departure_id, quantity | 201 cart |
| PATCH | `/cart/{cart}` | cart.update | quantity | 200 cart |
| DELETE | `/cart/{cart}` | cart.destroy | none | 200 cart |
| GET | `/inquiries` | inquiries.index | page?, per_page?, status? | 200 inquiry collection |
| POST | `/inquiries` | inquiries.store | cart_ids, contact, idempotency_key | 201 new / 200 replay |
| GET | `/inquiries/{inquiry}` | inquiries.show | none | 200 inquiry |
| POST | `/inquiries/{inquiry}/cancel` | inquiries.cancel | none | 200 inquiry |
| GET | `/admin/inquiries` | admin.inquiries.index | page?, per_page?, status? | 200 admin collection |
| GET | `/admin/inquiries/{inquiry}` | admin.inquiries.show | none | 200 admin inquiry |
| PATCH | `/admin/inquiries/{inquiry}` | admin.inquiries.update | status, admin_note? | 200 admin inquiry |

Input errors/unknown fields are 422. Changed capacity, price/calculation policy,
cart selection, idempotency payload or forbidden state transition is 409.
Foreign customer cart/inquiry IDs return 404. Authentication failure is 401;
admin role/permission/scope failures are 403. Business responses use
`ResponseHelper`: `{success,message,data}` or `{success:false,message,errors}`.

All undefined top-level fields are rejected, including `price`, `unit_price`,
`line_total`, `total_amount`, `currency_code`, `user_id`, `product_id` and
`product_option_id`. Inquiry creation does not accept quantities: it reads
locked real `Cart.quantity` values. Contact allows only `name` and `phone`.

## Cart contract and examples

`departure_id` is the travel departure ID, not the commerce option ID. Quantity
is an integer from 1 through the native ecommerce cart maximum (currently 99),
also subject to native product purchase limits and remaining test seats:
`max(0, min(departure.capacity, option.stock_quantity) - departure.reserved)`.

```http
POST /api/modules/raonslab-travel_lab/cart
Authorization: Bearer <token>
Content-Type: application/json

{"departure_id":3,"quantity":2}
```

Cart response shape (also returned by GET, PATCH and DELETE):

```json
{"success":true,"message":"...","data":{"items":[{"id":8,"departure_id":3,"product_id":2,"product_option_id":6,"quantity":2,"remaining_capacity":5,"product_name":{"ko":"합성 여행","en":"Synthetic journey"},"departure_date":"2026-11-01","return_date":"2026-11-03","unit_price":12000,"line_total":24000,"available":true,"unavailable_reason":null}],"totals":{"subtotal":24000,"total_shipping":0,"final_amount":24000},"currency_code":"KRW"}}
```

`totals` includes the native Summary's full fields; the example selects its main
fields. Unavailable rows remain visible for removal with null prices and a
reason, and are excluded from totals. Ordinary commerce cart rows are excluded.
Duplicate option rows or additional-option selections cannot be travel inquiries.

```http
PATCH /api/modules/raonslab-travel_lab/cart/8
Content-Type: application/json

{"quantity":1}
```

`DELETE /cart/8` takes no body and returns the remaining cart. Removing a cart
does not change test capacity or commerce stock.

A departure date must be strictly after today in the application's configured
timezone. Same-day and past departures are rejected even when a
caller knows an ID or retains a previously valid cart. Stale rows remain visible
for removal with `departure_unavailable`, and inquiry rejection preserves the cart.

Every eligible product requires an explicitly assigned, active **nondefault**
shipping policy with an active KR FREE country setting. Every configured country
setting must be FREE with zero base fees, no extra fees and no API endpoint.
Missing/empty/paid policies are unavailable before price calculation. The server
pins the native cart calculation country to KR and restores the prior request
context; client `X-Shipping-Country` cannot change the inquiry amount. The native
calculator must return every selected item and zero shipping, discounts and
used points. No local multiplication/subtraction replaces native calculation.

## Submission, replay, retrieval and cancellation

```http
POST /api/modules/raonslab-travel_lab/inquiries
Authorization: Bearer <token>
Content-Type: application/json
Idempotency-Key: synthetic-request-001

{"cart_ids":[8],"contact":{"name":"Synthetic member","phone":null}}
```

`cart_ids`: distinct positive IDs, 1–100 entries. Contact name: required string,
maximum 100; optional phone: nullable string, maximum 40. The key may instead
be sent in the body as `idempotency_key`; when both are present they must match.
Key length is 8–100, starting with an ASCII alphanumeric and then alphanumeric,
dot, underscore, colon or hyphen. Contact strings are trimmed, null/empty phone
is omitted, contact keys and selected cart IDs are normalized before hashing.

New submission returns 201; an identical same-user/key replay returns the stored
inquiry with 200 even after cart consumption or cancellation. Changed content
under the same key returns 409. User-row serialization and the unique
`(user_id,idempotency_key)` index defend concurrent duplicates. User, inquiry,
cart, departure, product/option and policy rows are read in deterministic locking
order. The reserve UPDATE also carries the stock/capacity ceiling. Inquiry/items,
complete calculation/currency snapshot, initial actor event, test allocation and
selected-cart deletion all commit together or roll back together.

Inquiry response:

```json
{"success":true,"message":"...","data":{"id":5,"reference":"TL-00000005","status":"TEST_INQUIRY","allowed_transitions":["UNDER_REVIEW","DECLINED","CANCELLED"],"can_cancel":true,"first_product_name":"Synthetic journey","total_quantity":2,"total_amount":"24000.00","currency_code":"KRW","contact":{"name":"Synthetic member"},"items":[{"id":9,"departure_id":3,"product_id":2,"product_option_id":6,"quantity":2,"unit_price":"12000.00","line_total":"24000.00","product_name":{"en":"Synthetic journey"},"departure_date":"2026-11-01"}],"abilities":{"can_cancel":true}}}
```

`product_name`, `departure_label`, `party_size` and `requester_name` are additional
flattened summary fields. Items are immutable snapshots; later catalog edits do
not change past inquiry names, dates or amounts. `can_cancel` is true only for
the owner when the enum permits cancellation.

`GET /inquiries/5` returns that inquiry; `POST /inquiries/5/cancel` takes no body
and returns it with `status=CANCELLED`. Exact repeated cancellation is a no-op;
it never returns seats twice or restores a cart. `GET /inquiries?page=1&per_page=20&status=TEST_INQUIRY`
returns only owned matching rows in `data.data` and `data.pagination` with
current_page, total, per_page and last_page. Per-page range: 1–100.

## Admin status, filtering and audit

GET requires `raonslab-travel_lab.inquiries.read`; PATCH requires `.update`.
Permission metadata declares owner `user_id` and route resource `inquiry` so
native G7 global/self scope applies to list, detail, resource abilities and writes.

```http
GET /api/modules/raonslab-travel_lab/admin/inquiries?status=UNDER_REVIEW&page=1&per_page=20
Authorization: Bearer <admin-token>
```

Admin collections use the same `data.data` / `data.pagination` structure and add
row `user_id`, `admin_note`, `abilities.can_update`. Admin detail
`GET /admin/inquiries/5` also exposes the stored `calculation_snapshot` and
`events`: `{id,actor_id,from_status,to_status,note,created_at}`.

```http
PATCH /api/modules/raonslab-travel_lab/admin/inquiries/5
Content-Type: application/json

{"status":"UNDER_REVIEW","admin_note":"Synthetic test review"}
```

Status values are uppercase backed enum values. Allowed transitions come from
the single `InquiryStatus::allowedNext()` method:

| Current | Next |
|---|---|
| TEST_INQUIRY | UNDER_REVIEW, DECLINED, CANCELLED |
| UNDER_REVIEW | TEST_ACCEPTED, DECLINED, CANCELLED |
| TEST_ACCEPTED | CANCELLED |
| DECLINED / CANCELLED | none |

TEST_ACCEPTED is only test acceptance. Same-status note edits persist and append
an actor event; exact same-note retries are no-ops. Omitted admin_note preserves
the note; explicit null clears it and audits that change. Max note length: 2000.
Decline/cancel releases reserved seats exactly once inside the inquiry
transaction. Every real transition appends an actor event. Any allocation/audit
failure rolls back the entire operation.

## Native commerce guard and verification boundaries

`BlockTravelCommerceCheckout` uses synchronous priority-1 hooks:
`temp_order.before_create`, `temp_order.before_update`, `order.before_create`,
`order.before_payment_complete`. Membership checks cover travel Product IDs OR
mapped Option IDs and use the workflow repository. Native cart/direct-item
checkout is rejected before temporary-order/order/payment/stock writes. Native
checkout maps the purchase-restriction exception to 400; native order creation
maps it to 422. Ordinary commerce items pass this guard unchanged.

Focused actual-domain checks:

```sh
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter='TravelWorkflowTest|TravelWorkflowRegressionTest|TravelCheckoutGuardTest'
php scripts/travel-lab/run.php test modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportApiTest.php
```

The first command uses real travel models/enums/migrations/provider and native
commerce services/Sanctum with SQLite memory isolation. There are no fallback
domain models or travel schemas. Native board support tests use the second,
guarded MySQL path and are excluded from the SQLite suite. SQLite PASS proves
sequential boundaries, response contracts and rollback, not MySQL row-lock
contention, parallel idempotency, deployed browser behavior or official Validation.
Those require lead-owned independent fixed-SHA validation after integration.
