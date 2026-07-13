<?php

namespace Modules\BookingModule\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Services\AppCustomRequestSubmissionService;
use Throwable;

class AppCustomRequestController extends Controller
{
    public function submit(Request $request, AppCustomRequestSubmissionService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'category_id' => 'required|uuid|exists:categories,id',
            'category_name' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 422);
        }

        try {
            $customRequest = $service->submit([
                'customer_id' => $request->user()?->id,
                'name' => trim((string) $request->input('name')),
                'phone' => trim((string) $request->input('phone')),
                'category_id' => (string) $request->input('category_id'),
                'category_name' => trim((string) $request->input('category_name')),
                'description' => trim((string) $request->input('description')),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'app_custom_request_submit_failed', 'message' => translate('Something_went_wrong')],
            ]), 500);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, [
            'id' => $customRequest->id,
            'reference_id' => $customRequest->reference_id,
            'lead_id' => $customRequest->lead_id,
        ]));
    }
}
