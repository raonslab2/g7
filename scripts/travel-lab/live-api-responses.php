<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;
use Modules\Raonslab\TravelLab\Services\InquiryService;

require __DIR__.'/live-bootstrap.php';
require __DIR__.'/live-fixtures.php';

$tokens = [];
$exitCode = 0;
$departure = null;
$memberId = null;
$idempotencyKey = null;
$faultInjected = false;
try {
    travelLabCheck(array_slice($argv, 1) === [] || array_slice($argv, 1) === ['--fail-after-inquiry'], 'Only the narrow --fail-after-inquiry probe is supported.');
    $app = travelLabApp();
    $kernel = $app->make(Kernel::class);
    $api = '/api/modules/raonslab-travel_lab';
    $records = [];
    $request = function (string $method, string $path, array $body = [], ?string $token = null, int $expected = 200) use ($kernel, &$records): array {
        Auth::forgetGuards();
        $server = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        $input = Request::create($path, $method, [], [], [], $server, $body === [] ? null : json_encode($body, JSON_THROW_ON_ERROR));
        $response = $kernel->handle($input);
        $kernel->terminate($input, $response);
        $decoded = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $records[] = ['method' => $method, 'route' => $path, 'status' => $response->getStatusCode(), 'response' => $decoded];
        travelLabCheck($response->getStatusCode() === $expected, 'Runtime HTTP status mismatch for '.$method.' '.$path.': '.$response->getStatusCode());

        return $decoded;
    };
    $request('GET', $api.'/catalog');
    $run = bin2hex(random_bytes(6));
    // A failed fixture construction rolls back its native product/metadata/option rows.
    // Authored seed products/departures are never selected or changed by this capture.
    $departure = DB::transaction(fn () => travelLabDeparture($run.'-api', 2));
    $request('GET', $api.'/catalog/'.$departure->product_id);
    $request('GET', $api.'/catalog/'.$departure->product_id.'/departures');
    $member = travelLabUser($run, 'api');
    $memberId = $member->id;
    $memberToken = $member->createToken('isolated-api-response', ['*'], now()->addHour());
    $tokens[] = $memberToken->accessToken;
    $cart = $request('POST', $api.'/cart', ['departure_id' => $departure->id, 'quantity' => 1], $memberToken->plainTextToken, 201);
    $idempotencyKey = 'api-'.bin2hex(random_bytes(8));
    $inquiry = $request('POST', $api.'/inquiries', ['cart_ids' => [$cart['data']['items'][0]['id']], 'contact' => ['name' => 'Synthetic API response member'], 'idempotency_key' => $idempotencyKey], $memberToken->plainTextToken, 201);
    if (($argv[1] ?? '') === '--fail-after-inquiry') {
        $faultInjected = true;
        throw new RuntimeException('Injected capture failure immediately after successful inquiry creation.');
    }
    $request('GET', $api.'/inquiries/'.$inquiry['data']['id'], [], $memberToken->plainTextToken);
    $admin = User::query()->where('email', 'admin@travel-lab.example.invalid')->firstOrFail();
    $adminToken = $admin->createToken('isolated-api-response', ['*'], now()->addHour());
    $tokens[] = $adminToken->accessToken;
    $request('GET', $api.'/admin/inquiries/'.$inquiry['data']['id'], [], $adminToken->plainTextToken);
    $request('GET', $api.'/support/notices');
    $request('GET', $api.'/support/faqs');
    $request('GET', $api.'/support/questions', [], $memberToken->plainTextToken);
    $request('POST', $api.'/inquiries/'.$inquiry['data']['id'].'/cancel', [], $memberToken->plainTextToken);
    $path = dirname(__DIR__, 2).'/storage/framework/testing/travel-live-api-responses.json';
    file_put_contents($path, json_encode(['source_sha' => trim(shell_exec('git rev-parse HEAD')), 'source_state' => 'WORKING_TREE_IMPLEMENTER_CHECK', 'scope' => 'Actual installed root HTTP kernel, synthetic marked app DB only; no headers/credentials', 'responses' => $records], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    echo 'PASS: '.count($records).' actual runtime API responses saved to '.$path.PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: '.($faultInjected ? 'Injected post-inquiry failure.' : 'API capture failed ('.get_class($error).').').PHP_EOL);
    $exitCode = 1;
} finally {
    $cleanup = ['failure_injected' => $faultInjected];
    if ($departure !== null) {
        try {
            // The key is retained before POST, so cleanup still finds the committed request
            // if later HTTP/JSON decoding fails before its response ID reaches the caller.
            $owned = $memberId !== null && $idempotencyKey !== null
                ? Inquiry::query()->where('user_id', $memberId)->where('idempotency_key', $idempotencyKey)->first() : null;
            if ($owned !== null) {
                travelLabCheck($owned->items()->count() === 1
                    && $owned->items()->where('departure_id', $departure->id)->where('product_id', $departure->product_id)->count() === 1,
                    'Refusing cancellation outside this capture fixture.');
                if ($owned->status !== InquiryStatus::DECLINED) {
                    $owned = app(InquiryService::class)->cancel($memberId, $owned->id);
                }
                $cleanup['inquiry_id'] = $owned->id;
                $cleanup['inquiry_status'] = $owned->status->value;
                travelLabCheck(in_array($owned->status, [InquiryStatus::CANCELLED, InquiryStatus::DECLINED], true), 'Capture inquiry did not reach a released terminal state.');
            }
            $cleanup['departure_id'] = $departure->id;
            $cleanup['reserved'] = $departure->fresh()->reserved;
            travelLabCheck($cleanup['reserved'] === 0, 'Capture fixture still holds a reservation.');
        } catch (Throwable $error) {
            $cleanup['release_error_class'] = get_class($error);
            $exitCode = 1;
        } finally {
            // Hiding is attempted even if native cancellation fails; the error stays visible.
            try {
                $metadata = app(CatalogRepositoryInterface::class)->updateMetadata($departure->product_id, ['published' => false]);
                $cleanup['product_id'] = $departure->product_id;
                $cleanup['published'] = (bool) $metadata->published;
                travelLabCheck($cleanup['published'] === false, 'Capture fixture remains published.');
            } catch (Throwable $error) {
                $cleanup['hide_error_class'] = get_class($error);
                $exitCode = 1;
            }
        }
    }
    foreach ($tokens as $token) {
        try {
            $token->delete();
        } catch (Throwable $error) {
            $cleanup['token_cleanup_error_class'] = get_class($error);
            $exitCode = 1;
        }
    }
    echo 'CLEANUP: '.json_encode($cleanup, JSON_THROW_ON_ERROR).PHP_EOL;
}
exit($exitCode);
