@php $pkPos = $slide['hero_position'] ?? 'center 28%'; @endphp
<div class="pg-pk-funnel">
    @if (!empty($slide['hero_image']))
        <div class="pg-pk-funnel-photo" style="background-image:url('{{ process_guide_training_asset($slide['hero_image']) }}'); background-position: {{ $pkPos }}"></div>
    @endif
    <div class="pg-pk-funnel-copy">
        <ul class="pg-pk-funnel-depts">
            @foreach ($slide['departments'] ?? [] as $dept)
                @php
                    $label = is_array($dept) ? ($dept['label'] ?? '') : $dept;
                    $icon = is_array($dept) ? ($dept['icon'] ?? 'apartment') : 'apartment';
                @endphp
                <li>
                    <span class="material-icons" aria-hidden="true">{{ $icon }}</span>
                    {{ $label }}
                </li>
            @endforeach
        </ul>
        <div class="pg-pk-funnel-brand">
            <span class="material-icons" aria-hidden="true">hub</span>
            Panun Kaergar
        </div>
        <div class="pg-pk-funnel-arrow" aria-hidden="true">↓</div>
        <div class="pg-pk-funnel-customer">
            <span class="material-icons" aria-hidden="true">home</span>
            Customer
        </div>
        @if (!empty($slide['highlight']))
            <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
        @endif
    </div>
</div>
