@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('Meta_CAPI_Events'))

@push('css_or_js')
    @include('whatsappmodule::admin.partials.social-inbox-page-surface-css')
    <style>
        .wa-meta-capi-table td { vertical-align: middle; }
        .wa-meta-capi-table .wa-meta-capi-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.78rem;
            word-break: break-all;
        }
        .wa-meta-capi-error { max-width: 220px; font-size: 0.78rem; }
    </style>
@endpush

@section('content')
    <div class="main-content social-inbox-page social-inbox-page--{{ $siInboxCh }}">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-start gap-3 mb-3">
                <div class="flex-grow-1 min-w-0">
                    <h2 class="h4 mb-1">{{ translate('Meta_CAPI_Events') }}</h2>
                    <p class="text-muted small mb-0">
                        {{ translate('Meta_CAPI_Events_hint') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end ms-auto">
                    <a href="{{ $fbEventsManagerUrl }}" target="_blank" rel="noopener" class="btn btn--primary">
                        {{ translate('Open_Facebook_Events_Manager') }}
                    </a>
                    <a href="{{ $fbTestEventsUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                        {{ translate('Open_Facebook_Test_Events') }}
                    </a>
                    <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => $siInboxCh, 'tab' => 'chats']) }}"
                       class="btn btn-outline-secondary">
                        {{ translate('Back_to_chats') }}
                    </a>
                </div>
            </div>

            <div class="alert alert-light border small mb-3">
                <div class="mb-1">
                    <strong>{{ translate('Status') }}:</strong>
                    @if($capiConfigured)
                        <span class="text-success">{{ translate('Meta_CAPI_configured') }}</span>
                        @if($datasetId)
                            · {{ translate('Dataset') }}: <code>{{ $datasetId }}</code>
                        @endif
                    @else
                        <span class="text-danger">{{ translate('Meta_CAPI_not_configured') }}</span>
                        — set <code>META_CAPI_ENABLED=true</code> and <code>META_CAPI_DATASET_ID</code> in <code>.env</code>
                    @endif
                </div>
                <div class="text-muted">
                    {{ translate('Meta_CAPI_Events_fb_help') }}
                    <a href="{{ $fbEventsManagerUrl }}" target="_blank" rel="noopener">{{ translate('Facebook_Events_Manager') }}</a>
                    ·
                    <a href="https://developers.facebook.com/docs/marketing-api/conversions-api/business-messaging/"
                       target="_blank" rel="noopener">{{ translate('Meta_docs_business_messaging_CAPI') }}</a>
                </div>
            </div>

            @if(!$tableReady)
                <div class="alert alert-warning">
                    {{ translate('Meta_CAPI_table_missing') }}
                    <code>php artisan migrate --force</code>
                </div>
            @else
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-secondary">{{ translate('All') }}: {{ $statusCounts['all'] ?? 0 }}</span>
                    <span class="badge bg-success">{{ translate('Sent') }}: {{ $statusCounts['sent'] ?? 0 }}</span>
                    <span class="badge bg-danger">{{ translate('Failed') }}: {{ $statusCounts['failed'] ?? 0 }}</span>
                    <span class="badge bg-warning text-dark">{{ translate('Pending') }}: {{ $statusCounts['pending'] ?? 0 }}</span>
                </div>

                <form method="get" class="card border-0 shadow-sm mb-3">
                    <div class="card-body row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ translate('Search') }}</label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control"
                                   placeholder="{{ translate('Phone_lead_id_or_ctwa_clid') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">{{ translate('Event') }}</label>
                            <select name="event_name" class="form-select">
                                <option value="">{{ translate('All') }}</option>
                                <option value="LeadSubmitted" @selected($eventName === 'LeadSubmitted')>LeadSubmitted</option>
                                <option value="Schedule" @selected($eventName === 'Schedule')>Schedule</option>
                                <option value="Purchase" @selected($eventName === 'Purchase')>Purchase</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">{{ translate('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ translate('All') }}</option>
                                <option value="sent" @selected($status === 'sent')>{{ translate('Sent') }}</option>
                                <option value="failed" @selected($status === 'failed')>{{ translate('Failed') }}</option>
                                <option value="pending" @selected($status === 'pending')>{{ translate('Pending') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('Filter') }}</button>
                            <a href="{{ route('admin.whatsapp.meta-capi-events.index', ['channel' => $siInboxCh]) }}"
                               class="btn btn-outline-secondary">{{ translate('Reset') }}</a>
                        </div>
                    </div>
                </form>

                <div class="card border-0 shadow-sm">
                    <div class="card-body table-responsive">
                        <table class="table align-middle wa-meta-capi-table mb-0">
                            <thead>
                            <tr>
                                <th>{{ translate('ID') }}</th>
                                <th>{{ translate('When') }}</th>
                                <th>{{ translate('Event') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Lead') }}</th>
                                <th>{{ translate('Booking') }}</th>
                                <th>ctwa_clid</th>
                                <th>{{ translate('Error') }}</th>
                                <th>{{ translate('Chat') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($events as $event)
                                @php
                                    $badge = match ($event->status) {
                                        'sent' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'pending' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $event->id }}</td>
                                    <td class="small">
                                        {{ optional($event->sent_at ?? $event->created_at)->format('d M Y h:i a') ?? '—' }}
                                    </td>
                                    <td><code>{{ $event->event_name }}</code></td>
                                    <td><span class="badge {{ $badge }}">{{ $event->status }}</span></td>
                                    <td>{{ $event->phone }}</td>
                                    <td>
                                        @if($event->lead_id)
                                            <a href="{{ route('admin.lead.show', $event->lead_id) }}" class="link-primary">#{{ $event->lead_id }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="small">{{ $event->booking_id ?: '—' }}</td>
                                    <td class="wa-meta-capi-mono">{{ \Illuminate\Support\Str::limit((string) $event->ctwa_clid, 28) }}</td>
                                    <td class="wa-meta-capi-error text-danger">{{ \Illuminate\Support\Str::limit((string) ($event->error_message ?? ''), 80) ?: '—' }}</td>
                                    <td>
                                        @if($event->phone)
                                            <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => $siInboxCh, 'tab' => 'chats', 'phone' => $event->phone]) }}"
                                               class="btn btn-sm btn-outline-primary">{{ translate('Open') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        {{ translate('No_Meta_CAPI_events_yet') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($events && $events->hasPages())
                        <div class="card-footer">
                            {{ $events->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
