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
            $row['team_total'] = $teamBySource->has($name)
                ? (int) ($teamBySource->get($name)['total'] ?? 0)
                : null;

            return $row;
        })->all();

        $leadAnalytics['total_handled'] = (int) ($leadAnalytics['total_handled'] ?? 0);
        $leadAnalytics['team_total_handled'] = (int) ($teamLeadAnalytics['total_handled'] ?? 0);

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
        }

        if (isset($followupAnalytics['overall']) && is_array($followupAnalytics['overall'])) {
            $followupAnalytics['overall']['team_total_done'] = (int) ($teamFollowupAnalytics['overall']['total_done'] ?? 0);
            $followupAnalytics['overall']['team_missed'] = (int) ($teamFollowupAnalytics['overall']['missed'] ?? 0);
        }

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

                    return $row;
                })
                ->all();
        }
        $leadAnalytics['team_total_handled'] = null;

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
        }

        return $followupAnalytics;
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
}
