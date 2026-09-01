@if (!empty($slide['journey']))
    <ol class="pg-pk-journey pg-pk-journey--{{ $slide['journey_tone'] ?? 'default' }}">
        @foreach ($slide['journey'] as $step)
            <li>
                @if (!empty($step['icon']))
                    <span class="material-icons" aria-hidden="true">{{ $step['icon'] }}</span>
                @endif
                <strong>{{ $step['label'] }}</strong>
            </li>
        @endforeach
    </ol>
@endif
@if (!empty($slide['journey_after']))
    <div class="pg-pk-journey-after">
        <p class="pg-pk-journey-after-kicker">{{ $slide['journey_after']['kicker'] ?? 'Panun Kaergar' }}</p>
        <ol class="pg-pk-journey pg-pk-journey--new">
            @foreach ($slide['journey_after']['steps'] ?? [] as $step)
                <li>
                    @if (!empty($step['icon']))
                        <span class="material-icons" aria-hidden="true">{{ $step['icon'] }}</span>
                    @endif
                    <strong>{{ $step['label'] }}</strong>
                </li>
            @endforeach
        </ol>
    </div>
@endif
@if (!empty($slide['highlight']))
    <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
@endif
