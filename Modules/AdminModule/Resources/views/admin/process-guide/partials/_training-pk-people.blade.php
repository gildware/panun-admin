@php $pkPos = $slide['hero_position'] ?? 'center 28%'; @endphp
<div class="pg-pk-people">
    @if (!empty($slide['hero_image']))
        <div class="pg-pk-people-photo" style="background-image:url('{{ process_guide_training_asset($slide['hero_image']) }}'); background-position: {{ $pkPos }}"></div>
    @endif
    <div class="pg-pk-people-copy">
        <div class="pg-pk-people-row">
            <div class="pg-pk-people-node">
                <span class="material-icons" aria-hidden="true">home</span>
                <span>Customer</span>
            </div>
            <span class="pg-pk-people-swap" aria-hidden="true">↔</span>
            <div class="pg-pk-people-core">
                <span class="material-icons" aria-hidden="true">groups</span>
                <strong>Panun Kaergar team</strong>
                @if (!empty($slide['links']))
                    <em>{{ implode('  ·  ', $slide['links']) }}</em>
                @endif
            </div>
            <span class="pg-pk-people-swap" aria-hidden="true">↔</span>
            <div class="pg-pk-people-node">
                <span class="material-icons" aria-hidden="true">handyman</span>
                <span>Professional</span>
            </div>
        </div>
        @if (!empty($slide['pulse']))
            <p class="pg-pk-pulse">{{ $slide['pulse'] }}</p>
        @endif
        @if (!empty($slide['highlight']))
            <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
        @endif
    </div>
</div>
