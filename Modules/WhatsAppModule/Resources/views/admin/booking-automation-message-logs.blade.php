@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('WhatsApp_booking_automation_log_title'))

@push('css_or_js')
    @include('whatsappmodule::admin.partials.social-inbox-page-surface-css')
    <style>
        .wa-booking-automation-log-table th,
        .wa-booking-automation-log-table td {
            white-space: nowrap;
        }
        .wa-booking-automation-log-table td.wa-booking-automation-col-template-info {
            white-space: normal;
            max-width: 28rem;
        }
    </style>
@endpush

@section('content')
    @php
        $siInboxCh = request()->route('channel') ?? 'whatsapp';
    @endphp
    <div class="main-content social-inbox-page social-inbox-page--{{ $siInboxCh }}">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-start gap-3 mb-3">
            <div class="flex-grow-1 min-w-0">
                <h2 class="h4 mb-1">{{ translate('WhatsApp_booking_automation_log_title') }}</h2>
                <p class="text-muted small mb-0">{{ translate('WhatsApp_booking_automation_log_hint') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end ms-auto">
                @can('whatsapp_message_template_update')
                    <form id="wa-booking-automation-log-clear-form"
                          action="{{ route('admin.whatsapp.booking-templates.automation-log.clear', ['channel' => $siInboxCh]) }}"
                          method="post"
                          class="d-none">
                        @csrf
                    </form>
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#waBookingAutomationLogClearModal">
                        {{ translate('WhatsApp_booking_automation_log_clear') }}
                    </button>
                @endcan
                <a href="{{ route('admin.whatsapp.booking-templates.edit', ['channel' => $siInboxCh]) }}" class="btn btn-outline-secondary">
                    {{ translate('WhatsApp_booking_automation_log_back_templates') }}
                </a>
            </div>
        </div>

        @can('whatsapp_message_template_update')
            <div class="modal fade" id="waBookingAutomationLogClearModal" tabindex="-1"
                 aria-labelledby="waBookingAutomationLogClearModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="waBookingAutomationLogClearModalLabel">
                                {{ translate('WhatsApp_booking_automation_log_clear') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="{{ translate('close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">{{ translate('WhatsApp_booking_automation_log_clear_confirm') }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                {{ translate('cancel') }}
                            </button>
                            <button type="submit"
                                    form="wa-booking-automation-log-clear-form"
                                    class="btn btn-danger">
                                {{ translate('WhatsApp_booking_automation_log_clear') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

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
                            <th>{{ translate('WhatsApp_booking_automation_col_message_template_info') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_trigger') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_template') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_recipient') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_party') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_booking') }}</th>
                            <th>{{ translate('WhatsApp_booking_automation_col_error') }}</th>
                            <th class="small">{{ translate('WhatsApp_booking_automation_col_context') }}</th>
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
                                <td class="small">
                                    @if(!empty($log->created_at))
                                        {{ $log->created_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') . ' IST' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeClass = 'bg-light text-dark';
                                        if ($log->result === 'sent') {
                                            $badgeClass = 'bg-success';
                                        } elseif ($log->result === 'failed') {
                                            $badgeClass = 'bg-danger';
                                        } elseif ($log->result === 'skipped') {
                                            $badgeClass = 'bg-secondary';
                                        }
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
                                <td class="small text-muted wa-booking-automation-col-template-info">{{ \Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::messageTemplateInfoForAdmin((string) $log->message_key) }}</td>
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
                                <td class="small text-muted" style="white-space: normal; max-width: 14rem;">
                                    @if(!empty($log->context_json) && is_array($log->context_json))
                                        <span title="{{ json_encode($log->context_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">{{ \Illuminate\Support\Str::limit(json_encode($log->context_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 120) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
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
    </div>
@endsection
