@php
    use Modules\ServiceManagement\Support\ServiceOverviewIconPresets;

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
@endphp

@if(!empty($overview))
    <div class="service-overview-mobile-preview">
        <div class="service-overview-mobile-preview__phone">
            <div class="service-overview-mobile-preview__screen">
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

                @if(!empty($overview['whats_included']['items']) || !empty($overview['whats_not_included']['items']) || !empty($overview['good_to_know']['items']) || !empty($overview['terms_and_conditions']['items']))
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
                        @if(!empty($overview['terms_and_conditions']['items']))
                            <div class="sov-info-card sov-info-card--neutral">
                                <div class="sov-info-head">
                                    <span class="material-icons">gavel</span>
                                    {{ $overview['terms_and_conditions']['title'] ?? translate('terms_and_conditions') }}
                                </div>
                                @foreach($overview['terms_and_conditions']['items'] as $item)
                                    <div class="sov-info-line">
                                        <span class="material-icons">{{ $iconMaterialMap[$item['icon'] ?? ''] ?? 'description' }}</span>
                                        <span>{{ $item['title'] ?? $item['text'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
            </div>
        </div>
    </div>

    @once
        @push('css_or_js')
            <style>
                .service-overview-mobile-preview { display: flex; justify-content: center; padding: 8px 0 4px; }
                .service-overview-mobile-preview__phone {
                    width: 100%; max-width: 390px; border: 10px solid #1e293b; border-radius: 32px;
                    background: #0f172a; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18); overflow: hidden;
                }
                .service-overview-mobile-preview__screen {
                    background: #fff; min-height: 520px; max-height: 720px; overflow: auto; padding: 14px;
                    font-size: 12px; line-height: 1.45; color: #1e293b;
                }
                .sov-intro { margin: 0 0 12px; color: #334155; }
                .sov-title { font-size: 14px; font-weight: 700; margin: 14px 0 8px; color: #0f172a; }
                .sov-top-icons { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 4px; }
                .sov-top-icon {
                    display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 14px;
                    border: 1px solid color-mix(in srgb, var(--sov-accent) 20%, white);
                    background: color-mix(in srgb, var(--sov-accent) 8%, white);
                }
                .sov-top-icon .material-icons { font-size: 18px; color: var(--sov-accent); }
                .sov-process-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
                .sov-process-step { flex: 0 0 118px; text-align: center; }
                .sov-process-step img, .sov-process-placeholder {
                    width: 118px; height: 78px; border-radius: 12px; object-fit: cover; display: block; margin: 0 auto 6px;
                }
                .sov-process-placeholder {
                    background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6;
                }
                .sov-step-no {
                    display: inline-flex; width: 24px; height: 24px; align-items: center; justify-content: center;
                    border-radius: 999px; background: #25274d; color: #fff; font-size: 11px; font-weight: 700;
                }
                .sov-step-label { display: block; margin-top: 6px; font-weight: 600; font-size: 11px; }
                .sov-step-desc { display: block; margin-top: 4px; font-size: 10px; color: #64748b; line-height: 1.35; }
                .sov-included-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
                .sov-included-item {
                    border: 1px solid #e2e8f0; border-radius: 12px; padding: 8px 4px; text-align: center; min-height: 72px;
                    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
                }
                .sov-included-item .material-icons { font-size: 18px; color: #64748b; }
                .sov-chips { display: flex; flex-wrap: wrap; gap: 6px; }
                .sov-chip {
                    display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 999px;
                    background: rgba(37, 39, 77, 0.07); color: #25274d; font-weight: 600; font-size: 11px;
                }
                .sov-chip .material-icons { font-size: 14px; }
                .sov-info-stack { display: flex; flex-direction: column; gap: 8px; }
                .sov-info-card { border-radius: 14px; padding: 10px; }
                .sov-info-card--good { background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.18); }
                .sov-info-card--bad { background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.18); }
                .sov-info-card--neutral { background: rgba(100, 116, 139, 0.06); border: 1px solid rgba(100, 116, 139, 0.18); }
                .sov-info-head { display: flex; align-items: center; gap: 6px; font-weight: 700; margin-bottom: 8px; font-size: 12px; }
                .sov-info-card--good .sov-info-head, .sov-info-card--good .sov-info-line .material-icons { color: #16a34a; }
                .sov-info-card--bad .sov-info-head, .sov-info-card--bad .sov-info-line .material-icons { color: #dc2626; }
                .sov-info-card--neutral .sov-info-head, .sov-info-card--neutral .sov-info-line .sov-info-bullet { color: #64748b; }
                .sov-info-line { display: flex; gap: 6px; margin-bottom: 6px; }
                .sov-info-line .material-icons { font-size: 14px; margin-top: 1px; }
                .sov-info-bullet {
                    width: 6px; height: 6px; border-radius: 999px; background: currentColor;
                    margin-top: 6px; flex: 0 0 6px;
                }
                .sov-why-row { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }
                .sov-why-card {
                    flex: 0 0 150px; border-radius: 14px; padding: 12px;
                    background: color-mix(in srgb, var(--sov-accent) 10%, white);
                    border: 1px solid color-mix(in srgb, var(--sov-accent) 18%, white);
                }
                .sov-why-card .material-icons { color: var(--sov-accent); font-size: 20px; }
                .sov-why-card strong { display: block; margin: 8px 0 4px; font-size: 12px; }
                .sov-why-card span:last-child { color: #64748b; font-size: 11px; }
            </style>
        @endpush
    @endonce
@else
    <div class="text-center text-muted py-4">
        <span class="material-icons d-block mb-2" style="font-size: 2rem; opacity: .45;">phone_iphone</span>
        <p class="mb-0 fs-12">{{ translate('no_overview_sections_added_yet') }}</p>
    </div>
@endif
