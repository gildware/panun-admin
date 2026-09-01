@if (!empty($slide['compare']))
    <div class="pg-pk-compare">
        @foreach ($slide['compare'] as $col)
            <div class="pg-pk-compare-col pg-pk-compare-col--{{ $col['tone'] ?? 'neutral' }}">
                @if (!empty($col['kicker']))
                    <p class="pg-pk-compare-kicker">
                        <span class="material-icons" aria-hidden="true">{{ $col['icon'] ?? ($col['tone'] === 'old' ? 'cancel' : 'check_circle') }}</span>
                        {{ $col['kicker'] }}
                    </p>
                @endif
                <h5 class="pg-pk-compare-title">{{ $col['title'] ?? '' }}</h5>
                @if (!empty($col['steps']))
                    <ol class="pg-pk-compare-steps">
                        @foreach ($col['steps'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                @endif
                @if (!empty($col['text']))
                    <p class="pg-pk-compare-text">{{ $col['text'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif
@if (!empty($slide['highlight']))
    <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
@endif
