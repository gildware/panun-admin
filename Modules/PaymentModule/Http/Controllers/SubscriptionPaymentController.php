<?php

namespace Modules\PaymentModule\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Modules\PaymentModule\Library\Payer;
use Modules\PaymentModule\Library\Payment as Payment;
use Modules\PaymentModule\Library\Receiver;
use Modules\PaymentModule\Traits\NativeRazorpayRedirect;
use Modules\PaymentModule\Traits\Payment as PaymentTrait;
use Modules\UserManagement\Entities\User;
use App\Lib\PaymentAccessToken;
use Modules\BusinessSettingsModule\Entities\SubscriptionPackage;

class SubscriptionPaymentController extends Controller
{
    use NativeRazorpayRedirect;
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse|Redirector|RedirectResponse|Application
     */
    public function index(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:' . implode(',', array_column(GATEWAYS_PAYMENT_METHODS, 'key')),
            'package_id' => 'required|uuid',
            'provider_id' => 'required|uuid',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->has('callback')) return redirect($request['callback'] . '?flag=fail');
            else return response()->json(response_formatter(DEFAULT_400), 400);
        }

        $customer_user_id = PaymentAccessToken::resolve($request['access_token']) ?? '';
        if ($customer_user_id === '') {
            if ($request->has('callback')) {
                return redirect($request['callback'] . '?flag=fail');
            }

            return response()->json(response_formatter(DEFAULT_401), 401);
        }

        $package = SubscriptionPackage::query()
            ->where('id', $request['package_id'])
            ->where('is_active', 1)
            ->first();

        if (! $package) {
            if ($request->has('callback')) {
                return redirect($request['callback'] . '?flag=fail');
            }

            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $vatPercentage = (int) ((business_config('subscription_vat', 'subscription_Setting'))->live_values ?? 0);
        $expectedAmount = round((float) $package->price + ((float) $package->price * ($vatPercentage / 100)), 2);
        $requestedAmount = round((float) $request['amount'], 2);

        if (abs($requestedAmount - $expectedAmount) > 0.009) {
            if ($request->has('callback')) {
                return redirect($request['callback'] . '?flag=fail');
            }

            return response()->json(response_formatter(DEFAULT_400), 400);
        }

        $customer = User::find($customer_user_id);
        $payer = new Payer($customer['first_name'] . ' ' . $customer['last_name'], $customer['email'], $customer['phone'], '');
        $payment_info = new Payment(
            success_hook: 'subscription_success',
            failure_hook: 'subscription_fail',
            currency_code: currency_code(),
            payment_method: $request['payment_method'],
            payment_platform: $request['payment_platform'],
            payer_id: $customer_user_id,
            receiver_id: null,
            additional_data: $request->all(),
            payment_amount: $expectedAmount,
            external_redirect_link: $request['callback'] ?? null,
            attribute: 'provider_id',
            attribute_id: time()
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');
        $redirect_link = PaymentTrait::generate_link($payer, $payment_info, $receiver_info);
        return $this->respondPaymentRedirect($request, $redirect_link);
    }
}
