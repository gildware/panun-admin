<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderIncident;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\ReviewModule\Entities\Review;

class AdminBusinessAiProviderInsightService
{
    public function __construct(
        protected ProviderPerformanceService $performance,
    ) {}

    /**
     * @param  Collection<int, Provider>  $providers
     * @return list<array<string, mixed>>
     */
    public function enrichSummaries(Collection $providers): array
    {
        if ($providers->isEmpty()) {
            return [];
        }

        $perf = $this->performance->getAggregatedProviderPerformanceMetrics(
            $providers->pluck('id')->map(fn ($id) => (string) $id)->all()
        );

        return $providers->map(function (Provider $p) use ($perf) {
            $m = $this->metricsArray($perf->get((string) $p->id));

            return [
                'id' => $p->id,
                'company_name' => $p->company_name,
                'contact' => $p->contact_person_name,
                'phone' => $p->company_phone,
                'email' => $p->company_email,
                'zone' => $p->relationLoaded('zone') ? $p->zone?->name : null,
                'is_approved' => (bool) $p->is_approved,
                'is_active' => (bool) $p->is_active,
                'is_active_for_jobs' => (bool) ($p->is_active_for_jobs ?? false),
                'avg_rating' => (float) ($p->avg_rating ?? 0),
                'order_count' => (int) ($p->order_count ?? 0),
                'service_man_count' => (int) ($p->service_man_count ?? 0),
                'performance_score' => $m['performance_score'] ?? null,
                'complaints_count' => $m['complaints_count'] ?? null,
                'bookings_completed_count' => $m['bookings_completed_count'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichDetail(Provider $provider): array
    {
        $provider->load([
            'owner',
            'zone',
            'zones',
            'bank_detail',
            'subscribed_services.sub_category',
            'servicemen.user',
            'incidents.booking',
        ]);

        $perf = $this->metricsArray(
            $this->performance->getAggregatedProviderPerformanceMetrics([(string) $provider->id])->first()
        );

        $bookingOverview = DB::table('bookings')
            ->where('provider_id', $provider->id)
            ->select('booking_status', DB::raw('count(*) as total'))
            ->groupBy('booking_status')
            ->pluck('total', 'booking_status');

        $completedBookings = (int) ($bookingOverview['completed'] ?? 0);
        $recentBookings = Booking::query()
            ->with(['customer:id,first_name,last_name,phone'])
            ->where('provider_id', $provider->id)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'readable_id', 'booking_status', 'total_booking_amount', 'customer_id', 'created_at']);

        $ratingBreakdown = Review::query()
            ->where('provider_id', $provider->id)
            ->select('review_rating', DB::raw('count(*) as total'))
            ->groupBy('review_rating')
            ->pluck('total', 'review_rating');

        $linkedLeads = $this->findLeadsByPhone($provider->company_phone);

        return [
            'id' => $provider->id,
            'company_name' => $provider->company_name,
            'contact' => $provider->contact_person_name,
            'phone' => $provider->company_phone,
            'email' => $provider->company_email,
            'zone' => $provider->zone?->name,
            'zones_served' => $provider->zones?->pluck('name')->all() ?? [],
            'is_approved' => (bool) $provider->is_approved,
            'is_active' => (bool) $provider->is_active,
            'is_active_for_jobs' => (bool) ($provider->is_active_for_jobs ?? false),
            'app_availability' => (bool) ($provider->app_availability ?? false),
            'avg_rating' => (float) ($provider->avg_rating ?? 0),
            'rating_count' => (int) ($provider->rating_count ?? 0),
            'order_count' => (int) ($provider->order_count ?? 0),
            'service_capacity_per_day' => (int) ($provider->service_capacity_per_day ?? 0),
            'service_man_count' => (int) ($provider->service_man_count ?? 0),
            'commission_percentage' => (float) ($provider->commission_percentage ?? 0),
            'commission_status' => (int) ($provider->commission_status ?? 0),
            'owner' => $provider->owner ? [
                'name' => trim($provider->owner->first_name.' '.$provider->owner->last_name),
                'phone' => $provider->owner->phone,
                'email' => $provider->owner->email,
            ] : null,
            'bank' => $provider->bank_detail ? [
                'bank_name' => $provider->bank_detail->bank_name ?? null,
                'branch' => $provider->bank_detail->branch ?? null,
                'account_holder' => $provider->bank_detail->acc_holder_name ?? null,
            ] : null,
            'subscribed_services' => $provider->subscribed_services?->map(fn ($s) => [
                'sub_category' => $s->sub_category?->name,
                'is_subscribed' => (bool) ($s->is_subscribed ?? true),
            ])->filter(fn ($s) => $s['sub_category'])->values()->all() ?? [],
            'servicemen' => $provider->servicemen?->map(fn ($sm) => [
                'name' => $sm->user
                    ? trim($sm->user->first_name.' '.$sm->user->last_name)
                    : null,
                'phone' => $sm->user?->phone,
                'is_active' => (bool) ($sm->is_active ?? true),
            ])->values()->all() ?? [],
            'booking_overview_by_status' => $bookingOverview,
            'completed_bookings' => $completedBookings,
            'performance' => $perf,
            'rating_breakdown' => $ratingBreakdown,
            'incidents' => $provider->incidents->take(15)->map(fn (ProviderIncident $i) => [
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
                'customer' => $b->customer
                    ? trim($b->customer->first_name.' '.$b->customer->last_name)
                    : null,
                'created_at' => $b->created_at?->toIso8601String(),
            ])->values()->all(),
            'linked_crm_leads' => $linkedLeads,
            'created_at' => $provider->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'full_provider_overview')));
        $q = Provider::query();
        if (! empty($args['date_from'])) {
            $q->where('created_at', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('created_at', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }

        $total = (clone $q)->count();
        $approved = (clone $q)->where('is_approved', 1)->count();
        $pending = (clone $q)->where('is_approved', 0)->count();
        $inactive = (clone $q)->where('is_active', 0)->count();
        $incidentCount = ProviderIncident::query()->count();

        $payload = ['ok' => true, 'analysis' => $analysis, 'providers_in_scope' => $total];

        return match ($analysis) {
            'approval_overview' => array_merge($payload, [
                'approved' => $approved,
                'pending_approval' => $pending,
                'inactive' => $inactive,
            ]),
            'incident_overview' => array_merge($payload, [
                'total_incidents' => $incidentCount,
                'providers_with_incidents' => ProviderIncident::query()->distinct('provider_id')->count('provider_id'),
            ]),
            'full_provider_overview' => array_merge($payload, [
                'approved' => $approved,
                'pending_approval' => $pending,
                'inactive' => $inactive,
                'total_incidents' => $incidentCount,
            ]),
            default => [
                'ok' => false,
                'error' => 'unknown_analysis',
                'allowed' => ['approval_overview', 'incident_overview', 'full_provider_overview'],
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
            ->where('lead_type', Lead::TYPE_PROVIDER)
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
