@php
    $logo = $slide['logo'] ?? 'pk-logo.png';
    $hero = $slide['hero_image'] ?? '';
    $heroPos = $slide['hero_position'] ?? 'center 30%';
    $title = (string) ($slide['title'] ?? '');
    $accent = (string) ($slide['title_accent'] ?? '');
    $titleHtml = $accent !== '' && str_contains($title, $accent)
        ? str_replace($accent, '<span>'.$accent.'</span>', e($title))
        : e($title);
@endphp
<div class="pg-pk-who">
    <aside class="pg-pk-who-intro">
        <div class="pg-pk-who-intro-copy">
            @if (!empty($slide['kicker']))
                <p class="pg-pk-who-kicker">{{ $slide['kicker'] }}</p>
            @endif
            <h2 class="pg-pk-who-title">{!! $titleHtml !!}</h2>
            @if (!empty($slide['subtitle']))
                <p class="pg-pk-who-sub">{{ $slide['subtitle'] }}</p>
            @endif
            @if (!empty($slide['lede']))
                <p class="pg-pk-who-lede">{{ $slide['lede'] }}</p>
            @endif
        </div>
        @if ($hero !== '')
            <div class="pg-pk-who-photo" style="background-image:url('{{ process_guide_training_asset($hero) }}'); background-position: {{ $heroPos }};"></div>
        @endif
        <div class="pg-pk-who-brandline">
            <img class="pg-pk-who-mark" src="{{ process_guide_training_asset($logo) }}" alt="">
            @if (!empty($slide['tagline']))
                <p>{{ $slide['tagline'] }}</p>
            @endif
        </div>
    </aside>

    <div class="pg-pk-who-main">
        <section class="pg-pk-who-model" aria-label="{{ $slide['model_label'] ?? 'Our model' }}">
            @if (!empty($slide['model_label']))
                <p class="pg-pk-who-model-label">{{ $slide['model_label'] }}</p>
            @endif
            <div class="pg-pk-who-flow">
                @foreach ($slide['flow'] ?? [] as $i => $node)
                    @if ($i > 0)
                        <span class="pg-pk-who-arrow" aria-hidden="true">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </span>
                    @endif
                    @if (($node['tone'] ?? '') === 'brand')
                        <div class="pg-pk-who-hub">
                            <img class="pg-pk-who-hub-logo" src="{{ process_guide_training_asset($logo) }}" alt="">
                            <strong>{{ $node['label'] ?? '' }}</strong>
                            @if (!empty($node['actions']))
                                <ul class="pg-pk-who-hub-actions">
                                    @foreach ($node['actions'] as $action)
                                        <li>
                                            <span class="material-symbols-outlined" aria-hidden="true">{{ $action['icon'] ?? 'circle' }}</span>
                                            {{ $action['label'] ?? '' }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="pg-pk-who-node">
                            <span class="pg-pk-who-node-icon" aria-hidden="true">
                                <span class="material-symbols-outlined">{{ $node['icon'] ?? 'circle' }}</span>
                            </span>
                            <strong>{{ $node['label'] ?? '' }}</strong>
                            @if (!empty($node['sub']))
                                <span>{{ $node['sub'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
            @if (!empty($slide['model_caption']))
                <p class="pg-pk-who-caption">{{ $slide['model_caption'] }}</p>
            @endif
        </section>

        @if (!empty($slide['pillars']))
            <ul class="pg-pk-who-pillars">
                @foreach ($slide['pillars'] as $pillar)
                    <li>
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $pillar['icon'] ?? 'circle' }}</span>
                        <strong>{{ $pillar['label'] ?? '' }}</strong>
                        @if (!empty($pillar['text']))
                            <p>{{ $pillar['text'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if (!empty($slide['banner']) || !empty($slide['banner_accent']))
            <p class="pg-pk-who-banner">
                <span class="pg-pk-who-banner-icon" aria-hidden="true">
                    <span class="material-symbols-outlined">smartphone</span>
                    <span class="material-symbols-outlined">block</span>
                </span>
                <span>
                    {{ $slide['banner'] ?? '' }}
                    @if (!empty($slide['banner_accent']))
                        <em>{{ $slide['banner_accent'] }}</em>
                    @endif
                </span>
            </p>
        @endif
    </div>
</div>
