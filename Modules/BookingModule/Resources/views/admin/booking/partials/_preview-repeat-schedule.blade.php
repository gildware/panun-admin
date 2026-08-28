@if(!empty($repeatPlan['is_repeat']))
    <p><strong>{{ translate('Booking_schedule_type') }}:</strong> {{ translate('Repeat_booking') }} ({{ translate(ucfirst((string) ($repeatPlan['type'] ?? ''))) }})</p>
    <p><strong>{{ translate('Starting_date') }}:</strong> {{ \Carbon\Carbon::parse($data['service_schedule'])->format('d M Y, h:i A') }}</p>
    @php
        $perPeriod = (int) ($repeatPlan['visits_per_period'] ?? $repeatPlan['planned_visits'] ?? 0);
        $periodWord = match ($repeatPlan['type'] ?? '') {
            'daily' => translate('Repeat_period_day'),
            'weekly' => translate('Repeat_period_week'),
            'yearly' => translate('Repeat_period_year'),
            default => translate('Repeat_period_month'),
        };
    @endphp
    <p><strong>{{ translate('Number_of_visits') }}:</strong> {{ $perPeriod }} / {{ $periodWord }}</p>
    @if(!empty($repeatPlan['until_stopped']) || empty($repeatPlan['end_date']))
        <p><strong>{{ translate('Repeat_until_date') }}:</strong> {{ translate('No_end_date') }} — {{ translate('Repeat_until_stopped') }}</p>
    @else
        <p><strong>{{ translate('Repeat_until_date') }}:</strong> {{ \Carbon\Carbon::parse($repeatPlan['end_date'])->format('d M Y') }}</p>
    @endif
    <p class="text-muted small mb-0">{{ translate('Repeat_visits_added_when_provider_attends') }}</p>
@else
    <p><strong>{{ translate('Service_Schedule') }}:</strong> {{ \Carbon\Carbon::parse($data['service_schedule'])->format('Y-m-d H:i') }}</p>
@endif
