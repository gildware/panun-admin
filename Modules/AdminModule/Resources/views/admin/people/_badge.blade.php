@php
    $tone = match ($status ?? '') {
        'approved', 'published', 'verified' => 'is-good',
        'pending', 'unsubmitted' => 'is-wait',
        'sent_back', 'missing', 'rejected' => 'is-bad',
        'submitted' => 'is-info',
        'cancelled', 'held', 'draft', 'upcoming', 'week_off', 'before' => 'is-draft',
        'holiday' => 'is-info',
        default => 'is-info',
    };
@endphp
<span class="people-ws-badge {{ $tone }}">{{ $label ?? $workspace->statusLabel($status ?? '') }}</span>
