@php
    $highlightSlots = 3;
    $storedIcons = $overviewContent['card_highlights'] ?? [];

    $slots = [];
    for ($i = 0; $i < $highlightSlots; $i++) {
        $slots[] = $storedIcons[$i] ?? ['icon' => '', 'text' => '', 'color' => 'blue'];
    }
@endphp

<div class="card bg-light border-0 mb-30">
    <div class="card-body">
        <div class="mb-3">
            <h6 class="mb-1">{{ translate('service_card_highlights') }}</h6>
            <p class="text-muted fs-12 mb-0">{{ translate('service_card_highlights_hint') }}</p>
        </div>

        <div class="row g-3">
            @foreach($slots as $index => $slot)
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100">
                        <p class="fs-12 text-muted mb-2">{{ translate('highlight') }} {{ $index + 1 }}</p>
                        <div class="mb-2">
                            <label class="form-label fs-12 mb-1">{{ translate('select_icon') }}</label>
                            <select class="form-select form-select-sm" name="service_card_top_icons[{{ $index }}][icon]">
                                <option value="">{{ translate('select_icon') }}</option>
                                @foreach($overviewIconOptions ?? [] as $opt)
                                    <option value="{{ $opt['key'] }}" {{ ($slot['icon'] ?? '') === $opt['key'] ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-12 mb-1">{{ translate('color') }}</label>
                            <select class="form-select form-select-sm" name="service_card_top_icons[{{ $index }}][color]">
                                @foreach(['green', 'blue', 'purple', 'orange'] as $color)
                                    <option value="{{ $color }}" {{ ($slot['color'] ?? 'blue') === $color ? 'selected' : '' }}>
                                        {{ ucfirst($color) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label fs-12 mb-1">{{ translate('label') }}</label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="service_card_top_icons[{{ $index }}][text]"
                                   placeholder="{{ translate('e.g._Expert_Installation') }}"
                                   value="{{ $slot['text'] ?? '' }}">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
