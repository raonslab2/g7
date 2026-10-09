<?php

namespace Modules\Raonslab\TravelLab\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Throwable;

/** Native numeric throttling with a short, shared admission lock; no controller is locked. */
class TravelThrottleRequests extends ThrottleRequests
{
    public function __construct(RateLimiter $limiter, private Factory $cache)
    {
        parent::__construct($limiter);
    }

    protected function handleRequest($request, Closure $next, array $limits)
    {
        $store = $this->cache->store(config('cache.limiter'))->getStore();
        // Travel routes use one numeric budget per middleware, never named/after-response limits.
        if (! $store instanceof DatabaseStore || count($limits) !== 1 || $limits[0]->afterCallback !== null) {
            throw new HttpResponseException($this->busy());
        }

        $lock = $store->lock('travel-admission:'.hash('sha256', $limits[0]->key), 30);
        $started = hrtime(true);
        try {
            $lock->block(3);
        } catch (LockTimeoutException) {
            throw new HttpResponseException($this->busy());
        }

        $held = true;
        $failure = null;
        try {
            if ((hrtime(true) - $started) >= 25_000_000_000 || ! $lock->isOwnedByCurrentProcess()) {
                throw new HttpResponseException($this->busy());
            }

            return parent::handleRequest($request, function ($request) use ($next, $lock, $started, &$held) {
                // Owner-conditional native DELETE also prevents an expired holder releasing a successor.
                // Native release's boolean alone does not certify ownership; check it and lease headroom.
                $valid = (hrtime(true) - $started) < 25_000_000_000 && $lock->isOwnedByCurrentProcess();
                $released = $lock->release();
                $held = false;
                if (! $valid || ! $released || (hrtime(true) - $started) >= 25_000_000_000) {
                    throw new HttpResponseException($this->busy());
                }

                return $next($request);
            }, $limits);
        } catch (Throwable $error) {
            $failure = $error;
            throw $error;
        } finally {
            if ($held) {
                try {
                    $lock->release();
                } catch (Throwable $cleanupError) {
                    // Preserve the original rejection/error. Failed cleanup remains lease-bounded.
                    if ($failure === null) {
                        throw $cleanupError;
                    }
                }
            }
        }
    }

    private function busy()
    {
        return ResponseHelper::error(__('raonslab-travel_lab::messages.throttle_busy'), 503)
            ->header('Retry-After', '1');
    }
}
