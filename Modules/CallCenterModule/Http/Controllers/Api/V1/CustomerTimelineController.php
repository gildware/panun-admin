<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Services\CustomerTimelineService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;

class CustomerTimelineController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly CustomerTimelineService $timeline,
    ) {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 30)));

        return $this->ok($this->timeline->build($profile, $page, $perPage));
    }
}
