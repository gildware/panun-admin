@php
    $formAction = $formAction ?? route('admin.my-progress.ranking-employee');
    $linkRoute = $linkRoute ?? 'admin.my-progress.ranking-employee';
    $employeeId = (string) ($employee->id ?? '');
    $period = $period ?? 'daily';
    $date = $date ?? today()->toDateString();
    $month = $month ?? today()->format('Y-m');
    $dateFrom = $dateFrom ?? today()->startOfMonth()->toDateString();
    $dateTo = $dateTo ?? today()->endOfMonth()->toDateString();
    $employeeOptions = $employeeOptions ?? [];
    $showEmployeePicker = ! empty($viewingAsAdmin) && $employeeOptions !== [];
    $metric = $metric ?? null;
    $headTitle = $headTitle ?? ($employee->first_name ?? translate('Progress_marks_breakdown') ?? 'Marks breakdown');
    $headSubtitle = $headSubtitle ?? ($periodLabel ?? null);
    $dayLabel = $dayLabel ?? null;
    $teamRank = $teamRank ?? null;

    $periodBadge = match ($period) {
        'monthly' => translate('Monthly') ?? 'Monthly',
        'custom' => translate('Custom_range') ?? 'Custom range',
        default => translate('Daily') ?? 'Daily',
    };

    $linkBase = array_filter([
        'employee_id' => $employeeId,
        'metric' => $metric,
    ]);
    $dailyLinkParams = array_merge($linkBase, ['period' => 'daily', 'date' => $date]);
    $monthlyLinkParams = array_merge($linkBase, ['period' => 'monthly', 'month' => $month]);
    $customLinkParams = array_merge($linkBase, ['period' => 'custom', 'date_from' => $dateFrom, 'date_to' => $dateTo]);
@endphp
<header class="shell-head shell-head--report">
    <div class="shell-head-top">
        <div class="shell-head-identity">
            <h1 class="shell-head-title">{{ $headTitle }}</h1>
            @if(! empty($teamRank))
                <span class="shell-rank-badge">#{{ (int) $teamRank }} {{ translate('Progress_team_rank') ?? 'team rank' }}</span>
            @endif
        </div>
        @if(! empty($headSubtitle))
            <div class="shell-period-display">
                <span class="shell-period-badge">{{ $periodBadge }}</span>
                <span class="shell-period-text">{{ $headSubtitle }}</span>
            </div>
        @endif
    </div>

    <form method="get" action="{{ $formAction }}" class="shell-toolbar" data-turbo="false">
        @if($showEmployeePicker)
            <div class="shell-toolbar-group shell-toolbar-group--employee">
                <label class="shell-field-label" for="ranking-report-employee">{{ translate('Employee') ?? 'Employee' }}</label>
                <select id="ranking-report-employee" name="employee_id" class="shell-control shell-control--select" onchange="this.form.submit()">
                    @foreach($employeeOptions as $option)
                        <option value="{{ $option['id'] }}" @selected((string) $option['id'] === $employeeId)>{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="employee_id" value="{{ $employeeId }}">
        @endif

        @if($metric)
            <input type="hidden" name="metric" value="{{ $metric }}">
        @endif

        <input type="hidden" name="period" value="{{ $period }}">

        <div class="shell-toolbar-group shell-toolbar-group--period">
            <span class="shell-field-label">{{ translate('Report') ?? 'Report' }}</span>
            <div class="shell-period-segment" role="group" aria-label="{{ translate('Report_period') ?? 'Report period' }}">
                <a href="{{ route($linkRoute, $dailyLinkParams) }}"
                   class="period-link {{ $period === 'daily' ? 'on' : '' }}"
                   data-turbo="false">{{ translate('Daily') ?? 'Daily' }}</a>
                <a href="{{ route($linkRoute, $monthlyLinkParams) }}"
                   class="period-link {{ $period === 'monthly' ? 'on' : '' }}"
                   data-turbo="false">{{ translate('Monthly') ?? 'Monthly' }}</a>
                <a href="{{ route($linkRoute, $customLinkParams) }}"
                   class="period-link {{ $period === 'custom' ? 'on' : '' }}"
                   data-turbo="false">{{ translate('Custom_range') ?? 'Custom' }}</a>
            </div>
        </div>

        <div class="shell-toolbar-group shell-toolbar-group--dates">
            <span class="shell-field-label">{{ translate('Date') ?? 'Date' }}</span>
            <div class="shell-date-controls">
                @if($period === 'daily')
                    <input type="date" name="date" value="{{ $date }}" class="shell-control shell-control--date" data-ranking-date aria-label="{{ translate('Date') ?? 'Date' }}">
                    <span class="shell-day-chip" data-ranking-day-label>{{ $dayLabel }}</span>
                @elseif($period === 'monthly')
                    <input type="month" name="month" value="{{ $month }}" class="shell-control shell-control--month" aria-label="{{ translate('Month') ?? 'Month' }}">
                @else
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="shell-control shell-control--date" aria-label="{{ translate('date_from') ?? 'From' }}">
                    <span class="shell-date-sep" aria-hidden="true">–</span>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="shell-control shell-control--date" aria-label="{{ translate('date_to') ?? 'To' }}">
                @endif
            </div>
        </div>

        <button type="submit" class="shell-apply-btn">{{ translate('Apply') ?? 'Apply' }}</button>
    </form>
</header>

@once
    @push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var dateInput = document.querySelector('[data-ranking-date]');
            var dayLabel = document.querySelector('[data-ranking-day-label]');
            if (!dateInput || !dayLabel) {
                return;
            }

            var formatDay = function (value) {
                if (!value) {
                    return '';
                }

                var parts = value.split('-').map(Number);
                var date = new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0);

                return date.toLocaleDateString(undefined, { weekday: 'long' });
            };

            var syncDay = function () {
                dayLabel.textContent = formatDay(dateInput.value);
            };

            dateInput.addEventListener('change', syncDay);
            syncDay();
        });
    </script>
    @endpush
@endonce
