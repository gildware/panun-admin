<?php

namespace Modules\BookingModule\Entities;

use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\BidModule\Entities\Post;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\BookingModule\Http\Traits\BookingScopes;
use Modules\BookingModule\Services\BookingReadableIdAllocator;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BusinessSettingsModule\Emails\CashInHandOverflowMail;
use Modules\BusinessSettingsModule\Emails\SubscriptionToCommissionMail;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ReviewModule\Entities\Review;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;
use Modules\BookingModule\Entities\BookingCompensation;

class Booking extends Model
{
    use HasFactory, HasUuid, BookingTrait, BookingScopes;

    /**
     * Used for admin "pending till today" follow-up lists: only active jobs, not completed / canceled / refunded.
     * Reopen workflows use these same statuses while work is open.
     */
    public const STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS = ['pending', 'accepted', 'ongoing', 'on_hold'];

    protected $casts = [
        'readable_id' => 'string',
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
        'additional_charges_breakdown' => 'array',
        'total_referral_discount_amount' => 'float',
        'provider_payment_confirmed_at' => 'datetime',
        'admin_provider_feedback_skipped_at' => 'datetime',
        'admin_customer_feedback_skipped_at' => 'datetime',
        'last_reopen_event_at' => 'datetime',
        'reopen_resolved_at' => 'datetime',
        'settlement_config' => 'array',
        'settlement_snapshot' => 'array',
        'allow_complete_without_full_payment' => 'boolean',
        'after_visit_cancel' => 'boolean',
        'reopen_completion_allowed' => 'boolean',
        'reopen_disputed_snapshot' => 'array',
        'admin_commission_override' => 'float',
    ];

    protected $fillable = [
        'id',
        'readable_id',
        'customer_id',
        'provider_id',
        'zone_id',
        'booking_status',
        'is_paid',
        'payment_method',
        'transaction_id',
        'total_booking_amount',
        'total_tax_amount',
        'total_discount_amount',
        'service_schedule',
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
        'assignee_id',
        'booking_source',
        'service_description',
        'lead_id',
        'admin_provider_feedback_skipped_at',
        'admin_customer_feedback_skipped_at',
        'originated_from_booking_id',
        'last_reopen_event_at',
        'reopened_by',
        'reopen_resolved_at',
        'reopen_resolved_by',
        'reopen_resolve_remarks',
        'settlement_outcome',
        'settlement_config',
        'settlement_snapshot',
        'allow_complete_without_full_payment',
        'settlement_remarks',
        'after_visit_cancel',
        'reopen_completion_allowed',
        'reopen_disputed_snapshot',
        'admin_commission_override',
    ];

    protected $appends = ['evidence_photos_full_path'];

    /**
     * Completed jobs plus single-booking “after visit” cancellations that keep visit/closing settlement (revenue / earnings).
     */
    public function scopeForRevenueReporting($query): void
    {
        $query->where(function ($q) {
            $q->where('booking_status', 'completed')
                ->orWhere(function ($q2) {
                    $q2->where('booking_status', 'canceled')
                        ->where('after_visit_cancel', true);
                });
        });
    }


