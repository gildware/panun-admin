<?php

namespace Modules\PaymentModule\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Validator;
use Modules\PaymentModule\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Modules\PaymentModule\Entities\PaymentRequest;

class SenangPayController extends Controller
{
    use Processor;

    private $config_values;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('senang_pay', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($payment_data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($payment_data['payer_information']);
        $config = $this->config_values;
        session()->put('payment_id', $payment_data->id);
        return view('paymentmodule::senang-pay', compact('payment_data', 'payer', 'config'));
    }

    public function return_senang_pay(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $orderId = (string) ($request['order_id'] ?? session()->get('payment_id') ?? '');
        $paymentData = $orderId !== ''
            ? $this->payment::where(['id' => $orderId])->first()
            : null;

        $secretKey = (string) ($this->config_values->secret_key ?? '');
        $statusId = (string) ($request['status_id'] ?? '');
        $transactionId = (string) ($request['transaction_id'] ?? '');
        $msg = (string) ($request['msg'] ?? '');
        $receivedHash = (string) ($request['hash'] ?? '');

        $expectedHash = md5($secretKey . urldecode($statusId) . urldecode($orderId) . urldecode($transactionId) . urldecode($msg));
        $hashValid = $secretKey !== '' && hash_equals($expectedHash, $receivedHash);

        if ($hashValid && $statusId === '1' && $paymentData) {
            $paidAmount = $request->filled('amount')
                ? round((float) $request->input('amount'), 2)
                : round((float) $paymentData->payment_amount, 2);

            $status = $this->completeVerifiedPayment(
                $paymentData,
                $transactionId,
                'senang_pay',
                $paidAmount
            );

            if ($this->paymentCompletionSucceeded($status)) {
                $data = $this->payment::where(['id' => $paymentData->id])->first();

                return $this->payment_response($data, 'success');
            }
        }

        if (isset($paymentData) && function_exists($paymentData->failure_hook)) {
            call_user_func($paymentData->failure_hook, $paymentData);
        }

        return $this->payment_response($paymentData, 'fail');
    }
}
