<?php

namespace Modules\PaymentModule\Http\Controllers;

use App\Models\User;
use Razorpay\Api\Api;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\PaymentModule\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\PaymentModule\Services\RazorpayCheckoutCompletionService;
use App\Lib\PaymentRequestGuard;

class RazorPayController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;
    private RazorpayCheckoutCompletionService $completionService;

    public function __construct(
        PaymentRequest $payment,
        User $user,
        RazorpayCheckoutCompletionService $completionService
    ) {
        $config = $this->payment_config('razor_pay', 'payment_config');
        $razor = false;
        if (!is_null($config) && $config->mode == 'live') {
            $razor = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = json_decode($config->test_values);
        }

        if ($razor) {
            $config = array(
                'api_key' => $razor->api_key,
                'api_secret' => $razor->api_secret,
                'webhook_secret' => $razor->webhook_secret ?? env('RAZORPAY_WEBHOOK_SECRET'),
            );
            Config::set('razor_config', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
        $this->completionService = $completionService;
    }

    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($data['payer_information']);

        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "my_business";
            $business_logo = $business->business_logo ?? url('/');
        } else {
            $business_name = "my_business";
            $business_logo = url('/');
        }

        $business_name =  (business_config('business_name', 'business_information'))->live_values ?? 'my_business';
        $business_logo = getBusinessSettingsImageFullPath(key: 'business_logo', settingType: 'business_information', path: 'business/',  defaultPath : 'assets/admin-module/img/placeholder.png');

        $isApp = $data->payment_platform === 'app';
        $paymentAccessToken = PaymentRequestGuard::storedAccessToken($data) ?? '';

        return view('paymentmodule::razor-pay', compact('data', 'payer', 'business_logo', 'business_name', 'isApp', 'paymentAccessToken'));
    }

    public function buildNativePrepareResponse(Request $request, string $paymentId): JsonResponse
    {
        $data = $this->payment::where(['id' => $paymentId])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json([
                'status' => false,
                'message' => translate('Payment request not found'),
            ], 404);
        }

        if (! PaymentRequestGuard::assertCanAccessPaymentRequest($request, $data)) {
            return response()->json([
                'status' => false,
                'message' => translate('Unauthorized payment request'),
            ], 403);
        }

        $payer = json_decode($data['payer_information']);
        $business_name = (business_config('business_name', 'business_information'))->live_values ?? 'my_business';
        $business_logo = getBusinessSettingsImageFullPath(
            key: 'business_logo',
            settingType: 'business_information',
            path: 'business/',
            defaultPath: 'assets/admin-module/img/placeholder.png'
        );

        try {
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));

            $razorpayOrder = $api->order->create(
                $this->completionService->buildOrderCreatePayload($data)
            );

            return response()->json([
                'status' => true,
                'payment_request_id' => $data->id,
                'order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'key' => config('razor_config.api_key'),
                'name' => $business_name,
                'description' => (string)$data->payment_amount,
                'image' => $business_logo,
                'prefill' => [
                    'name' => $payer->name ?? '',
                    'email' => $payer->email ?? '',
                    'contact' => $payer->phone ?? '',
                ],
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function nativePrepare(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        return $this->buildNativePrepareResponse($request, $request['payment_id']);
    }

    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $input = $request->all();

        if (count($input) && !empty($input['razorpay_payment_id'])) {
            try {
                $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
                $paymentRequest = $this->payment::where(['id' => $request['payment_id']])->first();

                if (! $paymentRequest) {
                    return $this->payment_response($this->payment::where(['id' => $request['payment_id']])->first(), 'fail');
                }

                if ((int) $paymentRequest->is_paid === 1) {
                    return $this->payment_response($paymentRequest, 'success');
                }

                if (!empty($input['razorpay_order_id']) && !empty($input['razorpay_signature'])) {
                    $api->utility->verifyPaymentSignature([
                        'razorpay_order_id' => $input['razorpay_order_id'],
                        'razorpay_payment_id' => $input['razorpay_payment_id'],
                        'razorpay_signature' => $input['razorpay_signature'],
                    ]);
                }

                $payment = $api->payment->fetch($input['razorpay_payment_id']);
                if (! isset($payment['status']) || ! in_array($payment['status'], ['authorized', 'captured'], true)) {
                    return $this->payment_response($paymentRequest, 'fail');
                }

                if ((int) ($paymentRequest->is_paid ?? 0) === 1) {
                    $data = $this->payment::where(['id' => $request['payment_id']])->first();

                    return $this->payment_response($data, 'success');
                }

                if ($payment['status'] === 'captured') {
                    $result = $this->completionService->completeIfValid(
                        $paymentRequest,
                        $input['razorpay_payment_id'],
                        $payment->toArray(),
                        $api,
                        false
                    );

                    if (in_array($result, [
                        RazorpayCheckoutCompletionService::STATUS_COMPLETED,
                        RazorpayCheckoutCompletionService::STATUS_ALREADY_COMPLETED,
                    ], true)) {
                        $data = $this->payment::where(['id' => $request['payment_id']])->first();

                        return $this->payment_response($data, 'success');
                    }

                    return $this->payment_response($paymentRequest, 'fail');
                }

                $result = $this->completionService->completeIfValid(
                    $paymentRequest,
                    $input['razorpay_payment_id'],
                    $payment->toArray(),
                    $api,
                    true
                );

                if ($result === RazorpayCheckoutCompletionService::STATUS_ALREADY_COMPLETED) {
                    $data = $this->payment::where(['id' => $request['payment_id']])->first();

                    return $this->payment_response($data, 'success');
                }

                if ($result !== RazorpayCheckoutCompletionService::STATUS_COMPLETED) {
                    return $this->payment_response($paymentRequest, 'fail');
                }

                $data = $this->payment::where(['id' => $request['payment_id']])->first();

                return $this->payment_response($data, 'success');
            } catch (\Exception) {
                $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
                if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                    call_user_func($payment_data->failure_hook, $payment_data);
                }
                return $this->payment_response($payment_data, 'fail');
            }
        }
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    public function createOrder(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $request->validate([
            'payment_amount' => 'required|numeric',
            'currency_code' => 'required|string',
            'payment_request_id' => 'required|uuid',
            'access_token' => 'nullable|string',
        ]);

        try {
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $paymentRequest = $this->payment::where(['id' => $request['payment_request_id']])->first();

            if (! $paymentRequest) {
                return response()->json([
                    'status' => false,
                    'message' => translate('Payment request not found'),
                ], 404);
            }

            if (! PaymentRequestGuard::assertCanAccessPaymentRequest($request, $paymentRequest)) {
                return response()->json([
                    'status' => false,
                    'message' => translate('Unauthorized payment request'),
                ], 403);
            }

            $razorpayOrder = $api->order->create(
                $this->completionService->buildOrderCreatePayload($paymentRequest)
            );

            return response()->json([
                'status' => true,
                'payment_request_id' => $request['payment_request_id'],
                'order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency']
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function verifyPayment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $nativeSdk = $request->boolean('native_sdk');

        try {
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $paymentRequest = $this->payment::where(['id' => $request['payment_request_id']])->first();

            if (! $paymentRequest) {
                return $this->payment_response($paymentRequest, 'fail', $nativeSdk);
            }

            if (! PaymentRequestGuard::assertCanAccessPaymentRequest($request, $paymentRequest)) {
                if ($nativeSdk) {
                    return response()->json([
                        'status' => false,
                        'flag' => 'fail',
                        'message' => translate('Unauthorized payment request'),
                    ], 403);
                }

                return $this->payment_response($paymentRequest, 'fail', $nativeSdk);
            }

            if ((int) $paymentRequest->is_paid === 1) {
                return $this->payment_response($paymentRequest, 'success', $nativeSdk);
            }

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request['order_id'],
                'razorpay_payment_id' => $request['payment_id'],
                'razorpay_signature' => $request['signature'],
            ]);

            $payment = $api->payment->fetch($request['payment_id']);

            if ($payment && isset($payment['status']) && $payment['status'] == 'captured') {
                $result = $this->completionService->completeIfValid(
                    $paymentRequest,
                    (string) $request['payment_id'],
                    $payment->toArray(),
                    $api,
                    false
                );

                if (in_array($result, [
                    RazorpayCheckoutCompletionService::STATUS_COMPLETED,
                    RazorpayCheckoutCompletionService::STATUS_ALREADY_COMPLETED,
                ], true)) {
                    $data = $this->payment::where(['id' => $request['payment_request_id']])->first();

                    return $this->payment_response($data, 'success', $nativeSdk);
                }
            }
        } catch (\Exception $exception) {
            if ($nativeSdk) {
                return response()->json([
                    'status' => false,
                    'flag' => 'fail',
                    'message' => $exception->getMessage(),
                ], 400);
            }
        }

        $paymentData = $this->payment::where(['id' => $request['payment_request_id']])->first();
        if (isset($paymentData) && function_exists($paymentData->failure_hook)) {
            call_user_func($paymentData->failure_hook, $paymentData);
        }
        return $this->payment_response($paymentData, 'fail', $nativeSdk);
    }

    public function callback(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $input = $request->all();
        $data_id = base64_decode($request?->payment_data);

        if (count($input) && !empty($input['razorpay_payment_id'])) {
            try {
                $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));

                if (!empty($input['razorpay_order_id']) && !empty($input['razorpay_signature'])) {
                    $api->utility->verifyPaymentSignature([
                        'razorpay_order_id' => $input['razorpay_order_id'],
                        'razorpay_payment_id' => $input['razorpay_payment_id'],
                        'razorpay_signature' => $input['razorpay_signature'],
                    ]);
                } else {
                    return redirect()->route('payment-fail');
                }

                $payment = $api->payment->fetch($input['razorpay_payment_id']);
                if (!isset($payment['status']) || $payment['status'] !== 'captured') {
                    return redirect()->route('payment-fail');
                }

                $paymentRequest = $this->payment::where(['id' => $data_id])->first();
                if (! $paymentRequest) {
                    return redirect()->route('payment-fail');
                }

                if ((int) $paymentRequest->is_paid === 1) {
                    return $this->payment_response($paymentRequest, 'success');
                }

                $result = $this->completionService->completeIfValid(
                    $paymentRequest,
                    $input['razorpay_payment_id'],
                    $payment->toArray(),
                    $api,
                    false
                );

                if (! in_array($result, [
                    RazorpayCheckoutCompletionService::STATUS_COMPLETED,
                    RazorpayCheckoutCompletionService::STATUS_ALREADY_COMPLETED,
                ], true)) {
                    return redirect()->route('payment-fail');
                }

                $data = $this->payment::where(['id' => $data_id])->first();

                return $this->payment_response($data, 'success');
            } catch (\Exception) {
                return redirect()->route('payment-fail');
            }
        }
        return redirect()->route('payment-fail');
    }

    public function cancel(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($payment_data, 'fail', $request->boolean('native_sdk'));
    }
}
