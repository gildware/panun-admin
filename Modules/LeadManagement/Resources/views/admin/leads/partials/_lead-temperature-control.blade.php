@php
    $temperatureLead = $lead ?? null;
    $temperature = $temperatureLead?->lead_temperature;
    $canMarkTemperature = $temperatureLead
        && in_array($temperatureLead->lead_type, [
            \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER,
            \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER,
        ], true);
    $temperatureColors = [
        'hotter' => '#9f1239',
        'hot' => '#c2410c',
        'warm' => '#b45309',
        'cold' => '#0369a1',
    ];
@endphp
@if(!$canMarkTemperature)
    <span class="text-muted">—</span>
@elseif(auth()->user()?->can('lead_update'))
    @php
        $shownTemperature = isset($temperatureColors[$temperature]) ? $temperature : \Modules\LeadManagement\Entities\Lead::TEMPERATURE_WARM;
    @endphp
    <span class="lead-temp-pill lead-temp-pill--{{ $shownTemperature }}">
        <span class="material-icons" aria-hidden="true">{{ \Modules\LeadManagement\Entities\Lead::leadTemperatureIcon($shownTemperature) }}</span>
        <select class="lead-temperature-select"
                data-url="{{ route('admin.lead.temperature.update', $temperatureLead->id) }}"
                data-current="{{ $shownTemperature }}"
                aria-label="{{ translate('Lead_Temperature') }}">
            @foreach(\Modules\LeadManagement\Entities\Lead::leadTemperatures() as $value => $label)
                <option value="{{ $value }}" {{ $shownTemperature === $value ? 'selected' : '' }}>
                    {{ \Modules\LeadManagement\Entities\Lead::leadTemperatureShortLabel($value) }}
                </option>
            @endforeach
        </select>
    </span>
@else
    @include('leadmanagement::admin.leads.partials._lead-temperature-badge', ['temperature' => $temperature])
@endif
