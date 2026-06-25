<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProvidersWithdrawMethodsData;
use Modules\ProviderManagement\Entities\WithdrawRequest;
use Modules\TransactionModule\Entities\Transaction;
use Modules\TransactionModule\Entities\WithdrawalMethod;
use Modules\UserManagement\Entities\User;
use Modules\ProviderManagement\Traits\WithdrawTrait;

class WithdrawController extends Controller
{
    use WithdrawTrait;
    protected User $user;
    protected Provider $provider;
    protected WithdrawRequest $withdraw_request;
    protected Transaction $transaction;
    protected WithdrawalMethod $withdrawalMethod;
    protected ProvidersWithdrawMethodsData $providersWithdrawMethodsData;

    public function __construct(User $user, Provider $provider, WithdrawRequest $withdraw_request, Transaction $transaction, WithdrawalMethod $withdrawalMethod, ProvidersWithdrawMethodsData $providersWithdrawMethodsData)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->withdraw_request = $withdraw_request;
        $this->transaction = $transaction;
        $this->withdrawalMethod = $withdrawalMethod;
        $this->providersWithdrawMethodsData = $providersWithdrawMethodsData;
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'string' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $withdrawRequests = $this->withdraw_request
            ->with(['user.account', 'request_updater.account'])
            ->where('user_id', $request->user()->id)
            ->latest()->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $totalCollectedCash = $this->transaction
            ->where('from_user_id', $request->user()->id)
            ->where('trx_type', TRANSACTION_TYPE[1]['key'])
            ->sum('debit');

        return response()->json(response_formatter(DEFAULT_200, ['withdraw_requests' => $withdrawRequests, 'total_collected_cash' => $totalCollectedCash]), 200);
    }

    /**
     * withdraw amount
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerUser = $this->user->with(['account', 'provider'])->find($request->user()->id);
        $account = $providerUser->account;
        $receivable = (float) $account->account_receivable;
        $payable = (float) $account->account_payable;
        $providerInfo = $providerUser->provider;
        $withdrawable = provider_effective_withdrawable_balance(
            (string) $providerInfo->id,
            (string) $request->user()->id,
            $receivable,
            $payable
        );

        if ($request['amount'] > $withdrawable) {
            return response()->json(response_formatter(DEFAULT_400), 200);
        }

        if ($payable > 0 && $receivable > $payable && $providerInfo) {
            $providerInfo->is_suspended = 0;
            $providerInfo->save();
        }

        //min max check
        $withdrawRequestAmount = [
            'minimum' => (float)(business_config('minimum_withdraw_amount', 'business_information'))->live_values ?? null,
            'maximum' => (float)(business_config('maximum_withdraw_amount', 'business_information'))->live_values ?? null,
        ];

        if ($withdrawable < $request['amount'] || $request['amount'] < $withdrawRequestAmount['minimum'] || $request['amount'] > $withdrawRequestAmount['maximum']) {
            return response()->json(response_formatter(DEFAULT_400), 200);
        }


        DB::transaction(function () use ($account, $request, $payable) {
            withdrawRequestTransaction($request->user()->id, $request['amount']);

            //admin payment transaction
            if ($payable > 0){
                $provider = Provider::where('user_id', $request->user()->id)->first();

                //adjust
                withdrawRequestAcceptForAdjustTransaction($request->user()->id, $payable);
                collectCashTransaction($provider->id, $payable);
            }

            $this->withdraw_request->create([
                'user_id' => $request->user()->id,
                'request_updated_by' => $request->user()->id,
                'amount' => $request['amount'],
                'request_status' => 'pending',
                'is_paid' => 0,
                'note' => $request['note'],
            ]);
        });

        return response()->json(response_formatter(DEFAULT_200), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function paymentInformationIndex(Request $request): JsonResponse
    {
        $providerId = auth('api')->user()->id;

        $methods = $this->providersWithdrawMethodsData->where('provider_id', $providerId)
            ->latest()
            ->get();

        return response()->json(response_formatter(DEFAULT_200, $methods), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function paymentInformationStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'withdrawal_method_id' => 'required',
            'method_field_data' => 'required|array',
            'is_active' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $withdrawalMethod = $this->withdrawalMethod->find($request['withdrawal_method_id']);

        if (!$withdrawalMethod) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $fields = array_column($withdrawalMethod->method_fields ?? [], 'input_name');
        $submittedData = $request->method_field_data;

        foreach ($fields as $field) {
            if (!array_key_exists($field, $submittedData)) {
                return response()->json(response_formatter(DEFAULT_400, "Missing field: {$field}"), 400);
            }
        }

        $hasExisting = $this->providersWithdrawMethodsData->where('provider_id', $request->user()->id)->exists();

        $methods = $this->providersWithdrawMethodsData;
        $methods->provider_id = auth('api')->user()->id;
        $methods->withdrawal_method_id = $withdrawalMethod->id;
        $methods->method_name = $withdrawalMethod->method_name;
        $methods->method_field_data = $submittedData;
        $methods->is_default = !$hasExisting;
        $methods->is_active = (bool) $request->is_active;
        $methods->save();

        return response()->json(response_formatter(DEFAULT_STORE_200, $methods), 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function paymentInformationEdit($id): JsonResponse
    {
        $method = $this->providersWithdrawMethodsData->where('id', $id)
            ->where('provider_id', auth('api')->user()->id)
            ->first();

        if (!$method) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $method), 200);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function paymentInformationUpdate(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'withdrawal_method_id' => 'required',
            'method_field_data' => 'required|array',
            'is_active' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $method = $this->providersWithdrawMethodsData->where('id', $id)
            ->where('provider_id', auth('api')->user()->id)
            ->first();

        if (!$method) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $withdrawalMethod = $this->withdrawalMethod->find($request->withdrawal_method_id);

        if (!$withdrawalMethod) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $fields = array_column($withdrawalMethod->method_fields ?? [], 'input_name');
        $submittedData = $request->method_field_data;

        foreach ($fields as $field) {
            if (!array_key_exists($field, $submittedData)) {
                return response()->json(response_formatter(DEFAULT_400, "Missing field: {$field}"), 400);
            }
        }

        $method->update([
            'withdrawal_method_id' => $withdrawalMethod->id,
            'method_name' => $withdrawalMethod->method_name,
            'method_field_data' => $submittedData,
            'is_active' => (bool) $request->is_active
        ]);

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $method), 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function paymentInformationStatusUpdate($id): JsonResponse
    {
        $method = $this->providersWithdrawMethodsData->where('id', $id)
            ->where('provider_id', auth('api')->user()->id)
            ->first();

        if (!$method) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $method->is_active = !$method->is_active;
        $method->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $method), 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function paymentInformationDefaultStatusUpdate($id): JsonResponse
    {;
        $providerId = auth('api')->user()->id;

        $this->providersWithdrawMethodsData->where('provider_id', $providerId)
            ->update(['is_default' => false]);

        $method = $this->providersWithdrawMethodsData->where('id', $id)
            ->where('provider_id', $providerId)
            ->first();

        if (!$method) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $method->is_default = true;
        $method->is_active = true;
        $method->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $method), 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function paymentInformationDelete($id): JsonResponse
    {
        $method = $this->providersWithdrawMethodsData->where('id', $id)
            ->where('provider_id', auth('api')->user()->id)
            ->first();


        if (!$method) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $method->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }


}
