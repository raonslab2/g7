<?php

namespace Modules\Raonslab\Product\Console\Concerns;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Modules\Raonslab\Product\Auth\NativePageActorGuard;

/**
 * 운영자 Page 명령이 명시한 활성 최고 관리자를 PageService 기록자로 쓰게 한다.
 * 세션·쿠키·remember token·로그인 이벤트를 만들지 않는 in-memory guard 를 잠깐 기본값으로 둔다.
 */
trait ActsAsPageActor
{
    protected function resolveActor(string $identifier): ?User
    {
        if ($identifier === '') {
            return null;
        }

        $query = User::query();
        if (ctype_digit($identifier)) {
            $query->whereKey((int) $identifier);
        } elseif (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $query->where('email', $identifier);
        } else {
            return null;
        }

        return $query
            ->where('is_super', true)
            ->whereNull('blocked_at')
            ->whereNull('withdrawn_at')
            ->first();
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    protected function asPageActor(User $actor, string $guardName, Closure $callback): mixed
    {
        $auth = Auth::getFacadeRoot();
        $previousGuard = $auth->getDefaultDriver();
        config(["auth.guards.{$guardName}" => ['driver' => $guardName]]);
        $auth->extend($guardName, fn () => new NativePageActorGuard($actor));
        $auth->shouldUse($guardName);

        try {
            return $callback();
        } finally {
            $auth->shouldUse($previousGuard);
            $auth->forgetGuards();
            config()->offsetUnset("auth.guards.{$guardName}");
        }
    }
}
