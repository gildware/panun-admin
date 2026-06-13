<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProviderManagement\Services\ProviderDashboardBundleService;

class DashboardBundleController extends Controller
{
    public function __construct(
        protected ProviderDashboardBundleService $dashboardBundleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()
            ->json(response_formatter(DEFAULT_200, $this->dashboardBundleService->build($request)), 200)
            ->header('Cache-Control', 'private, max-age=60');
    }
}
