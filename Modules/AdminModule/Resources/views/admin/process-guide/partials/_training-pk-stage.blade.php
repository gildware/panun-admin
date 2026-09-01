@php
    $hero = $slide['hero_image'] ?? '';
    $logo = $slide['logo'] ?? '';
    $eyebrow = $slide['tagline'] ?? '';
    $lede = $slide['subtitle'] ?? '';
    $support = $slide['body'] ?? '';
    $foot = $slide['footer'] ?? '';
    $badge = $slide['badge'] ?? '';
    $isCover = ($slide['type'] ?? '') === 'pk-cover';
    $centered = $isCover && $hero === '';
@endphp
<div class="pg-pk-stage{{ $isCover ? ' pg-pk-stage--cover' : ' pg-pk-stage--close' }}{{ $centered ? ' pg-pk-stage--centered' : '' }}">
    @if ($hero !== '')
        <div class="pg-pk-stage-photo" style="background-image:url('{{ process_guide_training_asset($hero) }}'); background-position: {{ $slide['hero_position'] ?? 'center 32%' }}"></div>
    @endif
    <div class="pg-pk-stage-copy">
        @if ($logo !== '')
            <div class="pg-pk-stage-mark">
                <img class="pg-pk-stage-logo" src="{{ process_guide_training_asset($logo) }}" alt="">
            </div>
        @endif
        @if ($eyebrow !== '')
            <p class="pg-pk-stage-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2 class="pg-pk-stage-title">{{ $slide['title'] }}</h2>
        @if ($lede !== '')
            @if ($centered)
                <span class="pg-pk-stage-rule" aria-hidden="true"></span>
            @endif
            <p class="pg-pk-stage-lede">{{ $lede }}</p>
        @endif
        @if ($support !== '')
            <p class="pg-pk-stage-support">{{ $support }}</p>
        @endif
        @if ($support !== '' && $foot !== '')
            <hr class="pg-pk-stage-hr">
        @endif
        @if ($foot !== '')
            <p class="pg-pk-stage-foot">{!! nl2br(e($foot)) !!}</p>
        @endif
        @if ($badge !== '')
            <span class="pg-pk-stage-badge">{{ $badge }}</span>
        @endif
    </div>
</div>
