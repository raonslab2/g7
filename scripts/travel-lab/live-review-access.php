<?php

declare(strict_types=1);
use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/live-bootstrap.php';

try {
    travelLabApp();
    $owner = User::query()->where('email', 'admin@travel-lab.example.invalid')->firstOrFail();
    travelLabCheck($owner->isAdmin(), 'Marked synthetic administrator is required.');
    Auth::setUser($owner);
    $role = app(RoleRepositoryInterface::class)->findByIdentifier('admin');
    travelLabCheck($role !== null, 'Native administrator role is absent.');
    $run = bin2hex(random_bytes(6));
    $directory = dirname(__DIR__, 2).'/storage/framework/testing/travel-live-review-'.$run;
    mkdir($directory, 0700, true);
    $access = ['base_url' => 'http://127.0.0.1:18871', 'db' => 'req81_travel_lab',
        'source_sha' => trim(shell_exec('git rev-parse HEAD')), 'expires_at' => now()->addHours(4)->toIso8601String()];
    foreach (['member', 'other_member', 'admin'] as $kind) {
        $password = bin2hex(random_bytes(16));
        $data = ['name' => 'Synthetic reviewer '.$kind, 'email' => 'review-'.$run.'-'.$kind.'@travel-lab.example.invalid',
            'password' => $password, 'email_verified_at' => now(), 'language' => 'ko'];
        if ($kind === 'admin') {
            $data['role_ids'] = [$role->id];
        }
        $user = app(UserService::class)->createUser($data);
        travelLabCheck($kind !== 'admin' || $user->isAdmin(), 'Native administrator assignment did not apply.');
        $access[$kind] = ['user_id' => $user->id, 'email' => $user->email, 'password' => $password,
            'bearer_token' => $user->createToken('isolated-review-'.$run, ['*'], now()->addHours(4))->plainTextToken];
    }
    $path = $directory.'/access.json';
    file_put_contents($path, json_encode($access, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    travelLabCheck(trim(shell_exec('git check-ignore '.escapeshellarg($path))) !== '', 'Review secrets must stay Git ignored.');
    echo 'PASS: native synthetic reviewer accounts and four-hour tokens written only to '.$path.PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: reviewer access provisioning failed ('.get_class($error).'); no secrets printed.'.PHP_EOL);
    exit(1);
}
