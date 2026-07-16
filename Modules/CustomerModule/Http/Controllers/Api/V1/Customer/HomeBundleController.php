<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Services\CustomerHomeBaseBundleCache;
use Modules\CustomerModule\Services\CustomerHomeBundleService;
use Modules\CustomerModule\Services\CustomerHomeContentVersion;

class HomeBundleController extends Controller
{
    public function __construct(
        protected CustomerHomeBundleService $homeBundleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $content = $this->homeBundleService->build($request);
        } catch (\Throwable $e) {
            report($e);
            // Never 500 the home screen — empty/fast response beats multi-API fallback.
            $layoutHash = 'unknown';
            try {
                $layoutHash = $this->homeBundleService->layoutHash();
            } catch (\Throwable) {
            }
            $content = array_merge(
                [
                    'content_version' => CustomerHomeContentVersion::resolveForRequest($layoutHash),
                    'cache_status' => 'error',
                ],
                CustomerHomeBaseBundleCache::emptyPayload(),
            );
        }

        return response()
            ->json(response_formatter(DEFAULT_200, $content), 200)
            ->header('Cache-Control', 'private, no-cache');
    }

    public function version(Request $request): JsonResponse
    {
        return response()
            ->json(response_formatter(DEFAULT_200, $this->homeBundleService->versionPayload($request)), 200)
            ->header('Cache-Control', 'private, no-cache');
    }
}
