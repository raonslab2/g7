<?php

namespace Modules\Raonslab\Product\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSameOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = trim((string) $request->headers->get('Origin', ''));
        $expected = $request->getSchemeAndHttpHost();

        if (! $request->isSecure()
            || $origin === ''
            || ! str_starts_with(strtolower($origin), 'https://')
            || ! hash_equals($this->normalizeOrigin($expected), $this->normalizeOrigin($origin))) {
            return ResponseHelper::forbidden('auth.permission_denied');
        }

        return $next($request);
    }

    private function normalizeOrigin(string $origin): string
    {
        $parts = parse_url($origin);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return strtolower($parts['scheme'].'://'.$parts['host'].$port);
    }
}
