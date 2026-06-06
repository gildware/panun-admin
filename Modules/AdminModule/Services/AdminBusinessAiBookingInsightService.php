<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingReopenEvent;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\LeadManagement\Entities\Lead;
use Modules\UserManagement\Entities\User;

class AdminBusinessAiBookingInsightService
{
    /**
     * @param  Collection<int, Booking>  $bookings
     * @return list<array<string, mixed>>
     */
    public function enrichSummaries(Collection $bookings): array
    {
        return $bookings->map(fn (Booking $b) => $this->summary($b))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichDetail(Booking $booking): array
    {
        $booking->load([
            'customer',
            'provider',
            'zone',
            'category',
            'subCategory',
            'serviceman.user',
            'assignee',
            'service_address',
            'detail.service',
            'detail.variation',
            'extra_services',
            'booking_partial_payments',
            'booking_offline_payments',
            'compensations',
            'followups',
            'repeat',
            'status_histories.cancellationReason',
            'status_histories.holdReopenReason',
            'status_histories.disputeReason',
            'status_histories.user',
            'latestParentCancellationStatusHistory.cancellationReason',
            'latestParentHoldStatusHistory.holdReopenReason',
            'latestParentDisputeStatusHistory.disputeReason',
            'schedule_histories.user',
            'change_logs.changedBy',
            'reopenEvents.holdReopenReason',
            'reopenEvents.childBooking',
            'reopenEvents.actor',
            'originatedFromBooking',
            'spawnedFollowupBookings',
            'reopenedByUser',
            'booking_details_amounts',
            'bookingDeniedNote',
            'reviews',
        ]);

        $lead = $booking->lead_id
            ? Lead::query()->with(['source', 'adSource'])->find($booking->lead_id)
            : null;

        $adminIds = collect()
            ->merge($booking->followups->pluck('created_by'))
            ->merge($booking->change_logs->pluck('changed_by'))
            ->merge($booking->status_histories->pluck('changed_by'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $adminNames = $this->adminNamesById($adminIds);

        $amounts = $booking->booking_details_amounts;
        $grandTotal = round(max(0.0, get_booking_total_amount($booking)), 2);
        $summary = $this->summary($booking);
        $detailPayload = [
            'lead' => $lead ? [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone_number,
                'type' => $lead->lead_type,
                'handled_by' => $lead->handled_by,
                'source' => $lead->source?->name,
                'ad_source' => $lead->adSource?->name,
                'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
                'remarks' => $lead->remarks,
            ] : null,
            'customer_email' => $booking->customer?->email,
            'provider_phone' => $booking->provider?->company_phone,
            'provider_email' => $booking->provider?->company_email,
            'zone' => $booking->zone?->name,
            'zone_id' => $booking->zone_id,
            'category' => $booking->category?->name,
            'category_id' => $booking->category_id,
            'sub_category' => $booking->subCategory?->name,
            'sub_category_id' => $booking->sub_category_id,
            'service_address' => $booking->service_address_location,
            'service_address_structured' => $booking->service_address ? [
                'address' => $booking->service_address->address ?? null,
                'city' => $booking->service_address->city ?? null,
                'zip_code' => $booking->service_address->zip_code ?? null,
                'latitude' => $booking->service_address->latitude ?? null,
                'longitude' => $booking->service_address->longitude ?? null,
            ] : null,
            'service_location' => $booking->service_location,
            'service_description' => $booking->service_description,
            'booking_source' => $booking->booking_source,
            'assignee' => $booking->assignee
                ? trim($booking->assignee->first_name.' '.$booking->assignee->last_name)
                : null,
            'assignee_id' => $booking->assignee_id,
            'serviceman' => $booking->serviceman?->user
                ? trim($booking->serviceman->user->first_name.' '.$booking->serviceman->user->last_name)
                : null,
            'serviceman_id' => $booking->serviceman_id,
            'scheduled_at' => $booking->service_schedule,
            'transaction_id' => $booking->transaction_id,
            'booking_otp' => $booking->booking_otp,
            'lead_id' => $booking->lead_id,
            'originated_from_booking_id' => $booking->originated_from_booking_id,
            'is_checked' => (bool) $booking->is_checked,
            'is_verified' => (bool) $booking->is_verified,
            'is_repeated' => (bool) ($booking->is_repeated ?? false),
            'provider_payment_confirmed_at' => $booking->provider_payment_confirmed_at,
            'coupon_code' => $booking->coupon_code,
            'tax_amount' => (float) ($booking->total_tax_amount ?? 0),
            'discount_amount' => (float) ($booking->total_discount_amount ?? 0),
            'campaign_discount' => (float) ($booking->total_campaign_discount_amount ?? 0),
            'coupon_discount' => (float) ($booking->total_coupon_discount_amount ?? 0),
            'additional_charge' => (float) ($booking->additional_charge ?? 0),
            'additional_tax_amount' => (float) ($booking->additional_tax_amount ?? 0),
            'additional_discount_amount' => (float) ($booking->additional_discount_amount ?? 0),
            'additional_campaign_discount_amount' => (float) ($booking->additional_campaign_discount_amount ?? 0),
            'admin_commission_override' => $booking->admin_commission_override,
            'grand_total_computed' => $grandTotal,
            'financial_amounts' => $amounts ? [
                'service_unit_cost' => (float) ($amounts->service_unit_cost ?? 0),
                'service_quantity' => (int) ($amounts->service_quantity ?? 0),
                'service_tax' => (float) ($amounts->service_tax ?? 0),
                'discount_by_admin' => (float) ($amounts->discount_by_admin ?? 0),
                'discount_by_provider' => (float) ($amounts->discount_by_provider ?? 0),
                'coupon_discount_by_admin' => (float) ($amounts->coupon_discount_by_admin ?? 0),
                'coupon_discount_by_provider' => (float) ($amounts->coupon_discount_by_provider ?? 0),
                'campaign_discount_by_admin' => (float) ($amounts->campaign_discount_by_admin ?? 0),
                'campaign_discount_by_provider' => (float) ($amounts->campaign_discount_by_provider ?? 0),
                'discount_cost_bearer' => $amounts->discount_cost_bearer ?? null,
                'admin_commission' => (float) ($amounts->admin_commission ?? 0),
                'provider_earning' => (float) ($amounts->provider_earning ?? 0),
            ] : null,
            'latest_status_reasons' => [
                'cancellation_reason' => $booking->latestParentCancellationStatusHistory?->cancellationReason?->name,
                'cancellation_remarks' => $booking->latestParentCancellationStatusHistory?->status_change_remarks,
                'hold_reason' => $booking->latestParentHoldStatusHistory?->holdReopenReason?->name,
                'hold_remarks' => $booking->latestParentHoldStatusHistory?->status_change_remarks,
                'dispute_reason' => $booking->latestParentDisputeStatusHistory?->disputeReason?->name,
                'dispute_remarks' => $booking->latestParentDisputeStatusHistory?->status_change_remarks,
            ],
            'booking_denied_note' => $booking->bookingDeniedNote?->value,
            'settlement' => [
                'outcome' => $booking->settlement_outcome,
                'remarks' => $booking->settlement_remarks,
                'config' => $booking->settlement_config,
                'snapshot' => $booking->settlement_snapshot,
                'after_visit_cancel' => (bool) $booking->after_visit_cancel,
                'allow_complete_without_full_payment' => (bool) $booking->allow_complete_without_full_payment,
                'reopen_completion_allowed' => (bool) ($booking->reopen_completion_allowed ?? false),
                'has_disputed_snapshot' => ! empty($booking->reopen_disputed_snapshot),
                'is_loss_making' => $booking->isLossMakingFinancialSettlement(),
            ],
            'reopen' => [
                'is_reopened' => $booking->isReopenedTagged(),
                'originated_from_readable_id' => $booking->originatedFromBooking?->readable_id,
                'last_reopen_at' => $booking->last_reopen_event_at?->toIso8601String(),
                'reopened_by' => $booking->reopenedByUser
                    ? trim($booking->reopenedByUser->first_name.' '.$booking->reopenedByUser->last_name)
                    : null,
                'resolved_at' => $booking->reopen_resolved_at?->toIso8601String(),
                'resolve_remarks' => $booking->reopen_resolve_remarks,
                'spawned_followup_bookings' => $booking->spawnedFollowupBookings
                    ->map(fn (Booking $b) => ['readable_id' => $b->readable_id, 'status' => $b->booking_status])
                    ->values()
                    ->all(),
            ],
            'services' => $booking->detail?->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->service?->name,
                'variant_key' => $d->variant_key ?? null,
                'variant' => $d->variation?->variant ?? $d->variation?->variant_key ?? null,
                'qty' => (int) ($d->quantity ?? $d->qty ?? 1),
                'unit_cost' => (float) ($d->service_cost ?? 0),
                'discount' => (float) ($d->discount_amount ?? 0),
                'campaign_discount' => (float) ($d->campaign_discount_amount ?? 0),
                'tax' => (float) ($d->tax_amount ?? 0),
                'line_total' => (float) ($d->total_cost ?? 0),
            ])->filter(fn ($s) => $s['name'])->values()->all() ?? [],
            'extra_services' => $booking->extra_services?->map(fn ($e) => [
                'title' => $e->title ?? $e->name ?? $e->service_name ?? null,
                'type' => $e->type ?? null,
                'details' => $e->details ?? null,
                'qty' => (int) ($e->quantity ?? $e->qty ?? 1),
                'price' => (float) ($e->price ?? 0),
                'discount' => (float) ($e->discount ?? 0),
                'total' => (float) ($e->total ?? 0),
            ])->values()->all() ?? [],
            'partial_payments' => $booking->booking_partial_payments->map(fn (BookingPartialPayment $p) => [
                'id' => $p->id,
                'paid_with' => $p->paid_with,
                'payment_method' => $p->paymentMethodLabelForAdmin($booking),
                'paid_amount' => (float) ($p->paid_amount ?? 0),
                'due_amount' => (float) ($p->due_amount ?? 0),
                'received_by' => $p->received_by,
                'transaction_id' => $p->transaction_id,
                'paid_at' => $p->created_at?->toIso8601String(),
            ])->values()->all(),
            'compensations' => $booking->compensations->map(fn (BookingCompensation $c) => [
                'from' => $c->from_party,
                'to' => $c->to_party,
                'amount' => (float) ($c->amount ?? 0),
                'remarks' => $c->remarks ?? null,
                'at' => $c->created_at?->toIso8601String(),
            ])->values()->all(),
            'followups' => $booking->followups->map(fn (BookingFollowup $f) => [
                'id' => $f->id,
                'date' => $f->date?->toIso8601String(),
                'status' => $f->status,
                'for' => $f->for,
                'reason' => $f->reason,
                'remarks' => $f->remarks,
                'reschedule_reason' => $f->reschedule_reason,
                'created_by' => $adminNames[(string) $f->created_by] ?? null,
                'created_at' => $f->created_at?->toIso8601String(),
            ])->values()->all(),
            'repeats' => $booking->repeat->map(fn (BookingRepeat $r) => [
                'id' => $r->id,
                'status' => $r->booking_status,
                'schedule' => $r->service_schedule,
                'amount' => (float) get_booking_total_amount($r),
            ])->values()->all(),
            'status_history' => $booking->status_histories->map(fn (BookingStatusHistory $h) => [
                'status' => $h->booking_status,
                'at' => $h->created_at?->toIso8601String(),
                'changed_by' => $adminNames[(string) $h->changed_by] ?? ($h->user ? trim($h->user->first_name.' '.$h->user->last_name) : null),
                'cancellation_reason' => $h->cancellationReason?->name,
                'hold_reason' => $h->holdReopenReason?->name,
                'dispute_reason' => $h->disputeReason?->name,
                'remarks' => $h->status_change_remarks,
            ])->values()->all(),
            'schedule_history' => $booking->schedule_histories->map(fn ($h) => [
                'schedule' => $h->schedule ?? null,
                'at' => $h->created_at?->toIso8601String(),
                'changed_by' => $h->user ? trim($h->user->first_name.' '.$h->user->last_name) : null,
                'is_guest' => (bool) ($h->is_guest ?? false),
            ])->values()->all(),
            'change_logs' => $booking->change_logs->map(fn (BookingChangeLog $c) => [
                'at' => $c->created_at?->toIso8601String(),
                'changed_by' => $c->changedBy
                    ? trim($c->changedBy->first_name.' '.$c->changedBy->last_name)
                    : ($c->actor_name ?? null),
                'property' => $c->property_label ?? $c->property_key,
                'old_value' => $c->old_value,
                'new_value' => $c->new_value,
            ])->values()->all(),
            'reopen_events' => $booking->reopenEvents->map(fn (BookingReopenEvent $e) => [
                'resolution' => $e->resolution,
                'reason' => $e->holdReopenReason?->name,
                'child_readable_id' => $e->childBooking?->readable_id ?? null,
                'actor' => $e->actor ? trim($e->actor->first_name.' '.$e->actor->last_name) : null,
                'at' => $e->created_at?->toIso8601String(),
            ])->values()->all(),
            'evidence_photos_count' => is_array($booking->evidence_photos) ? count($booking->evidence_photos) : 0,
            'reviews_count' => $booking->reviews?->count() ?? 0,
            'created_at' => $booking->created_at?->toIso8601String(),
            'updated_at' => $booking->updated_at?->toIso8601String(),
        ];

        $detailPayload['all_fields'] = $this->flattenBookingAdminFields($summary, $detailPayload);

        return array_merge($summary, $detailPayload);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'full_booking_overview')));
        $q = Booking::query();
        if (! empty($args['date_from'])) {
            $q->where('created_at', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('created_at', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }
        if (! empty($args['booking_status'])) {
            $q->where('booking_status', strtolower(trim((string) $args['booking_status'])));
        }

        $base = clone $q;
        $byStatus = (clone $base)->selectRaw('booking_status, count(*) as cnt')->groupBy('booking_status')->pluck('cnt', 'booking_status');
        $total = (clone $base)->count();
        $paid = (clone $base)->where('is_paid', 1)->count();
        $withLead = (clone $base)->whereNotNull('lead_id')->count();
        $reopened = (clone $base)->whereNotNull('last_reopen_event_at')->count();
        $lossMaking = (clone $base)->where('settlement_outcome', 'scaled_to_payments')->count();

        $overdueFollowups = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereDate('date', '<=', Carbon::today())
            ->whereHas('booking', function ($bq) {
                $bq->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->count();

        $payload = [
            'ok' => true,
            'analysis' => $analysis,
            'bookings_in_scope' => $total,
        ];

        return match ($analysis) {
            'status_breakdown' => array_merge($payload, ['by_status' => $byStatus]),
            'followup_backlog' => array_merge($payload, [
                'overdue_scheduled_followups' => $overdueFollowups,
            ]),
            'settlement_overview' => array_merge($payload, [
                'scaled_to_payments_count' => $lossMaking,
                'after_visit_cancel_count' => (clone $base)->where('after_visit_cancel', true)->count(),
            ]),
            'full_booking_overview' => array_merge($payload, [
                'by_status' => $byStatus,
                'paid_count' => $paid,
                'unpaid_count' => $total - $paid,
                'with_lead_link' => $withLead,
                'reopened_count' => $reopened,
                'overdue_scheduled_followups' => $overdueFollowups,
                'scaled_to_payments_count' => $lossMaking,
            ]),
            default => [
                'ok' => false,
                'error' => 'unknown_analysis',
                'allowed' => ['status_breakdown', 'followup_backlog', 'settlement_overview', 'full_booking_overview'],
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Booking $b): array
    {
        return [
            'id' => $b->id,
            'readable_id' => $b->readable_id,
            'status' => $b->booking_status,
            'total_amount' => (float) ($b->total_booking_amount ?? 0),
            'is_paid' => (bool) $b->is_paid,
            'payment_method' => $b->payment_method,
            'scheduled_at' => $b->service_schedule,
            'created_at' => $b->created_at?->toIso8601String(),
            'updated_at' => $b->updated_at?->toIso8601String(),
            'lead_id' => $b->lead_id,
            'zone' => $b->relationLoaded('zone') ? $b->zone?->name : null,
            'category' => $b->relationLoaded('category') ? $b->category?->name : null,
            'sub_category' => $b->relationLoaded('subCategory') ? $b->subCategory?->name : null,
            'assignee' => $b->relationLoaded('assignee') && $b->assignee
                ? trim($b->assignee->first_name.' '.$b->assignee->last_name)
                : null,
            'booking_source' => $b->booking_source,
            'service_address' => $b->service_address_location,
            'customer' => $b->customer ? [
                'id' => $b->customer->id,
                'name' => trim($b->customer->first_name.' '.$b->customer->last_name),
                'phone' => $b->customer->phone,
                'email' => $b->customer->email ?? null,
            ] : null,
            'provider' => $b->provider ? [
                'id' => $b->provider->id,
                'company' => $b->provider->company_name,
                'contact' => $b->provider->contact_person_name,
                'phone' => $b->provider->company_phone,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function flattenBookingAdminFields(array $summary, array $detail): array
    {
        $flat = $summary;
        $scalarKeys = [
            'zone_id', 'category_id', 'sub_category_id', 'service_location', 'service_description',
            'transaction_id', 'booking_otp', 'originated_from_booking_id', 'is_checked', 'is_verified',
            'is_repeated', 'coupon_code', 'tax_amount', 'discount_amount', 'campaign_discount',
            'coupon_discount', 'additional_charge', 'grand_total_computed', 'booking_denied_note',
            'assignee_id', 'serviceman_id', 'provider_payment_confirmed_at',
        ];
        foreach ($scalarKeys as $key) {
            if (array_key_exists($key, $detail)) {
                $flat[$key] = $detail[$key];
            }
        }
        if (! empty($detail['latest_status_reasons']) && is_array($detail['latest_status_reasons'])) {
            foreach ($detail['latest_status_reasons'] as $k => $v) {
                $flat['latest_'.$k] = $v;
            }
        }
        if (! empty($detail['settlement']) && is_array($detail['settlement'])) {
            foreach ($detail['settlement'] as $k => $v) {
                if (! in_array($k, ['config', 'snapshot'], true)) {
                    $flat['settlement_'.$k] = $v;
                }
            }
        }
        if (! empty($detail['reopen']) && is_array($detail['reopen'])) {
            foreach ($detail['reopen'] as $k => $v) {
                if ($k !== 'spawned_followup_bookings') {
                    $flat['reopen_'.$k] = $v;
                }
            }
        }
        if (! empty($detail['lead']) && is_array($detail['lead'])) {
            foreach ($detail['lead'] as $k => $v) {
                $flat['lead_'.$k] = $v;
            }
        }
        if (! empty($detail['financial_amounts']) && is_array($detail['financial_amounts'])) {
            foreach ($detail['financial_amounts'] as $k => $v) {
                $flat['financial_'.$k] = $v;
            }
        }

        return $flat;
    }

    /**
     * @param  list<string|int|null>  $ids
     * @return array<string, string>
     */
    private function adminNamesById(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(fn (User $u) => [
                (string) $u->id => trim($u->first_name.' '.$u->last_name) ?: ($u->email ?? ''),
            ])
            ->all();
    }
}
