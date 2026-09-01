@php $pkPos = $slide['hero_position'] ?? 'center 28%'; @endphp
<div class="pg-pk-split">
    @if (!empty($slide['hero_image']))
        <div class="pg-pk-split-photo" style="background-image:url('{{ process_guide_training_asset($slide['hero_image']) }}'); background-position: {{ $pkPos }}"></div>
    @endif
    <div class="pg-pk-split-copy">
        @if (!empty($slide['statement']))
            <p class="pg-pk-statement">{{ $slide['statement'] }}</p>
        @endif
        @if (!empty($slide['journey']))
            <ol class="pg-pk-stack">
                @foreach ($slide['journey'] as $step)
                    @php
                        $label = is_array($step) ? ($step['label'] ?? '') : $step;
                        $icon = is_array($step) ? ($step['icon'] ?? '') : '';
                    @endphp
                    <li>
                        @if ($icon !== '')
                            <span class="material-icons" aria-hidden="true">{{ $icon }}</span>
                        @endif
                        {{ $label }}
                    </li>
                @endforeach
            </ol>
        @endif
        @if (!empty($slide['highlight']))
            <p class="pg-pk-highlight pg-pk-highlight--light">{{ $slide['highlight'] }}</p>
        @endif
    </div>
</div>
