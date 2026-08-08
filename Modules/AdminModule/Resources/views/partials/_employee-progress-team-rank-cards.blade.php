@php
    $rows = $rows ?? [];
    $highlightEmployeeId = $highlightEmployeeId ?? null;
    $variant = $variant ?? 'dash';
    $isOverview = $variant === 'overview';
    $maxScore = max(1, ...(array_map(static fn ($r) => abs((int) ($r['score'] ?? 0)), $rows) ?: [1]));
@endphp
@if($rows === [])
    <div class="progress-empty">{{ translate('Progress_solo_team') }}</div>
@else
    <div class="{{ $isOverview ? 'rank-row team-rank-cards team-rank-cards--overview' : 'team-rank-cards' }}">
        @foreach($rows as $index => $row)
            @php
                $isHighlighted = $highlightEmployeeId && (string) $highlightEmployeeId === (string) ($row['employee_id'] ?? '');
                $initials = collect(explode(' ', $row['label'] ?? ''))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                $avatarClass = match ($index) { 1 => 'silver', 2 => 'bronze', default => '' };
                $barPct = min(100, round((abs((int) ($row['score'] ?? 0)) / $maxScore) * 100));
                $cardClass = $isOverview
                    ? 'rank-item rank-item--scored rank-item--card'
                    : 'rank-item rank-item--scored rank-item--dash';
            @endphp
            <div class="{{ $cardClass }} {{ $isHighlighted ? 'is-highlighted' : '' }}">
                <div class="rank-item-main">
                    <div class="avatar {{ $avatarClass }}">{{ $initials ?: '#'.($row['rank'] ?? ($index + 1)) }}</div>
                    <div class="rank-meta">
                        <div class="rank-name">{{ $row['label'] }}</div>
                        <div class="rank-sub">
                            {{ translate('Quantity') ?? 'Quantity' }} {{ (int) ($row['quantity_score'] ?? 0) }}
                            @if((int) ($row['helped_score'] ?? 0) > 0)
                                · {{ translate('Progress_helped_others') ?? 'Helped other' }} {{ (int) ($row['helped_score'] ?? 0) }}
                            @endif
                            · {{ translate('Penalties') ?? 'Penalties' }} {{ (int) ($row['penalty_score'] ?? 0) }}
                        </div>
                        <div class="rank-bar"><i style="width: {{ $barPct }}%"></i></div>
                    </div>
                    <div class="rank-val">{{ (int) ($row['score'] ?? 0) }}</div>
                </div>
                @include('adminmodule::partials._employee-progress-rank-marks', [
                    'marks' => $row['marks'] ?? [],
                    'helpedMarks' => $row['helped_marks'] ?? [],
                    'quantityScore' => (int) ($row['quantity_score'] ?? 0),
                    'helpedScore' => (int) ($row['helped_score'] ?? 0),
                    'penaltyScore' => (int) ($row['penalty_score'] ?? 0),
                    'grandScore' => (int) ($row['score'] ?? 0),
                ])
            </div>
        @endforeach
    </div>
@endif
