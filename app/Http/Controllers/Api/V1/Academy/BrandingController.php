<?php

namespace App\Http\Controllers\Api\V1\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Academy\AcademyBrandingResource;
use App\Http\Traits\Api\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    use ApiResponses;

    /**
     * Get academy branding information for the mobile app.
     *
     * This endpoint is public (no auth required) and only requires
     * academy resolution via X-Academy-Subdomain header or academy query param.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        if (!$academy) {
            return $this->error(
                'Academy not found',
                404,
                'ACADEMY_NOT_FOUND'
            );
        }

        return $this->resource(
            new AcademyBrandingResource($academy),
            'Academy branding retrieved successfully'
        );
    }
}
