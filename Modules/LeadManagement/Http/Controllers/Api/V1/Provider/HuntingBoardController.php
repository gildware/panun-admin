<?php

namespace Modules\LeadManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadHuntingBoardService;

class HuntingBoardController extends Controller
{
    public function __construct(
        private readonly LeadHuntingBoardService $huntingBoard,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $limit = (int) $request->input('limit', 20);
        $offset = (int) $request->input('offset', 1);

        $content = $this->huntingBoard->publicJobsForProvider($provider, $limit, $offset, [
            'date_range' => (string) $request->input('date_range', 'all'),
            'category_id' => (string) $request->input('category_id', ''),
            'subcategory_id' => (string) $request->input('subcategory_id', ''),
            'area_id' => (string) $request->input('area_id', ''),
            'sort' => (string) $request->input('sort', 'date'),
        ]);

        return response()->json(response_formatter(DEFAULT_200, $content), 200);
    }

    public function pendingCount(Request $request): JsonResponse
    {
        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'pending_action_count' => $this->huntingBoard->pendingActionCountForProvider($provider),
        ]), 200);
    }

    public function interest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $lead = Lead::query()->find((int) $request->input('lead_id'));
        if (! $lead) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        try {
            $this->huntingBoard->expressInterest($lead, $provider, $request->input('note'));
        } catch (\RuntimeException $e) {
            return response()->json([
                'response_code' => 'default_400',
                'message' => $e->getMessage(),
                'content' => null,
                'errors' => [],
            ], 400);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200), 200);
    }

    public function reject(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'reason' => 'required|string|min:3|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $lead = Lead::query()->find((int) $request->input('lead_id'));
        if (! $lead) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        try {
            $this->huntingBoard->rejectJob($lead, $provider, (string) $request->input('reason'));
        } catch (\RuntimeException $e) {
            return response()->json([
                'response_code' => 'default_400',
                'message' => $e->getMessage(),
                'content' => null,
                'errors' => [],
            ], 400);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200), 200);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $lead = Lead::query()->find((int) $request->input('lead_id'));
        if (! $lead) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        try {
            $this->huntingBoard->withdrawInterest($lead, $provider);
        } catch (\RuntimeException $e) {
            return response()->json([
                'response_code' => 'default_400',
                'message' => $e->getMessage(),
                'content' => null,
                'errors' => [],
            ], 400);
        }

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }
}
