<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\LeadManagement\Entities\SuperfoneCall;

class SuperfoneCallReport
{
    public function build(): array
    {
        $todayStart = now('Asia/Kolkata')->startOfDay()->format('Y-m-d H:i:s');
        $todayEnd = now('Asia/Kolkata')->endOfDay()->format('Y-m-d H:i:s');

        $today = $this->totals($todayStart, $todayEnd);
        $overall = $this->totals();
        $employees = $this->mergeGroups(
            $this->grouped(translate('Unassigned'), $todayStart, $todayEnd),
            $this->grouped(translate('Unassigned')),
        );
        $dates = [
            'day' => $this->byPeriod('day'),
            'week' => $this->byPeriod('week'),
            'month' => $this->byPeriod('month'),
        ];
        $hours = $this->byHour();

        return [
            'todayLabel' => now('Asia/Kolkata')->format('d M Y'),
            'today' => $today,
            'overall' => $overall,
            'employees' => $employees,
            'dates' => $dates,
            'hours' => $hours,
            'activity' => $this->employeeActivity($todayStart, $todayEnd),
            'typeColors' => $this->typeColors(),
            'charts' => $this->charts($today, $overall, $dates, $hours),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function totals(?string $from = null, ?string $to = null): array
    {
        $query = SuperfoneCall::query();
        if ($from !== null && $to !== null) {
            $query->whereBetween('started_at', [$from, $to]);
        }

        return $this->cast($query->selectRaw($this->metricSql())->first());
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function grouped(string $emptyLabel, ?string $from = null, ?string $to = null): array
    {
        $nameSql = "CASE WHEN staff_name IS NULL OR TRIM(staff_name) = '' THEN '' ELSE staff_name END";

        $query = SuperfoneCall::query()
            ->selectRaw($nameSql.' as bucket')
            ->selectRaw($this->metricSql())
            ->groupBy(DB::raw($nameSql))
            ->orderByDesc('total')
            ->orderBy('bucket');

        if ($from !== null && $to !== null) {
            $query->whereBetween('started_at', [$from, $to]);
        }

        return $query->get()->map(function ($row) use ($emptyLabel) {
            $cast = $this->cast($row);
            $name = trim((string) $row->bucket);
            $cast['label'] = $name !== '' ? $name : $emptyLabel;

            return $cast;
        })->all();
    }

    /**
     * @param  list<array<string, int|string>>  $today
     * @param  list<array<string, int|string>>  $overall
     * @return list<array{label: string, today: array<string, int|string>, overall: array<string, int|string>}>
     */
    private function mergeGroups(array $today, array $overall): array
    {
        $blank = $this->blank();
        $map = [];

        foreach ($overall as $row) {
            $map[$row['label']] = [
                'label' => $row['label'],
                'today' => $blank,
                'overall' => $row,
            ];
        }

        foreach ($today as $row) {
            if (! isset($map[$row['label']])) {
                $map[$row['label']] = [
                    'label' => $row['label'],
                    'today' => $row,
                    'overall' => $blank,
                ];
                continue;
            }

            $map[$row['label']]['today'] = $row;
        }

        $rows = array_values($map);
        usort($rows, function (array $a, array $b): int {
            return [$b['overall']['total'], $b['today']['total'], $a['label']]
                <=> [$a['overall']['total'], $a['today']['total'], $b['label']];
        });

        return $rows;
    }

    /**
     * @param  array<string, int|string>  $today
     * @param  array<string, int|string>  $overall
     * @param  array<string, array{rows: list<array<string, int|string>>, peak: string}>  $dates
     * @param  array{rows: list<array<string, int|string>>, peak: string}  $hours
     * @return array<string, mixed>
     */
    private function charts(array $today, array $overall, array $dates, array $hours): array
    {
        $colors = $this->typeColors();
        $periods = [translate('Today'), translate('Overall')];

        return [
            'overall' => [
                'categories' => $periods,
                'series' => [
                    $this->series(translate('Inbound').' · '.translate('Answered'), $colors['answered'], $today, $overall, 'inbound_answered'),
                    $this->series(translate('Missed'), $colors['missed'], $today, $overall, 'inbound_missed'),
                    $this->series(translate('Unallocated'), $colors['unallocated'], $today, $overall, 'inbound_unallocated'),
                    $this->series(translate('Outbound').' · '.translate('Answered'), $colors['answered'], $today, $overall, 'outbound_answered'),
                    $this->series(translate('Unanswered'), $colors['unanswered'], $today, $overall, 'outbound_unanswered'),
                    $this->series(translate('Channel_not_available'), $colors['channel'], $today, $overall, 'outbound_channel'),
                ],
            ],
            'inbound' => [
                'categories' => $periods,
                'series' => [
                    $this->series(translate('Answered'), $colors['answered'], $today, $overall, 'inbound_answered'),
                    $this->series(translate('Missed'), $colors['missed'], $today, $overall, 'inbound_missed'),
                    $this->series(translate('Unallocated'), $colors['unallocated'], $today, $overall, 'inbound_unallocated'),
                ],
            ],
            'outbound' => [
                'categories' => $periods,
                'series' => [
                    $this->series(translate('Answered'), $colors['answered'], $today, $overall, 'outbound_answered'),
                    $this->series(translate('Unanswered'), $colors['unanswered'], $today, $overall, 'outbound_unanswered'),
                    $this->series(translate('Channel_not_available'), $colors['channel'], $today, $overall, 'outbound_channel'),
                ],
            ],
            'dates' => [
                'day' => $this->periodChart($dates['day']['rows'], $colors),
                'week' => $this->periodChart($dates['week']['rows'], $colors),
                'month' => $this->periodChart($dates['month']['rows'], $colors),
            ],
            'hours' => [
                'categories' => array_column($hours['rows'], 'label'),
                'series' => [
                    ['name' => translate('Inbound'), 'color' => $colors['inbound'], 'data' => array_map(fn (array $row) => (int) $row['inbound'], $hours['rows'])],
                    ['name' => translate('Missed'), 'color' => $colors['missed'], 'data' => array_map(fn (array $row) => (int) $row['inbound_missed'], $hours['rows'])],
                    ['name' => translate('Outbound'), 'color' => $colors['outbound'], 'data' => array_map(fn (array $row) => (int) $row['outbound'], $hours['rows'])],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, int|string>  $today
     * @param  array<string, int|string>  $overall
     * @return array{name: string, color: string, data: list<int>}
     */
    private function series(string $name, string $color, array $today, array $overall, string $key): array
    {
        return [
            'name' => $name,
            'color' => $color,
            'data' => [(int) $today[$key], (int) $overall[$key]],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeColors(): array
    {
        return [
            'answered' => '#0f7a3a',
            'missed' => '#c62828',
            'unallocated' => '#6d28d9',
            'unanswered' => '#b45309',
            'channel' => '#0e7490',
            'inbound' => '#1d4ed8',
            'outbound' => '#334155',
        ];
    }

    private function metricSql(): string
    {
        $channel = "UPPER(REPLACE(REPLACE(COALESCE(disposition, ''), '_', ''), ' ', '')) IN ('CHANUNAVAIL', 'CHANNELNOTAVAILABLE', 'CHANNELUNAVAILABLE', 'CHANNELUNAVAIL')";
        $unallocated = "UPPER(COALESCE(disposition, '')) = 'UNALLOCATED'";

        return <<<SQL
COUNT(*) as total,
SUM(direction = 'INBOUND') as inbound,
SUM(direction = 'OUTBOUND') as outbound,
SUM(direction = 'INBOUND' AND outcome = 'answered') as inbound_answered,
SUM(direction = 'INBOUND' AND COALESCE(outcome, '') <> 'answered' AND {$unallocated}) as inbound_unallocated,
SUM(direction = 'INBOUND' AND COALESCE(outcome, '') <> 'answered' AND NOT ({$unallocated})) as inbound_missed,
SUM(direction = 'OUTBOUND' AND outcome = 'answered') as outbound_answered,
SUM(direction = 'OUTBOUND' AND COALESCE(outcome, '') <> 'answered' AND {$channel}) as outbound_channel,
SUM(direction = 'OUTBOUND' AND COALESCE(outcome, '') <> 'answered' AND NOT ({$channel})) as outbound_unanswered,
SUM(CASE WHEN direction = 'INBOUND' THEN COALESCE(duration_seconds, 0) ELSE 0 END) as inbound_duration_seconds,
SUM(CASE WHEN direction = 'OUTBOUND' THEN COALESCE(duration_seconds, 0) ELSE 0 END) as outbound_duration_seconds
SQL;
    }

    /**
     * @return array<string, int|string>
     */
    private function cast(?object $row): array
    {
        $inbound = (int) data_get($row, 'inbound', 0);
        $outbound = (int) data_get($row, 'outbound', 0);
        $inboundAnswered = (int) data_get($row, 'inbound_answered', 0);
        $outboundAnswered = (int) data_get($row, 'outbound_answered', 0);
        $inboundSeconds = (int) data_get($row, 'inbound_duration_seconds', 0);
        $outboundSeconds = (int) data_get($row, 'outbound_duration_seconds', 0);

        return [
            'total' => (int) data_get($row, 'total', 0),
            'inbound' => $inbound,
            'outbound' => $outbound,
            'inbound_answered' => $inboundAnswered,
            'inbound_missed' => (int) data_get($row, 'inbound_missed', 0),
            'inbound_unallocated' => (int) data_get($row, 'inbound_unallocated', 0),
            'outbound_answered' => $outboundAnswered,
            'outbound_unanswered' => (int) data_get($row, 'outbound_unanswered', 0),
            'outbound_channel' => (int) data_get($row, 'outbound_channel', 0),
            'inbound_duration_label' => $this->durationLabel($inboundSeconds),
            'outbound_duration_label' => $this->durationLabel($outboundSeconds),
            'total_duration_label' => $this->durationLabel($inboundSeconds + $outboundSeconds),
            'inbound_answer_rate' => $inbound > 0 ? (int) round(($inboundAnswered / $inbound) * 100) : 0,
            'outbound_answer_rate' => $outbound > 0 ? (int) round(($outboundAnswered / $outbound) * 100) : 0,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function blank(): array
    {
        return $this->cast(null);
    }

    /**
     * @param  list<array<string, int|string>>  $rows
     * @param  array<string, string>  $colors
     * @return array{categories: list<string>, series: list<array{name: string, color: string, data: list<int>}>}
     */
    private function periodChart(array $rows, array $colors): array
    {
        $rows = array_reverse($rows);

        return [
            'categories' => array_column($rows, 'label'),
            'series' => [
                ['name' => translate('Inbound'), 'color' => $colors['inbound'], 'data' => array_map(fn (array $row) => (int) $row['inbound'], $rows)],
                ['name' => translate('Missed'), 'color' => $colors['missed'], 'data' => array_map(fn (array $row) => (int) $row['inbound_missed'], $rows)],
                ['name' => translate('Unallocated'), 'color' => $colors['unallocated'], 'data' => array_map(fn (array $row) => (int) $row['inbound_unallocated'], $rows)],
                ['name' => translate('Outbound'), 'color' => $colors['outbound'], 'data' => array_map(fn (array $row) => (int) $row['outbound'], $rows)],
            ],
        ];
    }

    /**
     * @return array{rows: list<array<string, int|string>>, peak: string}
     */
    private function byPeriod(string $grain): array
    {
        $bucketSql = match ($grain) {
            'week' => 'DATE_SUB(DATE(started_at), INTERVAL WEEKDAY(started_at) DAY)',
            'month' => "DATE_FORMAT(started_at, '%Y-%m-01')",
            default => 'DATE(started_at)',
        };
        $limit = match ($grain) {
            'week' => 16,
            'month' => 12,
            default => 30,
        };

        $rows = SuperfoneCall::query()
            ->selectRaw($bucketSql.' as bucket')
            ->selectRaw($this->metricSql())
            ->where('started_at', '>=', '2000-01-01 00:00:00')
            ->groupBy(DB::raw($bucketSql))
            ->orderByDesc('bucket')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($grain) {
                $cast = $this->cast($row);
                $cast['label'] = $this->periodLabel((string) $row->bucket, $grain);

                return $cast;
            })
            ->all();

        $peak = $rows[0] ?? null;
        foreach ($rows as $row) {
            if ($peak === null || (int) $row['total'] > (int) $peak['total']) {
                $peak = $row;
            }
        }

        return [
            'rows' => $rows,
            'peak' => (string) ($peak['label'] ?? ''),
        ];
    }

    private function periodLabel(string $bucket, string $grain): string
    {
        $start = Carbon::parse($bucket);
        if ($grain === 'month') {
            return $start->format('M Y');
        }

        if ($grain === 'week') {
            $end = $start->copy()->addDays(6);
            if ($start->format('M Y') === $end->format('M Y')) {
                return $start->format('d').'–'.$end->format('d M Y');
            }

            return $start->format('d M').' – '.$end->format('d M Y');
        }

        return $start->format('d M Y');
    }

    /**
     * @return array{rows: list<array<string, int|string>>, busy: list<array<string, int|string>>, peak: string}
     */
    private function byHour(): array
    {
        $found = SuperfoneCall::query()
            ->selectRaw('HOUR(started_at) as bucket')
            ->selectRaw($this->metricSql())
            ->where('started_at', '>=', '2000-01-01 00:00:00')
            ->groupBy(DB::raw('HOUR(started_at)'))
            ->get()
            ->keyBy(fn ($row) => (int) $row->bucket);

        $rows = [];
        $peak = null;
        foreach (range(0, 23) as $hour) {
            $cast = $this->cast($found->get($hour));
            $cast['label'] = $this->hourLabel($hour);
            $rows[] = $cast;
            if ((int) $cast['inbound'] > 0 && ($peak === null || (int) $cast['inbound'] > (int) $peak['inbound'])) {
                $peak = $cast;
            }
        }

        return [
            'rows' => $rows,
            'busy' => array_values(array_filter($rows, fn (array $row) => (int) $row['total'] > 0)),
            'peak' => (string) ($peak['label'] ?? ''),
        ];
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function employeeActivity(string $from, string $to): array
    {
        $nameSql = "CASE WHEN staff_name IS NULL OR TRIM(staff_name) = '' THEN '' ELSE staff_name END";
        $unallocated = "UPPER(COALESCE(disposition, '')) <> 'UNALLOCATED'";

        $rows = SuperfoneCall::query()
            ->selectRaw($nameSql.' as bucket')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(started_at BETWEEN ? AND ?) as today_total', [$from, $to])
            ->selectRaw("SUM(direction = 'INBOUND' AND COALESCE(outcome, '') <> 'answered' AND {$unallocated} AND started_at BETWEEN ? AND ?) as today_missed", [$from, $to])
            ->selectRaw('MIN(CASE WHEN started_at BETWEEN ? AND ? THEN started_at END) as first_today', [$from, $to])
            ->selectRaw('MAX(CASE WHEN started_at BETWEEN ? AND ? THEN started_at END) as last_today', [$from, $to])
            ->groupBy(DB::raw($nameSql))
            ->get();

        $usual = [];
        SuperfoneCall::query()
            ->selectRaw($nameSql.' as bucket')
            ->selectRaw('HOUR(started_at) as hour_bucket')
            ->selectRaw('COUNT(*) as total')
            ->where('started_at', '>=', '2000-01-01 00:00:00')
            ->groupBy(DB::raw($nameSql), DB::raw('HOUR(started_at)'))
            ->orderBy('hour_bucket')
            ->get()
            ->each(function ($row) use (&$usual) {
                $name = trim((string) $row->bucket);
                $count = (int) $row->total;
                if (! isset($usual[$name]) || $count > $usual[$name]['count']) {
                    $usual[$name] = ['count' => $count, 'hour' => (int) $row->hour_bucket];
                }
            });

        $activity = $rows->map(function ($row) use ($usual) {
            $name = trim((string) $row->bucket);
            $todayTotal = (int) $row->today_total;
            $active = $todayTotal > 0;

            return [
                'label' => $name !== '' ? $name : translate('Unassigned'),
                'status' => $active ? 'active' : 'inactive',
                'status_label' => $active ? translate('Active') : translate('Inactive'),
                'today' => $todayTotal,
                'missed' => (int) $row->today_missed,
                'total' => (int) $row->total,
                'first' => $row->first_today ? Carbon::parse((string) $row->first_today)->format('h:i A') : '—',
                'last' => $row->last_today ? Carbon::parse((string) $row->last_today)->format('h:i A') : '—',
                'usual' => isset($usual[$name]) ? $this->hourLabel($usual[$name]['hour']) : '—',
            ];
        })->all();

        usort($activity, function (array $a, array $b): int {
            return [$a['status'] === 'active' ? 0 : 1, -$a['today'], -$a['total'], $a['label']]
                <=> [$b['status'] === 'active' ? 0 : 1, -$b['today'], -$b['total'], $b['label']];
        });

        return $activity;
    }

    private function hourLabel(int $hour): string
    {
        $hour = $hour % 24;
        $clock = $hour % 12;

        return ($clock === 0 ? 12 : $clock).' '.($hour < 12 ? 'AM' : 'PM');
    }

    private function durationLabel(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $remain);
        }

        return $seconds.'s';
    }
}
