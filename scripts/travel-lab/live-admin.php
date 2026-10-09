<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/live-bootstrap.php';

try {
    travelLabApp();
    $environment = travelLabEnvironment();
    travelLabCheck(($environment['INSTALLER_ADMIN_EMAIL'] ?? '') === 'admin@travel-lab.example.invalid',
        'Recovery is restricted to the synthetic lab administrator.');
    $user = User::query()->where('email', 'admin@travel-lab.example.invalid')->first();
    travelLabCheck($user !== null && $user->isAdmin(), 'Expected synthetic administrator is absent; no users were replaced.');
    $password = $environment['INSTALLER_ADMIN_PASSWORD'] ?? '';
    travelLabCheck((bool) preg_match('/^[a-f0-9]{32}$/', $password), 'Recovery requires a generated local password.');
    if (! Hash::check($password, $user->password)) {
        // Only the marked lab account is changed, through the native user service.
        Auth::setUser($user);
        app(UserService::class)->updateUser($user, ['password' => $password]);
        echo 'PASS: synthetic administrator credential recovered; all user rows preserved.'.PHP_EOL;
    } else {
        echo 'PASS: synthetic administrator credential preserved.'.PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}
