<?php

namespace Modules\BookingModule\Http\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\BookingReopenEvent;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;

trait BookingScopes
{
    /** @var array<string, bool> */
    private static array $hasReopenResolvedAtColumn = [];

    private static function bookingsTableHasReopenResolvedAt(string $table): bool
    {
        if (! array_key_exists($table, self::$hasReopenResolvedAtColumn)) {
            self::$hasReopenResolvedAtColumn[$table] = Schema::hasColumn($table, 'reopen_resolved_at');
        }

        return self::$hasReopenResolvedAtColumn[$table];
    }


    public function scopeOfBookingStatus($query, $status): void
    {
        if ($status === 'canceled') {
            $query->whereIn('booking_status', ['canceled', 'refunded']);
        } else {
            $query->where('booking_status', '=', $status);
        }
    }
    public function scopeOfRepeatBookingStatus($query, $status): void
    {
        $query->where('is_repeated', '=', $status);
    }

    /**
     * Open reopen tickets only: linked follow-up bookings, or the same booking after an in-place reopen.
     * Excludes resolved cases and the original completed parent when only a new linked booking was created.
     *
     * @deprecated Use openReopenTickets(); reopenedChain is an alias for the same filter.
     */
    public function scopeReopenedChain($query): void
    {
        $query->openReopenTickets();
    }

