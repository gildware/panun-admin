<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;

/**
 * Read-only business intelligence tools for the admin panel AI expert.
 */
class AdminBusinessAiToolExecutor
{
    public function __construct(
        protected BookingReportAnalyticsService $bookingAnalytics,
        protected LeadOpenStatusService $leadOpenStatus,
        protected ProviderPerformanceService $providerPerformance,
        protected AdminBusinessAiWhatsAppInsightService $whatsAppInsights,
    ) {}

    /**
     * @param  array<string, mixed>|\stdClass  $args
     * @return array<string, mixed>
     */
    public function execute(string $name, array|\stdClass $args): array
    {
        $args = $this->normalizeArgs($args);

        return match ($name) {
            'get_business_dashboard_overview' => $this->getBusinessDashboardOverview(),
            'query_leads' => $this->queryLeads($args),
            'get_lead_details' => $this->getLeadDetails($args),
            'query_bookings' => $this->queryBookings($args),
            'get_booking_details' => $this->getBookingDetails($args),
            'query_providers' => $this->queryProviders($args),
            'get_provider_details' => $this->getProviderDetails($args),
            'query_customers' => $this->queryCustomers($args),
            'get_customer_details' => $this->getCustomerDetails($args),
            'get_business_reports' => $this->getBusinessReports($args),
            'get_whatsapp_conversations_overview' => $this->whatsAppInsights->overview(),
            'query_whatsapp_conversations' => $this->whatsAppInsights->queryConversations($args),
            'get_whatsapp_conversation_details' => $this->whatsAppInsights->conversationDetails($args),
            default => ['ok' => false, 'error' => 'unknown_tool'],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function functionDeclarations(): array
    {
        return [
            [
                'name' => 'get_business_dashboard_overview',
                'description' => 'Live dashboard KPIs: revenue, earnings, customers, providers, services, payables, losses. Call first for broad business health questions.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'query_leads',
                'description' => 'Search CRM leads with filters. Returns summary rows (id, name, phone, type, source, handled_by, next_followup_at, remarks snippet).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Name or phone substring'],
                        'lead_type' => ['type' => 'string', 'description' => 'customer|provider|unknown|invalid|future_customer|all'],
                        'handled_by' => ['type' => 'string', 'description' => 'Employee name/email or AI or __unassigned__'],
                        'open_only' => ['type' => 'boolean', 'description' => 'Only open leads per pipeline status'],
                        'overdue_followup' => ['type' => 'boolean', 'description' => 'next_followup_at before now'],
                        'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD received from'],
                        'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD received to'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_lead_details',
                'description' => 'Full lead record with followups and tags by lead id or phone.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'lead_id' => ['type' => 'integer'],
                        'phone' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'query_bookings',
                'description' => 'Search system bookings. Filter by readable_id, status, customer/provider phone or name, zone, date range.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'readable_id' => ['type' => 'string'],
                        'booking_status' => ['type' => 'string', 'description' => 'pending|accepted|ongoing|completed|canceled|etc'],
                        'customer_search' => ['type' => 'string'],
                        'provider_search' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_booking_details',
                'description' => 'One booking with customer, provider, amounts, schedule, status by readable_id or uuid.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'readable_id' => ['type' => 'string'],
                        'booking_id' => ['type' => 'string', 'description' => 'UUID'],
                    ],
                ],
            ],
            [
                'name' => 'query_providers',
                'description' => 'Search approved/active providers with performance hints.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Company or contact name / phone'],
                        'is_approved' => ['type' => 'boolean'],
                        'is_active' => ['type' => 'boolean'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_provider_details',
                'description' => 'Provider profile, zones, ratings, booking counts, incidents summary.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'provider_id' => ['type' => 'string'],
                        'search' => ['type' => 'string', 'description' => 'Name or phone if id unknown'],
                    ],
                ],
            ],
            [
                'name' => 'query_customers',
                'description' => 'Search customer accounts.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Name, phone, or email'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_customer_details',
                'description' => 'Customer profile with booking history summary.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'get_business_reports',
                'description' => 'Aggregated analytics: booking_analytics (includes zone_wise / area breakdown), financial_summary, lead_pipeline, provider_performance, customer_overview, whatsapp_pipeline. Optional date_from/date_to (YYYY-MM-DD).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'report_type' => [
                            'type' => 'string',
                            'description' => 'booking_analytics|financial_summary|lead_pipeline|provider_performance|customer_overview|whatsapp_pipeline',
                        ],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['report_type'],
                ],
            ],
            [
                'name' => 'get_whatsapp_conversations_overview',
                'description' => 'WhatsApp inbox snapshot: open/closed chats, unread, AI vs human assignment, unassigned chats, human-support queue, CRM lead linkage, and who handles linked leads for unassigned chats.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'query_whatsapp_conversations',
                'description' => 'Search/filter WhatsApp threads with CRM lead relation. Filters: chat_handler (ai|human|unassigned|human_support_pending|all), status_bucket (open|closed|all), has_linked_lead, lead_handler_unassigned, unread_only.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string'],
                        'chat_handler' => ['type' => 'string'],
                        'status_bucket' => ['type' => 'string'],
                        'has_linked_lead' => ['type' => 'boolean'],
                        'lead_handler_unassigned' => ['type' => 'boolean'],
                        'unread_only' => ['type' => 'boolean'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_whatsapp_conversation_details',
                'description' => 'One WhatsApp thread: messages, chat handler, linked CRM leads (with lead handler), WhatsApp booking requests, AI conversation state.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'phone' => ['type' => 'string', 'description' => 'WhatsApp thread phone key'],
                    ],
                    'required' => ['phone'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|\stdClass  $args
     * @return array<string, mixed>
     */
    private function normalizeArgs(array|\stdClass $args): array
    {
        if ($args instanceof \stdClass) {
            $decoded = json_decode(json_encode($args), true);

            return is_array($decoded) ? $decoded : [];
        }

        return $args;
    }

    /**
     * @return array<string, mixed>
     */
    private function performanceMetricsArray(mixed $metrics): array
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

    private function limit(array $args): int
    {
        $max = (int) config('admin_business_ai.max_query_limit', 50);
        $default = (int) config('admin_business_ai.default_query_limit', 25);
        $n = (int) ($args['limit'] ?? $default);

        return max(1, min($max, $n));
    }

    /**
     * @return array<string, mixed>
     */
    private function getBusinessDashboardOverview(): array
    {
        $baseQuery = BookingDetailsAmount::whereHas('booking', function ($query) {
            $query->forRevenueReporting();
        })->orWhereHas('repeat', function ($subQuery) {
            $subQuery->ofBookingStatus('completed');
        });
        $scaledAdj = admin_dashboard_scaled_admin_commission_adjustments(null);
        $adminCommission = (float) $baseQuery->sum('admin_commission') + (float) ($scaledAdj['total'] ?? 0);
        $discountByAdmin = (float) $baseQuery->sum('discount_by_admin');
        $couponDiscount = (float) $baseQuery->sum('coupon_discount_by_admin');
        $campaignDiscount = (float) $baseQuery->sum('campaign_discount_by_admin');
        $ourEarning = $adminCommission - $discountByAdmin - $couponDiscount - $campaignDiscount;

        $allCompletedRepeats = BookingRepeat::ofBookingStatus('completed')->with('booking.extra_services')->get();
        $repeatLineTotalByParentId = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($allCompletedRepeats);

        $totalRevenue = 0.0;
        $sparePartsTotal = 0.0;
        foreach (Booking::query()->forRevenueReporting()->with('extra_services')->cursor() as $b) {
            $slice = get_admin_dashboard_reporting_total_and_spare_for_booking($b);
            $totalRevenue += $slice['reported_total'];
            $sparePartsTotal += $slice['spare_parts'];
        }
        foreach ($allCompletedRepeats as $r) {
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatLineTotalByParentId[$parentKey] ?? get_booking_total_amount($r));
            $slice = get_admin_dashboard_reporting_total_and_spare_for_repeat($r, $den);
            $totalRevenue += $slice['reported_total'];
            $sparePartsTotal += $slice['spare_parts'];
        }

        $financial = admin_dashboard_financial_summary_metrics();

        return [
            'ok' => true,
            'as_of' => now()->toIso8601String(),
            'total_revenue' => round($totalRevenue, 2),
            'service_charges_total' => round($totalRevenue - $sparePartsTotal, 2),
            'spare_parts_total' => round($sparePartsTotal, 2),
            'our_earning' => round($ourEarning, 2),
            'total_customers' => User::query()->where('user_type', 'customer')->count(),
            'total_providers_approved' => Provider::query()->where('is_approved', 1)->count(),
            'total_services' => Service::query()->count(),
            'total_leads' => Lead::query()->count(),
            'open_leads_estimate' => $this->countOpenLeads(),
            'financial_summary' => $financial,
        ];
    }

    private function countOpenLeads(): int
    {
        $q = Lead::query();
        $this->leadOpenStatus->restrictQueryToOpenLeads($q);

        return $q->count();
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function queryLeads(array $args): array
    {
        $q = Lead::query()->with(['source:id,name', 'adSource:id,name', 'customerLeadTags:id,name']);
        $this->applyLeadFilters($q, $args);

        if (! empty($args['open_only'])) {
            $this->leadOpenStatus->restrictQueryToOpenLeads($q);
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('date_time_of_lead_received')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'leads' => $rows->map(fn (Lead $l) => $this->leadSummary($l))->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function applyLeadFilters(Builder $q, array $args): void
    {
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', $s)->orWhere('phone_number', 'like', $s);
            });
        }
        $type = (string) ($args['lead_type'] ?? '');
        if ($type !== '' && $type !== 'all') {
            $q->where('lead_type', $type);
        }
        if (! empty($args['handled_by'])) {
            $hb = (string) $args['handled_by'];
            if ($hb === Lead::FILTER_UNASSIGNED_VALUE) {
                $q->where(function ($w) {
                    $w->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
                });
            } else {
                $q->where('handled_by', 'like', '%'.$hb.'%');
            }
        }
        if (! empty($args['overdue_followup'])) {
            $q->whereNotNull('next_followup_at')->where('next_followup_at', '<', now());
        }
        if (! empty($args['date_from'])) {
            $q->where('date_time_of_lead_received', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('date_time_of_lead_received', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function leadSummary(Lead $l): array
    {
        return [
            'id' => $l->id,
            'name' => $l->name,
            'phone' => $l->phone_number,
            'lead_type' => $l->lead_type,
            'source' => $l->source?->name,
            'ad_source' => $l->adSource?->name,
            'handled_by' => $l->handled_by,
            'received_at' => $l->date_time_of_lead_received?->toIso8601String(),
            'next_followup_at' => $l->next_followup_at?->toIso8601String(),
            'tags' => $l->customerLeadTags->pluck('name')->all(),
            'remarks_snippet' => mb_substr((string) ($l->remarks ?? ''), 0, 200),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getLeadDetails(array $args): array
    {
        $lead = null;
        if (! empty($args['lead_id'])) {
            $lead = Lead::query()->with(['source', 'adSource', 'customerLeadTags', 'followups', 'createdBy'])->find((int) $args['lead_id']);
        } elseif (! empty($args['phone'])) {
            $phone = preg_replace('/\D+/', '', (string) $args['phone']) ?? '';
            $lead = Lead::query()->with(['source', 'adSource', 'customerLeadTags', 'followups', 'createdBy'])
                ->where('phone_number', 'like', '%'.$phone.'%')
                ->orderByDesc('id')
                ->first();
        }

        if (! $lead) {
            return ['ok' => false, 'error' => 'lead_not_found'];
        }

        return [
            'ok' => true,
            'lead' => array_merge($this->leadSummary($lead), [
                'remarks' => $lead->remarks,
                'created_by' => $lead->createdBy ? trim($lead->createdBy->first_name.' '.$lead->createdBy->last_name) : null,
                'followups' => $lead->followups->take(15)->map(fn ($f) => [
                    'at' => $f->followup_at?->toIso8601String(),
                    'note' => mb_substr((string) ($f->note ?? ''), 0, 500),
                    'next_followup_at' => $f->next_followup_at?->toIso8601String(),
                ])->values()->all(),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function queryBookings(array $args): array
    {
        $q = Booking::query()->with(['customer:id,first_name,last_name,phone,email', 'provider:id,company_name,contact_person_name']);
        $this->applyBookingFilters($q, $args);

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'bookings' => $rows->map(fn (Booking $b) => $this->bookingSummary($b))->values()->all(),
        ];
    }

    /**
     * @param  Builder<Booking>  $q
     * @param  array<string, mixed>  $args
     */
    private function applyBookingFilters(Builder $q, array $args): void
    {
        if (! empty($args['readable_id'])) {
            $q->where('readable_id', 'like', '%'.trim((string) $args['readable_id']).'%');
        }
        if (! empty($args['booking_status'])) {
            $q->where('booking_status', strtolower(trim((string) $args['booking_status'])));
        }
        if (! empty($args['customer_search'])) {
            $s = '%'.trim((string) $args['customer_search']).'%';
            $q->whereHas('customer', function ($cq) use ($s) {
                $cq->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }
        if (! empty($args['provider_search'])) {
            $s = '%'.trim((string) $args['provider_search']).'%';
            $q->whereHas('provider', function ($pq) use ($s) {
                $pq->where('company_name', 'like', $s)
                    ->orWhere('contact_person_name', 'like', $s)
                    ->orWhere('company_phone', 'like', $s);
            });
        }
        if (! empty($args['date_from'])) {
            $q->where('created_at', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('created_at', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingSummary(Booking $b): array
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
            'customer' => $b->customer ? [
                'name' => trim($b->customer->first_name.' '.$b->customer->last_name),
                'phone' => $b->customer->phone,
            ] : null,
            'provider' => $b->provider ? [
                'company' => $b->provider->company_name,
                'contact' => $b->provider->contact_person_name,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getBookingDetails(array $args): array
    {
        $booking = null;
        if (! empty($args['readable_id'])) {
            $rid = trim((string) $args['readable_id']);
            $booking = Booking::query()
                ->with(['customer', 'provider', 'zone', 'category', 'subCategory', 'detail.service', 'booking_partial_payments'])
                ->where('readable_id', $rid)
                ->first();
        } elseif (! empty($args['booking_id'])) {
            $booking = Booking::query()
                ->with(['customer', 'provider', 'zone', 'category', 'subCategory', 'detail.service', 'booking_partial_payments'])
                ->find((string) $args['booking_id']);
        }

        if (! $booking) {
            return ['ok' => false, 'error' => 'booking_not_found'];
        }

        return [
            'ok' => true,
            'booking' => array_merge($this->bookingSummary($booking), [
                'zone' => $booking->zone?->name,
                'category' => $booking->category?->name,
                'sub_category' => $booking->subCategory?->name,
                'service_address' => $booking->service_address_location,
                'customer_email' => $booking->customer?->email,
                'provider_phone' => $booking->provider?->company_phone,
                'partial_payments_count' => $booking->booking_partial_payments?->count() ?? 0,
                'services' => $booking->detail?->map(fn ($d) => $d->service?->name)->filter()->values()->all() ?? [],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function queryProviders(array $args): array
    {
        $q = Provider::query()->with(['owner:id,first_name,last_name,phone,email', 'zone:id,name']);
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where(function ($w) use ($s) {
                $w->where('company_name', 'like', $s)
                    ->orWhere('contact_person_name', 'like', $s)
                    ->orWhere('company_phone', 'like', $s)
                    ->orWhere('company_email', 'like', $s);
            });
        }
        if (isset($args['is_approved'])) {
            $q->where('is_approved', $args['is_approved'] ? 1 : 0);
        }
        if (isset($args['is_active'])) {
            $q->where('is_active', $args['is_active'] ? 1 : 0);
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($this->limit($args))->get();
        $perf = $this->providerPerformance->getAggregatedProviderPerformanceMetrics(
            $rows->pluck('id')->map(fn ($id) => (string) $id)->all()
        );

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'providers' => $rows->map(function (Provider $p) use ($perf) {
                $m = $this->performanceMetricsArray($perf->get((string) $p->id));

                return [
                    'id' => $p->id,
                    'company_name' => $p->company_name,
                    'contact' => $p->contact_person_name,
                    'phone' => $p->company_phone,
                    'zone' => $p->zone?->name,
                    'is_approved' => (bool) $p->is_approved,
                    'is_active' => (bool) $p->is_active,
                    'avg_rating' => (float) ($p->avg_rating ?? 0),
                    'order_count' => (int) ($p->order_count ?? 0),
                    'performance_score' => $m['performance_score'] ?? null,
                    'complaints_count' => $m['complaints_count'] ?? null,
                    'bookings_completed_count' => $m['bookings_completed_count'] ?? null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getProviderDetails(array $args): array
    {
        $provider = null;
        if (! empty($args['provider_id'])) {
            $provider = Provider::query()->with(['owner', 'zone', 'zones'])->find((string) $args['provider_id']);
        } elseif (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $provider = Provider::query()->with(['owner', 'zone', 'zones'])
                ->where(function ($w) use ($s) {
                    $w->where('company_name', 'like', $s)->orWhere('company_phone', 'like', $s);
                })
                ->first();
        }

        if (! $provider) {
            return ['ok' => false, 'error' => 'provider_not_found'];
        }

        $perf = $this->performanceMetricsArray(
            $this->providerPerformance->getAggregatedProviderPerformanceMetrics([(string) $provider->id])->first()
        );
        $completedBookings = Booking::query()
            ->where('provider_id', $provider->id)
            ->where('booking_status', 'completed')
            ->count();

        return [
            'ok' => true,
            'provider' => [
                'id' => $provider->id,
                'company_name' => $provider->company_name,
                'contact' => $provider->contact_person_name,
                'phone' => $provider->company_phone,
                'email' => $provider->company_email,
                'zone' => $provider->zone?->name,
                'zones_served' => $provider->zones?->pluck('name')->all() ?? [],
                'is_approved' => (bool) $provider->is_approved,
                'is_active' => (bool) $provider->is_active,
                'avg_rating' => (float) ($provider->avg_rating ?? 0),
                'rating_count' => (int) ($provider->rating_count ?? 0),
                'completed_bookings' => $completedBookings,
                'performance' => $perf,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function queryCustomers(array $args): array
    {
        $q = User::query()->where('user_type', 'customer');
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where(function ($w) use ($s) {
                $w->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'customers' => $rows->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->first_name.' '.$u->last_name),
                'phone' => $u->phone,
                'email' => $u->email,
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getCustomerDetails(array $args): array
    {
        $customer = null;
        if (! empty($args['customer_id'])) {
            $customer = User::query()->where('user_type', 'customer')->find((string) $args['customer_id']);
        } elseif (! empty($args['phone'])) {
            $phone = preg_replace('/\D+/', '', (string) $args['phone']) ?? '';
            $customer = User::query()->where('user_type', 'customer')->where('phone', 'like', '%'.$phone.'%')->first();
        }

        if (! $customer) {
            return ['ok' => false, 'error' => 'customer_not_found'];
        }

        $bookings = Booking::query()->where('customer_id', $customer->id);
        $bookingStats = [
            'total' => (clone $bookings)->count(),
            'completed' => (clone $bookings)->where('booking_status', 'completed')->count(),
            'pending' => (clone $bookings)->whereIn('booking_status', ['pending', 'accepted', 'ongoing'])->count(),
            'cancelled' => (clone $bookings)->whereIn('booking_status', ['canceled', 'cancelled'])->count(),
        ];
        $recent = Booking::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['readable_id', 'booking_status', 'total_booking_amount', 'created_at']);

        return [
            'ok' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => trim($customer->first_name.' '.$customer->last_name),
                'phone' => $customer->phone,
                'email' => $customer->email,
                'is_active' => (bool) $customer->is_active,
                'created_at' => $customer->created_at?->toIso8601String(),
                'booking_stats' => $bookingStats,
                'recent_bookings' => $recent->map(fn (Booking $b) => [
                    'readable_id' => $b->readable_id,
                    'status' => $b->booking_status,
                    'amount' => (float) ($b->total_booking_amount ?? 0),
                    'created_at' => $b->created_at?->toIso8601String(),
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getBusinessReports(array $args): array
    {
        $type = strtolower(trim((string) ($args['report_type'] ?? '')));
        $from = ! empty($args['date_from']) ? Carbon::parse((string) $args['date_from'])->startOfDay() : null;
        $to = ! empty($args['date_to']) ? Carbon::parse((string) $args['date_to'])->endOfDay() : null;

        return match ($type) {
            'booking_analytics' => $this->reportBookingAnalytics($from, $to),
            'financial_summary' => $this->reportFinancialSummary(),
            'lead_pipeline' => $this->reportLeadPipeline($from, $to),
            'provider_performance' => $this->reportProviderPerformance(),
            'customer_overview' => $this->reportCustomerOverview($from, $to),
            'whatsapp_pipeline' => ['ok' => true, 'report_type' => 'whatsapp_pipeline', 'data' => $this->whatsAppInsights->overview()],
            default => ['ok' => false, 'error' => 'unknown_report_type', 'allowed' => [
                'booking_analytics', 'financial_summary', 'lead_pipeline', 'provider_performance', 'customer_overview', 'whatsapp_pipeline',
            ]],
        };
    }

    private function reportBookingAnalytics(?Carbon $from, ?Carbon $to): array
    {
        $q = Booking::query();
        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to);
        }

        $payload = $this->bookingAnalytics->build($q);

        return ['ok' => true, 'report_type' => 'booking_analytics', 'data' => $payload];
    }

    private function reportFinancialSummary(): array
    {
        return [
            'ok' => true,
            'report_type' => 'financial_summary',
            'data' => [
                'dashboard_overview' => $this->getBusinessDashboardOverview(),
                'financial_metrics' => admin_dashboard_financial_summary_metrics(),
            ],
        ];
    }

    private function reportLeadPipeline(?Carbon $from, ?Carbon $to): array
    {
        $base = Lead::query();
        if ($from) {
            $base->where('date_time_of_lead_received', '>=', $from);
        }
        if ($to) {
            $base->where('date_time_of_lead_received', '<=', $to);
        }

        $byType = (clone $base)->selectRaw('lead_type, count(*) as cnt')->groupBy('lead_type')->pluck('cnt', 'lead_type');
        $overdue = Lead::query()->whereNotNull('next_followup_at')->where('next_followup_at', '<', now())->count();
        $dueToday = Lead::query()->whereDate('next_followup_at', today())->count();
        $unassigned = Lead::query()->where(function ($w) {
            $w->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
        })->count();

        return [
            'ok' => true,
            'report_type' => 'lead_pipeline',
            'data' => [
                'total_in_range' => (clone $base)->count(),
                'by_type' => $byType,
                'overdue_followups' => $overdue,
                'followups_due_today' => $dueToday,
                'unassigned_leads' => $unassigned,
                'open_leads' => $this->countOpenLeads(),
            ],
        ];
    }

    private function reportProviderPerformance(): array
    {
        $approved = Provider::query()->where('is_approved', 1)->count();
        $pendingApproval = Provider::query()->where('is_approved', 0)->count();
        $inactive = Provider::query()->where('is_active', 0)->count();

        $top = Provider::query()
            ->where('is_approved', 1)
            ->orderByDesc('order_count')
            ->limit(10)
            ->get(['id', 'company_name', 'order_count', 'avg_rating', 'rating_count']);

        return [
            'ok' => true,
            'report_type' => 'provider_performance',
            'data' => [
                'approved_count' => $approved,
                'pending_approval' => $pendingApproval,
                'inactive_count' => $inactive,
                'top_by_orders' => $top->map(fn (Provider $p) => [
                    'id' => $p->id,
                    'company' => $p->company_name,
                    'orders' => (int) ($p->order_count ?? 0),
                    'avg_rating' => (float) ($p->avg_rating ?? 0),
                ])->values()->all(),
            ],
        ];
    }

    private function reportCustomerOverview(?Carbon $from, ?Carbon $to): array
    {
        $q = User::query()->where('user_type', 'customer');
        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to);
        }

        return [
            'ok' => true,
            'report_type' => 'customer_overview',
            'data' => [
                'total_customers' => User::query()->where('user_type', 'customer')->count(),
                'new_in_range' => (clone $q)->count(),
                'active_customers' => User::query()->where('user_type', 'customer')->where('is_active', 1)->count(),
                'customers_with_bookings' => Booking::query()->distinct('customer_id')->count('customer_id'),
            ],
        ];
    }
}