    public function service_address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'service_address_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function serviceman(): BelongsTo
    {
        return $this->belongsTo(Serviceman::class, 'serviceman_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function extra_services(): HasMany
    {
        return $this->hasMany(BookingExtraService::class);
    }

    public function repeatDetail(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }
    public function repeat(): HasMany
    {
        return $this->hasMany(BookingRepeat::class);
    }

    public function booking_partial_payments(): HasMany
    {
        return $this->hasMany(BookingPartialPayment::class)->latest();
    }

    public function compensations(): HasMany
    {
        return $this->hasMany(BookingCompensation::class, 'booking_id')->latest();
    }

    public function booking_details_amounts(): hasOne
    {
        return $this->hasOne(BookingDetailsAmount::class);
    }

    public function bookingDeniedNote(): hasOne
    {
        return $this->hasOne(BookingAdditionalInformation::class, 'booking_id')->where('key', 'booking_deny_note');
    }

    public function details_amounts(): hasMany
    {
        return $this->hasMany(BookingDetailsAmount::class);
    }

    public function schedule_histories(): HasMany
    {
        return $this->hasMany(BookingScheduleHistory::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function status_histories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    /**
     * Latest parent-row status history when the booking was set to canceled (not a repeat-instance row).
     */
    public function latestParentCancellationStatusHistory(): HasOne
    {
        return $this->hasOne(BookingStatusHistory::class)
            ->whereNull('booking_repeat_id')
            ->whereIn('booking_status', ['canceled', 'cancelled'])
            ->latestOfMany(['created_at', 'id']);
    }

    /**
     * Latest parent-row history when the booking was set to on_hold.
     */
    public function latestParentHoldStatusHistory(): HasOne
    {
        return $this->hasOne(BookingStatusHistory::class)
            ->whereNull('booking_repeat_id')
            ->where('booking_status', 'on_hold')
            ->latestOfMany(['created_at', 'id']);
    }

    /**
     * Latest parent-row history when the booking was dispute-closed (dispute reason captured on the status history row).
     */
    public function latestParentDisputeStatusHistory(): HasOne
    {
        return $this->hasOne(BookingStatusHistory::class)
            ->whereNull('booking_repeat_id')
            ->whereNotNull('booking_dispute_reason_id')
            ->latestOfMany(['created_at', 'id']);
    }

    /**
     * Latest reopen-from-completed event relevant to this row (in-place / new linked booking, or child follow-up).
     */
    public function reopenFromCompletedDisplayEvent(): ?BookingReopenEvent
    {
        if (! empty($this->originated_from_booking_id)) {
            if ($this->relationLoaded('originatedFromBooking') && $this->originatedFromBooking?->relationLoaded('reopenEvents')) {
                foreach ($this->originatedFromBooking->reopenEvents as $ev) {
                    if ((string) ($ev->child_booking_id ?? '') === (string) $this->id) {
                        return $ev;
                    }
                }
            }

            return BookingReopenEvent::query()
                ->where('source_booking_id', $this->originated_from_booking_id)
                ->where('child_booking_id', $this->id)
                ->with('holdReopenReason')
                ->orderByDesc('created_at')
                ->first();
        }

        if ($this->relationLoaded('reopenEvents')) {
            return $this->reopenEvents->first(fn ($ev) => in_array($ev->resolution, [
                BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE,
                BookingReopenEvent::RESOLUTION_NEW_BOOKING,
            ], true));
        }

        return $this->reopenEvents()
            ->whereIn('resolution', [
                BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE,
                BookingReopenEvent::RESOLUTION_NEW_BOOKING,
            ])
            ->with('holdReopenReason')
            ->orderByDesc('created_at')
            ->first();
    }

    public function followups(): HasMany
    {
        return $this->hasMany(BookingFollowup::class)->orderByDesc('date')->orderByDesc('created_at');
    }

    public function change_logs(): HasMany
    {
        return $this->hasMany(BookingChangeLog::class)->orderByDesc('created_at');
    }

    public function originatedFromBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'originated_from_booking_id');
    }

    public function spawnedFollowupBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'originated_from_booking_id');
    }

    public function reopenEvents(): HasMany
    {
        return $this->hasMany(BookingReopenEvent::class, 'source_booking_id')->orderByDesc('created_at');
    }

