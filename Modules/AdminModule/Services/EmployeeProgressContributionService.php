<?php

namespace Modules\AdminModule\Services;

/**
 * Attaches employee-vs-all contribution totals onto progress report metric rows.
 * Row "total" becomes the team-wide count for the same metric key/label.
 */
class EmployeeProgressContributionService
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $teamRows
     * @return list<array<string, mixed>>
     */
    public function mergeRowsByKey(array $rows, array $teamRows, string $keyField = 'key', string $countField = 'count'): array
    {
        $teamByKey = collect($teamRows)->keyBy(fn (array $row) => (string) ($row[$keyField] ?? ''));

        return array_map(function (array $row) use ($teamByKey, $keyField, $countField) {
            if ($this->shouldSkipContribution($row)) {
                $row['total'] = null;

                return $row;
            }

            $key = (string) ($row[$keyField] ?? '');
            if ($key === '' || ! $teamByKey->has($key)) {
                $row['total'] = null;

                return $row;
            }

            $row['total'] = (int) ($teamByKey->get($key)[$countField] ?? 0);

            return $row;
        }, $rows);
    }

    /**
     * @param  array<string, mixed>  $analytics
     * @param  array<string, mixed>  $teamAnalytics
     * @return array<string, mixed>
     */
    public function attachBookingAnalytics(array $analytics, array $teamAnalytics): array
    {
        $analytics['booking_status_breakdown'] = $this->mergeRowsByKey(
            $analytics['booking_status_breakdown'] ?? [],
            $teamAnalytics['booking_status_breakdown'] ?? [],
        );

        $teamReasons = collect($teamAnalytics['booking_reason_reports'] ?? [])->keyBy('key');
        $analytics['booking_reason_reports'] = collect($analytics['booking_reason_reports'] ?? [])
            ->map(function (array $report) use ($teamReasons) {
                $teamReport = $teamReasons->get($report['key'] ?? '');
                $report['rows'] = $this->mergeRowsByKey(
                    $report['rows'] ?? [],
                    $teamReport['rows'] ?? [],
                    'label',
                    'count',
                );

                return $report;
            })
            ->values()
            ->all();

        $analytics['kpis'] = $this->mergeKpis($analytics['kpis'] ?? [], $teamAnalytics['kpis'] ?? []);
        $analytics['charts'] = $this->attachBookingCharts($analytics['charts'] ?? [], $teamAnalytics['charts'] ?? []);

        return $analytics;
    }

    /**
     * @param  array<string, mixed>  $leadAnalytics
     * @param  array<string, mixed>  $teamLeadAnalytics
     * @return array<string, mixed>
     */
    public function attachLeadAnalytics(array $leadAnalytics, array $teamLeadAnalytics): array
    {
        $leadAnalytics['type_breakdown'] = $this->mergeRowsByKey(
            $leadAnalytics['type_breakdown'] ?? [],
            $teamLeadAnalytics['type_breakdown'] ?? [],
        );

        foreach (['customer', 'provider'] as $section) {
            if (! isset($leadAnalytics[$section]) || ! is_array($leadAnalytics[$section])) {
                continue;
            }
            $leadAnalytics[$section]['outcome_rows'] = $this->mergeRowsByKey(
                $leadAnalytics[$section]['outcome_rows'] ?? [],
                $teamLeadAnalytics[$section]['outcome_rows'] ?? [],
            );
            $leadAnalytics[$section]['cancel_reasons'] = $this->mergeRowsByKey(
                $leadAnalytics[$section]['cancel_reasons'] ?? [],
                $teamLeadAnalytics[$section]['cancel_reasons'] ?? [],
                'label',
                'count',
            );
            $leadAnalytics[$section]['team_total'] = (int) ($teamLeadAnalytics[$section]['total'] ?? 0);
            $leadAnalytics[$section]['team_booked'] = (int) ($teamLeadAnalytics[$section]['booked'] ?? ($teamLeadAnalytics[$section]['registered'] ?? 0));
            $leadAnalytics[$section]['team_cancelled'] = (int) ($teamLeadAnalytics[$section]['cancelled'] ?? 0);
        }
        $leadAnalytics['future_customer_reasons'] = $this->mergeRowsByKey(
            $leadAnalytics['future_customer_reasons'] ?? [],
            $teamLeadAnalytics['future_customer_reasons'] ?? [],
            'label',
            'count',
        );
        $leadAnalytics['invalid_reasons'] = $this->mergeRowsByKey(
            $leadAnalytics['invalid_reasons'] ?? [],
            $teamLeadAnalytics['invalid_reasons'] ?? [],
            'label',
            'count',
        );

        if (isset($leadAnalytics['outbound']) && is_array($leadAnalytics['outbound'])) {
            $leadAnalytics['outbound']['summary_rows'] = $this->mergeRowsByKey(
                $leadAnalytics['outbound']['summary_rows'] ?? [],
                $teamLeadAnalytics['outbound']['summary_rows'] ?? [],
            );
            $leadAnalytics['outbound']['by_status'] = $this->mergeRowsByKey(
                $leadAnalytics['outbound']['by_status'] ?? [],
                $teamLeadAnalytics['outbound']['by_status'] ?? [],
                'label',
                'count',
            );
            $leadAnalytics['outbound']['by_channel'] = $this->mergeRowsByKey(
                $leadAnalytics['outbound']['by_channel'] ?? [],
                $teamLeadAnalytics['outbound']['by_channel'] ?? [],
                'label',
                'count',
            );
            $leadAnalytics['outbound']['team_total'] = (int) ($teamLeadAnalytics['outbound']['total'] ?? 0);
        }

        $sourceRows = $leadAnalytics['sources']['rows'] ?? [];
        $teamSourceRows = $teamLeadAnalytics['sources']['rows'] ?? [];
        $teamBySource = collect($teamSourceRows)->keyBy(fn (array $row) => (string) ($row['source'] ?? ''));
        $leadAnalytics['sources']['rows'] = collect($sourceRows)->map(function (array $row) use ($teamBySource) {
            $name = (string) ($row['source'] ?? '');
            $teamRow = $teamBySource->get($name);
            $row['team_total'] = $teamRow ? (int) ($teamRow['total'] ?? 0) : null;
            foreach (['customer', 'provider', 'unknown', 'invalid', 'future_customer'] as $typeKey) {
                $row['team_'.$typeKey] = $teamRow ? (int) ($teamRow[$typeKey] ?? 0) : null;
            }

            return $row;
        })->all();

        $leadAnalytics['total_handled'] = (int) ($leadAnalytics['total_handled'] ?? 0);
        $leadAnalytics['team_total_handled'] = (int) ($teamLeadAnalytics['total_handled'] ?? 0);
        $leadAnalytics['charts'] = $this->attachLeadCharts($leadAnalytics['charts'] ?? [], $teamLeadAnalytics['charts'] ?? []);

        return $leadAnalytics;
    }

    /**
     * @param  array<string, mixed>  $followupAnalytics
     * @param  array<string, mixed>  $teamFollowupAnalytics
     * @return array<string, mixed>
     */
    public function attachFollowupAnalytics(array $followupAnalytics, array $teamFollowupAnalytics): array
    {
        foreach (['leads', 'bookings'] as $scope) {
            if (! isset($followupAnalytics[$scope]) || ! is_array($followupAnalytics[$scope])) {
                continue;
            }
            $followupAnalytics[$scope]['widget_rows'] = $this->mergeRowsByKey(
                $followupAnalytics[$scope]['widget_rows'] ?? [],
                $teamFollowupAnalytics[$scope]['widget_rows'] ?? [],
            );
            $followupAnalytics[$scope]['team_total_done'] = (int) ($teamFollowupAnalytics[$scope]['total_done'] ?? 0);
            $followupAnalytics[$scope]['team_missed'] = (int) ($teamFollowupAnalytics[$scope]['missed'] ?? 0);
            $followupAnalytics[$scope]['team_delay_buckets'] = $this->delayBucketsFromLateRows(
                $teamFollowupAnalytics[$scope]['late_rows'] ?? []
            );
        }

        if (isset($followupAnalytics['overall']) && is_array($followupAnalytics['overall'])) {
            $followupAnalytics['overall']['team_total_done'] = (int) ($teamFollowupAnalytics['overall']['total_done'] ?? 0);
            $followupAnalytics['overall']['team_missed'] = (int) ($teamFollowupAnalytics['overall']['missed'] ?? 0);
        }

        $followupAnalytics['outcome_impact'] = $this->attachOutcomeImpact(
            $followupAnalytics['outcome_impact'] ?? [],
            $teamFollowupAnalytics['outcome_impact'] ?? [],
        );
        $followupAnalytics['charts'] = $this->attachFollowupCharts(
            $followupAnalytics['charts'] ?? [],
            $teamFollowupAnalytics['charts'] ?? [],
        );

        return $followupAnalytics;
    }

    /**
     * Clear contribution totals when viewing all employees (no mine/all comparison).
     *
     * @param  array<string, mixed>  $analytics
     * @return array<string, mixed>
     */
    public function clearBookingContribution(array $analytics): array
    {
        $analytics['booking_status_breakdown'] = $this->nullTotals($analytics['booking_status_breakdown'] ?? []);
        $analytics['booking_reason_reports'] = collect($analytics['booking_reason_reports'] ?? [])
            ->map(function (array $report) {
                $report['rows'] = $this->nullTotals($report['rows'] ?? []);

                return $report;
            })
            ->all();
        $analytics['kpis'] = $this->nullKpiTotals($analytics['kpis'] ?? []);
        if (isset($analytics['charts']) && is_array($analytics['charts'])) {
            unset($analytics['charts']['team_booking_trend_series'], $analytics['charts']['team_bookings_series'], $analytics['charts']['team_leads_series'], $analytics['charts']['team_followups_series'], $analytics['charts']['show_contribution']);
        }

        return $analytics;
    }

    /**
     * @param  array<string, mixed>  $leadAnalytics
     * @return array<string, mixed>
     */
    public function clearLeadContribution(array $leadAnalytics): array
    {
        $leadAnalytics['type_breakdown'] = $this->nullTotals($leadAnalytics['type_breakdown'] ?? []);
        foreach (['customer', 'provider'] as $section) {
            if (! isset($leadAnalytics[$section]) || ! is_array($leadAnalytics[$section])) {
                continue;
            }
            $leadAnalytics[$section]['outcome_rows'] = $this->nullTotals($leadAnalytics[$section]['outcome_rows'] ?? []);
            $leadAnalytics[$section]['cancel_reasons'] = $this->nullTotals($leadAnalytics[$section]['cancel_reasons'] ?? []);
        }
        $leadAnalytics['future_customer_reasons'] = $this->nullTotals($leadAnalytics['future_customer_reasons'] ?? []);
        $leadAnalytics['invalid_reasons'] = $this->nullTotals($leadAnalytics['invalid_reasons'] ?? []);
        if (isset($leadAnalytics['outbound']) && is_array($leadAnalytics['outbound'])) {
            $leadAnalytics['outbound']['summary_rows'] = $this->nullTotals($leadAnalytics['outbound']['summary_rows'] ?? []);
            $leadAnalytics['outbound']['by_status'] = $this->nullTotals($leadAnalytics['outbound']['by_status'] ?? []);
            $leadAnalytics['outbound']['by_channel'] = $this->nullTotals($leadAnalytics['outbound']['by_channel'] ?? []);
        }
        if (isset($leadAnalytics['sources']['rows'])) {
            $leadAnalytics['sources']['rows'] = collect($leadAnalytics['sources']['rows'])
                ->map(function (array $row) {
                    $row['team_total'] = null;
                    foreach (['customer', 'provider', 'unknown', 'invalid', 'future_customer'] as $typeKey) {
                        $row['team_'.$typeKey] = null;
                    }

                    return $row;
                })
                ->all();
        }
        $leadAnalytics['team_total_handled'] = null;
        if (isset($leadAnalytics['charts']) && is_array($leadAnalytics['charts'])) {
            unset(
                $leadAnalytics['charts']['team_customer_outcome_series'],
                $leadAnalytics['charts']['team_provider_outcome_series'],
                $leadAnalytics['charts']['team_lead_type_series'],
                $leadAnalytics['charts']['team_source_series'],
                $leadAnalytics['charts']['show_contribution'],
            );
        }

        return $leadAnalytics;
    }

    /**
     * @param  array<string, mixed>  $followupAnalytics
     * @return array<string, mixed>
     */
    public function clearFollowupContribution(array $followupAnalytics): array
    {
        foreach (['leads', 'bookings'] as $scope) {
            if (! isset($followupAnalytics[$scope]) || ! is_array($followupAnalytics[$scope])) {
                continue;
            }
            $followupAnalytics[$scope]['widget_rows'] = $this->nullTotals($followupAnalytics[$scope]['widget_rows'] ?? []);
            unset($followupAnalytics[$scope]['team_delay_buckets'], $followupAnalytics[$scope]['team_total_done'], $followupAnalytics[$scope]['team_missed']);
        }
        $followupAnalytics['outcome_impact'] = $this->clearOutcomeImpact($followupAnalytics['outcome_impact'] ?? []);
        if (isset($followupAnalytics['charts']) && is_array($followupAnalytics['charts'])) {
            unset(
                $followupAnalytics['charts']['team_lead_done_series'],
                $followupAnalytics['charts']['team_lead_late_series'],
                $followupAnalytics['charts']['team_lead_missed_series'],
                $followupAnalytics['charts']['team_booking_done_series'],
                $followupAnalytics['charts']['team_booking_late_series'],
                $followupAnalytics['charts']['team_booking_missed_series'],
                $followupAnalytics['charts']['show_contribution'],
            );
        }

        return $followupAnalytics;
    }

    /**
     * @param  array<string, mixed>  $impact
     * @return array<string, mixed>
     */
    private function clearOutcomeImpact(array $impact): array
    {
        if ($impact === []) {
            return $impact;
        }

        if (isset($impact['leads']) && is_array($impact['leads'])) {
            $impact['leads']['general_by_timing'] = $this->nullOutcomeTimingRows($impact['leads']['general_by_timing'] ?? []);
            foreach (['customer', 'provider'] as $section) {
                if (! isset($impact['leads'][$section]) || ! is_array($impact['leads'][$section])) {
                    continue;
                }
                $impact['leads'][$section]['comparison_rows'] = $this->nullOutcomeTimingRows(
                    $impact['leads'][$section]['comparison_rows'] ?? []
                );
            }
        }

        if (isset($impact['bookings']) && is_array($impact['bookings'])) {
            $impact['bookings']['comparison_rows'] = $this->nullOutcomeTimingRows(
                $impact['bookings']['comparison_rows'] ?? []
            );
        }

        return $impact;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function nullOutcomeTimingRows(array $rows): array
    {
        return array_map(function (array $row) {
            foreach (['team_total', 'team_customer', 'team_provider', 'team_future_customer', 'team_invalid', 'team_unknown', 'team_success_count', 'team_cancel_count', 'team_pending_count'] as $field) {
                $row[$field] = null;
            }

            return $row;
        }, $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $kpis
     * @param  list<array<string, mixed>>  $teamKpis
     * @return list<array<string, mixed>>
     */
    private function mergeKpis(array $kpis, array $teamKpis): array
    {
        $teamByKey = collect($teamKpis)->keyBy(fn (array $kpi) => (string) ($kpi['key'] ?? ''));

        return array_map(function (array $kpi) use ($teamByKey) {
            $raw = $kpi['raw'] ?? null;
            if (! is_numeric($raw)) {
                $kpi['total'] = null;

                return $kpi;
            }

            $key = (string) ($kpi['key'] ?? '');
            $team = $teamByKey->get($key);
            $teamRaw = $team['raw'] ?? null;
            $kpi['total'] = is_numeric($teamRaw) ? (int) $teamRaw : null;

            return $kpi;
        }, $kpis);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function nullTotals(array $rows): array
    {
        return array_map(function (array $row) {
            $row['total'] = null;

            return $row;
        }, $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $kpis
     * @return list<array<string, mixed>>
     */
    private function nullKpiTotals(array $kpis): array
    {
        return array_map(function (array $kpi) {
            $kpi['total'] = null;

            return $kpi;
        }, $kpis);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function shouldSkipContribution(array $row): bool
    {
        if (($row['display'] ?? '') === 'percent') {
            return true;
        }

        if (array_key_exists('value', $row) && ! is_numeric($row['value'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $charts
     * @param  array<string, mixed>  $teamCharts
     * @return array<string, mixed>
     */
    private function attachBookingCharts(array $charts, array $teamCharts): array
    {
        $charts['show_contribution'] = true;
        $charts['team_booking_trend_series'] = $teamCharts['booking_trend_series'] ?? [];
        $charts['team_bookings_series'] = $teamCharts['bookings_series'] ?? [];
        $charts['team_leads_series'] = $teamCharts['leads_series'] ?? [];
        $charts['team_followups_series'] = $teamCharts['followups_series'] ?? [];
        $charts['team_followup_completed_series'] = $teamCharts['followup_completed_series'] ?? [];
        $charts['team_followup_missed_series'] = $teamCharts['followup_missed_series'] ?? [];
        $charts['team_outcome_series'] = $teamCharts['outcome_series'] ?? [];
        $charts['team_funnel_series'] = $teamCharts['funnel_series'] ?? [];

        return $charts;
    }

    /**
     * @param  array<string, mixed>  $charts
     * @param  array<string, mixed>  $teamCharts
     * @return array<string, mixed>
     */
    private function attachLeadCharts(array $charts, array $teamCharts): array
    {
        $charts['show_contribution'] = true;
        $charts['team_customer_outcome_series'] = $teamCharts['customer_outcome_series'] ?? [];
        $charts['team_provider_outcome_series'] = $teamCharts['provider_outcome_series'] ?? [];
        $charts['team_lead_type_series'] = $teamCharts['lead_type_series'] ?? [];
        $charts['team_source_series'] = $teamCharts['source_series'] ?? [];

        return $charts;
    }

    /**
     * @param  array<string, mixed>  $charts
     * @param  array<string, mixed>  $teamCharts
     * @return array<string, mixed>
     */
    private function attachFollowupCharts(array $charts, array $teamCharts): array
    {
        $charts['show_contribution'] = true;
        foreach ([
            'lead_done_series',
            'lead_late_series',
            'lead_missed_series',
            'booking_done_series',
            'booking_late_series',
            'booking_missed_series',
        ] as $key) {
            $charts['team_'.$key] = $teamCharts[$key] ?? [];
        }

        return $charts;
    }

    /**
     * @param  array<string, mixed>  $impact
     * @param  array<string, mixed>  $teamImpact
     * @return array<string, mixed>
     */
    private function attachOutcomeImpact(array $impact, array $teamImpact): array
    {
        if ($impact === []) {
            return $impact;
        }

        if (isset($impact['leads']) && is_array($impact['leads'])) {
            $impact['leads']['general_by_timing'] = $this->mergeOutcomeTimingRows(
                $impact['leads']['general_by_timing'] ?? [],
                $teamImpact['leads']['general_by_timing'] ?? [],
            );
            foreach (['customer', 'provider'] as $section) {
                if (! isset($impact['leads'][$section]) || ! is_array($impact['leads'][$section])) {
                    continue;
                }
                $impact['leads'][$section]['comparison_rows'] = $this->mergeOutcomeTimingRows(
                    $impact['leads'][$section]['comparison_rows'] ?? [],
                    $teamImpact['leads'][$section]['comparison_rows'] ?? [],
                );
            }
        }

        if (isset($impact['bookings']) && is_array($impact['bookings'])) {
            $impact['bookings']['comparison_rows'] = $this->mergeOutcomeTimingRows(
                $impact['bookings']['comparison_rows'] ?? [],
                $teamImpact['bookings']['comparison_rows'] ?? [],
            );
        }

        return $impact;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $teamRows
     * @return list<array<string, mixed>>
     */
    private function mergeOutcomeTimingRows(array $rows, array $teamRows): array
    {
        $teamByKey = collect($teamRows)->keyBy(fn (array $row) => (string) ($row['key'] ?? ''));

        return array_map(function (array $row) use ($teamByKey) {
            $key = (string) ($row['key'] ?? '');
            $team = $teamByKey->get($key);
            $row['team_total'] = $team ? (int) ($team['total'] ?? 0) : null;
            foreach (['customer', 'provider', 'future_customer', 'invalid', 'unknown', 'success_count', 'cancel_count', 'pending_count'] as $field) {
                $row['team_'.$field] = $team ? (int) ($team[$field] ?? 0) : null;
            }

            return $row;
        }, $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $lateRows
     * @return list<array{label: string, count: int, crit: bool}>
     */
    private function delayBucketsFromLateRows(array $lateRows): array
    {
        $buckets = [
            ['label' => translate('Progress_delay_under_1h') ?? '< 1 hour', 'count' => 0, 'crit' => false],
            ['label' => translate('Progress_delay_1_24h') ?? '1–24 hours', 'count' => 0, 'crit' => false],
            ['label' => translate('Progress_delay_1_3d') ?? '1–3 days', 'count' => 0, 'crit' => true],
            ['label' => translate('Progress_delay_over_3d') ?? '3+ days', 'count' => 0, 'crit' => true],
        ];

        foreach ($lateRows as $row) {
            $minutes = (int) ($row['delay_minutes'] ?? 0);
            if ($minutes < 60) {
                $buckets[0]['count']++;
            } elseif ($minutes < 1440) {
                $buckets[1]['count']++;
            } elseif ($minutes < 4320) {
                $buckets[2]['count']++;
            } else {
                $buckets[3]['count']++;
            }
        }

        return $buckets;
    }
}
