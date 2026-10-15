@php
    $tone = $tone ?? 'muted';
    $class = match ($tone) {
        'ok' => 'bg-success',
        'bad' => 'bg-danger',
        'warn' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
@endphp
<span class="badge {{ $class }}">{{ $text }}</span>
