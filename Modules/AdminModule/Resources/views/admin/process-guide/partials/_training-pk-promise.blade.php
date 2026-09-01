<div class="pg-pk-promise{{ !empty($slide['hero_image']) ? ' pg-pk-promise--hero' : '' }}">
    @if (!empty($slide['hero_image']))
        <div class="pg-pk-promise-photo" style="background-image:url('{{ process_guide_training_asset($slide['hero_image']) }}'); background-position: {{ $slide['hero_position'] ?? 'center 34%' }}"></div>
    @endif
    <div class="pg-pk-promise-inner">
        @if (!empty($slide['title']))
            <p class="pg-pk-promise-head">{{ $slide['title'] }}</p>
        @endif
        @if (!empty($slide['promise_cards']))
            <div class="pg-pk-promise-grid">
                @foreach ($slide['promise_cards'] as $card)
                    <article class="pg-pk-promise-card">
                        <p class="pg-pk-promise-kicker">
                            @if (!empty($card['icon']))
                                <span class="material-icons" aria-hidden="true">{{ $card['icon'] }}</span>
                            @endif
                            {{ $card['kicker'] ?? '' }}
                        </p>
                        <p class="pg-pk-promise-quote">{{ $card['quote'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        @endif
        @if (!empty($slide['promise_title']))
            <p class="pg-pk-promise-climax">{{ $slide['promise_title'] }}</p>
        @endif
        @if (!empty($slide['promise_sub']))
            <p class="pg-pk-promise-climax-sub">{{ $slide['promise_sub'] }}</p>
        @endif
    </div>
</div>
