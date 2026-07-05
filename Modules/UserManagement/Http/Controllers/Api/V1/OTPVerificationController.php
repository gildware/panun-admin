<?php

namespace Modules\UserManagement\Http\Controllers\Api\V1;

use Carbon\CarbonInterval;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\CustomerModule\Traits\CustomerTrait;
use Modules\PaymentModule\Entities\Setting;
use Modules\SMSModule\Lib\SMS_gateway;
use Modules\UserManagement\Emails\OTPMail;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserVerification;
use Modules\PaymentModule\Traits\SmsGateway;

class OTPVerificationController extends Controller
{
    use CustomerTrait;

    public function __construct(
        private User $user,
        private UserVerification $userVerification
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required|max:255',
            'identity_type' => 'required|in:phone,email',
            'check_user' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $identity = $this->normalizeOtpIdentity($request['identity'], $request['identity_type']);
        $identityType = $request['identity_type'];

        if ($identityType === 'phone') {
            $identity = $this->canonicalPhoneIdentity($identity);
            $this->collapsePhoneVerificationIdentities($identity);
        }

        if ($request->check_user){
            $user = $identityType === 'phone'
                ? $this->findCustomerByContactPhone($identity)
                : $this->user->where('email', $identity)->whereIn('user_type', CUSTOMER_USER_TYPES)->first();
            if (!isset($user)) {
                return response()->json(response_formatter(DEFAULT_404), 404);
            }
            if ($identityType === 'phone' && $user->phone) {
                $identity = trim($user->phone);
            }
        }

        $dataValues = Setting::where('settings_type', 'sms_config')->get();
        $count = 0;
        foreach ($dataValues as $gateway) {
            $status = $gateway?->live_values['status'] ?? 0;
            if ($status == 1) {
                $count += 1;
            }
        }

        $firebaseOTPVerification = business_config('firebase_otp_verification','third_party');
        $firebaseOTPVerificationStatus = $firebaseOTPVerification ? $firebaseOTPVerification->is_active : 0;
        if ($firebaseOTPVerificationStatus == 1){
            $count++;
        }

        if ($identityType == 'phone' && $count < 1 && ! use_dummy_login_otp()) {
            return response()->json(response_formatter(SMS_GATEWAY_NOT_ACTIVE_400), 400);
        }

        //reset check
        $userVerification = $this->userVerification->where('identity', $identity)->first();
        $otpResendTime = business_config('otp_resend_time', 'otp_login_setup')?->live_values;
        if(isset($userVerification) &&  Carbon::parse($userVerification->created_at)->DiffInSeconds() < $otpResendTime){
            $time= $otpResendTime - Carbon::parse($userVerification->created_at)->DiffInSeconds();

            return response()->json(response_formatter([
                "response_code" => "auth_login_401",
                "message" => translate('Please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans(),
            ]), 401);
        }

        $otp = generate_login_otp();
        $this->userVerification->updateOrCreate([
                'identity' => $identity,
                'identity_type'=> $identityType
            ],
            [
            'identity' => $identity,
            'identity_type' => $identityType,
            'user_id' => null,
            'otp' => $otp,
            'expires_at' => now()->addMinute(3),
        ]);

        //send otp
        if (use_dummy_login_otp()) {
            $response = 'success';
        } elseif ($identityType == 'phone') {
            $publishedStatus = 0;
            $paymentPublishedStatus = config('get_payment_publish_status');
            if (isset($paymentPublishedStatus[0]['is_published'])) {
                $publishedStatus = $paymentPublishedStatus[0]['is_published'];
            }
            if($publishedStatus == 1){
                $response = SmsGateway::send($identity, $otp);
            }else{
                $response = SMS_gateway::send($identity, $otp);
            }

        } else if ($identityType == 'email') {
            $emailStatus = business_config('email_config_status', 'email_config')->live_values;
            if ($emailStatus){
                try {
                    Mail::to($identity)->send(new OTPMail($otp));
                    $response = 'success';
                } catch (Exception $exception) {
                    $response = 'error';
                }
            }
        } else {
            $response = 'error';
        }

        if ($response == 'success')
            return response()->json(response_formatter(DEFAULT_SENT_OTP_200), 200);
        else
            return response()->json(response_formatter(DEFAULT_SENT_OTP_FAILED_200), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
            'identity_type' => 'required',
            'otp' => 'required|max:6'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $identity = $this->normalizeOtpIdentity($request['identity'], $request['identity_type']);
        $identityType = $request['identity_type'];
        $otp = trim((string) $request['otp']);

        if ($identityType === 'phone') {
            $identity = $this->canonicalPhoneIdentity($identity);
        }

        $user = $identityType === 'phone'
            ? $this->findCustomerByContactPhone($identity)
            : $this->user->where('email', $identity)->whereIn('user_type', CUSTOMER_USER_TYPES)->first();
        if (!isset($user)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if ($identityType === 'phone' && $user->phone) {
            $identity = trim($user->phone);
        }

        $maxOtpHit = business_config('maximum_otp_hit', 'otp_login_setup')->test_values ?? 5;
        $maxOtpHitTime = business_config('otp_resend_time', 'otp_login_setup')->test_values ?? 60;// seconds
        $tempBlockTime = business_config('temporary_otp_block_time', 'otp_login_setup')->test_values ?? 600; // seconds

        $verify = $this->findOtpVerification($identity, $otp, $identityType);

        if (isset($verify)) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();
                return response()->json(response_formatter([
                    'response_code' => translate('auth_login_401'),
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ]), 403);

            }

            if ($identityType == 'email') {
                $user->is_email_verified = 1;
                $user->save();

            } else if ($identityType == 'phone') {
                $user->is_phone_verified = 1;
                $user->save();
            }

            if (user_can_use_customer_app($user)) {
                try {
                    $loginData = ['token' => $user->createToken(CUSTOMER_PANEL_ACCESS)->accessToken, 'is_active' => $user['is_active']];
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json(response_formatter([
                        'response_code' => 'default_500',
                        'message' => translate('Something went wrong'),
                    ]), 500);
                }

                $verify->delete();

                return response()->json(response_formatter(OTP_VERIFICATION_SUCCESS_200, $loginData), 200);
            }

            $verify->delete();

            return response()->json(response_formatter(OTP_VERIFICATION_SUCCESS_200), 200);
        }
        else{
            $verificationData = $this->findUserVerification($identity, $identityType);

            if(isset($verificationData)){
                if(isset($verificationData->temp_block_time ) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                    return response()->json(response_formatter([
                        'response_code' => translate('auth_login_401'),
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]), 403);
                }

                if($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->updated_at)->DiffInSeconds() >= $maxOtpHitTime){
                    $userVerify = $this->findUserVerification($identity, $identityType);
                    if ($userVerify) {
                        $userVerify->hit_count = 0;
                        $userVerify->is_temp_blocked = 0;
                        $userVerify->temp_block_time = null;
                        $userVerify->save();
                    }
                }


                if($verificationData->hit_count >= $maxOtpHit &&  Carbon::parse($verificationData->updated_at)->DiffInSeconds() < $maxOtpHitTime &&  $verificationData->is_temp_blocked == 0){
                    $userVerify = $this->findUserVerification($identity, $identityType);
                    if ($userVerify) {
                        $userVerify->is_temp_blocked = 1;
                        $userVerify->temp_block_time = now();
                        $userVerify->save();
                    }

                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                    return response()->json(response_formatter([
                        'response_code' => translate('auth_login_401'),
                        'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]), 403);
                }

            }

            $this->incrementOtpHitCount($identity, $identityType);
        }

        return response()->json(response_formatter(OTP_VERIFICATION_FAIL_403), 403);
    }

    public function loginVerifyOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $phone = $this->canonicalPhoneIdentity($this->normalizeOtpIdentity($request['phone'], 'phone'));
        $otp = trim((string) $request['otp']);

        $maxOtpHit = business_config('maximum_otp_hit', 'otp_login_setup')->test_values ?? 5;
        $maxOtpHitTime = business_config('otp_resend_time', 'otp_login_setup')->test_values ?? 60; // seconds
        $tempBlockTime = business_config('temporary_otp_block_time', 'otp_login_setup')->test_values ?? 600; // seconds

        $verify = $this->findOtpVerification($phone, $otp);

        if (isset($verify)) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();
                return response()->json(response_formatter([
                    'response_code' => translate('auth_login_401'),
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ]), 403);

            }

            $temporaryToken = Str::random(40);

            $isUserExist = $this->findCustomerByContactPhone($phone);
            if ($isUserExist) {
                $isUserExist->is_phone_verified = 1;
                $isUserExist->save();

                if ($isUserExist->is_active != 1) {
                    $verify->delete();

                    return response()->json(response_formatter(USER_INACTIVE_400), 400);
                }

                try {
                    $authData = self::authenticate($isUserExist, CUSTOMER_PANEL_ACCESS);
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json(response_formatter([
                        'response_code' => 'default_500',
                        'message' => translate('Something went wrong'),
                    ]), 500);
                }

                $verify->delete();

                return response()->json(response_formatter(AUTH_LOGIN_200, $authData), 200);
            } else {
                $verify->delete();

                return response()->json(response_formatter(AUTH_LOGIN_200, ['temporary_token' => $temporaryToken, 'status' => false], 200));
            }

        }
        else{
            $verificationData = $this->findUserVerification($phone);

            if(isset($verificationData)){
                if(isset($verificationData->temp_block_time ) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                    return response()->json(response_formatter([
                        'response_code' => translate('auth_login_401'),
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]), 403);
                }

                if($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->updated_at)->DiffInSeconds() >= $maxOtpHitTime){
                    $userVerify = $this->findUserVerification($phone);
                    if ($userVerify) {
                        $userVerify->hit_count = 0;
                        $userVerify->is_temp_blocked = 0;
                        $userVerify->temp_block_time = null;
                        $userVerify->save();
                    }
                }


                if($verificationData->hit_count >= $maxOtpHit &&  Carbon::parse($verificationData->updated_at)->DiffInSeconds() < $maxOtpHitTime &&  $verificationData->is_temp_blocked == 0){
                    $userVerify = $this->findUserVerification($phone);
                    if ($userVerify) {
                        $userVerify->is_temp_blocked = 1;
                        $userVerify->temp_block_time = now();
                        $userVerify->save();
                    }

                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                    return response()->json(response_formatter([
                        'response_code' => translate('auth_login_401'),
                        'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]), 403);
                }

            }
            $this->incrementOtpHitCount($phone);
        }

        return response()->json(response_formatter(OTP_VERIFICATION_FAIL_403), 403);
    }

    /**
     * Record a failed provider login OTP attempt and return the standard failure response.
     */
    public function providerLoginOtpFailed(Request $request): JsonResponse
    {
        $maxOtpHit = business_config('maximum_otp_hit', 'otp_login_setup')->test_values ?? 5;
        $maxOtpHitTime = business_config('otp_resend_time', 'otp_login_setup')->test_values ?? 60;
        $tempBlockTime = business_config('temporary_otp_block_time', 'otp_login_setup')->test_values ?? 600;

        $verificationData = $this->userVerification->where('identity', $request['phone'])->first();

        if (isset($verificationData)) {
            if (isset($verificationData->temp_block_time) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime) {
                $time = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();

                return response()->json(response_formatter([
                    'response_code' => translate('auth_login_401'),
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans(),
                ]), 403);
            }

            if ($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->updated_at)->DiffInSeconds() >= $maxOtpHitTime) {
                $userVerify = $this->findUserVerification($request['phone']);
                if ($userVerify) {
                    $userVerify->hit_count = 0;
                    $userVerify->is_temp_blocked = 0;
                    $userVerify->temp_block_time = null;
                    $userVerify->save();
                }
            }

            if ($verificationData->hit_count >= $maxOtpHit && Carbon::parse($verificationData->updated_at)->DiffInSeconds() < $maxOtpHitTime && $verificationData->is_temp_blocked == 0) {
                $userVerify = $this->findUserVerification($request['phone']);
                if ($userVerify) {
                    $userVerify->is_temp_blocked = 1;
                    $userVerify->temp_block_time = now();
                    $userVerify->save();
                }

                $time = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();

                return response()->json(response_formatter([
                    'response_code' => translate('auth_login_401'),
                    'message' => translate('Too_many_attempts. please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans(),
                ]), 403);
            }
        }

        $this->incrementOtpHitCount($request['phone']);

        return response()->json(response_formatter(OTP_VERIFICATION_FAIL_403), 403);
    }

    public function registrationWithOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'referral_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        try {
            $phone = $this->canonicalPhoneIdentity(
                $this->normalizeOtpIdentity($request['phone'], 'phone')
            );

            $existingUser = $this->findCustomerByContactPhone($phone);
            if ($existingUser) {
                if ($request['email']) {
                    $emailTaken = $this->user->where('email', $request['email'])
                        ->where('id', '!=', $existingUser->id)
                        ->exists();
                    if ($emailTaken) {
                        return response()->json(response_formatter(ALREADY_USE_EMAIL_ANOTHER_ACCOUNT), 403);
                    }
                }

                $existingUser->first_name = $request->first_name;
                $existingUser->last_name = $request->last_name;
                if ($request['email']) {
                    $existingUser->email = $request['email'];
                }
                $existingUser->is_phone_verified = 1;
                $existingUser->is_active = 1;
                $existingUser->save();

                if ($request['guest_id']) {
                    $this->updateAddressAndCartUser($existingUser->id, $request['guest_id']);
                }

                return response()->json(
                    response_formatter(AUTH_LOGIN_200, $this->authenticateCustomer($existingUser)),
                    200
                );
            }

            if ($request['email']) {
                $isEmailExist = $this->user->where(['email' => $request['email']])->first();

                if ($isEmailExist) {
                    return response()->json(response_formatter(ALREADY_USE_EMAIL_ANOTHER_ACCOUNT), 403);
                }
            }

            $referralResult = resolve_customer_referral_registration($request->input('referral_code'));
            if ($referralResult['error'] !== null) {
                return $referralResult['error'];
            }

            $user = $this->user->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request['email'],
                'phone' => $phone,
                'password' => bcrypt(rand(11111111, 99999999)),
                'user_type' => 'customer',
                'is_phone_verified' => 1,
                'is_active' => 1,
                'customer_app_access' => true,
                'referred_by' => $referralResult['referrer_id'],
            ]);

