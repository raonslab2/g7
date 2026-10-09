<?php

namespace Modules\Raonslab\TravelLab\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Adapt only a conflict explicitly marked by our synchronous travel delete guard. */
class TravelCatalogConflictResponse
{
    public const ATTRIBUTE = 'raonslab_travel_lab.product_delete_conflict';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->remove(self::ATTRIBUTE);
        try {
            $response = $next($request);
            if ($request->attributes->get(self::ATTRIBUTE) === true && $response->getStatusCode() === 409) {
                return ResponseHelper::moduleError('raonslab-travel_lab', 'workflow.product_delete_restricted', 409);
            }

            return $response;
        } finally {
            $request->attributes->remove(self::ATTRIBUTE);
        }
    }
}
