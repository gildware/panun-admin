<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;
use Modules\CallCenterModule\Transformers\CustomerTransformer;
use Modules\UserManagement\Entities\User;

class CustomerController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly CustomerTransformer $customerTransformer,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function byPhone(string $phone): JsonResponse
    {
        $user = $this->profiles->findUserByPhone(urldecode($phone));
        if (!$user) {
            return $this->notFound('customer_not_found', 'No customer found for this phone number');
        }

        $profile = $this->profiles->getProfileForUser($user);

        return $this->ok($this->profiles->toApi($profile));
    }

    public function show(int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile || !$profile->user) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        return $this->ok($this->profiles->toApi($profile));
    }

    public function byRef(string $customerId): JsonResponse
    {
        $profile = $this->profiles->getProfileByRef($customerId);
        if (!$profile || !$profile->user) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        return $this->ok($this->profiles->toApi($profile));
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $query = trim((string) $request->query('q'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $users = User::query()
            ->inCustomerDirectory()
            ->where(function ($w) use ($query) {
                $w->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('ref_code', 'like', "%{$query}%")
                    ->orWhereIn('id', function ($sub) use ($query) {
                        $sub->select('user_id')
                            ->from('call_center_customer_profiles')
                            ->where('customer_ref', 'like', "%{$query}%");
                    });
            })
            ->paginate($perPage, ['*'], 'page', $page);

        $data = [];
        foreach ($users as $user) {
            $profile = $this->profiles->getProfileForUser($user);
            $data[] = $this->customerTransformer->transformSearchItem($user, $profile);
        }

        return $this->ok([
            'data' => $data,
            'meta' => $this->paginatedMeta($users->total(), $page, $perPage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $endpoint = 'POST /customers';
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'nullable|string|max:32',
            'tags' => 'nullable|array',
            'location' => 'nullable|array',
            'priority' => 'nullable|string|max:16',
            'source' => 'nullable|string|max:32',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = $this->profiles->findUserByPhone($request->input('phone'));
        if ($existing) {
            $profile = $this->profiles->getProfileForUser($existing);

            return $this->ok($this->profiles->toApi($profile));
        }

        $payload = $this->profiles->createCustomer($request->all());
        $this->idempotency->store(
            trim((string) $request->header('Idempotency-Key')),
            $endpoint,
            201,
            $payload
        );

        return $this->created($payload);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile || !$profile->user) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'nullable|string|max:32',
            'tags' => 'nullable|array',
            'location' => 'nullable|array',
            'priority' => 'nullable|string|max:16',
            'assigned_agent_id' => 'nullable|integer',
            'assigned_agent_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        return $this->ok($this->profiles->updateCustomer($profile, $request->all()));
    }

    public function updateAiSummary(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $validator = Validator::make($request->all(), [
            'summary' => 'required|string',
            'updated_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $profile->update(['ai_summary' => $request->input('summary')]);

        return $this->ok([
            'id' => $profile->id,
            'ai_summary' => $profile->ai_summary,
        ]);
    }
}