            if ($request['guest_id']) {
                $this->updateAddressAndCartUser($user->id, $request['guest_id']);
            }

            grant_customer_welcome_bonus($user);

            return response()->json(
                response_formatter(AUTH_LOGIN_200, $this->authenticateCustomer($user)),
                200
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(response_formatter([
                'response_code' => 'default_500',
                'message' => translate('Something went wrong'),
            ]), 500);
        }
    }

    public function firebaseAuthVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionInfo' => 'required',
            'phoneNumber' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $firebaseOTPVerification = (business_config('firebase_otp_verification', 'third_party'))?->live_values ?? ['status' => 0, 'web_api_key' => ''];
        $webApiKey = $firebaseOTPVerification ? $firebaseOTPVerification['web_api_key'] : '';

        $response = Http::post('https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key='. $webApiKey, [
            'sessionInfo' => $request->sessionInfo,
            'phoneNumber' => $request->phoneNumber,
            'code' => $request->code,
        ]);

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            return response()->json(response_formatter(OTP_VERIFICATION_FAIL_403), 403);
        }

        $user = $this->findCustomerByContactPhone($responseData['phoneNumber']);

        if ($request['guest_id'] && isset($user)){
            $this->updateAddressAndCartUser($user->id, $request['guest_id']);
        }

        if (isset($user)) {
            $user->is_phone_verified = 1;
            $user->save();

            try {
                $authData = self::authenticate($user, CUSTOMER_PANEL_ACCESS);
            } catch (\Throwable $e) {
                report($e);

                return response()->json(response_formatter([
                    'response_code' => 'default_500',
                    'message' => translate('Something went wrong'),
                ]), 500);
            }

            return response()->json(response_formatter(AUTH_LOGIN_200, $authData), 200);
        }

        $tempToken = Str::random(120);
        return response()->json(response_formatter(AUTH_LOGIN_200, ['temporary_token' => $tempToken, 'status' => false], 200));
    }

    protected function authenticate($user, $access_type): array
    {
        return ['token' => $user->createToken($access_type)->accessToken, 'is_active' => $user['is_active']];
    }

    private function authenticateCustomer(User $user): array
    {
        try {
            return self::authenticate($user, CUSTOMER_PANEL_ACCESS);
        } catch (\Throwable $e) {
            report($e);

            throw $e;
        }
    }

    private function findUserVerification(string $identity, string $identityType = 'phone'): ?UserVerification
    {
        if ($identityType === 'email') {
            return $this->userVerification->where('identity', trim($identity))->first();
        }

        $normalized = $this->normalizeOtpIdentity($identity, 'phone');
        $record = $this->userVerification->where('identity', $normalized)->first();
        if ($record) {
            return $record;
        }

        $digits = User::normalizeContactPhoneDigits($normalized);
        if ($digits === '') {
            return null;
        }

        return $this->userVerification
            ->get()
            ->first(function ($row) use ($digits) {
                return User::normalizeContactPhoneDigits((string) $row->identity) === $digits;
            });
    }

    private function findOtpVerification(string $identity, string $otp, string $identityType = 'phone'): ?UserVerification
    {
        $otp = trim($otp);

        if ($identityType === 'email') {
            $verify = $this->userVerification->where([
                'identity' => trim($identity),
                'otp' => $otp,
            ])->first();

            return $this->validOtpRecord($verify);
        }

        $normalized = $this->normalizeOtpIdentity($identity, 'phone');
        $verify = $this->userVerification->where(['identity' => $normalized, 'otp' => $otp])->first();
        if ($verify = $this->validOtpRecord($verify)) {
            return $verify;
        }

        $digits = User::normalizeContactPhoneDigits($normalized);
        if ($digits === '') {
            return null;
        }

        $verify = $this->userVerification
            ->where('otp', $otp)
            ->get()
            ->first(function ($row) use ($digits) {
                return User::normalizeContactPhoneDigits((string) $row->identity) === $digits;
            });

        return $this->validOtpRecord($verify);
    }

    private function validOtpRecord(?UserVerification $verify): ?UserVerification
    {
        if (! $verify) {
            return null;
        }

        if ($verify->expires_at && Carbon::parse($verify->expires_at)->isPast()) {
            $verify->delete();

            return null;
        }

        return $verify;
    }

    private function findCustomerByContactPhone(string $phone): ?User
    {
        return User::findByContactPhoneScoped($phone, CUSTOMER_USER_TYPES);
    }

    private function canonicalPhoneIdentity(string $phone): string
    {
        $normalized = $this->normalizeOtpIdentity($phone, 'phone');
        $user = $this->findCustomerByContactPhone($normalized);

        if ($user && $user->phone) {
            return trim($user->phone);
        }

        return $normalized;
    }

    private function collapsePhoneVerificationIdentities(string $canonicalIdentity): void
    {
        $digits = User::normalizeContactPhoneDigits($canonicalIdentity);
        if ($digits === '') {
            return;
        }

        foreach ($this->userVerification->where('identity_type', 'phone')->get() as $row) {
            if (User::normalizeContactPhoneDigits((string) $row->identity) === $digits
                && trim((string) $row->identity) !== $canonicalIdentity) {
                $row->delete();
            }
        }
    }

    private function normalizeOtpIdentity(string $identity, string $identityType): string
    {
        $identity = trim($identity);
        if ($identityType !== 'phone') {
            return $identity;
        }

        return preg_replace('/\s+/', '', $identity) ?? $identity;
    }

    private function incrementOtpHitCount(string $identity, string $identityType = 'phone'): void
    {
        $userVerify = $this->findUserVerification($identity, $identityType);
        if (!$userVerify) {
            return;
        }

        $userVerify->hit_count = ($userVerify->hit_count ?? 0) + 1;
        $userVerify->temp_block_time = null;
        $userVerify->save();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkExistingCustomer(Request $request): JsonResponse
    {
        $newUserValidator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ]);

        if ($newUserValidator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($newUserValidator)), 400);
        }

        if ($this->findCustomerByContactPhone($request['phone'])) {
            return response()->json(response_formatter(USER_EXIST_400, null, [["error_code" => "phone", "message" => translate('Phone already taken')]]), 400);
        }
        return response()->json(response_formatter(DEFAULT_200, null), 200);
    }
}
