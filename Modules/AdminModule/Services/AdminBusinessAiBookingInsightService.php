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
            'extra_services',
            'booking_partial_payments',
            'compensations',
            'followups',
            'repeat',
            'status_histories',
            'change_logs.changedBy',
            'reopenEvents.holdReopenReason',
            'reopenEvents.childBooking',
            'originatedFromBooking',
            'spawnedFollowupBookings',
            'booking_details_amounts',
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

        return array_merge($this->summary($booking), [
            'lead' => $lead ? [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone_number,
                'type' => $lead->lead_type,
                'handled_by' => $lead->handled_by,
                'source' => $lead->source?->name,
            ] : null,
            'customer_email' => $booking->customer?->email,
            'provider_phone' => $booking->provider?->company_phone,
            'provider_email' => $booking->provider?->company_email,
            'zone' => $booking->zone?->name,
            'category' => $booking->category?->name,
            'sub_category' => $booking->subCategory?->name,
            'service_address' => $booking->service_address_location,
            'service_location' => $booking->service_location,
            'service_description' => $booking->service_description,
            'booking_source' => $booking->booking_source,
            'assignee' => $booking->assignee
                ? trim($booking->assignee->first_name.' '.$booking->assignee->last_name)
                : null,
            'serviceman' => $booking->serviceman?->user
                ? trim($booking->serviceman->user->first_name.' '.$booking->serviceman->user->last_name)
                : null,
            'scheduled_at' => $booking->service_schedule,
            'is_verified' => (bool) $booking->is_verified,
            'coupon_code' => $booking->coupon_code,
            'tax_amount' => (float) ($booking->total_tax_amount ?? 0),
            'discount_amount' => (float) ($booking->total_discount_amount ?? 0),
            'campaign_discount' => (float) ($booking->total_campaign_discount_amount ?? 0),
            'coupon_discount' => (float) ($booking->total_coupon_discount_amount ?? 0),
            'additional_charge' => (float) ($booking->additional_charge ?? 0),
            'grand_total_computed' => $grandTotal,
            'financial_amounts' => $amounts ? [
                'admin_commission' => (float) ($amounts->admin_commission ?? 0),
                'provider_earning' => (float) ($amounts->provider_earning ?? 0),
                'discount_by_admin' => (float) ($amounts->discount_by_admin ?? 0),
            ] : null,
            'settlement' => [
                'outcome' => $booking->settlement_outcome,
                'remarks' => $booking->settlement_remarks,
                'after_visit_cancel' => (bool) $booking->after_visit_cancel,
                'allow_complete_without_full_payment' => (bool) $booking->allow_complete_without_full_payment,
                'is_loss_making' => $booking->isLossMakingFinancialSettlement(),
            ],
            'reopen' => [
                'is_reopened' => $booking->isReopenedTagged(),
                'originated_from_readable_id' => $booking->originatedFromBooking?->readable_id,
                'last_reopen_at' => $booking->last_reopen_event_at?->toIso8601String(),
                'resolved_at' => $booking->reopen_resolved_at?->toIso8601String(),
                'resolve_remarks' => $booking->reopen_resolve_remarks,
                'spawned_followup_bookings' => $booking->spawnedFollowupBookings
                    ->map(fn (Booking $b) => ['readable_id' => $b->readable_id, 'status' => $b->booking_status])
                    ->values()
                    ->all(),
            ],
            'services' => $booking->detail?->map(fn ($d) => [
                'name' => $d->service?->name,
                'qty' => (int) ($d->qty ?? 1),
                'cost' => (float) ($d->service_cost ?? 0),
            ])->filter(fn ($s) => $s['name'])->values()->all() ?? [],
            'extra_services' => $booking->extra_services?->map(fn ($e) => [
                'name' => $e->name ?? $e->service_name ?? null,
                'qty' => (int) ($e->qty ?? 1),
                'price' => (float) ($e->price ?? 0),
            ])->values()->all() ?? [],
            'partial_payments' => $booking->booking_partial_payments->map(fn (BookingPartialPayment $p) => [
                'amount' => (float) ($p->paid_amount ?? $p->amount ?? 0),
                'method' => $p->payment_method ?? null,
                'paid_at' => $p->created_at?->toIso8601String(),
            ])->values()->all(),
            'compensations' => $booking->compensations->map(fn (BookingCompensation $c) => [
                'from' => $c->from_party,
                'to' => $c->to_party,
                'amount' => (float) ($c->amount ?? 0),
                'remarks' => $c->remarks ?? null,
                'at' => $c->created_at?->toIso8601String(),
            ])->values()->all(),
            'followups' => $booking->followups->take(20)->map(fn (BookingFollowup $f) => [
                'date' => $f->date?->toIso8601String(),
                'status' => $f->status,
                'for' => $f->for,
                'reason' => $f->reason,
                'remarks' => $f->remarks,
                'created_by' => $adminNames[(string) $f->created_by] ?? null,
            ])->values()->all(),
            'repeats' => $booking->repeat->map(fn (BookingRepeat $r) => [
                'id' => $r->id,
                'status' => $r->booking_status,
                'schedule' => $r->service_schedule,
                'amount' => (float) get_booking_total_amount($r),
            ])->values()->all(),
            'status_history' => $booking->status_histories->take(15)->map(fn (BookingStatusHistory $h) => [
                'status' => $h->booking_status,
                'at' => $h->created_at?->toIso8601String(),
                'changed_by' => $adminNames[(string) $h->changed_by] ?? null,
            ])->values()->all(),
            'change_logs' => $booking->change_logs->take(15)->map(fn (BookingChangeLog $c) => [
                'at' => $c->created_at?->toIso8601String(),
                'changed_by' => $c->changedBy
                    ? trim($c->changedBy->first_name.' '.$c->changedBy->last_name)
                    : ($c->actor_name ?? null),
                'property' => $c->property_label ?? $c->property_key,
                'old_value' => $c->old_value,
                'new_value' => $c->new_value,
            ])->values()->all(),
            'reopen_events' => $booking->reopenEvents->take(10)->map(fn (BookingReopenEvent $e) => [
                'resolution' => $e->resolution,
                'reason' => $e->holdReopenReason?->name,
                'child_readable_id' => $e->childBooking?->readable_id ?? null,
                'at' => $e->created_at?->toIso8601String(),
            ])->values()->all(),
            'reviews_count' => $booking->reviews?->count() ?? 0,
        ]);
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
            'lead_id' => $b->lead_id,
            'zone' => $b->relationLoaded('zone') ? $b->zone?->name : null,
            'customer' => $b->customer ? [
                'id' => $b->customer->id,
                'name' => trim($b->customer->first_name.' '.$b->customer->last_name),
                'phone' => $b->customer->phone,
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
