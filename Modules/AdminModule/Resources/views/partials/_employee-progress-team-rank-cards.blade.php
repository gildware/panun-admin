@php
    $rows = $rows ?? [];
    $highlightEmployeeId = $highlightEmployeeId ?? null;
    $maxScore = max(1, ...(array_map(static fn ($r) => abs((int) ($r['score'] ?? 0)), $rows) ?: [1]));
@endphp
@if($rows === [])
    <div class="progress-empty">{{ translate('Progress_solo_team') }}</div>
@else
    <div class="team-rank-cards">
        @foreach($rows as $index => $row)
            @php
                $isHighlighted = $highlightEmployeeId && (string) $highlightEmployeeId === (string) ($row['employee_id'] ?? '');
                $initials = collect(explode(' ', $row['label'] ?? ''))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                $avatarClass = match ($index) { 1 => 'silver', 2 => 'bronze', default => '' };
                $barPct = min(100, round((abs((int) ($row['score'] ?? 0)) / $maxScore) * 100));
            @endphp
            <div class="rank-item rank-item--scored rank-item--dash {{ $isHighlighted ? 'is-highlighted' : '' }}">
                <div class="rank-item-main">
                    <div class="avatar {{ $avatarClass }}">{{ $initials ?: '#'.($row['rank'] ?? ($index + 1)) }}</div>
                    <div class="rank-meta">
                        <div class="rank-name">#{{ $row['rank'] ?? ($index + 1) }} · {{ $row['label'] }}</div>
                        <div class="rank-sub">
                            {{ translate('Quantity') ?? 'Quantity' }} {{ (int) ($row['quantity_score'] ?? 0) }}
                            · {{ translate('Penalties') ?? 'Penalties' }} {{ (int) ($row['penalty_score'] ?? 0) }}
                        </div>
                        <div class="rank-bar"><i style="width: {{ $barPct }}%"></i></div>
                    </div>
                    <div class="rank-val">{{ (int) ($row['score'] ?? 0) }}</div>
                </div>
                @include('adminmodule::partials._employee-progress-rank-marks', [
                    'marks' => $row['marks'] ?? [],
                ])
            </div>
        @endforeach
    </div>
@endif
