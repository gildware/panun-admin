@php
    $tone = match ($status ?? '') {
        'approved', 'published', 'verified' => 'is-good',
        'pending' => 'is-wait',
        'sent_back', 'missing', 'rejected' => 'is-bad',
        'cancelled', 'held' => 'is-draft',
        'draft' => 'is-draft',
        default => 'is-info',
    };
@endphp
<span class="people-ws-badge {{ $tone }}">{{ $workspace->statusLabel($status ?? '') }}</span>
