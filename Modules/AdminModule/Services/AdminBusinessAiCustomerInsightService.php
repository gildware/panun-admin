<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\ProviderManagement\Services\CustomerPerformanceService;
use Modules\ReviewModule\Entities\Review;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\UserManagement\Entities\User;

class AdminBusinessAiCustomerInsightService
{
    public function __construct(
        protected CustomerPerformanceService $performance,
    ) {}

    /**
     * @param  Collection<int, User>  $customers
     * @return list<array<string, mixed>>
     */
    public function enrichSummaries(Collection $customers): array
    {
        if ($customers->isEmpty()) {
            return [];
        }

        $metrics = $this->performance->getAggregatedCustomerPerformanceMetrics(
            $customers->pluck('id')->all()
        );

        return $customers->map(function (User $u) use ($metrics) {
            $m = $this->metricsArray($metrics->get($u->id));

            return [
                'id' => $u->id,
                'name' => trim($u->first_name.' '.$u->last_name),
                'phone' => $u->phone,
                'email' => $u->email,
                'is_active' => (bool) $u->is_active,
                'wallet_balance' => (float) ($u->wallet_balance ?? 0),
                'loyalty_point' => (float) ($u->loyalty_point ?? 0),
                'created_at' => $u->created_at?->toIso8601String(),
                'performance_score' => $m['performance_score'] ?? null,
                'complaints_count' => $m['complaints_count'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichDetail(User $customer): array
    {
        $customer->load(['account', 'addresses', 'zones']);

        $bookingOverview = DB::table('bookings')
            ->where('customer_id', $customer->id)
            ->select('booking_status', DB::raw('count(*) as total'))
            ->groupBy('booking_status')
            ->pluck('total', 'booking_status');

        $metrics = $this->metricsArray(
            $this->performance->getAggregatedCustomerPerformanceMetrics([$customer->id])->first()
        );

        $totalBookingAmount = function_exists('sum_customer_bookings_payable_grand_total')
            ? sum_customer_bookings_payable_grand_total($customer->id)
            : (float) Booking::query()->where('customer_id', $customer->id)->sum('total_booking_amount');

        $recentBookings = Booking::query()
            ->with(['provider:id,company_name'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'readable_id', 'booking_status', 'total_booking_amount', 'provider_id', 'created_at']);

        $incidents = CustomerIncident::query()
            ->where('customer_id', $customer->id)
            ->with(['booking:id,readable_id'])
            ->latest()
            ->limit(15)
            ->get();

        $reviews = Review::query()
            ->where('customer_id', $customer->id)
            ->with(['booking:id,readable_id', 'provider:id,company_name'])
            ->latest()
            ->limit(10)
            ->get();

        $linkedLeads = $this->findLeadsByPhone($customer->phone);

        $ledgerSummary = $this->ledgerSummaryForCustomer($customer->id, $recentBookings->pluck('id')->all());

        return [
            'id' => $customer->id,
            'name' => trim($customer->first_name.' '.$customer->last_name),
            'phone' => $customer->phone,
            'email' => $customer->email,
            'gender' => $customer->gender,
            'is_active' => (bool) $customer->is_active,
            'is_phone_verified' => (bool) $customer->is_phone_verified,
            'is_email_verified' => (bool) $customer->is_email_verified,
            'wallet_balance' => (float) ($customer->wallet_balance ?? 0),
            'loyalty_point' => (float) ($customer->loyalty_point ?? 0),
            'account' => $customer->account ? [
                'payable_balance' => (float) ($customer->account->payable_balance ?? 0),
                'receivable_balance' => (float) ($customer->account->receivable_balance ?? 0),
                'total_withdrawn' => (float) ($customer->account->total_withdrawn ?? 0),
            ] : null,
            'zones' => $customer->zones?->pluck('name')->all() ?? [],
            'addresses' => $customer->addresses?->map(fn ($a) => [
                'label' => $a->address_label ?? $a->address_type ?? null,
                'address' => $a->address,
                'city' => $a->city ?? null,
                'zone' => $a->zone ?? null,
            ])->values()->all() ?? [],
            'created_at' => $customer->created_at?->toIso8601String(),
            'booking_overview_by_status' => $bookingOverview,
            'total_booking_amount_payable' => (float) $totalBookingAmount,
            'booking_stats' => [
                'total' => (int) $bookingOverview->sum(),
                'completed' => (int) ($bookingOverview['completed'] ?? 0),
                'pending' => (int) (($bookingOverview['pending'] ?? 0) + ($bookingOverview['accepted'] ?? 0) + ($bookingOverview['ongoing'] ?? 0)),
                'cancelled' => (int) (($bookingOverview['canceled'] ?? 0) + ($bookingOverview['cancelled'] ?? 0)),
            ],
            'performance' => $metrics,
            'incidents' => $incidents->map(fn (CustomerIncident $i) => [
                'action_type' => $i->action_type,
                'incident_type' => $i->incident_type,
                'tags' => is_array($i->tags) ? $i->tags : [],
                'score_delta' => (int) ($i->score_delta ?? 0),
                'notes' => $i->notes,
                'booking_readable_id' => $i->booking?->readable_id,
                'at' => $i->created_at?->toIso8601String(),
            ])->values()->all(),
            'recent_bookings' => $recentBookings->map(fn (Booking $b) => [
                'readable_id' => $b->readable_id,
                'status' => $b->booking_status,
                'amount' => (float) ($b->total_booking_amount ?? 0),
                'provider' => $b->provider?->company_name,
                'created_at' => $b->created_at?->toIso8601String(),
            ])->values()->all(),
            'reviews' => $reviews->map(fn (Review $r) => [
                'rating' => (float) ($r->review_rating ?? 0),
                'comment' => $r->review_comment ?? $r->comment ?? null,
                'booking_readable_id' => $r->booking?->readable_id,
                'provider' => $r->provider?->company_name,
                'at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
            'linked_crm_leads' => $linkedLeads,
            'payments_summary' => $ledgerSummary,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'full_customer_overview')));
        $q = User::query()->where('user_type', 'customer');
        if (! empty($args['date_from'])) {
            $q->where('created_at', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('created_at', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }

        $total = (clone $q)->count();
        $active = (clone $q)->where('is_active', 1)->count();
        $withBookings = Booking::query()->distinct('customer_id')->count('customer_id');
        $incidentCount = CustomerIncident::query()->count();

        $payload = ['ok' => true, 'analysis' => $analysis, 'customers_in_scope' => $total];

        return match ($analysis) {
            'registration_overview' => array_merge($payload, [
                'active' => $active,
                'inactive' => $total - $active,
                'with_at_least_one_booking' => $withBookings,
            ]),
            'incident_overview' => array_merge($payload, [
                'total_incidents' => $incidentCount,
                'customers_with_incidents' => CustomerIncident::query()->distinct('customer_id')->count('customer_id'),
            ]),
            'full_customer_overview' => array_merge($payload, [
                'active' => $active,
                'with_at_least_one_booking' => $withBookings,
                'total_incidents' => $incidentCount,
            ]),
            default => [
                'ok' => false,
                'error' => 'unknown_analysis',
                'allowed' => ['registration_overview', 'incident_overview', 'full_customer_overview'],
            ],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findLeadsByPhone(?string $phone): array
    {
        $norm = $this->normalizePhone($phone);
        if ($norm === null) {
            return [];
        }

        return Lead::query()
            ->where('phone_number', 'like', '%'.$norm.'%')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'phone_number', 'lead_type', 'handled_by', 'next_followup_at'])
            ->map(fn (Lead $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'type' => $l->lead_type,
                'handled_by' => $l->handled_by,
                'next_followup_at' => $l->next_followup_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $bookingIds
     * @return array<string, mixed>
     */
    private function ledgerSummaryForCustomer(string $customerId, array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [
                'customer_paid_to_company' => 0.0,
                'company_paid_to_customer' => 0.0,
                'recent_transactions' => [],
            ];
        }

        $rows = LedgerTransaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'booking_id', 'amount', 'transaction_type', 'payment_method', 'date', 'remarks']);

        $paidToCompany = (float) LedgerTransaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->where('transaction_type', 'customer_paid_to_company')
            ->sum('amount');
        $paidToCustomer = (float) LedgerTransaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->where('transaction_type', 'company_paid_to_customer')
            ->sum('amount');

        return [
            'customer_paid_to_company' => round($paidToCompany, 2),
            'company_paid_to_customer' => round($paidToCustomer, 2),
            'recent_transactions' => $rows->map(fn ($t) => [
                'amount' => (float) ($t->amount ?? 0),
                'type' => $t->transaction_type,
                'method' => $t->payment_method,
                'date' => $t->date,
                'remarks' => $t->remarks,
            ])->values()->all(),
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    /**
     * @return array<string, mixed>
     */
    private function metricsArray(mixed $metrics): array
    {
        if (is_array($metrics)) {
            return $metrics;
        }
        if (is_object($metrics)) {
            $decoded = json_decode(json_encode($metrics), true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
