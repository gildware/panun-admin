@extends('adminmodule::layouts.new-master')

@section('title', translate('Todays_pending_followups'))

@push('css_or_js')
    <style>
        .missed-followup-row,
        .missed-followup-row > td {
            background-color: #fff !important;
            color: #dc3545 !important;
        }
        .table-hover > tbody > tr.missed-followup-row:hover > * {
            background-color: #fff !important;
            color: #dc3545 !important;
        }
        .missed-followup-row a,
        .missed-followup-row a.text-primary,
        .missed-followup-row .text-primary,
        .missed-followup-row .small a {
            color: #dc3545 !important;
        }
        /* Keep primary action button text white even on missed rows. */
        .missed-followup-row a.btn,
        .missed-followup-row .btn,
        .missed-followup-row a.btn--primary,
        .missed-followup-row .btn--primary,
        .missed-followup-row a.btn.btn--primary {
            color: #fff !important;
        }
        .missed-followup-row a.btn:hover,
        .missed-followup-row .btn:hover,
        .missed-followup-row a.btn--primary:hover,
        .missed-followup-row .btn--primary:hover {
            color: #fff !important;
        }
        .missed-followup-row .badge {
            border: 1px solid rgba(220, 53, 69, 0.6);
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="page-title mb-0">Leads Follow-ups- Pending Till Today's ({{ $totalFollowups ?? 0 }})</h2>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.lead.todays_followups') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('From_Date') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('To_Date') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('Handled_By') }}</label>
                                <select name="handled_by" class="form-select">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="{{ \Modules\LeadManagement\Entities\Lead::FILTER_UNASSIGNED_VALUE }}" {{ (string) ($selectedHandledById ?? '') === \Modules\LeadManagement\Entities\Lead::FILTER_UNASSIGNED_VALUE ? 'selected' : '' }}>{{ translate('AI_handled_or_Unassigned') }}</option>
                                    @foreach($assignees ?? [] as $assignee)
                                        @php
                                            $fullName = trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? ''));
                                            $label = $fullName ?: ($assignee->email ?? (string) $assignee->id);
                                        @endphp
                                        <option value="{{ $assignee->id }}" {{ (string) $assignee->id === (string) ($selectedHandledById ?? '') ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6 d-flex gap-2">
                                <button type="submit" class="btn btn--primary w-100">{{ translate('Filter') }}</button>
                                <a href="{{ route('admin.lead.todays_followups') }}" class="btn btn--secondary w-100">{{ translate('Reset') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if($leads->isNotEmpty())
                        <div class="table-responsive px-3">
                            <table class="table table-hover align-middle mb-0 fs-13">
                                <thead class="text-secondary border-bottom">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Lead_ID') }}</th>
                                        <th>{{ translate('Name') }}</th>
                                        <th>{{ translate('Phone') }}</th>
                                        <th>{{ translate('Lead_Type') }}</th>
                                        <th>{{ translate('Handled_By') }}</th>
                                        <th>{{ translate('Followup_On') }}</th>
                                        <th class="text-end">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leads as $key => $lead)
                                        <tr class="{{ $lead->next_followup_at && !$lead->next_followup_at->isToday() ? 'missed-followup-row' : '' }}">
                                            <td>{{ $key + $leads->firstItem() }}</td>
                                            <td>
                                                <a href="{{ route('admin.lead.show', $lead->id) }}" class="text-primary text-decoration-none">
                                                    {{ $lead->id }}
                                                </a>
                                            </td>
                                            <td>{{ $lead->name ?? '—' }}</td>
                                            <td>
                                                @if(!empty($lead->phone_number))
                                                    <a href="tel:{{ $lead->phone_number }}" class="text-decoration-none text-primary">
                                                        {{ $lead->phone_number }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $typeLabel = \Modules\LeadManagement\Entities\Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type;
                                                @endphp
                                                <span class="badge rounded-pill bg-primary text-capitalize">{{ $typeLabel }}</span>
                                            </td>
                                            <td>{{ $lead->handled_by_name ?? '—' }}</td>
                                            <td>
                                                @php($due = $lead->next_followup_at)
                                                @if(!$due)
                                                    —
                                                @elseif($due->isToday())
                                                    {{ translate('Today') }}
                                                @elseif($due->isYesterday())
                                                    {{ translate('Yesterday') }}
                                                @else
                                                    @php($daysBefore = max(1, (int) round($due->diffInRealDays(\Carbon\Carbon::now(), true))))
                                                    {{ $daysBefore }} {{ translate('days_before') }}
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.lead.show', $lead->id) }}" class="btn btn-sm btn--primary">
                                                    {{ translate('view') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top">
                            {{ $leads->links() }}
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-center p-5">
                            <span class="opacity-50">{{ translate('No_follow_ups_yet') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

