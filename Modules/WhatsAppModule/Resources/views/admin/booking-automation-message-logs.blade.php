@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('WhatsApp_booking_automation_log_title'))

@push('css_or_js')
    <style>
        .wa-booking-automation-log-table th,
        .wa-booking-automation-log-table td {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">{{ translate('WhatsApp_booking_automation_log_title') }}</h2>
                <p class="text-muted small mb-0">{{ translate('WhatsApp_booking_automation_log_hint') }}</p>
            </div>
            <a href="{{ route('admin.whatsapp.booking-templates.edit') }}" class="btn btn-outline-secondary">
                {{ translate('WhatsApp_booking_automation_log_back_templates') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 wa-booking-automation-log-table">
                        <thead class="table-light">
                        <tr>
                            <th>{{ translate('WhatsApp_booking_automation_col_time') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_result') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_delivery') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_message_key') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_trigger') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_template') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_recipient') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_party') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_booking') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_error') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            @php
                                $waId = $log->wa_message_id ? (string) $log->wa_message_id : '';
                                $delivery = null;
                                $deliveryDetail = null;
                                if ($log->result === 'sent' && $waId !== '') {
                                    $delivery = $statusByWaId[$waId] ?? null;
                                    $deliveryDetail = $detailByWaId[$waId] ?? null;
                                }
                            @endphp
                            <tr>
                                <td class="small">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '—' }}</td>
                                <td>
                                    @php
                                        $badgeClass = match ($log->result) {
                                            'sent' => 'bg-success',
                                            'failed' => 'bg-danger',
                                            'skipped' => 'bg-secondary',
                                            default => 'bg-light text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $log->result }}</span>
                                </td>
                                <td class="small">
                                    @if($log->result !== 'sent')
                                        <span class="text-muted">—</span>
                                    @elseif($waId === '')
                                        <span class="text-muted">—</span>
                                    @elseif($delivery)
                                        <span class="badge bg-info text-dark">{{ $delivery }}</span>
                                        @if($deliveryDetail)
                                            <span class="text-danger small ms-1" title="{{ $deliveryDetail }}">{{ $deliveryDetail }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">{{ translate('WhatsApp_booking_automation_delivery_pending') }}</span>
                                    @endif
                                </td>
                                <td><code class="small">{{ $log->message_key }}</code></td>
                                <td class="small">{{ $log->trigger_event ?? '—' }}</td>
                                <td class="small">{{ $log->template_name ?? '—' }}</td>
                                <td class="small"><code>{{ $log->recipient_phone ?? '—' }}</code></td>
                                <td class="small">{{ $log->recipient_party }}</td>
                                <td class="small">
                                    @if($log->booking_id)
                                        #{{ $log->booking_id }}
                                    @else
                                        —
                                    @endif
                                    @if($log->booking_repeat_id)
                                        <span class="text-muted"> · R#{{ $log->booking_repeat_id }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->error_detail ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
                <div class="card-footer">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