    public function scopeOpenReopenTickets($query): void
    {
        $table = $query->getModel()->getTable();
        if (self::bookingsTableHasReopenResolvedAt($table)) {
            $query->whereNull($table . '.reopen_resolved_at');
        }
        $query->where(function ($q) {
                $q->whereNotNull('originated_from_booking_id')
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('booking_reopen_events')
                            ->whereColumn('booking_reopen_events.source_booking_id', 'bookings.id')
                            ->where('booking_reopen_events.resolution', BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE);
                    });
            })
            ->where(function ($q) {
                $q->where('booking_status', '!=', 'completed')
                    ->orWhereNotNull('originated_from_booking_id')
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('booking_reopen_events')
                            ->whereColumn('booking_reopen_events.source_booking_id', 'bookings.id')
                            ->where('booking_reopen_events.resolution', BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE);
                    });
            });
    }

    /**
     * Booking is on hold and the most recent transition to on_hold was from ongoing (hold after visit).
     * Parent booking rows only (booking_repeat_id IS NULL).
     */
    public function scopeHoldAfterVisit($query): void
    {
        $table = $query->getModel()->getTable();
        $query->where($table . '.booking_status', 'on_hold');

        // Latest parent on_hold history id for this booking.
        $latestHoldIdSql = "(SELECT MAX(h1.id) FROM booking_status_histories h1 WHERE h1.booking_id = {$table}.id AND h1.booking_repeat_id IS NULL AND h1.booking_status = 'on_hold')";
        // Previous parent history id right before that.
        $prevIdSql = "(SELECT MAX(h2.id) FROM booking_status_histories h2 WHERE h2.booking_id = {$table}.id AND h2.booking_repeat_id IS NULL AND h2.id < {$latestHoldIdSql})";
        // Previous status must be ongoing.
        $query->whereRaw("(SELECT h3.booking_status FROM booking_status_histories h3 WHERE h3.id = {$prevIdSql}) = 'ongoing'");
    }

    /**
     * Cancel-after-visit (visit retained cancel) bookings only.
     */
    public function scopeCancelledAfterVisit($query): void
    {
        $table = $query->getModel()->getTable();
        $query->whereIn($table . '.booking_status', ['canceled', 'cancelled', 'refunded'])
            ->where(function ($q) use ($table) {
                $q->where($table . '.after_visit_cancel', true)
                    ->orWhere($table . '.settlement_outcome', \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL);
            });
    }

    /**
     * Completed with little / no real service (visit fee split).
     */
    public function scopeCompletedNoOrLittle($query): void
    {
        $table = $query->getModel()->getTable();
        $query->where($table . '.booking_status', 'completed')
            ->where($table . '.settlement_outcome', \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT);
    }

    /**
     * Loss making / scaled to payments settlement outcome.
     */
    public function scopeLossMaking($query): void
    {
        $table = $query->getModel()->getTable();
        $query->where($table . '.settlement_outcome', \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS);
    }

    /**
     * Loss making (scaled) bookings that still have remaining loss (pending recovery).
     *
     * Uses settlement snapshot keys written by {@see \Modules\BookingModule\Services\BookingFinancialSettlementService::buildPreview()}.
     */
    public function scopeLossMakingPending($query): void
    {
        $table = $query->getModel()->getTable();
        $query->lossMaking()
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.settlement_snapshot, '$.scaled_loss_amount')) AS DECIMAL(18,2)) > 0.009")
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.settlement_snapshot, '$.scaled_loss_writeoff_amount')) AS DECIMAL(18,2)) <= 0.009");
    }

    /**
     * Loss making (scaled) bookings where the loss is fully recovered by additional payments (loss amount is zero).
     */
    public function scopeLossRecovered($query): void
    {
        $table = $query->getModel()->getTable();
        $query->lossMaking()
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.settlement_snapshot, '$.scaled_loss_amount')) AS DECIMAL(18,2)) <= 0.009")
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.settlement_snapshot, '$.scaled_loss_writeoff_amount')) AS DECIMAL(18,2)) <= 0.009");
    }

    /**
     * Loss settled (scaled) bookings where a write-off/discount was applied to settle the remaining loss.
     */
    public function scopeLossSettled($query): void
    {
        $table = $query->getModel()->getTable();
        $query->lossMaking()
            ->where(function ($q) use ($table) {
                $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.settlement_snapshot, '$.scaled_loss_writeoff_amount')) AS DECIMAL(18,2)) > 0.009")
                    ->orWhereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.settlement_config, '$.scaled_loss_writeoff_amount')) AS DECIMAL(18,2)) > 0.009");
            });
    }

    /**
     * Disputed close snapshot exists on the booking row.
     */
    public function scopeDisputedClosed($query): void
    {
        $table = $query->getModel()->getTable();
        $query->whereNotNull($table . '.reopen_disputed_snapshot')
            ->where($table . '.reopen_disputed_snapshot', '!=', '');
    }

    /**
     * Disputed-close bookings that ended up cancelled/refunded.
     */
    public function scopeDisputedClosedCancelled($query): void
    {
        $table = $query->getModel()->getTable();
        $query->disputedClosed()
            ->whereIn($table . '.booking_status', ['canceled', 'cancelled', 'refunded']);
    }

    /**
     * Disputed-close bookings that ended up completed.
     */
    public function scopeDisputedClosedCompleted($query): void
    {
        $table = $query->getModel()->getTable();
        $query->disputedClosed()
            ->where($table . '.booking_status', 'completed');
    }

    /**
     * Reopen case resolved (not disputed-close).
     */
    public function scopeResolvedReopenCase($query): void
    {
        $table = $query->getModel()->getTable();
        if (self::bookingsTableHasReopenResolvedAt($table)) {
            $query->whereNotNull($table . '.reopen_resolved_at');
        } else {
            $query->whereRaw('1=0');
        }
        $query->where(function ($q) use ($table) {
            $q->whereNull($table . '.reopen_disputed_snapshot')
                ->orWhere($table . '.reopen_disputed_snapshot', '=', '')
                ->orWhere($table . '.reopen_disputed_snapshot', '=', '[]');
        });
    }

    public function scopeSearch($query, $keywords, array $searchColumns): mixed
    {
        return $query->when($keywords && $searchColumns, function ($query) use ($keywords, $searchColumns) {
            $keys = explode(' ', $keywords);
            $query->where(function ($query) use ($keys, $searchColumns) {
                foreach ($keys as $key) {
                    foreach ($searchColumns as $column) {
                        $query->orWhere($column, 'LIKE', '%' . $key . '%');
                    }
                }
            });
        });
    }

    public function scopeFilterByZoneId($query, $zoneId): mixed
    {
        return $query->when($zoneId, function ($query) use ($zoneId) {
            $query->where('zone_id', $zoneId);
        });
    }

    public function scopeFilterByZoneIds($query, $zoneIds): mixed
    {
        return $query->when($zoneIds, function ($query) use ($zoneIds) {
            $query->whereIn('zone_id', $zoneIds);
        });
    }

    public function scopeFilterByCategoryIds($query, $categoryIds): mixed
    {
        return $query->when($categoryIds, function ($query) use ($categoryIds) {
            $query->whereIn('category_id', $categoryIds);
        });
    }

    public function scopeFilterBySubcategoryIds($query, $subCategoryIds): mixed
    {
        return $query->when($subCategoryIds, function ($query) use ($subCategoryIds) {
            $query->whereIn('sub_category_id', $subCategoryIds);
        });
    }

    /**
     * @param  array<int, string>|null  $assigneeIds  UUIDs and/or '__unassigned__' for bookings with no assignee
     */
    public function scopeFilterByAssigneeIds($query, ?array $assigneeIds): mixed
    {
        $assigneeIds = array_values(array_unique(array_filter(
            is_array($assigneeIds) ? $assigneeIds : [],
            fn ($v) => $v !== null && $v !== ''
        )));

        if ($assigneeIds === []) {
            return $query;
        }

        $includeUnassigned = in_array('__unassigned__', $assigneeIds, true);
        $userIds = array_values(array_filter($assigneeIds, fn ($v) => $v !== '__unassigned__'));

        return $query->where(function ($sub) use ($includeUnassigned, $userIds) {
            if ($includeUnassigned && $userIds !== []) {
                $sub->whereNull('assignee_id')->orWhereIn('assignee_id', $userIds);
            } elseif ($includeUnassigned) {
                $sub->whereNull('assignee_id');
            } else {
                $sub->whereIn('assignee_id', $userIds);
            }
        });
    }

    public function scopeFilterByDateRange($query, $fromDate, $toDate): mixed
    {
        return $query->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
            if (!($fromDate instanceof Carbon)) {
                $fromDate = Carbon::parse($fromDate);
            }
            if (!($toDate instanceof Carbon)) {
                $toDate = Carbon::parse($toDate);
            }

            if ($fromDate->equalTo($toDate)) {
                $query->whereDate('created_at', $fromDate->startOfDay());
            } else {
                $query->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            }
        });
    }

    public function scopeAdminPendingBookings($query, $maxBookingAmount): mixed
    {
        return $query
            ->where('booking_status', 'pending')
            ->where(function ($query) use ($maxBookingAmount) {
                $query->where('payment_method', '!=', 'cash_after_service')
                    ->orWhere(function ($query) use ($maxBookingAmount) {
                        $query->where('payment_method', 'cash_after_service')
                            ->where('total_booking_amount', '<=', $maxBookingAmount)
                            ->orWhere('is_verified', 1);
                    });
            });
    }

    public function scopeAdminAcceptedBookings($query, $maxBookingAmount): mixed
    {
        return $query
            ->where('booking_status', 'accepted')
            ->where(function ($query) use ($maxBookingAmount) {
                $query->where('payment_method', '!=', 'cash_after_service')
                    ->orWhere(function ($query) use ($maxBookingAmount) {
                        $query->where('payment_method', 'cash_after_service')
                            ->where('total_booking_amount', '<=', $maxBookingAmount)
                            ->orWhere('is_verified', 1);
                    });
            });
    }

    public function scopeProviderPendingBookings($query, Provider $provider, $maxBookingAmount)
    {
        $providerId = $provider->id;
        $packageSubscriber = PackageSubscriber::where('provider_id', $providerId)->first();
        $endDate = optional($packageSubscriber)->package_end_date;
        $canceled = optional($packageSubscriber)->is_canceled;
        $packageEndDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        $currentDate = Carbon::now()->subDay();
        $isPackageEnded = $packageEndDate ? $currentDate->diffInDays($packageEndDate, false) : null;
        $scheduleBookingEligibility = nextBookingEligibility($providerId);

        if ($packageSubscriber) {
            if ($isPackageEnded > 0 && $scheduleBookingEligibility && !$canceled) {
                if ($provider->service_availability && (int)($provider->is_active_for_jobs ?? 1) === 1 && (!$provider->is_suspended || !business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values)) {
                    $zoneIds = $provider->zones()->pluck('zones.id')->filter()->values()->all();
                    if ($zoneIds === [] && $provider->zone_id) {
                        $zoneIds = [(string) $provider->zone_id];
                    }
                    $subscribedSubCategories = SubscribedService::where(['provider_id' => $provider->id])->where(['is_subscribed' => 1])->pluck('sub_category_id')->toArray();

                    return $query
                        ->ofBookingStatus('pending')
                        ->whereIn('sub_category_id', $subscribedSubCategories)
                        ->whereIn('zone_id', $zoneIds)
                        ->when($maxBookingAmount > 0, function ($query) use ($maxBookingAmount) {
                            $query->where(function ($query) use ($maxBookingAmount) {
                                $query->where('payment_method', 'cash_after_service')
                                    ->where(function ($query) use ($maxBookingAmount) {
                                        $query->where('is_verified', 1)
                                            ->orWhere('total_booking_amount', '<=', $maxBookingAmount);
                                    })
                                    ->orWhere('payment_method', '<>', 'cash_after_service');
                            });
                        })
                        ->where(function($query) use ($provider) {
                            $query->whereNull('provider_id')->orWhere('provider_id', $provider->id);
                        });
                } else {
                    return $query->whereNull('id');
                }
            } else {
                return $query->whereRaw('1 = 0'); // This ensures no results are returned
            }
        } else {
            if ($provider->service_availability && (int)($provider->is_active_for_jobs ?? 1) === 1 && (!$provider->is_suspended || !business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values)) {
                $zoneIds = $provider->zones()->pluck('zones.id')->filter()->values()->all();
                if ($zoneIds === [] && $provider->zone_id) {
                    $zoneIds = [(string) $provider->zone_id];
                }
                $subscribedSubCategories = SubscribedService::where(['provider_id' => $provider->id])->where(['is_subscribed' => 1])->pluck('sub_category_id')->toArray();

                return $query
                    ->ofBookingStatus('pending')
                    ->whereIn('sub_category_id', $subscribedSubCategories)
                    ->whereIn('zone_id', $zoneIds)
                    ->when($maxBookingAmount > 0, function ($query) use ($maxBookingAmount) {
                        $query->where(function ($query) use ($maxBookingAmount) {
                            $query->where('payment_method', 'cash_after_service')
                                ->where(function ($query) use ($maxBookingAmount) {
                                    $query->where('is_verified', 1)
                                        ->orWhere('total_booking_amount', '<=', $maxBookingAmount);
                                })
                                ->orWhere('payment_method', '<>', 'cash_after_service');
                        });
                    })
                    ->where(function($query) use ($provider) {
                        $query->whereNull('provider_id')->orWhere('provider_id', $provider->id);
                    });
            } else {
                return $query->whereNull('id');
            }
        }
    }

    public function scopeProviderAcceptedBookings($query, $provider_id, $maxBookingAmount): mixed
    {
        return $query
            ->ofBookingStatus('accepted')
            ->where(function ($query) use ($provider_id) {
                $query->where('provider_id', $provider_id)
                    ->orWhereHas('repeat', function ($subQuery) use ($provider_id) {
                        $subQuery->where('provider_id', $provider_id);
                    });
            })
            ->when($maxBookingAmount > 0, function ($query) use ($maxBookingAmount) {
                $query->where(function ($query) use ($maxBookingAmount) {
                    $query->where('payment_method', 'cash_after_service')
                        ->where(function ($query) use ($maxBookingAmount) {
                            $query->where('total_booking_amount', '<=', $maxBookingAmount)
                                ->orWhere('is_verified', 1);
                        })
                        ->orWhere('payment_method', '<>', 'cash_after_service');
                });
            });
    }
}
