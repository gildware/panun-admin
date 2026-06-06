<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\ProviderManagement\Entities\Provider;
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
        protected AdminBusinessAiWhatsAppInsightService $whatsAppInsights,
        protected AdminBusinessAiLeadInsightService $leadInsights,
        protected AdminBusinessAiBookingInsightService $bookingInsights,
        protected AdminBusinessAiCustomerInsightService $customerInsights,
        protected AdminBusinessAiProviderInsightService $providerInsights,
        protected AdminBusinessAiDashboardInsightService $dashboardInsights,
        protected AdminBusinessAiEntityRelationService $entityRelations,
        protected AdminBusinessAiEmployeeInsightService $employeeInsights,
        protected AdminBusinessAiFinancialInsightService $financialInsights,
        protected AdminBusinessAiBookingQueueInsightService $bookingQueueInsights,
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
            'get_dashboard_snapshot' => $this->dashboardInsights->snapshot(),
            'get_entity_relations' => $this->entityRelations->resolve($args),
            'analyze_employee_activity' => $this->employeeInsights->analyze($args),
            'query_incomplete_leads' => $this->employeeInsights->queryIncompleteLeads($args),
            'query_leads' => $this->queryLeads($args),
            'get_lead_details' => $this->getLeadDetails($args),
            'analyze_leads' => $this->leadInsights->analyze($args),
            'query_outbound_enquiries' => $this->queryOutboundEnquiries($args),
            'query_bookings' => $this->queryBookings($args),
            'get_booking_details' => $this->getBookingDetails($args),
            'analyze_bookings' => $this->bookingInsights->analyze($args),
            'query_providers' => $this->queryProviders($args),
            'get_provider_details' => $this->getProviderDetails($args),
            'analyze_providers' => $this->providerInsights->analyze($args),
            'query_customers' => $this->queryCustomers($args),
            'get_customer_details' => $this->getCustomerDetails($args),
            'analyze_customers' => $this->customerInsights->analyze($args),
            'get_business_reports' => $this->getBusinessReports($args),
            'get_whatsapp_conversations_overview' => $this->whatsAppInsights->overview(),
            'query_whatsapp_conversations' => $this->whatsAppInsights->queryConversations($args),
            'get_whatsapp_conversation_details' => $this->whatsAppInsights->conversationDetails($args),
            'query_ledger' => $this->financialInsights->queryLedger($args),
            'query_transactions' => $this->financialInsights->queryTransactions($args),
            'query_withdraw_requests' => $this->financialInsights->queryWithdrawRequests($args),
            'query_pending_provider_balances' => $this->financialInsights->queryPendingProviderBalances($args),
            'query_booking_queues' => $this->bookingQueueInsights->query($args),
            'get_booking_queues_overview' => $this->bookingQueueInsights->overview(),
            'get_lead_inbound_report' => $this->leadInsights->inboundLeadReport($args),
            'get_employee_lead_productivity' => $this->leadInsights->employeeLeadProductivity($args),
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
                'name' => 'get_dashboard_snapshot',
                'description' => 'Full admin dashboard mirror: top cards, financial summary, compensation, recent ledger, pending bookings, top providers/customers, today booking+lead followups, monthly earning chart.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'analyze_employee_activity',
                'description' => 'Employee workload and data quality: leads handled (customer/provider), open leads, bookings as assignee, WhatsApp chats assigned, outbound enquiries, incomplete leads under each handler. analysis: workload_by_employee|chat_assignments|incomplete_leads_by_handler|full_employee_overview.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => ['type' => 'string'],
                        'employee_id' => ['type' => 'string', 'description' => 'Filter to one admin employee'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['analysis'],
                ],
            ],
            [
                'name' => 'query_incomplete_leads',
                'description' => 'Leads with unspecified/missing data (zone, category, status, handler, etc.): who handles them, lead type (customer/provider), whether they have a system booking.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'lead_type' => ['type' => 'string', 'description' => 'customer|provider|all'],
                        'handled_by' => ['type' => 'string', 'description' => 'Employee user id'],
                        'open_only' => ['type' => 'boolean'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_entity_relations',
                'description' => 'Cross-link entities by phone, lead_id, booking_id, readable_id, customer_id, or provider_id. Returns CRM leads, bookings, customer, provider, WhatsApp threads, outbound enquiries, and relation map.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'phone' => ['type' => 'string'],
                        'lead_id' => ['type' => 'integer'],
                        'booking_id' => ['type' => 'string'],
                        'readable_id' => ['type' => 'string'],
                        'customer_id' => ['type' => 'string'],
                        'provider_id' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'query_leads',
                'description' => 'Search CRM leads with all admin-tab fields: zone, categories, service, status, cancellation reason/remarks, received date, followups, handler, tags. Filter by customer_status or provider_status name (e.g. No Response, Pending) or status_search.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Name or phone substring'],
                        'lead_type' => ['type' => 'string', 'description' => 'customer|provider|unknown|invalid|future_customer|all'],
                        'customer_status' => ['type' => 'string', 'description' => 'Customer lead status name substring, e.g. No Response, Pending, Booked'],
                        'provider_status' => ['type' => 'string', 'description' => 'Provider lead status name substring'],
                        'status_search' => ['type' => 'string', 'description' => 'Match customer or provider status name'],
                        'non_responsive_only' => ['type' => 'boolean', 'description' => 'Invalid reason No Response, customer cancellation No Response From Customer, or status containing no response'],
                        'handled_by' => ['type' => 'string', 'description' => 'Employee name/email or AI or __unassigned__'],
                        'open_only' => ['type' => 'boolean', 'description' => 'Only open leads per pipeline status'],
                        'overdue_followup' => ['type' => 'boolean', 'description' => 'next_followup_at before now'],
                        'zone' => ['type' => 'string', 'description' => 'Zone name substring'],
                        'category' => ['type' => 'string', 'description' => 'Category/subcategory name substring'],
                        'source' => ['type' => 'string', 'description' => 'Lead source name substring'],
                        'tag' => ['type' => 'string', 'description' => 'Customer lead tag name'],
                        'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD received from'],
                        'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD received to'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_lead_details',
                'description' => 'Complete lead dossier with all_fields (zone, categories, service, cancellation reason/remarks, received date, followups, handler, tags, district/zones for provider). Includes type_history, activity_summary, status_timeline, followups, change_logs, provider checklist, linked bookings.',
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
                        'zone' => ['type' => 'string', 'description' => 'Zone name substring'],
                        'category' => ['type' => 'string', 'description' => 'Category name substring'],
                        'assignee_search' => ['type' => 'string', 'description' => 'Booking assignee employee name/email'],
                        'assignee_id' => ['type' => 'string', 'description' => 'Booking assignee user UUID'],
                        'lead_id' => ['type' => 'integer'],
                        'is_paid' => ['type' => 'boolean'],
                        'settlement_outcome' => ['type' => 'string', 'description' => 'e.g. scaled_to_payments'],
                        'overdue_followup' => ['type' => 'boolean', 'description' => 'Bookings with scheduled followup on or before today'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'analyze_bookings',
                'description' => 'Aggregate booking intelligence. Key: booking_timing_report (peak hours + lag: created→followup/accepted/completed/canceled/payment), cancellation_timing_report, followup_timing_report. Also: status_breakdown, followup_backlog, settlement_overview. cohort: all|pending|accepted|ongoing|completed|canceled|overdue_followup|loss_making|unpaid|verify_pending|offline_payment|reopened|after_visit_cancel. Scans up to 5000 bookings.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => ['type' => 'string', 'description' => 'booking_timing_report|cancellation_timing_report|followup_timing_report|status_breakdown|followup_backlog|settlement_overview|full_booking_overview'],
                        'cohort' => ['type' => 'string', 'description' => 'For booking_timing_report'],
                        'booking_status' => ['type' => 'string'],
                        'zone' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['analysis'],
                ],
            ],
            [
                'name' => 'get_booking_details',
                'description' => 'Complete booking dossier with all_fields: zone, category, services/variants, cancellation/hold/dispute reasons+remarks, schedule history, followups, partial payments (paid_with, due, received_by), settlement, repeats, compensations, reopen, status/change history, lead link, financial breakdown.',
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
                'name' => 'analyze_providers',
                'description' => 'Aggregate provider intelligence: approval_overview, incident_overview, full_provider_overview.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['analysis'],
                ],
            ],
            [
                'name' => 'get_provider_details',
                'description' => 'Complete provider dossier (admin provider tabs): owner, zones, bank, subscribed services, servicemen, performance, incidents, bookings, ratings, linked CRM leads.',
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
                'name' => 'analyze_customers',
                'description' => 'Aggregate customer intelligence: registration_overview, incident_overview, full_customer_overview.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['analysis'],
                ],
            ],
            [
                'name' => 'get_customer_details',
                'description' => 'Complete customer dossier (admin customer tabs): overview, addresses, wallet/loyalty, performance, incidents, reviews, payments/ledger, linked CRM leads.',
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
                            'description' => 'booking_analytics|financial_summary|lead_pipeline|provider_performance|customer_overview|whatsapp_pipeline|earning|expense|commission_earning|transaction_summary',
                        ],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['report_type'],
                ],
            ],
            [
                'name' => 'analyze_leads',
                'description' => 'Aggregate lead intelligence. For cancellation reason questions use customer_cancellation_reasons or provider_cancellation_reasons (returns by_reason ranked list). Also: invalid_reasons, future_customer_reasons, no_response_timing_report, lead_timing_report, no_response_leads, lead_activity_report, status breakdowns. Use date_from/date_to for range. Scans up to 5000 leads.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => ['type' => 'string', 'description' => 'no_response_timing_report|lead_timing_report|no_response_leads|lead_activity_report|customer_cancellation_reasons|customer_status_breakdown|invalid_reasons|full_lead_overview|etc'],
                        'lead_type' => ['type' => 'string', 'description' => 'customer|provider|invalid|future_customer|unknown|all'],
                        'cohort' => ['type' => 'string', 'description' => 'For lead_timing_report: all|non_responsive|invalid|invalid_no_response|customer|provider|customer_cancelled|customer_pending'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['analysis'],
                ],
            ],
            [
                'name' => 'get_whatsapp_conversations_overview',
                'description' => 'WhatsApp inbox snapshot: open/closed chats, unread, AI vs human assignment, unassigned chats, human-support queue, CRM lead linkage, and who handles linked leads for unassigned chats.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'query_whatsapp_conversations',
                'description' => 'Search/filter WhatsApp threads: who is assigned (chat_handler), linked CRM lead type (customer/provider), lead handler. Filters: chat_handler (ai|human|unassigned|human_support_pending|all), linked_lead_type (customer|provider), chat_handler_employee_id, status_bucket, has_linked_lead, lead_handler_unassigned, unread_only.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string'],
                        'chat_handler' => ['type' => 'string'],
                        'linked_lead_type' => ['type' => 'string', 'description' => 'customer|provider'],
                        'chat_handler_employee_id' => ['type' => 'string'],
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
            [
                'name' => 'query_ledger',
                'description' => 'Company ledger (money in/out): search by booking readable_id, transaction_id, reference. Returns totals and entries.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'description' => 'all|in|out'],
                        'search' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'query_transactions',
                'description' => 'Full payment activity: company ledger IN/OUT plus customer→provider direct booking payments. Mirrors admin transaction list.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'trx_type' => ['type' => 'string', 'description' => 'all|credit|debit'],
                        'search' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'query_withdraw_requests',
                'description' => 'Provider withdraw request queue: pending/approved/denied/settled with amounts and status breakdown.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'description' => 'pending|approved|denied|settled|all'],
                        'search' => ['type' => 'string', 'description' => 'Provider company name'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'query_pending_provider_balances',
                'description' => 'Providers who owe company money (pending collect-cash balances). Total due and per-provider breakdown.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string'],
                        'category_id' => ['type' => 'string'],
                        'sort' => ['type' => 'string', 'description' => 'balance_desc|balance_asc|name_asc'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_booking_queues_overview',
                'description' => 'Counts for booking operational queues: verify requests, offline payments, special scenarios, overdue followups.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'query_booking_queues',
                'description' => 'Booking operational queues: verify_requests (high-value cash), offline_payments, special_scenarios (loss-making/settlement), overdue_followups.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'queue' => ['type' => 'string', 'description' => 'verify_requests|offline_payments|special_scenarios|overdue_followups'],
                        'verify_type' => ['type' => 'string', 'description' => 'pending|denied — for verify_requests'],
                        'scenario' => ['type' => 'string', 'description' => 'all|loss_making|cancelled_after_visit|little_or_no_service'],
                        'zone' => ['type' => 'string'],
                        'category' => ['type' => 'string'],
                        'assignee_search' => ['type' => 'string'],
                        'readable_id' => ['type' => 'string'],
                        'search' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                    'required' => ['queue'],
                ],
            ],
            [
                'name' => 'get_lead_inbound_report',
                'description' => 'Full admin Lead Reports inbound analytics: conversion by zone/category/subcategory, cancellation reasons, hour/day patterns. report_type: customer|provider.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'report_type' => ['type' => 'string', 'description' => 'customer|provider'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['report_type'],
                ],
            ],
            [
                'name' => 'get_employee_lead_productivity',
                'description' => 'Per-employee lead productivity (admin lead user report): leads handled, open leads, by type, bookings from leads, followups.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'employee_id' => ['type' => 'string', 'description' => 'Admin user UUID'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                    ],
                    'required' => ['employee_id'],
                ],
            ],
            [
                'name' => 'query_outbound_enquiries',
                'description' => 'Search outbound lead enquiries (admin outbound tab): name, phone, status, contacted_through, handler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                        'handled_by' => ['type' => 'string'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
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
            'leads' => $this->leadInsights->enrichSummaries($rows),
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
        $this->leadInsights->applyStatusFilters($q, $args);
        $this->leadInsights->applyDimensionFilters($q, $args);
        if (! empty($args['non_responsive_only'])) {
            $this->leadInsights->applyNonResponsiveFilter($q);
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getLeadDetails(array $args): array
    {
        $lead = null;
        if (! empty($args['lead_id'])) {
            $lead = Lead::query()->find((int) $args['lead_id']);
        } elseif (! empty($args['phone'])) {
            $phone = preg_replace('/\D+/', '', (string) $args['phone']) ?? '';
            $lead = Lead::query()
                ->where('phone_number', 'like', '%'.$phone.'%')
                ->orderByDesc('id')
                ->first();
        }

        if (! $lead) {
            return ['ok' => false, 'error' => 'lead_not_found'];
        }

        return [
            'ok' => true,
            'lead' => $this->leadInsights->enrichDetail($lead),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function queryBookings(array $args): array
    {
        $q = Booking::query()->with([
            'customer:id,first_name,last_name,phone,email',
            'provider:id,company_name,contact_person_name,company_phone',
            'zone:id,name',
            'category:id,name',
            'subCategory:id,name',
            'assignee:id,first_name,last_name',
        ]);
        $this->applyBookingFilters($q, $args);

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'bookings' => $this->bookingInsights->enrichSummaries($rows),
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
        if (! empty($args['zone'])) {
            $zoneName = trim((string) $args['zone']);
            $q->whereHas('zone', fn ($zq) => $zq->where('name', 'like', '%'.$zoneName.'%'));
        }
        if (! empty($args['category'])) {
            $catName = trim((string) $args['category']);
            $q->where(function ($cq) use ($catName) {
                $cq->whereHas('category', fn ($catQ) => $catQ->where('name', 'like', '%'.$catName.'%'))
                    ->orWhereHas('subCategory', fn ($subQ) => $subQ->where('name', 'like', '%'.$catName.'%'));
            });
        }
        if (! empty($args['assignee_id'])) {
            $q->where('assignee_id', (string) $args['assignee_id']);
        } elseif (! empty($args['assignee_search'])) {
            $s = '%'.trim((string) $args['assignee_search']).'%';
            $q->whereHas('assignee', function ($aq) use ($s) {
                $aq->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }
        if (! empty($args['lead_id'])) {
            $q->where('lead_id', (int) $args['lead_id']);
        }
        if (array_key_exists('is_paid', $args)) {
            $q->where('is_paid', ! empty($args['is_paid']) ? 1 : 0);
        }
        if (! empty($args['settlement_outcome'])) {
            $q->where('settlement_outcome', trim((string) $args['settlement_outcome']));
        }
        if (! empty($args['overdue_followup'])) {
            $q->whereHas('followups', function ($fq) {
                $fq->where('status', 'scheduled')->whereDate('date', '<=', Carbon::today());
            })->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
        }
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
            $booking = Booking::query()->where('readable_id', $rid)->first();
        } elseif (! empty($args['booking_id'])) {
            $booking = Booking::query()->find((string) $args['booking_id']);
        }

        if (! $booking) {
            return ['ok' => false, 'error' => 'booking_not_found'];
        }

        return [
            'ok' => true,
            'booking' => $this->bookingInsights->enrichDetail($booking),
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

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'providers' => $this->providerInsights->enrichSummaries($rows),
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

        return [
            'ok' => true,
            'provider' => $this->providerInsights->enrichDetail($provider),
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
            'customers' => $this->customerInsights->enrichSummaries($rows),
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

        return [
            'ok' => true,
            'customer' => $this->customerInsights->enrichDetail($customer),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function queryOutboundEnquiries(array $args): array
    {
        $q = LeadOutboundEnquiry::query()->with(['createdBy', 'handledBy', 'statusConfig']);
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where(function ($w) use ($s) {
                $w->where('customer_name', 'like', $s)
                    ->orWhere('phone_number', 'like', $s)
                    ->orWhere('remarks', 'like', $s);
            });
        }
        if (! empty($args['status'])) {
            $q->where('status', trim((string) $args['status']));
        }
        if (! empty($args['handled_by'])) {
            $q->where('handled_by', 'like', '%'.trim((string) $args['handled_by']).'%');
        }
        if (! empty($args['date_from'])) {
            $q->where('contacted_at', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('contacted_at', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('contacted_at')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'outbound_enquiries' => $rows->map(fn (LeadOutboundEnquiry $e) => [
                'id' => $e->id,
                'name' => $e->customer_name,
                'phone' => $e->phone_number,
                'status' => $e->status,
                'status_label' => $e->statusConfig?->name,
                'contacted_through' => $e->contacted_through,
                'remarks' => $e->remarks,
                'handled_by' => $e->handledBy
                    ? trim($e->handledBy->first_name.' '.$e->handledBy->last_name)
                    : null,
                'contacted_at' => $e->contacted_at?->toIso8601String(),
            ])->values()->all(),
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
            'earning' => $this->financialInsights->reportEarning($from, $to),
            'expense' => $this->financialInsights->reportExpense($from, $to),
            'commission_earning' => $this->financialInsights->reportCommission($from, $to),
            'transaction_summary' => $this->financialInsights->queryTransactions([
                'date_from' => $from?->toDateString(),
                'date_to' => $to?->toDateString(),
                'trx_type' => 'all',
                'limit' => 15,
            ]),
            default => ['ok' => false, 'error' => 'unknown_report_type', 'allowed' => [
                'booking_analytics', 'financial_summary', 'lead_pipeline', 'provider_performance', 'customer_overview', 'whatsapp_pipeline',
                'earning', 'expense', 'commission_earning', 'transaction_summary',
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
