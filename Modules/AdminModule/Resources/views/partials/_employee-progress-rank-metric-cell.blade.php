@php
    $columnKey = (string) ($column['key'] ?? '');
    $value = $row[$columnKey] ?? null;
    $display = ($value === null || $value === '') ? '—' : (string) $value;
    $rowUrl = $row['url'] ?? null;
    $isEmpty = $display === '—';

    $statusSlug = static function (?string $raw): string {
        if ($raw === null || $raw === '' || $raw === '—') {
            return '';
        }
        $slug = strtolower(trim($raw));
        $slug = str_replace([' ', '-'], '_', $slug);

        return match ($slug) {
            'cancelled' => 'canceled',
            default => $slug,
        };
    };

    $humanize = static function (?string $raw): string {
        if ($raw === null || $raw === '' || $raw === '—') {
            return '—';
        }

        return ucwords(str_replace('_', ' ', strtolower(trim($raw))));
    };
@endphp

@switch($columnKey)
    @case('status')
        @if(! $isEmpty)
            <span class="rm-status-pill" data-status="{{ $statusSlug($display) }}">{{ $humanize($display) }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('kind')
        @if(! $isEmpty)
            @php
                $kindClass = str_contains(strtolower($display), 'booking') ? 'is-booking' : 'is-lead';
            @endphp
            <span class="rm-kind-pill {{ $kindClass }}">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('for')
        @if(! $isEmpty)
            @php
                $forClass = match (strtolower($display)) {
                    'provider' => 'is-provider',
                    'customer' => 'is-customer',
                    default => 'is-neutral',
                };
            @endphp
            <span class="rm-for-pill {{ $forClass }}">{{ $humanize($display) }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('type')
        @if(! $isEmpty)
            <span class="rm-type-pill">{{ $humanize($display) }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('score')
        @if(! $isEmpty)
            @php
                $scoreNum = (int) preg_replace('/\D/', '', $display);
                $scoreClass = $scoreNum >= 80 ? 'is-high' : ($scoreNum >= 50 ? 'is-mid' : 'is-low');
            @endphp
            <span class="rm-score-pill {{ $scoreClass }}">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('delay')
        @if(! $isEmpty)
            <span class="rm-delay-pill">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('readable_id')
        @if(! $isEmpty)
            @if($rowUrl)
                <a href="{{ $rowUrl }}" class="rm-ref-link" target="_blank" rel="noopener">{{ $display }}</a>
            @else
                <span class="rm-ref-id">{{ $display }}</span>
            @endif
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('lead')
    @case('reference')
        @if(! $isEmpty)
            @if($rowUrl)
                <a href="{{ $rowUrl }}" class="rm-entity-link" target="_blank" rel="noopener">{{ $display }}</a>
            @else
                <span class="rm-entity-name">{{ $display }}</span>
            @endif
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('phone')
        @if(! $isEmpty)
            <span class="rm-cell-phone">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('assignee')
        @if(! $isEmpty)
            <span class="rm-assignee-pill">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('remarks')
    @case('changes')
        @if(! $isEmpty)
            <span class="rm-cell-text" title="{{ $display }}">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('at')
    @case('due_at')
    @case('followup_at')
    @case('closed_at')
        @if(! $isEmpty)
            <span class="rm-cell-datetime">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @case('customer')
        @if(! $isEmpty)
            <span class="rm-entity-name">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
        @break

    @default
        @if(! $isEmpty)
            <span class="rm-cell-default">{{ $display }}</span>
        @else
            <span class="rm-cell-muted">—</span>
        @endif
@endswitch
