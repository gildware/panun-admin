@php
    $links = $links ?? [];
    $baseUrl = $baseUrl ?? route('admin.people.index');
@endphp
<div class="people-ws">
    @if(count($links))
        <nav class="people-ws-tabs">
            @foreach($links as $key => $label)
                <a class="{{ ($section ?? '') === $key ? 'is-on' : '' }}" href="{{ $baseUrl }}?section={{ $key }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    <div class="people-ws-main">
