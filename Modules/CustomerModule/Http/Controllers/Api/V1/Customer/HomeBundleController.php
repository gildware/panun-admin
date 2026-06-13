<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Services\CustomerHomeBundleService;

class HomeBundleController extends Controller
{
    public function __construct(
        protected CustomerHomeBundleService $homeBundleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()
            ->json(response_formatter(DEFAULT_200, $this->homeBundleService->build($request)), 200)
            ->header('Cache-Control', 'public, max-age=120');
    }
}
