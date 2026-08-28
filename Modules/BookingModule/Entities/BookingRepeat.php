<?php

namespace Modules\BookingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Mail;
use Modules\BookingModule\Http\Traits\BookingScopes;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\BusinessSettingsModule\Emails\CashInHandOverflowMail;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\Serviceman;

class BookingRepeat extends Model
{
    use HasFactory, HasUuid, BookingTrait, BookingScopes;

    protected $casts = [
        'is_paid' => 'integer',
        'is_verified' => 'integer',
        'total_booking_amount' => 'float',
        'total_tax_amount' => 'float',
        'total_discount_amount' => 'float',
        'total_campaign_discount_amount' => 'float',
        'total_coupon_discount_amount' => 'float',
        'is_checked' => 'integer',
        'additional_charge' => 'float',
        'additional_tax_amount' => 'float',
        'additional_discount_amount' => 'float',
        'additional_campaign_discount_amount' => 'float',
        'evidence_photos' => 'array',
        'extra_fee' => 'float',
        'total_referral_discount_amount' => 'float',
        'provider_payment_confirmed_at' => 'datetime',
    ];

    protected $fillable = [
        'id',
        'readable_id',
        'provider_id',
        'booking_status',
        'is_paid',
        'payment_method',
        'transaction_id',
        'total_booking_amount',
        'total_tax_amount',
        'total_discount_amount',
        'service_schedule',
        'visit_remarks',
        'service_address_id',
        'created_at',
        'updated_at',
        'category_id',
        'sub_category_id',
        'serviceman_id',
        'total_campaign_discount_amount',
        'total_coupon_discount_amount',
        'coupon_code',
        'is_checked',
        'additional_charge',
        'additional_tax_amount',
        'additional_discount_amount',
        'additional_campaign_discount_amount',
        'evidence_photos',
        'booking_otp',
        'is_verified',
        'provider_payment_confirmed_at',
        'service_address_location',
        'service_location',
    ];

    protected $appends = ['evidence_photos_full_path', 'skipNotification'];

    protected $hidden = ['skipNotification'];

    public function getSkipNotificationAttribute()
    {
        return $this->attributes['skipNotification'] ?? false;
    }

    public function setSkipNotificationAttribute($value)
    {
        $this->attributes['skipNotification'] = $value;
    }

    protected static function newFactory()
    {
        return \Modules\BookingModule\Database\factories\BookingRepeatFactory::new();
    }
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function serviceman(): BelongsTo
    {
        return $this->belongsTo(Serviceman::class, 'serviceman_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(BookingRepeatDetails::class);
    }

    public function extra_services(): HasMany
    {
        return $this->hasMany(BookingExtraService::class, 'booking_repeat_id');
    }

    public function details_amounts(): hasMany
    {
        return $this->hasMany(BookingDetailsAmount::class);
    }

    public function booking_details_amounts(): hasOne
    {
        return $this->hasOne(BookingDetailsAmount::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class, 'booking_repeat_id');
    }

    public function scheduleHistories(): HasMany
    {
        return $this->hasMany(BookingScheduleHistory::class, 'booking_repeat_id');
    }

    public function repeatHistories(): HasMany
    {
        return $this->hasMany(BookingRepeatHistory::class, 'booking_repeat_id')->latest();
    }

    public function getEvidencePhotosFullPathAttribute()
    {
        $evidenceImages = $this->evidence_photos ?? [];
        $defaultImagePath = asset('assets/admin-module/img/media/user.png');
        if (empty($evidenceImages)) {
            if (request()->is('api/*')) {
                $defaultImagePath = null;
            }
            return $defaultImagePath ? [$defaultImagePath] : [];
        }

        $path = 'booking/evidence/';

        return getIdentityImageFullPath(identityImages: $evidenceImages, path: $path, defaultPath: $defaultImagePath);
    }

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {
            $booking_notification_status = business_config('booking', 'notification_settings')->live_values;
            $permission = isNotificationActive(null, 'booking', 'notification', 'user');
            $providerPermission = isNotificationActive(null, 'booking', 'notification', 'provider');
            $servicemanPermission = isNotificationActive(null, 'booking', 'notification', 'serviceman');

            if ($model->isDirty('booking_status')) {
                $key = null;
                if ($model->booking_status == 'ongoing') {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_status_change',
                            'settings_type' => 'customer_notification'
                        ];
                    }
                    if ($providerPermission){
                        $notifications[] = [
                            'key' => 'booking_status_change',
                            'settings_type' => 'provider_notification'
                        ];
                    }
                    if ($servicemanPermission) {
                        $notifications[] = [
                            'key' => 'ongoing_booking',
                            'settings_type' => 'serviceman_notification'
                        ];
                    }
                } elseif ($model->booking_status == 'completed') {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_complete',
                            'settings_type' => 'customer_notification'
                        ];
                    }
                    if ($providerPermission) {
                        $notifications[] = [
                            'key' => 'booking_complete',
                            'settings_type' => 'provider_notification'
                        ];
                    }
                    if ($servicemanPermission) {
                        $notifications[] = [
                            'key' => 'booking_complete',
                            'settings_type' => 'serviceman_notification'
                        ];
                    }