    public function reopenedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function reopenCaseResolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopen_resolved_by');
    }

    public function isReopenedTagged(): bool
    {
        return $this->originated_from_booking_id !== null
            || $this->last_reopen_event_at !== null;
    }

    /**
     * Scaled-to-payments settlement with remaining customer shortfall (loss). Once the booking is fully paid to
     * the invoice total, this returns false so UI and reopen rules treat the job as financially recovered.
     */
    public function isLossMakingFinancialSettlement(): bool
    {
        if (trim((string) ($this->settlement_outcome ?? '')) !== \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return false;
        }

        $grand = round(max(0.0, get_booking_total_amount($this)), 2);
        if ($grand <= 0.009) {
            return true;
        }

        $svc = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
        $config = is_array($this->settlement_config) ? $this->settlement_config : [];
        [, $lossTotal] = $svc->resolveScaledLossBreakdown(
            $this,
            $config,
            $grand,
            $svc->totalPaidForMainBooking($this)
        );

        return $lossTotal > 0.009;
    }

    /**
     * After-visit cancel or visit-only decided charges: must not be reopened in place or used as a follow-up booking source.
     */
    public function blocksAdminReopenDueToDecidedChargesSpecialSettlement(): bool
    {
        $o = trim((string) ($this->settlement_outcome ?? ''));

        return $o === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT
            || $o === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
    }

    /**
     * Admin "Reopen" / follow-up-from-completed (non-repeat, completed, not loss-making scaled, not loss-recovered scaled end state, not decided-charges special settlement).
     */
    public function adminEligibleForReopenFromCompleted(): bool
    {
        if ((int) ($this->is_repeated ?? 0) !== 0) {
            return false;
        }
        // Disputed-close bookings are final; do not allow reopening again.
        if (!empty($this->reopen_disputed_snapshot) && is_array($this->reopen_disputed_snapshot)) {
            return false;
        }
        if (($this->booking_status ?? '') !== 'completed') {
            return false;
        }
        if ($this->isLossMakingFinancialSettlement()) {
            return false;
        }
        // Scaled settlement after full recovery (was loss-tagged flow; shortfall cleared) — terminal like disputed close.
        if ($this->isScaledSettlementLossRecovered()) {
            return false;
        }
        if ($this->blocksAdminReopenDueToDecidedChargesSpecialSettlement()) {
            return false;
        }

        return true;
    }

    /**
     * Hide admin per-booking commission override when a modal special financial scenario is on file (settlement defines economics).
     * Compensation is handled separately — see {@see self::adminEligibleForCompensationRecording()}.
     */
    public function blocksAdminCommissionOverrideAndCompensation(): bool
    {
        // Disputed-close bookings are final for commission override edits.
        if (!empty($this->reopen_disputed_snapshot) && is_array($this->reopen_disputed_snapshot)) {
            return true;
        }

        return \Modules\BookingModule\Services\BookingFinancialSettlementService::specialScenarioOutcomeDisablesCommissionOverrideAndCompensation(
            (string) ($this->settlement_outcome ?? '')
        );
    }

    /**
     * Admin may record booking-linked compensation once the job is in a terminal state (including dispute-and-close,
     * special settlement outcomes, scaled loss, etc.). Non-repeat bookings only, same as other per-booking financial tools.
     */
    public function adminEligibleForCompensationRecording(): bool
    {
        if ((int) ($this->is_repeated ?? 0) !== 0) {
            return false;
        }

        $st = strtolower((string) ($this->booking_status ?? ''));
        if ($st === 'cancelled') {
            $st = 'canceled';
        }

        return in_array($st, ['completed', 'canceled', 'refunded'], true);
    }

    /**
     * Scaled settlement on file and no remaining customer shortfall (bad debt / loss total is zero).
     */
    public function isScaledSettlementLossRecovered(): bool
    {
        if (trim((string) ($this->settlement_outcome ?? '')) !== \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return false;
        }

        return ! $this->isLossMakingFinancialSettlement();
    }

    /**
     * Open reopen ticket: follow-up booking from a completed job, or same booking after in-place reopen.
     * Excludes the original completed parent when only a linked follow-up was created.
     */
    public function isOpenReopenTicket(): bool
    {
        if ($this->reopen_resolved_at !== null) {
            return false;
        }

        $hasOriginated = $this->originated_from_booking_id !== null;
        $hasInPlaceEvent = $this->reopenEvents()
            ->where('resolution', BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE)
            ->exists();

        if (!$hasOriginated && !$hasInPlaceEvent) {
            return false;
        }

        if (($this->booking_status ?? '') === 'completed'
            && !$hasOriginated
            && $this->spawnedFollowupBookings()->exists()
            && !$hasInPlaceEvent) {
            return false;
        }

        return true;
    }

    /**
     * Open reopen ticket: admin must use "Resolve booking" (or another reopen completion path) before marking Completed via status controls.
     */
    public function adminMustConfigureReopenBeforeComplete(): bool
    {
        return $this->isOpenReopenTicket() && ! (bool) ($this->reopen_completion_allowed ?? false);
    }

    /**
     * Employee may close the reopen case only after the booking is completed again.
     */
    public function canMarkReopenResolved(): bool
    {
        return $this->isOpenReopenTicket()
            && ($this->booking_status ?? '') === 'completed';
    }

    /**
     * When a booking was already marked completed once, completion accounting was skipped to avoid duplicate
     * ledger effects. After an in-place reopen (or follow-up workflow), marking completed again must still
     * refresh commission rows and settlement preview — only the first completion posts heavy transactions.
     */
    public static function shouldResyncCommissionAfterPriorCompletion(self $model): bool
    {
        if ($model->last_reopen_event_at !== null) {
            return true;
        }
        if ($model->originated_from_booking_id !== null) {
            return true;
        }

        $latestCompleted = BookingStatusHistory::query()
            ->where('booking_id', $model->id)
            ->where('booking_status', 'completed')
            ->whereNull('booking_repeat_id')
            ->orderByDesc('id')
            ->first();
        if (!$latestCompleted) {
            return false;
        }

        return BookingStatusHistory::query()
            ->where('booking_id', $model->id)
            ->whereNull('booking_repeat_id')
            ->where('id', '>', $latestCompleted->id)
            ->whereNotIn('booking_status', ['completed'])
            ->exists();
    }

    /**
     * Recompute persisted {@see BookingDetailsAmount} commission / provider earning from current booking
     * totals (including extra services and fees), and refresh financial settlement snapshot when applicable.
     * Call after extras or other edits that change {@see get_booking_total_amount} but do not go through completion accounting.
     */
    public function resyncStoredCommissionAndSettlementSnapshot(): void
    {
        if (!$this->provider_id) {
            return;
        }

        if (!$this->details_amounts()->exists()) {
            return;
        }

        $this->update_admin_commission($this, (float) $this->total_booking_amount, $this->provider_id);

        $outcome = trim((string) ($this->settlement_outcome ?? ''));
        if ($outcome !== '' && $outcome !== \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_STANDARD) {
            try {
                $this->settlement_snapshot = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class)
                    ->buildPreview($this);
                $this->saveQuietly();
            } catch (\Throwable) {
                // Keep prior snapshot if preview fails
            }
        }
    }

    public function booking_offline_payments(): HasMany
    {
        return $this->hasMany(BookingOfflinePayment::class, 'booking_id');
    }

    public function ignores(): HasMany
    {
        return $this->hasMany(BookingIgnore::class, 'booking_id');
    }

    public function customizeBooking(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'id', 'booking_id');
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

        self::creating(function ($model) {
            // Format: PKDDMONYYNNN e.g. PK07MAR26001 (first booking of 7 March 2026)
            if (!empty($model->readable_id)) {
                return;
            }
            $model->readable_id = BookingReadableIdAllocator::allocateNext();
        });

        self::created(function ($model) {
            $providerId = $model->provider_id;

            if ($providerId) {
                $provider = PackageSubscriber::where('provider_id', $providerId)->first();

                if ($provider) {
                    $firstLog = $provider->package_subscriber_log_id;

                    $bookingType = new SubscriptionBookingType();
                    $bookingType->booking_id = $model->id;
                    $bookingType->type = 'subscription';
                    $bookingType->save();

                    $subscriptionSubscriberBooking = new SubscriptionSubscriberBooking();
                    $subscriptionSubscriberBooking->provider_id = $providerId;
                    $subscriptionSubscriberBooking->booking_id = $model->id;

                    if ($firstLog) {
                        $subscriptionSubscriberBooking->package_subscriber_log_id = $firstLog;
                    }

                    $subscriptionSubscriberBooking->save();
                }
            }

            // Auto-add next follow-up for customer and provider: 1 day before scheduled, or 1 hour before if same-day
            if ($model->service_schedule) {
                $scheduledAt = Carbon::parse($model->service_schedule);
                $bookedAt = Carbon::parse($model->created_at);
                $followUpAt = $scheduledAt->isSameDay($bookedAt)
                    ? $scheduledAt->copy()->subHour()
                    : $scheduledAt->copy()->subDay();
                $reason = translate('Reminder_before_service');
                foreach (['customer', 'provider'] as $for) {
                    BookingFollowup::create([
                        'booking_id' => $model->id,
                        'date' => $followUpAt,
                        'reason' => $reason,
                        'for' => $for,
                        'status' => 'scheduled',
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        });


        self::updating(function ($model) {
            // Prevent completion unless full payment received (use $model so in-flight settlement_outcome / settlement_config apply — DB row is not updated yet).
            if ($model->isDirty('booking_status') && $model->booking_status === 'completed') {
                $model->loadMissing('booking_partial_payments');
                if (! booking_can_be_completed($model)) {
                    throw new \RuntimeException(translate('Booking cannot be completed until full payment is received.'));
                }
                if ((string) ($model->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL) {
                    throw new \RuntimeException(translate('Change_financial_settlement_before_completing_visit_retained_is_cancel_only'));
                }
            }

            $booking_notification_status = business_config('booking', 'notification_settings')->live_values;
            $permission = isNotificationActive(null, 'booking', 'notification', 'user');
            $providerPermission = isNotificationActive(null, 'booking', 'notification', 'provider');
            $servicemanPermission = isNotificationActive(null, 'booking', 'notification', 'serviceman');

            if ($model->isDirty('booking_status')) {
                $key = null;
                if ($model->booking_status == 'pending') {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_place',
                            'settings_type' => 'customer_notification'
                        ];
                    }
                } elseif ($model->booking_status == 'ongoing') {
                    if ($permission) {
                        $notifications[] = [
                            'key' => 'booking_ongoing',
                            'settings_type' => 'customer_notification'
                        ];
                    }
                    if ($providerPermission){
                            $notifications[] = [
                                'key' => 'ongoing_booking',
                                'settings_type' => 'provider_notification'
                            ];
                    }
                    if ($servicemanPermission) {
                        $notifications[] = [
                            'key' => 'ongoing_booking',
                            'settings_type' => 'serviceman_notification'
                        ];
                    }
            } elseif ($model->booking_status == 'accepted') {
                if ($permission) {
                    $notifications[] = [
                        'key' => 'booking_accepted',
                        'settings_type' => 'customer_notification'
                    ];
                }
                if ($providerPermission && $model->is_repeated == 0) {
                    $notifications[] = [
                        'key' => 'booking_accepted',
                        'settings_type' => 'provider_notification'
                    ];
                }
            } elseif ($model->booking_status == 'completed' && $model->is_repeated == 0) {
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

                $skipHeavyCompletionAccounting = false;
                if ($model->isDirty('booking_status') && $model->getOriginal('booking_status') !== 'completed') {
                    $skipHeavyCompletionAccounting = BookingStatusHistory::query()
                        ->where('booking_id', $model->id)
                        ->where('booking_status', 'completed')
                        ->whereNull('booking_repeat_id')
                        ->exists();
                }

                $resyncCommissionAfterReopenCycle = $skipHeavyCompletionAccounting
                    && self::shouldResyncCommissionAfterPriorCompletion($model);

                $model->is_paid = 1;

                $provider = $model->provider;

                if ($provider && (!$skipHeavyCompletionAccounting || $resyncCommissionAfterReopenCycle)) {
                    $model->update_admin_commission($model, $model->total_booking_amount, $model->provider_id);
                    $outcome = trim((string) ($model->settlement_outcome ?? ''));
                    if ($outcome !== '' && $outcome !== \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_STANDARD) {
                        try {
                            $model->settlement_snapshot = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class)
                                ->buildPreview($model);
                        } catch (\Throwable) {
                            // keep existing snapshot if preview fails
                        }
                    }
                }

                if (!$skipHeavyCompletionAccounting && !$model->is_guest && $model?->customer) {
                    $model->referral_earning_calculation($model->customer_id, $model->zone_id);

                    $model->loyaltyPointCalculation($model->customer_id, $model->total_booking_amount);

                    if ($model->total_referral_discount_amount > 0){
                        referralEarningTransactionAfterBookingCompleteFirst($model->customer, $model->total_referral_discount_amount, $model->id);
                    }
                }

                //================ Transactions for Booking ================

                if (!$skipHeavyCompletionAccounting && $model?->provider) {
                    if ($model->booking_partial_payments->isNotEmpty()) {
                        $anyReceivedByProvider = $model->booking_partial_payments->contains(fn ($p) => ($p->received_by ?? '') === 'provider');
                        $scaledToPayments = trim((string) ($model->settlement_outcome ?? '')) === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
                        // Visit fee split: retained customer total is below catalog grand — never synthesize a CAS "remainder"
                        // partial or run legacy completion against full booking totals (admin partials + ledger are canonical).
                        $runLegacyPartialCompletion = trim((string) ($model->settlement_outcome ?? ''))
                            !== \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;

                        if ($runLegacyPartialCompletion && $model['payment_method'] == 'cash_after_service' && $anyReceivedByProvider) {
                            // Loss-making (scaled): never fabricate a final "cash after service" partial — the latest
                            // installment row's due_amount is not cash received and was inflating company/customer totals.
                            // Disputed reopen close: snapshot + refunds are canonical — never fabricate a remainder vs invoice.
                            $disputedSnapshot = ! empty($model->reopen_disputed_snapshot) && is_array($model->reopen_disputed_snapshot);
                            if (! $scaledToPayments && ! $disputedSnapshot) {
                                $sumPaidBefore = (float) $model->booking_partial_payments->sum('paid_amount');
                                $grand = round((float) get_booking_total_amount($model), 2);
                                $remaining = round(max(0.0, $grand - $sumPaidBefore), 2);
                                if ($remaining > 0.009) {
                                    $booking_partial_payment = new BookingPartialPayment;
                                    $booking_partial_payment->booking_id = $model->id;
                                    $booking_partial_payment->paid_with = 'cash_after_service';
                                    $booking_partial_payment->paid_amount = $remaining;
                                    $booking_partial_payment->due_amount = 0;
                                    $booking_partial_payment->save();
                                }
                            }

                            completeBookingTransactionForPartialCas($model);
                        } elseif ($runLegacyPartialCompletion && $model['payment_method'] == 'cash_after_service' && !$anyReceivedByProvider) {
                            completeBookingTransactionForPartialDigital($model);
                        } elseif ($runLegacyPartialCompletion && $model['payment_method'] != 'wallet_payment') {
                            completeBookingTransactionForPartialDigital($model);
                        }

                    } elseif ($model->payment_method == 'cash_after_service') {
                        completeBookingTransactionForCashAfterService($model);
                    } else {
                        if ($model->additional_charge == 0) {
                            completeBookingTransactionForDigitalPayment($model);
                        }

                        if ($model->additional_charge > 0) {
                            completeBookingTransactionForDigitalPaymentAndExtraService($model);
                        }
                    }

                    $limit_status = provider_warning_amount_calculate($provider->owner->account->account_payable, $provider->owner->account->account_receivable);

                    if ($limit_status == '100_percent' && business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values) {
                        $provider->is_suspended = 1;
                        $provider->save();

                        $notification = isNotificationActive($provider?->id, 'transaction', 'notification', 'provider');
                        $title = get_push_notification_message('provider_suspend', 'provider_notification', $provider?->owner?->current_language_key);
                        if ($provider?->owner?->fcm_token && $title && $notification) {
                            device_notification($provider?->owner?->fcm_token, $title, null, null, $model->id, 'suspend', null, $provider->id);
                        }

                        $emailStatus = business_config('email_config_status', 'email_config')->live_values;

                        if ($emailStatus){
                            try {
                                Mail::to($provider?->owner?->email)->send(new CashInHandOverflowMail($provider));
                            } catch (\Exception $exception) {
                                info($exception);
                            }
                        }

                    }
                }

            } elseif ($model->booking_status == 'canceled') {
                if ($permission) {
                    $notifications[] = [
                        'key' => 'booking_cancel',
                        'settings_type' => 'customer_notification'
                    ];
                }
                if ($providerPermission) {
                    $notifications[] = [
                        'key' => 'booking_cancel',
                        'settings_type' => 'provider_notification'
                    ];
                }
                if ($servicemanPermission) {
                    $notifications[] = [
                        'key' => 'booking_cancel',
                        'settings_type' => 'serviceman_notification'
                    ];
                }

                $model->loadMissing('booking_partial_payments');
                if ((int) ($model->is_repeated ?? 0) === 0
                    && (string) ($model->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
                    && $model->provider_id) {
                    $details = $model->calculateCommissionDetails($model, $model->provider_id);
                    app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class)
                        ->syncDetailsAmounts($model, $details);
                }

                if ($model?->customer) {
                    refundTransactionForCanceledBooking($model);
                }

            } elseif ($model->booking_status == 'refund_request') {
                if ($permission) {
                    $notifications[] = [
                        [
                            'key' => 'refund',
                            'settings_type' => 'customer_notification'
                        ]
                    ];
                }
            }


            if (isset($booking_notification_status) && $booking_notification_status['push_notification_booking']) {
                foreach ($notifications ?? [] as $notification) {
                    $key = $notification['key'];
                    $settingsType = $notification['settings_type'];

                    if ($settingsType == 'customer_notification') {
                        $user = $model?->customer;
                        $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                        $title = get_push_notification_message($key, $settingsType, $user?->current_language_key);
                        $permission = isNotificationActive(null, 'booking', 'notification', 'user');
                        if ($user?->fcm_token && $user?->is_active && $title && $permission) {
                            device_notification($user?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
                        }
                    }

                    if ($settingsType == 'provider_notification') {

                        if ((!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $model?->provider?->is_suspended == 0) && $model->booking_status == 'pending') {
                            $provider = $model?->provider?->owner;
                            $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                            $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);

                            if ($provider?->fcm_token && $title && sendDeviceNotificationPermission($model?->provider_id)) {
                                device_notification($provider?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
                            }
                        } else {
                            $provider = $model?->provider?->owner;
                            $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                            $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);

                            if ($provider?->fcm_token && $title  && sendDeviceNotificationPermission($model?->provider_id)) {
                                device_notification($provider?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
                            }
                        }
                    }

                    if ($settingsType == 'serviceman_notification') {
                        $serviceman = $model?->serviceman?->user;
                        $title = get_push_notification_message($key, $settingsType, $serviceman?->current_language_key);
                        if ($serviceman?->fcm_token && $title) {
                            device_notification($serviceman?->fcm_token, $title, null, null, $model->id, 'booking');
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

            $providerId = $model->provider_id;

            if ($status == 'accepted'){
                $providerId = $model->provider_id;

                $subscriptionSubscriberBooking = new SubscriptionSubscriberBooking();
                $subscriptionSubscriberBooking->provider_id = $providerId;
                $subscriptionSubscriberBooking->booking_id = $model->id;

                if ($providerId) {
                    $provider = PackageSubscriber::where('provider_id', $providerId)->first();

                    if ($provider && !$model->isDirty('provider_id')) {
                        $firstLog = $provider->package_subscriber_log_id;

                        $bookingType = new SubscriptionBookingType();
                        $bookingType->booking_id = $model->id;
                        $bookingType->type = 'subscription';
                        $bookingType->save();

                        $subscriptionSubscriberBooking = new SubscriptionSubscriberBooking();
                        $subscriptionSubscriberBooking->provider_id = $providerId;
                        $subscriptionSubscriberBooking->booking_id = $model->id;

                        if ($firstLog) {
                            $subscriptionSubscriberBooking->package_subscriber_log_id = $firstLog;
                        }

                        $subscriptionSubscriberBooking->save();
                    }
                }
            }

            if ($model->isDirty('provider_id')){
                $provider = PackageSubscriber::where('provider_id', $providerId)->first();
                $firstLog = $provider?->package_subscriber_log_id;
                if ($provider){
                    $firstLog = $provider->package_subscriber_log_id;

                    SubscriptionBookingType::updateOrCreate(
                        [
                            'booking_id' => $model->id,
                        ],
                        [
                            'booking_id' => $model->id,
                            'type' => 'subscription'
                        ]
                    );
                }
                SubscriptionSubscriberBooking::updateOrCreate(
                    [
                        'booking_id' => $model->id,
                    ],
                    [
                        'provider_id'   => $providerId,
                        'booking_id' => $model->id,
                        'package_subscriber_log_id' => $firstLog,
                    ]
                );
            }
            $notifications = [];
            $booking_notification_status = business_config('booking', 'notification_settings')->live_values;

            if ($model->isDirty('serviceman_id') && !$model->is_repeted) {
                if ($bookingScheduleTimeChange) {
                    $notifications[] = [
                        'key' => 'serviceman_assign',
                        'settings_type' => 'customer_notification'
                    ];
                }
                if ($bookingScheduleTimeChangeProvider && !$model->is_repeted) {
                    $notifications[] = [
                        'key' => 'serviceman_assign',
                        'settings_type' => 'provider_notification'
                    ];
                }
                if ($bookingScheduleTimeChangeServiceman && !$model->is_repeted) {
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
                foreach ($notifications ?? [] as $notification) {
                    $key = $notification['key'];
                    $settingsType = $notification['settings_type'];

                    if ($settingsType == 'customer_notification') {
                        $user = $model?->customer;
                        $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                        $title = get_push_notification_message($key, $settingsType, $user?->current_language_key);
                        if ($user?->fcm_token && $title) {
                            device_notification($user?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
                        }
                    }

                    if ($settingsType == 'provider_notification') {
                        if ((!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $model?->provider?->is_suspended == 0) && $model->booking_status == 'pending') {
                            $provider = $model?->provider?->owner;
                            $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                            $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);
                            if ($provider?->fcm_token && $title && sendDeviceNotificationPermission($model?->provider_id)) {
                                device_notification($provider?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
                            }
                        } else {
                            $provider = $model?->provider?->owner;
                            $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                            $title = get_push_notification_message($key, $settingsType, $provider?->current_language_key);
                            if ($provider?->fcm_token && $title && sendDeviceNotificationPermission($model?->provider_id)) {
                                device_notification($provider?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
                            }
                        }
                    }

                    if ($settingsType == 'serviceman_notification') {
                        $serviceman = $model?->serviceman?->user;
                        $repeatOrRegular = $model?->is_repeated ? 'repeat' : 'regular';
                        $title = get_push_notification_message($key, $settingsType, $serviceman?->current_language_key);
                        if ($serviceman?->fcm_token && $title) {
                            device_notification($serviceman?->fcm_token, $title, null, null, $model->id, 'booking', null, null, null, null, $repeatOrRegular);
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
