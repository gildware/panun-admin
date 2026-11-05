<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ProviderManagement\Services\ProviderAgreementService;

class ProviderAgreementController extends Controller
{
    public function __construct(
        protected ProviderAgreementService $agreementService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(
            response_formatter(DEFAULT_200, $this->agreementService->statusFor($provider)),
            200
        );
    }

    public function sign(Request $request): JsonResponse
    {
        $provider = $request->user()?->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if ($this->agreementService->hasSigned($provider)) {
            return response()->json(
                response_formatter(DEFAULT_200, $this->agreementService->statusFor($provider)),
                200
            );
        }

        $imageMimes = implode(',', array_column(IMAGEEXTENSION, 'key'));
        $validator = Validator::make($request->all(), [
            'agreement_signature' => 'required|image|max:' . uploadMaxFileSizeInKB('image') . '|mimes:' . $imageMimes,
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        try {
            $status = $this->agreementService->sign(
                $provider,
                $request->file('agreement_signature')
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(response_formatter(DEFAULT_FAIL_200, null, [[
                'error_code' => 'agreement_signature',
                'message' => translate('Provider agreement upload failed'),
            ]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_STORE_200, $status), 200);
    }
}
