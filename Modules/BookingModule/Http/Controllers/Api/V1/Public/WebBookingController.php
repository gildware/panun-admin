<?php

namespace Modules\BookingModule\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Services\WebBookingSubmissionService;
use Throwable;

class WebBookingController extends Controller
{
    public function submit(Request $request, WebBookingSubmissionService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'service' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'preferred_date' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 422);
        }

        try {
            $webBooking = $service->submit([
                'name' => trim((string) $request->input('name')),
                'phone' => trim((string) $request->input('phone')),
                'service' => trim((string) $request->input('service')),
                'area' => trim((string) $request->input('area')),
                'message' => trim((string) $request->input('message')),
                'preferred_date' => $request->filled('preferred_date') ? trim((string) $request->input('preferred_date')) : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'web_booking_submit_failed', 'message' => translate('Something_went_wrong')],
            ]), 500);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, [
            'id' => $webBooking->id,
            'reference_id' => $webBooking->reference_id,
            'lead_id' => $webBooking->lead_id,
        ]));
    }
}
