@if (!empty($slide['icon_grid']))
    <ul class="pg-pk-icon-grid pg-pk-icon-grid--{{ $slide['icon_grid_cols'] ?? '3' }}">
        @foreach ($slide['icon_grid'] as $item)
            <li>
                @if (!empty($item['icon']))
                    <span class="pg-pk-icon-badge" aria-hidden="true">
                        <span class="material-icons">{{ $item['icon'] }}</span>
                    </span>
                @elseif (!empty($item['n']))
                    <span class="pg-pk-icon-n">{{ $item['n'] }}</span>
                @endif
                <div>
                    @if (!empty($item['n']) && !empty($item['icon']))
                        <em class="pg-pk-icon-index">{{ $item['n'] }}</em>
                    @endif
                    <strong>{{ $item['title'] ?? $item['label'] }}</strong>
                    @if (!empty($item['text']))
                        <span>{{ $item['text'] }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
