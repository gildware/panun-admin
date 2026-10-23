@extends('adminmodule::layouts.new-master')

@section('title', translate('Deleted_leads'))

@push('css_or_js')
    <style>
        .lead-index-page .page-title-wrap { margin-bottom: 0.75rem !important; gap: 0.75rem !important; }
        .lead-index-page .page-title { font-size: 1.35rem; margin-bottom: 0; }
        .table-leads-card .card-body { padding: 1rem 1.25rem; }
        .table-leads-fixed-layout { min-width: 1180px; font-size: 0.875rem; }
        .table-leads-fixed-layout th,
        .table-leads-fixed-layout td {
            white-space: nowrap;
            padding: 0.65rem 0.85rem;
            vertical-align: middle;
        }
        .table-leads-fixed-layout thead th {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--bs-secondary-color, #6c757d);
            background-color: var(--bg-color, #f8f9fa);
        }
        .table-leads-fixed-layout .badge {
            font-size: 0.75rem;
            font-weight: 500;
        }
        .lead-table-row { cursor: pointer; }
        .lead-table-row:hover { background-color: rgba(13, 110, 253, 0.06); }
        .deleted-lead-actions { display: inline-flex; align-items: center; gap: 0.35rem; }
        .deleted-lead-actions .btn { padding: 0.2rem 0.55rem; font-size: 0.75rem; line-height: 1.3; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="lead-index-page">
                <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                    <h2 class="page-title">{{ translate('Deleted_leads') }}</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" action="{{ route('admin.lead.deleted') }}" class="d-flex gap-2">
                            <input type="text" name="search" value="{{ $search }}" class="form-control"
                                   placeholder="{{ translate('Search_name_or_phone') }}" style="min-width: 220px;">
                            <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                        </form>
                        <a href="{{ route('admin.lead.index') }}" class="btn btn--secondary">{{ translate('Back_to_leads') }}</a>
                    </div>
                </div>

                <div class="card table-leads-card">
                    <div class="card-body">
                        <div class="table-responsive overflow-auto">
                            <table class="table table-hover align-middle table-leads-fixed-layout mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>{{ translate('ID') }}</th>
                                    <th>{{ translate('Name') }}</th>
                                    <th>{{ translate('Phone') }}</th>
                                    <th>{{ translate('Lead_Type') }}</th>
                                    <th>{{ translate('Recieved_On') }}</th>
                                    <th>{{ translate('Handled_By') }}</th>
                                    <th>{{ translate('Lead_Status') }}</th>
                                    <th>{{ translate('Deleted_by') }}</th>
                                    <th>{{ translate('Deleted_at') }}</th>
                                    <th class="text-center">{{ translate('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($leads as $lead)
                                    @php
                                        $leadDetailUrl = route('admin.lead.show', $lead->id);
                                        $deleter = $lead->deletedByUser;
                                        $deleterName = trim((string) (($deleter->first_name ?? '').' '.($deleter->last_name ?? '')));
                                        if ($deleterName === '' && $deleter) {
                                            $deleterName = (string) ($deleter->email ?: $deleter->phone);
                                        }
                                        $type = $lead->lead_type;
                                        $typeLabel = \Modules\LeadManagement\Entities\Lead::leadTypes()[$type] ?? $type;
                                        $typeBadgeClass = match ($type) {
                                            \Modules\LeadManagement\Entities\Lead::TYPE_INVALID => 'bg-danger',
                                            \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER => 'bg-success',
                                            \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER => 'bg-primary',
                                            \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER => 'bg-info',
                                            \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_PROVIDER => 'bg-secondary',
                                            default => 'bg-warning',
                                        };
                                        $handledBy = $lead->handled_by;
                                        $statusMeta = $leadStatusMeta[$lead->id] ?? null;
                                        $leadStatusLabel = $statusMeta['label'] ?? 'Closed';
                                        $leadStatusBadgeClass = $statusMeta['badge_class'] ?? 'bg-success';
                                    @endphp
                                    <tr class="lead-table-row" data-lead-url="{{ $leadDetailUrl }}">
                                        <td class="link-primary">{{ $lead->id }}</td>
                                        <td>{{ $lead->name ?? '—' }}</td>
                                        <td>{{ $lead->phone_number }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $typeBadgeClass }} text-capitalize">{{ $typeLabel }}</span>
                                        </td>
                                        <td>{{ $lead->date_time_of_lead_received?->format('d M Y, h:i a') ?? '—' }}</td>
                                        <td>
                                            @if(! \Modules\LeadManagement\Entities\Lead::assigneeIsHuman($handledBy))
                                                {{ translate('Unassigned') }}
                                            @else
                                                {{ $handledByNames[(string) $handledBy] ?? $handledBy }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill {{ $leadStatusBadgeClass }}">{{ $leadStatusLabel }}</span>
                                        </td>
                                        <td>{{ $deleterName !== '' ? $deleterName : '—' }}</td>
                                        <td>{{ optional($lead->deleted_at)->format('d M Y, h:i a') }}</td>
                                        <td class="text-center">
                                            <div class="deleted-lead-actions">
                                                <a href="{{ $leadDetailUrl }}" class="btn btn-sm btn--primary" data-turbo="false">{{ translate('view') }}</a>
                                                @can('lead_delete')
                                                    <button type="button"
                                                            class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#restoreLeadModal"
                                                            data-action="{{ route('admin.lead.deleted.restore', $lead->id) }}"
                                                            data-name="{{ $lead->name }}">
                                                        {{ translate('Restore') }}
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#forceDeleteLeadModal"
                                                            data-action="{{ route('admin.lead.deleted.force', $lead->id) }}"
                                                            data-name="{{ $lead->name }}">
                                                        {{ translate('Delete_permanently') }}
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">{{ translate('No_deleted_leads') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($leads->hasPages())
                            <div class="d-flex justify-content-end mt-3">{{ $leads->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restoreLeadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="">
                    @csrf
                    <div class="modal-header border-0">
                        <h5 class="modal-title">{{ translate('Restore') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0" data-confirm-text>{{ translate('Confirm_restore_lead') }}</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ translate('Restore') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceDeleteLeadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-danger">{{ translate('Delete_permanently') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0" data-confirm-text>{{ translate('Confirm_permanent_delete_lead') }}</p>
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
            document.querySelectorAll('.lead-table-row').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, input, select, textarea, label')) {
                        return;
                    }
                    var url = row.getAttribute('data-lead-url');
                    if (url) {
                        window.location.href = url;
                    }
                });
            });

            ['restoreLeadModal', 'forceDeleteLeadModal'].forEach(function (id) {
                var modal = document.getElementById(id);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    modal.querySelector('form').action = button.getAttribute('data-action') || '';
                    var text = modal.querySelector('[data-confirm-text]');
                    if (!text) return;
                    if (!text.getAttribute('data-base')) {
                        text.setAttribute('data-base', text.textContent);
                    }
                    var name = button.getAttribute('data-name') || '';
                    text.textContent = text.getAttribute('data-base') + (name ? ' (' + name + ')' : '');
                });
            });
        });
    </script>
@endpush