                    $visitPaidInFull = round((float) get_booking_total_paid($model), 2) + 0.005
                        >= round((float) get_booking_total_amount($model), 2);
                    if ($visitPaidInFull) {
                        $model->is_paid = 1;
                    }

                    $provider = $model->provider;

                    if ($provider) {
                        $model->update_admin_commission($model, (float) ($model->total_booking_amount ?? 0), $model->provider_id);
                    }


                    if (!$model?->booking?->is_guest && $model?->booking?->customer) {
                        $model->referral_earning_calculation($model?->booking?->customer_id, $model?->booking?->zone_id, (string) $model->id);

                        $model->loyaltyPointCalculation($model?->booking?->customer_id, $model);

                        if ($model->total_referral_discount_amount > 0){
                            referralEarningTransactionAfterBookingRepeatCompleteFirst($model->booking->customer, $model->total_referral_discount_amount, $model->id);
                        }
                    }

                    //================ Transactions for Booking ================

                    if ($model?->provider && $visitPaidInFull) {
                        if ($model->payment_method == 'cash_after_service') {
                            completeBookingRepeatTransactionForCashAfterService($model);
                        } else {
                            if ($model->additional_charge == 0) {
                                completeBookingRepeatTransactionForDigitalPayment($model);
                            }

//                            if ($model->additional_charge > 0) {
//                                completeBookingTransactionForDigitalPaymentAndExtraService($model);
//                            }
                        }

                        provider_apply_cash_limit_suspension_for_provider($provider);
                    }

                    if ($model?->provider
                        && $model->isDirty('booking_status')
                        && $model->getOriginal('booking_status') !== 'completed') {
                        record_booking_completion_provider_commission_payable($model);
                    }

                } elseif ($model->booking_status == 'canceled' && $model->skipNotification) {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_status_change',
                            'settings_type' => 'customer_notification'
                        ];
                    }
                    if ($providerPermission) {
                        $notifications[] = [
                            'key' => 'booking_status_change',
                            'settings_type' => 'provider_notification'
                        ];
                    }
                    if ($servicemanPermission) {
                        $notifications[] = [
                            'key' => 'booking_cancel',
                            'settings_type' => 'serviceman_notification'
                        ];
                    }

//                    if ($model?->customer) {
//                        refundTransactionForCanceledBooking($model);
//                    }

                } elseif ($model->booking_status == 'accepted') {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_accepted',
                            'settings_type' => 'customer_notification',
                        ];
                    }
                    if ($providerPermission) {
                        $notifications[] = [
                            'key' => 'booking_accepted',
                            'settings_type' => 'provider_notification',
                        ];
                    }
                } elseif (! in_array($model->booking_status, ['pending', 'accepted', 'completed', 'canceled', 'refund_request'], true)) {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_status_change',
                            'settings_type' => 'customer_notification',
                        ];
                    }
                    if ($providerPermission) {
                        $notifications[] = [
                            'key' => 'booking_status_change',
                            'settings_type' => 'provider_notification',
                        ];
                    }
                    if ($servicemanPermission) {
                        $notifications[] = [
                            'key' => 'ongoing_booking',
                            'settings_type' => 'serviceman_notification',
                        ];
                    }
                }
