@php
    $qualityItems = $qualityItems ?? ($fullReport['quality_stats'] ?? []);
@endphp

@if($qualityItems !== [])
    <div class="section-label">{{ translate('Progress_quality_metrics') }}</div>
    <div class="score-grid mb-3">
        @foreach($qualityItems as $stat)
            <div class="score-tile">
                <div class="sv">{{ $stat['value'] ?? 0 }}</div>
                <div class="sl">{{ $stat['label'] ?? '' }}</div>
                @if(! empty($stat['sub']))
                    <div class="sw">{{ $stat['sub'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif
