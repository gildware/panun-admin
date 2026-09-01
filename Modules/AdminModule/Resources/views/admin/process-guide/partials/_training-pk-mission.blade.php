<div class="pg-pk-mission">
    <div class="pg-pk-mission-col">
        <p class="pg-pk-mission-kicker">{{ $slide['left']['kicker'] ?? 'For customers' }}</p>
        <ul>
            @foreach ($slide['left']['items'] ?? [] as $item)
                <li>
                    @if (is_array($item))
                        <span class="material-icons" aria-hidden="true">{{ $item['icon'] ?? 'check' }}</span>
                        {{ $item['label'] ?? '' }}
                    @else
                        <span class="material-icons" aria-hidden="true">check</span>
                        {{ $item }}
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
    <div class="pg-pk-mission-center">
        <span>{{ $slide['center'] ?? 'PANUN KAERGAR' }}</span>
    </div>
    <div class="pg-pk-mission-col">
        <p class="pg-pk-mission-kicker">{{ $slide['right']['kicker'] ?? 'For professionals' }}</p>
        <ul>
            @foreach ($slide['right']['items'] ?? [] as $item)
                <li>
                    @if (is_array($item))
                        <span class="material-icons" aria-hidden="true">{{ $item['icon'] ?? 'check' }}</span>
                        {{ $item['label'] ?? '' }}
                    @else
                        <span class="material-icons" aria-hidden="true">check</span>
                        {{ $item }}
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@if (!empty($slide['highlight']))
    <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
@endif
