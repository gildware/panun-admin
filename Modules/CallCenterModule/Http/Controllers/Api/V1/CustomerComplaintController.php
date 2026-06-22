<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;
use Modules\CallCenterModule\Transformers\ComplaintTransformer;
use Modules\ProviderManagement\Entities\CustomerIncident;

class CustomerComplaintController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly ComplaintTransformer $complaintTransformer,
    ) {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $limit = min(50, max(1, (int) $request->query('limit', 10)));

        $complaints = CustomerIncident::query()
            ->where('customer_id', $profile->user_id)
            ->where('incident_type', 'COMPLAINT')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->ok([
            'data' => $complaints->map(fn (CustomerIncident $c) => $this->complaintTransformer->transform($c))->values()->all(),
        ]);
    }
}
