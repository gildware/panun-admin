@extends('adminmodule::layouts.new-master')

@section('title', translate('Deleted_calls'))

@push('css_or_js')
    <style>
        .deleted-calls-page .page-title-wrap { margin-bottom: 0.75rem !important; gap: 0.75rem !important; }
        .deleted-calls-page .page-title { font-size: 1.35rem; margin-bottom: 0; }
        .deleted-calls-card .card-body { padding: 1rem 1.25rem; }
        .deleted-calls-table { min-width: 1180px; font-size: 0.875rem; }
        .deleted-calls-table th,
        .deleted-calls-table td {
            white-space: nowrap;
            padding: 0.65rem 0.85rem;
            vertical-align: middle;
        }
        .deleted-calls-table thead th {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--bs-secondary-color, #6c757d);
            background-color: var(--bg-color, #f8f9fa);
        }
        .deleted-calls-table .badge { font-size: 0.75rem; font-weight: 500; }
        .deleted-call-row { cursor: pointer; }
        .deleted-call-row:hover { background-color: rgba(13, 110, 253, 0.06); }
        .deleted-call-actions { display: inline-flex; align-items: center; gap: 0.35rem; }
        .deleted-call-actions .btn { padding: 0.2rem 0.55rem; font-size: 0.75rem; line-height: 1.3; }
        .call-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.12rem 0.5rem 0.12rem 0.35rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .call-pill .material-icons { font-size: 14px; }
        .call-pill--in { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--out { background: #e7f0ff; color: #1d4ed8; }
        .call-pill--answered { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--missed { background: #fdecea; color: #c62828; }
        .call-pill--unanswered { background: #fff4e5; color: #b45309; }
        .call-pill--other, .call-pill--unknown { background: #f1f3f5; color: #6b7280; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="deleted-calls-page">
                <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                    <h2 class="page-title">{{ translate('Deleted_calls') }}</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" action="{{ route('admin.calls.deleted') }}" class="d-flex gap-2">
                            <input type="text" name="search" value="{{ $search }}" class="form-control"
                                   placeholder="{{ translate('Phone_name_or_agent') }}" style="min-width: 220px;">
                            <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                        </form>
                        <a href="{{ route('admin.calls.index') }}" class="btn btn--secondary">{{ translate('Back_to_calls') }}</a>
                    </div>
                </div>

                <div class="card deleted-calls-card">
                    <div class="card-body">
                        <div class="table-responsive overflow-auto">
                            <table class="table table-hover align-middle deleted-calls-table mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Time') }}</th>
                                    <th>{{ translate('Direction') }}</th>
                                    <th>{{ translate('Phone') }}</th>
                                    <th>{{ translate('Employee') }}</th>
                                    <th>{{ translate('Result') }}</th>
                                    <th>{{ translate('Duration') }}</th>
                                    <th>{{ translate('Lead') }}</th>
                                    <th>{{ translate('Deleted_by') }}</th>
                                    <th>{{ translate('Deleted_at') }}</th>
                                    <th class="text-center">{{ translate('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($calls as $call)
                                    @php
                                        $detailUrl = route('admin.calls.show', $call->id);
                                        $deleter = $call->deletedByUser;
                                        $deleterName = trim((string) (($deleter->first_name ?? '').' '.($deleter->last_name ?? '')));
                                        if ($deleterName === '' && $deleter) {
                                            $deleterName = (string) ($deleter->email ?: $deleter->phone);
                                        }
                                        $outcome = $call->outcome ?: 'unknown';
                                        if ($outcome === 'missed' && $call->direction === 'OUTBOUND') {
                                            $outcome = 'unanswered';
                                        }
                                        $outcomeClass = match ($outcome) {
                                            'answered' => 'call-pill--answered',
                                            'missed' => 'call-pill--missed',
                                            'unanswered' => 'call-pill--unanswered',
                                            'other' => 'call-pill--other',
                                            default => 'call-pill--unknown',
                                        };
                                        $label = $call->customer_phone ?: ($call->cdr_uuid ?: ('#'.$call->id));
                                    @endphp
                                    <tr class="deleted-call-row" data-call-url="{{ $detailUrl }}">
                                        <td>{{ $call->formatIst('started_at') }}</td>
                                        <td>
                                            @if($call->direction === 'INBOUND')
                                                <span class="call-pill call-pill--in">
                                                    <span class="material-icons">call_received</span>
                                                    {{ translate('Inbound') }}
                                                </span>
                                            @elseif($call->direction === 'OUTBOUND')
                                                <span class="call-pill call-pill--out">
                                                    <span class="material-icons">call_made</span>
                                                    {{ translate('Outbound') }}
                                                </span>
                                            @else
                                                {{ $call->directionLabel() }}
                                            @endif
                                        </td>
                                        <td>{{ $call->customer_phone ?: '—' }}</td>
                                        <td>{{ $call->staff_name ?: '—' }}</td>
                                        <td>
                                            <span class="call-pill {{ $outcomeClass }}">{{ $call->outcomeLabel() }}</span>
                                        </td>
                                        <td>{{ $call->durationLabel() }}</td>
                                        <td>
                                            @if($call->lead)
                                                <a href="{{ route('admin.lead.show', $call->lead_id) }}" data-turbo="false">#{{ $call->lead_id }}</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $deleterName !== '' ? $deleterName : '—' }}</td>
                                        <td>{{ optional($call->deleted_at)->format('d M Y, h:i a') }}</td>
                                        <td class="text-center">
                                            <div class="deleted-call-actions">
                                                <a href="{{ $detailUrl }}" class="btn btn-sm btn--primary" data-turbo="false">{{ translate('view') }}</a>
                                                <button type="button"
                                                        class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#restoreCallModal"
                                                        data-action="{{ route('admin.calls.deleted.restore', $call->id) }}"
                                                        data-name="{{ $label }}">
                                                    {{ translate('Restore') }}
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#forceDeleteCallModal"
                                                        data-action="{{ route('admin.calls.deleted.force', $call->id) }}"
                                                        data-name="{{ $label }}">
                                                    {{ translate('Delete_permanently') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">{{ translate('No_deleted_calls') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($calls->hasPages())
                            <div class="d-flex justify-content-end mt-3">{{ $calls->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restoreCallModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="" data-turbo="false" data-once-submit>
                    @csrf
                    <div class="modal-header border-0">
                        <h5 class="modal-title">{{ translate('Restore') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0" data-confirm-text>{{ translate('Confirm_restore_call') }}</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ translate('Restore') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceDeleteCallModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="" data-turbo="false" data-once-submit>
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-danger">{{ translate('Delete_permanently') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0" data-confirm-text>{{ translate('Confirm_permanent_delete_call') }}</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ translate('Delete_permanently') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.deleted-call-row').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, input, select, textarea, label')) {
                        return;
                    }
                    var url = row.getAttribute('data-call-url');
                    if (url) {
                        window.location.href = url;
                    }
                });
            });

            ['restoreCallModal', 'forceDeleteCallModal'].forEach(function (id) {
                var modal = document.getElementById(id);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    var form = modal.querySelector('form');
                    form.action = button.getAttribute('data-action') || '';
                    form.dataset.submitting = '';
                    var submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                    var text = modal.querySelector('[data-confirm-text]');
                    if (!text) return;
                    if (!text.getAttribute('data-base')) {
                        text.setAttribute('data-base', text.textContent);
                    }
                    var name = button.getAttribute('data-name') || '';
                    text.textContent = text.getAttribute('data-base') + (name ? ' (' + name + ')' : '');
                });
            });

            document.querySelectorAll('form[data-once-submit]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }
                    form.dataset.submitting = '1';
                    var submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }
                });
            });
        });
    </script>
@endpush
