<?php

namespace Modules\AdminModule\Services;

class EmployeeProgressMetricHelp
{
    /**
     * @return array<string, array{title: string, summary: string}>
     */
    public static function registry(): array
    {
        return array_merge(
            self::kpiHelp(),
            self::overviewHelp(),
            self::bookingHelp(),
            self::leadHelp(),
            self::followupHelp(),
            self::dailyBasisHelp(),
        );
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function entry(string $title, string $summary): array
    {
        return compact('title', 'summary');
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function kpiHelp(): array
    {
        return [
            'leads_added' => self::entry(
                translate('Leads_added'),
                'Shows how many new leads were added to your work during the selected dates.',
            ),
            'bookings_created' => self::entry(
                translate('Bookings_created'),
                'Shows how many bookings you created in the selected period.',
            ),
            'completed_bookings' => self::entry(
                translate('Bookings_completed'),
                'Shows how many of your bookings were marked completed during the selected period.',
            ),
            'completed_amount' => self::entry(
                translate('Completed_amount'),
                'Shows the total value of bookings you completed in this period.',
            ),
            'completion_rate' => self::entry(
                translate('completion_rate'),
                'Shows what share of the bookings you created were completed. A higher number means more bookings reached a successful finish.',
            ),
            'cancelled_bookings' => self::entry(
                translate('Cancelled'),
                'Shows how many bookings were cancelled during the selected period.',
            ),
            'data_quality' => self::entry(
                translate('Progress_quality_metrics'),
                'Shows how complete and tidy your logged work looks. Higher is better and means fewer missing details.',
            ),
            'lead_followups' => self::entry(
                translate('Lead_followups'),
                'Total lead follow-ups you logged in the selected period (Taken + Reschedule).',
            ),
            'booking_followups' => self::entry(
                translate('Booking_Followups'),
                'Booking follow-ups marked as Taken (completed) in the selected period. Reschedule is counted separately.',
            ),
            'missed_followups' => self::entry(
                translate('Progress_missed_followups'),
                'Shows follow-ups that were due in the selected dates but are still not done.',
            ),
            'pending_followups' => self::entry(
                translate('Pending').' '.translate('Follow_ups'),
                'Shows follow-ups still open and due later within the selected dates.',
            ),
            'followup_accuracy' => self::entry(
                translate('Follow_up_accuracy'),
                'Shows how well you are keeping up with follow-ups. Higher means fewer overdue items.',
            ),
            'completion_summary_ring' => self::entry(
                translate('completion_rate'),
                'A quick snapshot of your booking completion and follow-up performance for the current view.',
            ),
        ];
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function overviewHelp(): array
    {
        return [
            'chart_revenue_main' => self::entry(
                translate('Revenue_Overview') ?? translate('Daily_activity_breakdown'),
                'Compares your daily bookings and leads so you can spot busy days and quiet days.',
            ),
            'team_ranking' => self::entry(
                translate('Progress_team_ranking'),
                'Score = quantity marks − quality penalties. Quantity: Bookings created (+3), Leads handled (+3), Chat replies (+1). Penalties: Late follow-ups (−1), Missed follow-ups (−1), Booking cancellations (−3). Bookings created and Leads handled match the Bookings and Leads tabs for the same period.',
            ),
            'progress_insights' => self::entry(
                translate('Progress_improvements') ?? 'Insights',
                'Helpful notes about what is going well and what may need your attention right now.',
            ),
        ];
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function bookingHelp(): array
    {
        return [
            'booking_status_handled' => self::entry(
                translate('Bookings_created'),
                'All bookings you created in this period. The other booking cards show their share out of this total.',
            ),
            'booking_status_completed' => self::entry(
                translate('Bookings_completed'),
                'Bookings that were finished successfully during this period.',
            ),
            'booking_status_completed_amount' => self::entry(
                translate('Completed_amount'),
                'Total value of bookings you completed in this period.',
            ),
            'booking_status_completion_rate' => self::entry(
                translate('completion_rate'),
                'Share of bookings you created that were completed.',
            ),
            'booking_status_pending' => self::entry(
                translate('Pending'),
                'Bookings created in this period that are still pending.',
            ),
            'booking_status_accepted' => self::entry(
                translate('Accepted'),
                'Bookings created in this period that are currently accepted.',
            ),
            'booking_status_ongoing' => self::entry(
                translate('Ongoing'),
                'Bookings created in this period that are currently ongoing.',
            ),
            'booking_status_on_hold' => self::entry(
                translate('On_hold') ?? 'On hold',
                'Bookings created in this period that are on hold (before or without an after-visit hold).',
            ),
            'booking_status_hold_after_visit' => self::entry(
                translate('Hold_after_visit') ?? 'Hold after visit',
                'Bookings created in this period that were put on hold after work had already started (from Ongoing).',
            ),
            'booking_status_cancelled' => self::entry(
                translate('Cancelled'),
                'Bookings created in this period that were cancelled.',
            ),
            'booking_status_active_pipeline' => self::entry(
                translate('Progress_active_bookings'),
                'All bookings currently assigned to you that are still active, no matter when they were created.',
            ),
            'chart_bookings_trend' => self::entry(
                translate('Bookings_created'),
                'Shows day-by-day booking activity so you can see when you created and completed the most work.',
            ),
            'chart_revenue_bookings_trend' => self::entry(
                translate('Progress_booking_trend') ?? 'Booking Trend',
                'Shows bookings created, completed, and cancelled day by day for the selected period.',
            ),
            'chart_booking_trend' => self::entry(
                translate('Progress_booking_trend') ?? 'Booking Trend',
                'Shows bookings you created each day, stacked by full status mix including hold, hold after visit, cancelled after visit, disputed, and loss-making outcomes.',
            ),
            'booking_reason_reports_section' => self::entry(
                translate('Progress_booking_reason_reports') ?? 'Status reason reports',
                'Breaks down why bookings were put on hold, held after visit, cancelled, disputed, or marked as loss-making.',
            ),
            'booking_reason_on_hold' => self::entry(
                translate('On_hold') ?? 'On hold',
                'Reasons recorded when bookings created in this period were put on hold (not hold after visit).',
            ),
            'booking_reason_hold_after_visit' => self::entry(
                translate('Hold_after_visit') ?? 'Hold after visit',
                'Reasons for bookings put on hold after work had already started (from Ongoing).',
            ),
            'booking_reason_cancelled' => self::entry(
                translate('Cancelled'),
                'Cancellation reasons (admin, customer, or provider) for cancelled bookings created in this period.',
            ),
            'booking_reason_disputed' => self::entry(
                translate('Disputed') ?? 'Disputed',
                'Dispute reasons for disputed-close bookings created in this period.',
            ),
            'booking_reason_loss' => self::entry(
                translate('Loss_making') ?? 'Loss making',
                'Settlement remarks or loss stage (pending, recovered, settled) for loss-making bookings.',
            ),
            'chart_funnel' => self::entry(
                translate('Progress_activity_metrics'),
                'Shows your flow from leads to bookings to completed bookings in this period.',
            ),
            'chart_mix' => self::entry(
                translate('Booking_report_summary'),
                'Shows how your bookings split between completed, still pending, and cancelled.',
            ),
            'chart_rev_src' => self::entry(
                translate('Revenue_Overview') ?? translate('Revenue_Summary'),
                'Shows which sources brought the most booking activity or revenue in this period.',
            ),
            'chart_rev_secondary' => self::entry(
                translate('Completed_amount'),
                'Shows how your completed booking value changed across the selected dates.',
            ),
            'recent_bookings_table' => self::entry(
                translate('Bookings_Created'),
                'A list of recent bookings created in this period with customer, source, status, and age.',
            ),
            'revenue_summary_table' => self::entry(
                translate('Revenue_Summary'),
                'Breaks down your results by source so you can see which channels perform best.',
            ),
        ];
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function leadHelp(): array
    {
        $types = [
            'lead_type_customer' => translate('Customer'),
            'lead_type_provider' => translate('Provider'),
            'lead_type_unknown' => translate('Unknown'),
            'lead_type_invalid' => translate('Invalid'),
            'lead_type_future_customer' => translate('Future_Customer'),
        ];

        $help = [
            'leads_handled_section' => self::entry(
                translate('Progress_leads_handled') ?? translate('Leads_added'),
                'Shows the types of leads you handled during the selected period.',
            ),
            'lead_type_handled' => self::entry(
                translate('Progress_leads_handled') ?? translate('Leads_added'),
                'The total number of leads assigned to you in this period.',
            ),
            'chart_lead_types' => self::entry(
                translate('Progress_lead_type_mix') ?? translate('Leads_added'),
                'Shows the mix of customer, provider, and other lead types you handled.',
            ),
            'chart_lead_sources' => self::entry(
                translate('Progress_leads_by_source') ?? translate('Source'),
                'Shows which sources brought you the most leads in this period.',
            ),
            'customer_leads_section' => self::entry(
                translate('Customer').' '.translate('Leads'),
                'Shows what happened to customer leads you handled in this period.',
            ),
            'customer_outcome_booked' => self::entry(
                translate('Bookings_completed'),
                'Customer leads that turned into a booking.',
            ),
            'customer_outcome_pending' => self::entry(
                translate('Pending'),
                'Customer leads you are still working on.',
            ),
            'customer_outcome_cancelled' => self::entry(
                translate('Cancelled'),
                'Customer leads that were cancelled or lost.',
            ),
            'chart_customer_outcomes' => self::entry(
                translate('Progress_customer_conversion') ?? translate('Bookings_completed'),
                'Shows how your customer leads split between booked, pending, and cancelled.',
            ),
            'customer_cancel_reasons' => self::entry(
                translate('Progress_cancellation_reason') ?? translate('Cancelled'),
                'Shows the most common reasons customer leads were cancelled.',
            ),
            'provider_leads_section' => self::entry(
                translate('Provider').' '.translate('Leads'),
                'Shows what happened to provider leads you handled in this period.',
            ),
            'provider_outcome_registered' => self::entry(
                translate('Progress_provider_registered') ?? translate('completed'),
                'Provider leads that completed registration successfully.',
            ),
            'provider_outcome_pending' => self::entry(
                translate('Pending'),
                'Provider leads that are still in progress.',
            ),
            'provider_outcome_cancelled' => self::entry(
                translate('Cancelled'),
                'Provider leads that did not convert.',
            ),
            'chart_provider_outcomes' => self::entry(
                translate('Progress_provider_outcomes') ?? translate('Provider'),
                'Shows how your provider leads split between registered, pending, and cancelled.',
            ),
            'provider_cancel_reasons' => self::entry(
                translate('Progress_cancellation_reason') ?? translate('Cancelled'),
                'Shows the most common reasons provider leads were cancelled.',
            ),
            'invalid_leads_table' => self::entry(
                translate('Invalid').' '.translate('Leads'),
                'Lists leads marked invalid and the reason they were marked that way.',
            ),
            'future_customer_leads_table' => self::entry(
                (translate('Future_Customer') ?? 'Future Customer').' '.translate('Leads'),
                'Lists future-customer leads and the reason they were marked for a later follow-up.',
            ),
            'outbound_enquiries_section' => self::entry(
                translate('Outbound_Enquiries'),
                'Shows the outbound calls or enquiries you made and how they performed.',
            ),
            'outbound_total' => self::entry(
                translate('Outbound_Enquiries'),
                'The total outbound enquiries you logged in this period.',
            ),
            'outbound_converted_customer' => self::entry(
                translate('Progress_outbound_to_customer') ?? translate('Customer'),
                'Outbound enquiries that became customer leads.',
            ),
            'outbound_converted_booking' => self::entry(
                translate('Progress_outbound_to_booking') ?? translate('Bookings_completed'),
                'Outbound enquiries that led directly to a booking.',
            ),
            'outbound_still_fc' => self::entry(
                translate('Future_Customer'),
                'Outbound contacts still kept as future customers.',
            ),
            'outbound_open' => self::entry(
                translate('Pending'),
                'Outbound enquiries that are still open.',
            ),
            'outbound_by_status' => self::entry(
                translate('Status'),
                'Shows your outbound enquiries grouped by their current status.',
            ),
            'outbound_by_channel' => self::entry(
                translate('Source'),
                'Shows which channels your outbound enquiries came from.',
            ),
            'leads_by_source_table' => self::entry(
                translate('Progress_leads_by_source') ?? translate('Source'),
                'A detailed view of leads by source, including customer, provider, and other lead types.',
            ),
        ];

        foreach ($types as $key => $label) {
            $help[$key] = self::entry(
                $label,
                "Shows how many {$label} leads you handled in this period and what share they make up of your total leads.",
            );
        }

        return $help;
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function followupHelp(): array
    {
        $shared = [
            'done' => [
                'title' => translate('Progress_followups_done') ?? translate('Follow_ups'),
                'summary' => 'Follow-ups you logged during the selected period.',
            ],
            'taken' => [
                'title' => translate('Taken'),
                'summary' => 'Follow-ups marked as Taken (completed) during the selected period.',
            ],
            'rescheduled' => [
                'title' => translate('Reschedule'),
                'summary' => 'Follow-ups marked as Reschedule during the selected period.',
            ],
            'on_time' => [
                'title' => translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'),
                'summary' => 'Follow-ups you completed on or before the due date.',
            ],
            'late' => [
                'title' => translate('Progress_late_followups') ?? translate('Pending'),
                'summary' => 'Follow-ups you did complete, but after the due date.',
            ],
            'missed' => [
                'title' => translate('Progress_missed_followups'),
                'summary' => 'Follow-ups that were due but never completed and are still overdue.',
            ],
            'for_others' => [
                'title' => translate('Progress_followups_for_others'),
                'summary' => 'Follow-ups you logged for work assigned to another team member.',
            ],
            'by_others' => [
                'title' => translate('Progress_followups_by_others'),
                'summary' => 'Follow-ups on your work that were logged by someone else.',
            ],
        ];

        $help = [];

        foreach (['lead', 'booking'] as $scope) {
            $entity = $scope === 'lead' ? translate('Lead_followups') : translate('Booking_Followups');
            $item = $scope === 'lead' ? 'leads' : 'bookings';

            foreach ($shared as $suffix => $def) {
                $key = "{$scope}_followup_{$suffix}";
                $help[$key] = self::entry($def['title'].' · '.$entity, $def['summary']);
            }

            $help["{$scope}_followup_summary"] = self::entry(
                translate('Progress_followup_summary') ?? $entity,
                "A quick summary of your {$item} follow-up performance for the selected period.",
            );

            $help["chart_followup_{$scope}_trend"] = self::entry(
                $entity,
                'Daily stacked bar for the selected period: done on time, late, and missed follow-ups.',
            );

            $help["{$scope}_followup_outcome_impact"] = self::entry(
                translate('Progress_followup_outcome_impact'),
                $scope === 'lead'
                    ? 'Shows the overall lead-type result after follow-ups, then customer and provider outcome detail.'
                    : 'Compare three groups: on-time, late, and missed. The big number is completion rate; the bar shows completed vs cancelled vs still pending.',
            );

            if ($scope === 'lead') {
                $help['lead_followup_outcome_general'] = self::entry(
                    translate('Progress_general_result') ?? 'General result',
                    'For each follow-up timing group, shows how many leads are now customer, provider, future customer, or invalid.',
                );
                $help['lead_followup_outcome_customer'] = self::entry(
                    (translate('Customer') ?? 'Customer').' '.translate('Leads'),
                    'Customer leads only. The big number is conversion rate after on-time, late, or missed follow-ups.',
                );
                $help['lead_followup_outcome_provider'] = self::entry(
                    (translate('Provider') ?? 'Provider').' '.translate('Leads'),
                    'Provider leads only. The big number is registration rate after on-time, late, or missed follow-ups.',
                );
            }

            $help["chart_followup_{$scope}_outcome_impact"] = self::entry(
                translate('Progress_followup_outcome_impact'),
                $scope === 'lead'
                    ? 'Shows the overall lead-type result after follow-ups, then customer and provider outcome detail.'
                    : 'Compare three groups: on-time, late, and missed. The big number is completion rate; the bar shows completed vs cancelled vs still pending.',
            );

            $help["{$scope}_delay_breakdown"] = self::entry(
                translate('Progress_delay_breakdown') ?? translate('Progress_late_followups'),
                'Groups late follow-ups by how long after the due date they were completed.',
            );

            $help["{$scope}_late_followups_table"] = self::entry(
                translate('Progress_late_followups'),
                'Lists follow-ups that were completed late, with the due date and how late they were.',
            );
        }

        return $help;
    }

    /**
     * @return array<string, array{title: string, summary: string}>
     */
    private static function dailyBasisHelp(): array
    {
        return [
            'daily_basis_leads' => self::entry(
                translate('Leads'),
                'Counts new leads you added, leads assigned to you, distinct leads you updated, and lead follow-ups taken or rescheduled.',
            ),
            'daily_basis_bookings' => self::entry(
                translate('Bookings'),
                'Counts bookings you created, completed, cancelled, distinct bookings you updated, and booking follow-ups taken or rescheduled.',
            ),
            'daily_basis_communication' => self::entry(
                translate('Communication') ?? 'Communication',
                'Counts WhatsApp chats assigned to you, reply messages sent, distinct people you replied to, and call logs you added.',
            ),
            'daily_basis_day_table' => self::entry(
                translate('Day_wise_breakdown') ?? 'Day-wise breakdown',
                'Each row is a metric. Each column is one day. Scroll sideways to compare days; Total stays on the right.',
            ),
            'activity_leads_added' => self::entry(
                translate('New_Leads_Added'),
                'Leads you manually created on that day.',
            ),
            'activity_leads_assigned' => self::entry(
                translate('New_Leads_Assigned'),
                'Leads newly assigned to you on that day.',
            ),
            'activity_leads_handled' => self::entry(
                translate('Leads_Handled'),
                'Distinct leads where you updated data (from lead change logs).',
            ),
            'activity_lead_followups_taken' => self::entry(
                translate('Lead_Followups_Taken'),
                'Lead follow-ups you marked as taken.',
            ),
            'activity_lead_followups_rescheduled' => self::entry(
                translate('Lead_Followups_Rescheduled'),
                'Lead follow-ups you rescheduled.',
            ),
            'activity_bookings_created' => self::entry(
                translate('New_Bookings_Created'),
                'Bookings you created.',
            ),
            'activity_bookings_completed' => self::entry(
                translate('Bookings_Completed'),
                'Bookings you marked completed.',
            ),
            'activity_bookings_cancelled' => self::entry(
                translate('Bookings_Cancelled'),
                'Bookings you cancelled.',
            ),
            'activity_bookings_handled' => self::entry(
                translate('Bookings_Handled'),
                'Distinct bookings where you updated data (from booking change logs).',
            ),
            'activity_booking_followups_taken' => self::entry(
                translate('Booking_Followups_Taken'),
                'Booking follow-ups you completed / took.',
            ),
            'activity_booking_followups_rescheduled' => self::entry(
                translate('Booking_Followups_Rescheduled'),
                'Booking follow-ups you rescheduled.',
            ),
            'activity_whatsapp_assigned' => self::entry(
                translate('WhatsApp_Chats_Assigned'),
                'WhatsApp chats assigned to you (from AI or from another employee).',
            ),
            'activity_whatsapp_replies' => self::entry(
                translate('WhatsApp_Replies'),
                'Total outbound WhatsApp reply messages you sent.',
            ),
            'activity_whatsapp_chats_replied' => self::entry(
                translate('People_Replied'),
                'Distinct people (phone numbers) you replied to on WhatsApp.',
            ),
            'activity_call_logs' => self::entry(
                translate('Call_Logs_Added'),
                'Call logs you added on leads and bookings.',
            ),
            'overview_all_tabs' => self::entry(
                translate('Progress_overview_all_tabs') ?? 'Overview across all tabs',
                'Summary cards pull key numbers and insights from Bookings, Leads, Lead Follow-ups, Booking Follow-ups, and Daily Basis.',
            ),
            'overview_snap_bookings' => self::entry(
                translate('Bookings'),
                'Created, completed, cancelled, pending, and on-hold bookings for this period.',
            ),
            'overview_snap_leads' => self::entry(
                translate('Leads'),
                'Leads handled or added, plus customer conversion and cancellations.',
            ),
            'overview_snap_lead_fu' => self::entry(
                translate('Lead_followups'),
                'Lead follow-ups done, accuracy, late, missed, and rescheduled counts.',
            ),
            'overview_snap_booking_fu' => self::entry(
                translate('Booking_Followups'),
                'Booking follow-ups done, accuracy, late, missed, and rescheduled counts.',
            ),
            'overview_snap_daily' => self::entry(
                translate('Daily_Basis_Report') ?? 'Daily Basis Report',
                'Total daily actions plus WhatsApp assigns, replies, call logs, and online time.',
            ),
        ];
    }
}
