@php
    $iconMaterialMap = [
        'verified' => 'verified',
        'home' => 'home',
        'sparkle' => 'auto_awesome',
        'warranty' => 'verified_user',
        'calendar' => 'calendar_month',
        'location' => 'location_on',
        'tools' => 'build',
        'check' => 'check_circle',
        'door' => 'door_front',
        'building' => 'apartment',
        'shop' => 'storefront',
        'wood' => 'forest',
        'quality' => 'workspace_premium',
        'pricing' => 'payments',
        'support' => 'support_agent',
    ];
    $colorMap = [
        'green' => '#22C55E',
        'blue' => '#3B82F6',
        'purple' => '#8B5CF6',
        'orange' => '#F97316',
    ];
    $overview = $resolvedOverviewContent ?? null;
    $layout = $layout ?? 'readonly';
    $wrapperClass = $layout === 'phone' ? 'service-overview-mobile-preview__screen' : 'service-overview-readonly';
@endphp

@if(!empty($overview))
    <div class="{{ $wrapperClass }}">
        @if(!empty($overview['intro']))
            <p class="sov-intro">{{ $overview['intro'] }}</p>
        @endif

        @if(!empty($overview['top_icons']))
            <div class="sov-top-icons">
                @foreach($overview['top_icons'] as $item)
                    @php $accent = $colorMap[$item['color'] ?? 'blue'] ?? '#3B82F6'; @endphp
                    <div class="sov-top-icon" style="--sov-accent: {{ $accent }}">
                        <span class="material-icons">{{ $iconMaterialMap[$item['icon'] ?? ''] ?? 'circle' }}</span>
                        <span>{{ $item['text'] ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($overview['why_choose']['items']))
            <h6 class="sov-title">{{ $overview['why_choose']['title'] ?? translate('why_choose_panun_kaergar') }}</h6>
            <div class="sov-why-row">
                @foreach($overview['why_choose']['items'] as $item)
                    @php $accent = $colorMap[$item['color'] ?? 'blue'] ?? '#3B82F6'; @endphp
                    <div class="sov-why-card" style="--sov-accent: {{ $accent }}">
                        <span class="material-icons">{{ $iconMaterialMap[$item['icon'] ?? ''] ?? 'star' }}</span>
                        <strong>{{ $item['title'] ?? '' }}</strong>
                        <span>{{ $item['description'] ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($overview['service_process']['items']))
            <h6 class="sov-title">{{ $overview['service_process']['title'] ?? translate('service_process') }}</h6>
            <div class="sov-process-row">
                @foreach($overview['service_process']['items'] as $index => $step)
                    <div class="sov-process-step">
                        @if(!empty($step['image']))
                            <img src="{{ $step['image'] }}" alt="">
                        @else
                            <div class="sov-process-placeholder">
                                <span class="material-icons">{{ $iconMaterialMap[$step['icon'] ?? ''] ?? 'build' }}</span>
                            </div>
                        @endif
                        <span class="sov-step-no">{{ $index + 1 }}</span>
                        <span class="sov-step-label">{{ $step['title'] ?? $step['text'] ?? '' }}</span>
                        @if(!empty($step['description']))
                            <span class="sov-step-desc">{{ $step['description'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($overview['perfect_for']['items']))
            <h6 class="sov-title">{{ $overview['perfect_for']['title'] ?? translate('perfect_for') }}</h6>
            <div class="sov-chips">
                @foreach($overview['perfect_for']['items'] as $chip)
                    <span class="sov-chip">
                        <span class="material-icons">{{ $iconMaterialMap[$chip['icon'] ?? ''] ?? 'label' }}</span>
                        {{ $chip['text'] ?? '' }}
                    </span>
                @endforeach
            </div>
        @endif

        @if(!empty($overview['whats_included']['items']) || !empty($overview['whats_not_included']['items']) || !empty($overview['good_to_know']['items']))
            <div class="sov-info-stack">
                @if(!empty($overview['whats_included']['items']))
                    <div class="sov-info-card sov-info-card--good">
                        <div class="sov-info-head">
                            <span class="material-icons">check_circle</span>
                            {{ $overview['whats_included']['title'] ?? translate('whats_included') }}
                        </div>
                        @foreach($overview['whats_included']['items'] as $item)
                            <div class="sov-info-line">
                                <span class="material-icons">{{ $iconMaterialMap[$item['icon'] ?? ''] ?? 'check' }}</span>
                                <span>{{ $item['title'] ?? $item['text'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if(!empty($overview['whats_not_included']['items']))
                    <div class="sov-info-card sov-info-card--bad">
                        <div class="sov-info-head">
                            <span class="material-icons">cancel</span>
                            {{ $overview['whats_not_included']['title'] ?? translate('whats_not_included') }}
                        </div>
                        @foreach($overview['whats_not_included']['items'] as $item)
                            <div class="sov-info-line">
                                <span class="material-icons">{{ $iconMaterialMap[$item['icon'] ?? ''] ?? 'close' }}</span>
                                <span>{{ $item['title'] ?? $item['text'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if(!empty($overview['good_to_know']['items']))
                    <div class="sov-info-card sov-info-card--neutral">
                        <div class="sov-info-head">
                            <span class="material-icons">info</span>
                            {{ $overview['good_to_know']['title'] ?? translate('good_to_know') }}
                        </div>
                        @foreach($overview['good_to_know']['items'] as $item)
                            <div class="sov-info-line">
                                <span class="material-icons">{{ $iconMaterialMap[$item['icon'] ?? ''] ?? 'info' }}</span>
                                <span>{{ $item['title'] ?? $item['text'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
