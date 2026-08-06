@extends('adminmodule::layouts.new-master')

@section('title', translate('App_Custom_Request_Details'))

@section('content')
    <style>
        .acr-detail-layout { --acr-chat-height: calc(100vh - 260px); }
        @media (max-width: 991.98px) { .acr-detail-layout { --acr-chat-height: 460px; } }
        .acr-chat-panel { display: flex; flex-direction: column; height: var(--acr-chat-height); min-height: 360px; }
        .acr-chat-panel .card-body { display: flex; flex-direction: column; flex: 1; min-height: 0; padding: 0 !important; }
        .acr-chat-messages { flex: 1 1 auto; overflow-y: auto; display: flex; flex-direction: column; padding: 1rem; background: #f8f9fa; min-height: 0; }
        .acr-chat-messages::before { content: ''; flex: 1 1 auto; min-height: 0; }
        .acr-chat-messages-inner { display: block; width: 100%; flex: 0 0 auto; }
        .acr-chat-row { display: flex; width: 100%; margin-bottom: 0.6rem; }
        .acr-chat-row:last-child { margin-bottom: 0; }
        .acr-chat-row--customer { justify-content: flex-start; }
        .acr-chat-row--admin { justify-content: flex-end; }
        .acr-chat-bubble { display: inline-block; max-width: 78%; padding: 0.45rem 0.7rem; border-radius: 0.75rem; word-break: break-word; line-height: 1.4; vertical-align: top; height: auto !important; min-height: 0 !important; flex: none !important; }
        .acr-chat-bubble--customer { background: #fff; border: 1px solid #e9ecef; color: #212529; }
        .acr-chat-bubble--admin { background: var(--bs-primary, #25274d); color: #fff; }
        .acr-chat-bubble--admin .acr-chat-meta { color: rgba(255, 255, 255, 0.75); }
        .acr-chat-meta { font-size: 0.7rem; color: #6c757d; margin-bottom: 0.15rem; line-height: 1.2; }
        .acr-chat-text { margin: 0; line-height: 1.4; white-space: pre-line; }
        .acr-chat-compose { border-top: 1px solid #e9ecef; padding: 0.75rem 1rem; background: #fff; }
        .acr-status-badge { font-size: 0.75rem; padding: 0.25rem 0.55rem; border-radius: 999px; text-transform: capitalize; }
        .acr-status-badge--pending { background: #fff3cd; color: #856404; }
        .acr-status-badge--accepted { background: #d1e7dd; color: #0f5132; }
        .acr-status-badge--rejected { background: #f8d7da; color: #842029; }
        .acr-side-card .card-body { padding: 1.25rem !important; }
    </style>
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{ translate('App_Custom_Request_Details') }}</h2>
                <div class="d-flex flex-wrap gap-2">
                    @can('booking_delete')
                        <button type="button"
                                class="btn btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#acrDeleteModal"
                                data-acr-delete-url="{{ route('admin.booking.app-custom-requests.destroy', $customRequest->id) }}"
                                data-acr-delete-label="{{ $customRequest->reference_id }} — {{ $customRequest->name }}">
                            {{ translate('Delete') }}
                        </button>
                    @endcan
                    <a href="{{ route('admin.booking.app-custom-requests.index') }}" class="btn btn--secondary">
                        {{ translate('Back') }}
                    </a>
                </div>
            </div>

            <div class="row g-3 acr-detail-layout">
                {{-- Left: request details --}}
                <div class="col-lg-5 col-xl-4">
                    <div class="card mb-2 acr-side-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <div class="text-muted small">{{ translate('Reference') }}</div>
                                    <div class="fw-semibold fs-5">{{ $customRequest->reference_id }}</div>
                                </div>
                                <span class="acr-status-badge acr-status-badge--{{ $customRequest->status }}">
                                    {{ $customRequest->status }}
                                </span>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Customer_Name') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->name }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Phone_Number') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->phone }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Category') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->category_name ?: '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Submitted_at') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->created_at?->format('d M Y h:i a') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Description') }}</div>
                                    <div class="fw-semibold" style="white-space: pre-wrap;">{{ $customRequest->description ?: '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 acr-side-card">
                        <div class="card-body">
                            <h5 class="mb-2">{{ translate('Status') }}</h5>
                            <form method="POST" action="{{ route('admin.booking.app-custom-requests.update', $customRequest->id) }}">
                                @csrf
                                <div class="mb-2">
                                    <select name="status" class="form-control" required>
                                        @foreach(\Modules\BookingModule\Entities\AppCustomRequest::statusOptions() as $value => $label)
                                            <option value="{{ $value }}" @selected($customRequest->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn--primary w-100">{{ translate('Save') }}</button>
                            </form>
                        </div>
                    </div>

                    <div class="card acr-side-card">
                        <div class="card-body">
                            <div class="text-muted small mb-2">{{ translate('Lead') }}</div>
                            @if($customRequest->lead)
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a href="{{ route('admin.lead.show', $customRequest->lead->id) }}" class="link-primary fw-semibold">
                                        #{{ $customRequest->lead->id }} — {{ $customRequest->lead->name ?: '—' }}
                                    </a>
                                    @if($customRequest->lead->source)
                                        <span class="badge bg-light text-dark">{{ $customRequest->lead->source->name }}</span>
                                    @endif
                                </div>
                            @else
                                <div>—</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right: conversation + reply --}}
                <div class="col-lg-7 col-xl-8">
                    <div class="card acr-chat-panel h-100">
                        <div class="card-header bg-white border-bottom py-2">
                            <h5 class="mb-0">{{ translate('Conversation') }}</h5>
                            <div class="text-muted small">{{ $customRequest->name }}</div>
                        </div>
                        <div class="card-body">
                            <div class="acr-chat-messages" id="acr-chat-messages">
                                <div class="acr-chat-messages-inner">
                                    @forelse($customRequest->messages as $message)
                                        @php($isAdminMessage = $message->sender_type === 'admin')
                                        <div class="acr-chat-row acr-chat-row--{{ $isAdminMessage ? 'admin' : 'customer' }}">
                                            <div class="acr-chat-bubble acr-chat-bubble--{{ $isAdminMessage ? 'admin' : 'customer' }}">
                                                <div class="acr-chat-meta">
                                                    {{ $isAdminMessage ? translate('Admin') : translate('Customer') }}
                                                    · {{ $message->created_at?->format('d M Y h:i a') }}
                                                </div>
                                                <p class="acr-chat-text mb-0">{{ trim((string) $message->message) }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted text-center py-4">{{ translate('No_data_available') }}</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="acr-chat-compose">
                                <form method="POST" action="{{ route('admin.booking.app-custom-requests.update', $customRequest->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $customRequest->status }}">
                                    <div class="mb-2">
                                        <label class="form-label mb-1">{{ translate('Reply_to_customer') }}</label>
                                        <textarea name="admin_message" class="form-control" rows="2" placeholder="{{ translate('Write_your_reply_here') }}" required></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn--primary">
                                            {{ translate('Send') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('bookingmodule::admin.app-custom-request.partials._delete-modal')
@endsection

@push('script')
    <script>
        (function () {
            var el = document.getElementById('acr-chat-messages');
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        })();
    </script>
@endpush
