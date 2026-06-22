<?php

namespace Modules\PaymentModule\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\PaymentModule\Traits\Processor;
use Unicodeveloper\Paystack\Facades\Paystack;
use Modules\PaymentModule\Entities\PaymentRequest;

class PaystackController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('paystack', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $config = array(
                'publicKey' => env('PAYSTACK_PUBLIC_KEY', $values->public_key),
                'secretKey' => env('PAYSTACK_SECRET_KEY', $values->secret_key),
                'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
                'merchantEmail' => env('MERCHANT_EMAIL', $values->merchant_email),
            );
            Config::set('paystack', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request)
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

        $reference = Paystack::genTranxRef();

        return view('paymentmodule::paystack', compact('data', 'payer', 'reference'));
    }

    public function redirectToGateway(Request $request)
    {
        return Paystack::getAuthorizationUrl()->redirectNow();
    }

    public function handleGatewayCallback(Request $request)
    {
        $paymentDetails = Paystack::getPaymentData();
        if ($paymentDetails['status'] == true) {
            $metadata = $paymentDetails['data']['metadata'] ?? [];
            $paymentRequestId = $metadata['payment_request_id'] ?? null;

            $data = $paymentRequestId
                ? $this->payment::where(['id' => $paymentRequestId])->first()
                : $this->payment::where(['attribute_id' => $metadata['attribute_id'] ?? null])->first();

            $paidAmount = isset($paymentDetails['data']['amount'])
                ? round(((int) $paymentDetails['data']['amount']) / 100, 2)
                : null;

            $status = $this->completeVerifiedPayment(
                $data,
                (string) $request['trxref'],
                'paystack',
                $paidAmount
            );

            if ($this->paymentCompletionSucceeded($status)) {
                $data = $data?->fresh() ?? $this->payment::find($paymentRequestId);

                return $this->payment_response($data, 'success');
            }
        }

        $metadata = is_array($paymentDetails['data']['metadata'] ?? null)
            ? $paymentDetails['data']['metadata']
            : [];
        $payment_data = ! empty($metadata['payment_request_id'])
            ? $this->payment::where(['id' => $metadata['payment_request_id']])->first()
            : $this->payment::where(['attribute_id' => $metadata['attribute_id'] ?? null])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }
}
