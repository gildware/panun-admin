@php
    $pkPos = $slide['hero_position'] ?? 'center 28%';
    $hasPhoto = !empty($slide['hero_image']);
@endphp
@if (!empty($slide['process']))
    <div class="pg-pk-process-wrap{{ $hasPhoto ? ' pg-pk-process-wrap--photo' : '' }}">
        @if ($hasPhoto)
            <div class="pg-pk-process-photo" style="background-image:url('{{ process_guide_training_asset($slide['hero_image']) }}'); background-position: {{ $pkPos }}"></div>
        @endif
        <div class="pg-pk-process-main">
            <ol class="pg-pk-process pg-pk-process--{{ count($slide['process']) }}">
                @foreach ($slide['process'] as $i => $step)
                    @php
                        $label = is_array($step) ? ($step['label'] ?? '') : $step;
                        $icon = is_array($step) ? ($step['icon'] ?? '') : '';
                    @endphp
                    <li>
                        <span class="pg-pk-process-n">{{ $i + 1 }}</span>
                        @if ($icon !== '')
                            <span class="material-icons" aria-hidden="true">{{ $icon }}</span>
                        @endif
                        <strong>{{ $label }}</strong>
                    </li>
                @endforeach
            </ol>
            @if (!empty($slide['highlight']))
                <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
            @endif
        </div>
    </div>
@endif
