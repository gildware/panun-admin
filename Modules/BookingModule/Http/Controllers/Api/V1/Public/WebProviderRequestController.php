<?php

namespace Modules\BookingModule\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Services\WebProviderRequestSubmissionService;
use Throwable;

class WebProviderRequestController extends Controller
{
    public function submit(Request $request, WebProviderRequestSubmissionService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'service' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'experience' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 422);
        }

        try {
            $providerRequest = $service->submit([
                'name' => trim((string) $request->input('name')),
                'phone' => trim((string) $request->input('phone')),
                'service' => trim((string) $request->input('service')),
                'area' => trim((string) $request->input('area')),
                'message' => trim((string) $request->input('message')),
                'experience' => $request->filled('experience') ? trim((string) $request->input('experience')) : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'web_provider_request_submit_failed', 'message' => translate('Something_went_wrong')],
            ]), 500);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, [
            'id' => $providerRequest->id,
            'reference_id' => $providerRequest->reference_id,
            'lead_id' => $providerRequest->lead_id,
        ]));
    }
}
