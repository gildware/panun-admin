@php
    $temperature = isset($temperature) && in_array($temperature, \Modules\LeadManagement\Entities\Lead::TEMPERATURES, true)
        ? $temperature
        : \Modules\LeadManagement\Entities\Lead::TEMPERATURE_WARM;
@endphp
<span class="lead-temp-pill lead-temp-pill--{{ $temperature }}">
    <span class="material-icons" aria-hidden="true">{{ \Modules\LeadManagement\Entities\Lead::leadTemperatureIcon($temperature) }}</span>
    <span class="lead-temp-pill__text">{{ \Modules\LeadManagement\Entities\Lead::leadTemperatureShortLabel($temperature) }}</span>
</span>