//                elseif ($model->booking_status == 'refund_request') {
//                    if ($permission) {
//                        $notifications[] = [
//                            [
//                                'key' => 'refund',
//                                'settings_type' => 'customer_notification'
//                            ]
//                        ];
//                    }
//                }


                if (isset($booking_notification_status) && $booking_notification_status['push_notification_booking']) {
                    $pushBookingStatus = $model->isDirty('booking_status')
                        ? (string) $model->booking_status
                        : null;
                    $notificationData = booking_repeat_notification_template_data($model);

                    foreach ($notifications ?? [] as $notification) {
                        $key = $notification['key'];
                        $settingsType = $notification['settings_type'];

                        if ($settingsType == 'customer_notification') {
                            $user = $model?->booking?->customer;
                            $repeatOrRegular = $model?->booking?->is_repeated ? 'repeat' : 'regular';
                            $title = get_push_notification_message($key, $settingsType, $user?->current_language_key);
                            $description = get_push_notification_description($key, $settingsType, $user?->current_language_key);
                            $permission = isNotificationActive(null, 'booking', 'notification', 'user');
                            if (user_has_fcm_devices($user) && $user?->is_active && $title && $permission) {
                                device_notification_for_user($user, $title, $description, null, $model->id, 'booking', null, null, $notificationData, null, $repeatOrRegular, 'single', null, null, $pushBookingStatus);
                            }
                        }

                        if ($settingsType == 'provider_notification') {

                            if ((!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $model?->provider?->is_suspended == 0) && $model->booking_status == 'pending') {
                                $provider = $model?->provider?->owner;
                                $repeatOrRegular = $model?->booking?->is_repeated ? 'repeat' : 'regular';
                                $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);
                                $description = get_push_notification_description($key, $settingsType, $provider?->current_language_key);
                                if (user_has_fcm_devices($provider) && $title && sendDeviceNotificationPermission($model?->provider_id)) {
                                    device_notification_for_user($provider, $title, $description, null, $model->id, 'booking', null, null, $notificationData, null, $repeatOrRegular, 'single', null, null, $pushBookingStatus);
                                }
                            } else {
                                $provider = $model?->provider?->owner;
                                $repeatOrRegular = $model?->booking?->is_repeated ? 'repeat' : 'regular';
                                $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);
                                $description = get_push_notification_description($key, $settingsType, $provider?->current_language_key);
                                if (user_has_fcm_devices($provider) && $title  && sendDeviceNotificationPermission($model?->provider_id)) {
                                    device_notification_for_user($provider, $title, $description, null, $model->id, 'booking', null, null, $notificationData, null, $repeatOrRegular, 'single', null, null, $pushBookingStatus);
                                }
                            }
                        }

                        if ($settingsType == 'serviceman_notification') {
                            $serviceman = $model?->serviceman?->user;
                            $repeatOrRegular = $model?->booking?->is_repeated ? 'repeat' : 'regular';
                            $title = get_push_notification_message($key, $settingsType, $serviceman?->current_language_key);
                            $description = get_push_notification_description($key, $settingsType, $serviceman?->current_language_key);
                            if (user_has_fcm_devices($serviceman) && $title) {
                                device_notification_for_user($serviceman, $title, $description, null, $model->id, 'booking', null, null, $notificationData, null, $repeatOrRegular, 'single', null, null, $pushBookingStatus);
                            }
                        }
                    }
                }
            }
        });

        self::updated(function ($model) {
            $status = $model->booking_status;
            $bookingScheduleTimeChange = isNotificationActive(null, 'booking', 'notification', 'user');
            $bookingScheduleTimeChangeProvider = isNotificationActive(null, 'booking', 'notification', 'provider');
            $bookingScheduleTimeChangeServiceman = isNotificationActive(null, 'booking', 'notification', 'serviceman');

            $notifications = [];
            $booking_notification_status = business_config('booking', 'notification_settings')->live_values;

            if ($model->wasChanged('provider_id') && $model->provider_id) {
                if ($bookingScheduleTimeChange) {
                    $notifications[] = [
                        'key' => 'provider_assign',
                        'settings_type' => 'customer_notification'
                    ];
                }
                if ($bookingScheduleTimeChangeProvider) {
                    $notifications[] = [
                        'key' => 'booking_assigned_to_provider',
                        'settings_type' => 'provider_notification'
                    ];
                }
            }

            if ($model->isDirty('serviceman_id')) {
                if ($bookingScheduleTimeChangeProvider) {
                    $notifications[] = [
                        'key' => 'serviceman_assign',
                        'settings_type' => 'provider_notification'
                    ];
                }
                if ($bookingScheduleTimeChangeServiceman) {
                    $notifications[] = [
                        'key' => 'serviceman_assign',
                        'settings_type' => 'serviceman_notification'
                    ];
                }
            }

            if ($model->isDirty('service_schedule')) {
                if ($bookingScheduleTimeChange) {
                    $notifications[] = [
                        'key' => 'booking_schedule_time_change',
                        'settings_type' => 'customer_notification'
                    ];
                }
                if ($bookingScheduleTimeChangeProvider) {
                    $notifications[] = [
                        'key' => 'booking_schedule_time_change',
                        'settings_type' => 'provider_notification'
                    ];
                }
                if ($bookingScheduleTimeChangeServiceman) {
                    $notifications[] = [
                        'key' => 'booking_schedule_time_change',
                        'settings_type' => 'serviceman_notification'
                    ];
                }
            }

            if (isset($booking_notification_status) && $booking_notification_status['push_notification_booking']) {
                $notificationData = booking_repeat_notification_template_data($model);
                $repeatOrRegular = $model?->booking?->is_repeated ? 'repeat' : 'regular';

                foreach ($notifications ?? [] as $notification) {
                    $key = $notification['key'];
                    $settingsType = $notification['settings_type'];

                    if ($settingsType == 'customer_notification') {
                        $user = $model?->booking?->customer;
                        $title = get_push_notification_message($key, $settingsType, $user?->current_language_key);
                        $description = get_push_notification_description($key, $settingsType, $user?->current_language_key);
                        if (user_has_fcm_devices($user) && $title && $user->is_active) {
                            scenario_push_notification(
                                $user,
                                $title,
                                $description,
                                $model->id,
                                'booking',
                                $user->id,
                                $notificationData,
                                $repeatOrRegular,
                                null,
                                'customer',
                                $model->booking?->zone_id
                            );
                        }
                    }

                    if ($settingsType == 'provider_notification') {
                        if ((!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $model?->provider?->is_suspended == 0) && $model->booking_status == 'pending') {
                            $provider = $model?->provider?->owner;
                            $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);
                            $description = get_push_notification_description($key, $settingsType, $provider?->current_language_key);
                            if (user_has_fcm_devices($provider) && $title && sendDeviceNotificationPermission($model?->provider_id)) {
                                scenario_push_notification(
                                    $provider,
                                    $title,
                                    $description,
                                    $model->id,
                                    'booking',
                                    $provider->id,
                                    $notificationData,
                                    $repeatOrRegular,
                                    null,
                                    'provider-admin',
                                    $model->booking?->zone_id
                                );
                            }
                        } else {
                            $provider = $model?->provider?->owner;
                            $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);
                            $description = get_push_notification_description($key, $settingsType, $provider?->current_language_key);
                            if (user_has_fcm_devices($provider) && $title && sendDeviceNotificationPermission($model?->provider_id)) {
                                scenario_push_notification(
                                    $provider,
                                    $title,
                                    $description,
                                    $model->id,
                                    'booking',
                                    $provider->id,
                                    $notificationData,
                                    $repeatOrRegular,
                                    null,
                                    'provider-admin',
                                    $model->booking?->zone_id
                                );
                            }
                        }
                    }

                    if ($settingsType == 'serviceman_notification') {
                        $serviceman = $model?->serviceman?->user;
                        $title = get_push_notification_message($key, $settingsType, $serviceman?->current_language_key);
                        $description = get_push_notification_description($key, $settingsType, $serviceman?->current_language_key);
                        if (user_has_fcm_devices($serviceman) && $title) {
                            scenario_push_notification(
                                $serviceman,
                                $title,
                                $description,
                                $model->id,
                                'booking',
                                $serviceman->id,
                                $notificationData,
                                $repeatOrRegular,
                                null,
                                'serviceman',
                                $model->booking?->zone_id
                            );
                        }
                    }
                }
            }
        });


        self::deleting(function ($model) {

        });

        self::deleted(function ($model) {

        });
    }
}
