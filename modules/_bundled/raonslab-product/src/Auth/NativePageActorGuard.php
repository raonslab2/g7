<?php

namespace Modules\Raonslab\Product\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

/** In-memory CLI attribution guard: no session, cookie, remember token, or auth event. */
class NativePageActorGuard implements Guard
{
    use GuardHelpers;

    public function __construct(Authenticatable $actor)
    {
        $this->user = $actor;
    }

    public function user(): ?Authenticatable
    {
        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }
}
