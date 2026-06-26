@php
    $toTime = strtotime($notification->created_at);
    $fromTime = strtotime(now());
    $diff = round(abs($toTime - $fromTime) / 60, 2);
    $time = $diff . ' ' . translate('min');
    if ($diff > 60) {
        $diff = round($diff / 60);
        $time = $diff . ' ' . translate('hr');
        if ($diff > 24) {
            $diff = round($diff / 24);
            $time = $diff . ' ' . translate('day');
        }
    }
@endphp
<span class="card-text fz-12 text-opacity-75">{{ $time }} {{ translate('ago') }}</span>
