@php
    $stages = $slide['stages'] ?? [];
@endphp
<div class="pg-pk-problem">
    <header class="pg-pk-problem-head">
        <div class="pg-pk-problem-head-copy">
            @if (!empty($slide['kicker']))
                <p class="pg-pk-problem-kicker">{{ $slide['kicker'] }}</p>
            @endif
            <h2 class="pg-pk-problem-title">{{ $slide['title'] }}</h2>
            @if (!empty($slide['subtitle']))
                <p class="pg-pk-problem-sub">{{ $slide['subtitle'] }}</p>
            @endif
        </div>
        @if (!empty($slide['support']))
            <p class="pg-pk-problem-support">{!! nl2br(e($slide['support'])) !!}</p>
        @endif
    </header>

    <div class="pg-pk-problem-journey" aria-label="The customer hassle journey">
        @foreach ($stages as $i => $stage)
            @if ($i > 0)
                <span class="pg-pk-problem-arrow" aria-hidden="true">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </span>
            @endif
            <article class="pg-pk-problem-card pg-pk-problem-card--{{ $stage['accent'] ?? 'gold' }}">
                <div class="pg-pk-problem-meta">
                    <span class="pg-pk-problem-badge" aria-hidden="true">
                        <span class="material-symbols-outlined">{{ $stage['badge'] ?? 'circle' }}</span>
                    </span>
                    <p class="pg-pk-problem-stage"><span>{{ $stage['n'] ?? '' }}</span> {{ $stage['title'] ?? '' }}</p>
                </div>
                <div class="pg-pk-problem-lede">
                    @if (!empty($stage['desc']))
                        <p class="pg-pk-problem-desc">{{ $stage['desc'] }}</p>
                    @endif
                    @if (!empty($stage['line']))
                        <p class="pg-pk-problem-line">{{ $stage['line'] }}</p>
                    @endif
                </div>
                @if (!empty($stage['image']))
                    <div class="pg-pk-problem-photo" style="background-image:url('{{ process_guide_training_asset($stage['image']) }}')"></div>
                @endif
                <ul class="pg-pk-problem-points">
                    @foreach ($stage['points'] ?? [] as $point)
                        <li>
                            <span class="material-symbols-outlined" aria-hidden="true">{{ $point['icon'] ?? 'circle' }}</span>
                            <div>
                                <strong>{{ $point['title'] ?? '' }}</strong>
                                <p>{{ $point['text'] ?? '' }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>

    @if (!empty($slide['banner']))
        <p class="pg-pk-problem-banner">
            <span class="material-symbols-outlined" aria-hidden="true">home</span>
            <span>
                {{ $slide['banner'] }}
                @if (!empty($slide['banner_accent']))
                    <em>{{ $slide['banner_accent'] }}</em>
                @endif
            </span>
        </p>
    @endif

    <footer class="pg-pk-problem-foot">
        <span>Panun Kaergar</span>
        <span>{{ str_pad((string) ($slide['number'] ?? 4), 2, '0', STR_PAD_LEFT) }}</span>
    </footer>
</div>
