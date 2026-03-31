@extends('adminmodule::layouts.new-master')

@section('title', translate('Lead_Reports'))

@push('css_or_js')
    @if(in_array(($tab ?? 'inbound'), ['inbound','outbound','user'], true))
        <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/apex/apexcharts.css') }}">
    @endif
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <h2 class="page-title mb-1">{{ translate('Lead_Reports') }}</h2>
            </div>

            @php
                $activeTab = $tab ?? request()->input('tab', 'inbound');
                if (!in_array($activeTab, ['inbound','outbound','user'], true)) {
                    $activeTab = 'inbound';
                }
            @endphp

            <ul class="nav nav--tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'inbound' ? 'active' : '' }}"
                       href="{{ route('admin.lead.reports.index', array_merge($queryParams ?? [], ['tab' => 'inbound'])) }}">
                        {{ translate('Inbound') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'outbound' ? 'active' : '' }}"
                       href="{{ route('admin.lead.reports.index', array_merge($queryParams ?? [], ['tab' => 'outbound'])) }}">
                        {{ translate('Outbound') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'user' ? 'active' : '' }}"
                       href="{{ route('admin.lead.reports.index', array_merge($queryParams ?? [], ['tab' => 'user', 'user_id' => request()->input('user_id', auth()->id())])) }}">
                        {{ translate('User_Report') }}
                    </a>
                </li>
            </ul>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 fz-16">{{ translate('Search_Data') }}</div>
                    <form action="{{ route('admin.lead.reports.index') }}" method="GET">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('From_Date') }}</label>
                                <input type="date" name="date_from" class="form-control h-45" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('To_Date') }}</label>
                                <input type="date" name="date_to" class="form-control h-45" value="{{ $dateTo }}">
                            </div>
                            @if($activeTab === 'inbound')
                                <div class="col-lg-3 col-sm-6">
                                    <label class="mb-2">{{ translate('Lead_Type') }}</label>
                                    <select name="lead_type" class="js-select form-select">
                                        <option value="all" {{ ($selectedLeadType ?? 'all') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                        @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                                            <option value="{{ $value }}" {{ ($selectedLeadType ?? 'all') === $value ? 'selected' : '' }}>
                                                {{ translate($label) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($activeTab === 'outbound')
                                <div class="col-lg-3 col-sm-6">
                                    <label class="mb-2">{{ translate('Contacted_Through') }}</label>
                                    <select name="contacted_throughs[]" class="js-select form-select" multiple>
                                        <option value="call" {{ in_array('call', $selectedContactedThroughs ?? [], false) ? 'selected' : '' }}>
                                            {{ translate('Call') }}
                                        </option>
                                        <option value="message" {{ in_array('message', $selectedContactedThroughs ?? [], false) ? 'selected' : '' }}>
                                            {{ translate('Message') }}
                                        </option>
                                    </select>
                                </div>
                            @elseif($activeTab === 'user')
                                <div class="col-lg-3 col-sm-6">
                                    <label class="mb-2">{{ translate('User') }}</label>
                                    <select name="user_id" class="js-select form-select">
                                        @foreach($filterEmployees as $employee)
                                            @php
                                                $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                                $label = $fullName ?: $employee->email;
                                                $selected = ((string)($selectedUserId ?? request()->input('user_id', auth()->id()))) === (string)$employee->id;
                                            @endphp
                                            <option value="{{ $employee->id }}" {{ $selected ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if($activeTab !== 'user')
                                <div class="col-lg-3 col-sm-6">
                                    <label class="mb-2">{{ translate('Handled_By') }}</label>
                                    <select name="handled_by_ids[]" class="js-select form-select" multiple>
                                        @foreach($filterEmployees as $employee)
                                            @php
                                                $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                                $label = $fullName ?: $employee->email;
                                            @endphp
                                            <option value="{{ $employee->id }}" {{ in_array($employee->id, $selectedHandledByIds ?? [], false) ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if($activeTab === 'inbound')
                                <div class="col-lg-3 col-sm-6">
                                    <label class="mb-2">{{ translate('Source') }}</label>
                                    <select name="source_ids[]" class="js-select form-select" multiple>
                                        @foreach($filterSources as $source)
                                            <option value="{{ $source->id }}" {{ in_array($source->id, $selectedSourceIds ?? [], false) ? 'selected' : '' }}>
                                                {{ $source->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <label class="mb-2">{{ translate('Ad_Source') }}</label>
                                    <select name="ad_source_ids[]" class="js-select form-select" multiple>
                                        @foreach($filterAdSources as $adSource)
                                            <option value="{{ $adSource->id }}" {{ in_array($adSource->id, $selectedAdSourceIds ?? [], false) ? 'selected' : '' }}>
                                                {{ $adSource->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-3 col-sm-6 d-flex gap-2">
                                <button type="submit" class="btn btn--primary mt-4 flex-grow-1">{{ translate('Filter') }}</button>
                                <a href="{{ route('admin.lead.reports.index', ['tab' => $activeTab]) }}" class="btn btn--secondary mt-4 flex-grow-1">{{ translate('Reset') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($activeTab === 'outbound')
                <div class="row gy-3 pt-2">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $totalOutbound ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Total_Outbound_Enquiries_in_Range') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div class="w-100">
                                <div class="fw-semibold mb-2">{{ translate('By_Channel') }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                        @forelse(($outboundByChannel ?? []) as $row)
                                            <tr>
                                                <td>{{ $row['label'] }}</td>
                                                <td class="text-end">{{ $row['total'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-center text-muted py-2">{{ translate('Data_not_available') }}</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div class="w-100">
                                <div class="fw-semibold mb-2">{{ translate('By_User') }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                        @forelse(($outboundByUser ?? []) as $row)
                                            <tr>
                                                <td>{{ $row['label'] }}</td>
                                                <td class="text-end">{{ $row['total'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-center text-muted py-2">{{ translate('Data_not_available') }}</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div class="w-100">
                                <div class="fw-semibold mb-2">{{ translate('By_Status') }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                        @forelse(($outboundByStatus ?? []) as $row)
                                            <tr>
                                                <td>{{ $row['label'] }}</td>
                                                <td class="text-end">{{ $row['total'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-center text-muted py-2">{{ translate('Data_not_available') }}</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h4 class="mb-3">{{ translate('Outbound_Reports') }}</h4>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ translate('Call_vs_Message') }}</div>
                                    <div id="outbound-channel-chart" style="min-height: 260px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ translate('Status_wise') }}</div>
                                    <div id="outbound-status-chart" style="min-height: 260px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ translate('Call_status_wise') }}</div>
                                    <div id="outbound-call-status-chart" style="min-height: 260px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ translate('Message_status_wise') }}</div>
                                    <div id="outbound-message-status-chart" style="min-height: 260px;"></div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="fw-semibold mb-2">{{ translate('Users_wise_status') }}</div>
                                    <div id="outbound-user-status-chart" style="min-height: 340px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'user')
                <div class="row gy-3 pt-2">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $userLeadsTotal ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Leads_Handled') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/commission_earning.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $userCanceledTotal ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Cancelled_Leads') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/net_profit.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $userBookingsCount ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Bookings') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                            <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $userOutboundTotal ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Outbound_Enquiries') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <h4 class="mb-0">{{ translate('User_Report') }}: {{ $selectedUserName ?? '' }}</h4>
                                    <span class="text-muted">{{ translate('Date_Range') }}: {{ $dateFrom ?? '' }} - {{ $dateTo ?? '' }}</span>
                                </div>

                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Leads_Volume_Over_Time') }}</div>
                                            <div id="user-leads-volume-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Lead_Type_Distribution') }}</div>
                                            <div id="user-lead-type-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Lead_Status_Open_vs_Closed') }}</div>
                                            <div id="user-open-closed-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Provider_Status_Summary') }}</div>
                                            <div id="user-provider-status-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Customer_Status_Summary') }}</div>
                                            <div id="user-customer-status-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Outbound_By_Channel') }}</div>
                                            <div id="user-outbound-channel-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2">{{ translate('Outbound_By_Status') }}</div>
                                            <div id="user-outbound-status-chart" style="min-height: 260px;"></div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <span>{{ translate('Pending_Followups_Over_Time') }}</span>
                                                <span class="text-muted">{{ $userPendingFollowupsTotal ?? 0 }} {{ translate('pending') }}</span>
                                            </div>
                                            <div id="user-followup-chart" style="min-height: 320px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
            <div class="row gy-3 pt-2">
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-3 h-100">
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center">
                            <img width="35" class="avatar"
                                 src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $totalLeads ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Total_Leads_in_Range') }}</span>
                            </div>
                        </div>
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center">
                            <img width="35" class="avatar"
                                 src="{{ asset('assets/admin-module/img/icons/commission_earning.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $todayLeads ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Today_Leads') }}</span>
                            </div>
                        </div>
                        <div class="card flex-row gap-4 p-30 flex-wrap align-items-center">
                            <img width="35" class="avatar"
                                 src="{{ asset('assets/admin-module/img/icons/net_profit.png') }}" alt="">
                            <div>
                                <h2 class="fz-26">{{ $pendingFollowups ?? 0 }}</h2>
                                <span class="fz-12">{{ translate('Upcoming_or_Pending_Followups') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body ps-0">
                            <h4 class="ps-20">{{ translate('Lead_Volume_Over_Time') }}</h4>
                            <div id="lead-volume-chart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3 pt-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body ps-0">
                            <h4 class="ps-20">{{ translate('Lead_Type_Distribution') }}</h4>
                            <div id="lead-type-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body ps-0">
                            <h4 class="ps-20">{{ translate('Lead_Status_Open_vs_Closed') }}</h4>
                            <div id="lead-open-closed-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('User_Wise_Leads') }}</h4>
                            <div id="lead-user-chart" style="min-height: 260px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3 pt-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Source_Wise_Leads') }}</h4>
                            <div id="lead-source-chart" style="min-height: 260px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Ad_Source_Wise_Leads') }}</h4>
                            <div id="lead-ad-source-chart" style="min-height: 260px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3 pt-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Customer_Status_Summary') }}</h4>
                            <div id="customer-status-chart" style="min-height: 280px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Provider_Status_Summary') }}</h4>
                            <div id="provider-status-chart" style="min-height: 280px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3 pt-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Invalid_Lead_Reasons') }}</h4>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('Reason') }}</th>
                                        <th class="text-end">{{ translate('Leads') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($invalidReasonSummary as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ $row['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-3">{{ translate('Data_not_available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Future_Customer_Reasons') }}</h4>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('Reason') }}</th>
                                        <th class="text-end">{{ translate('Leads') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($futureCustomerReasonSummary as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ $row['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-3">{{ translate('Data_not_available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                        <div></div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn--secondary text-capitalize dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                    <span class="material-icons">file_download</span> {{ translate('download') }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                    <li>
                                        <a class="dropdown-item"
                                           href="{{ route('admin.lead.reports.download') . '?' . http_build_query($queryParams) }}">
                                            {{ translate('Excel') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="text-nowrap">
                            <tr>
                                <th>{{ translate('ID') }}</th>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Lead_Type') }}</th>
                                <th>{{ translate('Source') }}</th>
                                <th>{{ translate('Ad_Source') }}</th>
                                <th>{{ translate('Recieved_On') }}</th>
                                <th>{{ translate('Followup_On') }}</th>
                                <th>{{ translate('Handled_By') }}</th>
                                <th>{{ translate('Created_By') }}</th>
                                <th>{{ translate('Remarks') }}</th>
                                <th>{{ translate('Customer_Status') }}</th>
                                <th>{{ translate('Provider_Status') }}</th>
                                <th>{{ translate('Invalid_Reason') }}</th>
                                <th>{{ translate('Future_Customer_Reason') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($leads as $lead)
                                <tr>
                                    <td>{{ $lead->id }}</td>
                                    <td>{{ $lead->name ?? '—' }}</td>
                                    <td>{{ $lead->phone_number }}</td>
                                    <td>
                                        @php
                                            $type = $lead->lead_type;
                                            $label = \Modules\LeadManagement\Entities\Lead::leadTypes()[$type] ?? $type;
                                        @endphp
                                        {{ $label }}
                                    </td>
                                    <td>{{ $lead->source?->name ?? '—' }}</td>
                                    <td>{{ $lead->adSource?->name ?? '—' }}</td>
                                    <td>{{ $lead->date_time_of_lead_received?->format('d F Y h:i a') ?? '—' }}</td>
                                    <td>{{ $lead->next_followup_at?->format('d F Y h:i a') ?? '—' }}</td>
                                    <td>
                                        @php $handledBy = $lead->handled_by; @endphp
                                        @if(!$handledBy)
                                            —
                                        @elseif(isset($handledByNames[$handledBy]))
                                            {{ $handledByNames[$handledBy] }}
                                        @else
                                            {{ $handledBy }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($lead->createdBy)
                                            @php
                                                $creator = $lead->createdBy;
                                                $fullName = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
                                            @endphp
                                            {{ $fullName ?: $creator->email }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $lead->remarks ?: '—' }}</td>
                                    <td>{{ $customerStatusByLead[$lead->id] ?? '—' }}</td>
                                    <td>{{ $providerStatusByLead[$lead->id] ?? '—' }}</td>
                                    <td>{{ $invalidReasonByLead[$lead->id] ?? '—' }}</td>
                                    <td>{{ $futureCustomerReasonByLead[$lead->id] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center py-4">{{ translate('No_leads_found') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $leads->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    @if(in_array(($tab ?? 'inbound'), ['inbound','outbound','user'], true))
        <script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
    @endif
    <script>
        "use strict";

        $(document).ready(function () {
            $('.js-select').select2({
                width: '100%',
                placeholder: "{{ translate('All') }}",
                allowClear: true
            });
        });

        @if(($tab ?? 'inbound') === 'outbound')
        (function () {
            const channelLabels = {!! json_encode(array_column($outboundByChannel ?? [], 'label')) !!};
            const channelValues = {!! json_encode(array_column($outboundByChannel ?? [], 'total')) !!};

            const statusLabels = {!! json_encode($outboundStatusLabels ?? []) !!};
            const statusValues = {!! json_encode(array_column($outboundByStatus ?? [], 'total')) !!};

            const callStatusValues = {!! json_encode($outboundCallStatusCounts ?? []) !!};
            const messageStatusValues = {!! json_encode($outboundMessageStatusCounts ?? []) !!};

            const userCategories = {!! json_encode($outboundUserCategories ?? []) !!};
            const userStatusSeries = {!! json_encode($outboundUserStatusSeries ?? []) !!};

            // 1) Call vs Message
            (function () {
                const el = document.querySelector('#outbound-channel-chart');
                if (!el) return;
                const options = {
                    series: channelValues,
                    chart: { type: 'donut', height: 260 },
                    labels: channelLabels.map(function (l, i) { return (l || '—') + ' (' + (channelValues[i] ?? 0) + ')'; }),
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 2) Status wise
            (function () {
                const el = document.querySelector('#outbound-status-chart');
                if (!el) return;
                const labels = statusLabels.map(function (l, i) { return (l || '—') + ' (' + (statusValues[i] ?? 0) + ')'; });
                const options = {
                    series: statusValues,
                    chart: { type: 'pie', height: 260 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 3) Calls status wise
            (function () {
                const el = document.querySelector('#outbound-call-status-chart');
                if (!el) return;
                const options = {
                    series: [{ name: "{{ translate('Calls') }}", data: callStatusValues }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: statusLabels, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#4E73DF'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(el, options).render();
            })();

            // 4) Messages status wise
            (function () {
                const el = document.querySelector('#outbound-message-status-chart');
                if (!el) return;
                const options = {
                    series: [{ name: "{{ translate('Messages') }}", data: messageStatusValues }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: statusLabels, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#1CC88A'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(el, options).render();
            })();

            // 5) Users wise status (stacked)
            (function () {
                const el = document.querySelector('#outbound-user-status-chart');
                if (!el) return;
                const options = {
                    series: userStatusSeries,
                    chart: { type: 'bar', height: 340, stacked: true, toolbar: { show: true } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: userCategories, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false },
                    tooltip: { shared: true, intersect: false }
                };
                new ApexCharts(el, options).render();
            })();
        })();
        @elseif(($tab ?? 'inbound') === 'user')
        (function () {
            // 1) Leads volume over time (line)
            (function () {
                const el = document.querySelector('#user-leads-volume-chart');
                if (!el) return;
                const options = {
                    series: [{
                        name: "{{ translate('Leads') }}",
                        data: {!! json_encode($userLeadsPerDay ?? []) !!}
                    }],
                    chart: { height: 290, type: 'line', toolbar: { show: true } },
                    colors: ['#6F8AED'],
                    dataLabels: { enabled: true },
                    stroke: { curve: 'smooth' },
                    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: true } }, borderColor: '#CAD2FF', strokeDashArray: 5 },
                    markers: { size: 1 },
                    xaxis: { categories: {!! json_encode($userLeadsTimeline ?? []) !!} },
                    legend: { position: 'top', horizontalAlign: 'center' }
                };
                new ApexCharts(el, options).render();
            })();

            // 2) Lead type distribution (donut)
            (function () {
                const el = document.querySelector('#user-lead-type-chart');
                if (!el) return;
                const labels = {!! json_encode($userLeadsByTypeLabels ?? []) !!}.map(function (l, i) {
                    const v = ({!! json_encode($userLeadsByTypeValues ?? []) !!})[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const options = {
                    series: {!! json_encode($userLeadsByTypeValues ?? []) !!},
                    chart: { type: 'donut', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 3) Provider status summary (pie)
            (function () {
                const el = document.querySelector('#user-provider-status-chart');
                if (!el) return;
                const labels = {!! json_encode($userProviderStatusLabels ?? []) !!}.map(function (l, i) {
                    const v = ({!! json_encode($userProviderStatusValues ?? []) !!})[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const options = {
                    series: {!! json_encode($userProviderStatusValues ?? []) !!},
                    chart: { type: 'pie', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 3.5) Open vs Closed (donut)
            (function () {
                const el = document.querySelector('#user-open-closed-chart');
                if (!el) return;
                const values = {!! json_encode($userOpenClosedValues ?? [0, 0]) !!};
                const labelsRaw = {!! json_encode($userOpenClosedLabels ?? ['Open', 'Closed']) !!};
                const labels = labelsRaw.map(function (l, i) {
                    return (l || '—') + ' (' + (values[i] ?? 0) + ')';
                });
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 280 },
                    labels: labels,
                    colors: ['#e74a3b', '#1cc88a'],
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 4) Customer status summary (pie)
            (function () {
                const el = document.querySelector('#user-customer-status-chart');
                if (!el) return;
                const labels = {!! json_encode($userCustomerStatusLabels ?? []) !!}.map(function (l, i) {
                    const v = ({!! json_encode($userCustomerStatusValues ?? []) !!})[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const options = {
                    series: {!! json_encode($userCustomerStatusValues ?? []) !!},
                    chart: { type: 'pie', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 5) Outbound by channel (donut)
            (function () {
                const el = document.querySelector('#user-outbound-channel-chart');
                if (!el) return;
                const labels = {!! json_encode(array_column($userOutboundByChannel ?? [], 'label')) !!}.map(function (l, i) {
                    const v = {!! json_encode(array_column($userOutboundByChannel ?? [], 'total')) !!}[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const values = {!! json_encode(array_column($userOutboundByChannel ?? [], 'total')) !!};
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 260 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            // 6) Outbound by status (horizontal bar)
            (function () {
                const el = document.querySelector('#user-outbound-status-chart');
                if (!el) return;
                const categories = {!! json_encode($userOutboundStatusLabels ?? []) !!};
                const seriesValues = {!! json_encode($userOutboundStatusValues ?? []) !!};
                const options = {
                    series: [{ name: "{{ translate('Outbound') }}", data: seriesValues }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: categories, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#4E73DF'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(el, options).render();
            })();

            // 7) Pending followups over time (line)
            (function () {
                const el = document.querySelector('#user-followup-chart');
                if (!el) return;
                const options = {
                    series: [{
                        name: "{{ translate('Pending_Followups') }}",
                        data: {!! json_encode($userFollowupsPerDay ?? []) !!}
                    }],
                    chart: { height: 320, type: 'line', toolbar: { show: true } },
                    colors: ['#36B9CC'],
                    stroke: { curve: 'smooth' },
                    dataLabels: { enabled: false },
                    markers: { size: 2 },
                    xaxis: { categories: {!! json_encode($userFollowupTimeline ?? []) !!} },
                    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: true } }, borderColor: '#E6E6E6' }
                };
                new ApexCharts(el, options).render();
            })();
        })();
        @elseif(($tab ?? 'inbound') === 'inbound')
        (function () {
            const volumeOptions = {
                series: [
                    {
                        name: "{{ translate('Leads') }}",
                        data: {!! json_encode($leadsPerDay) !!}
                    }
                ],
                chart: {
                    height: 290,
                    type: 'line',
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#6F8AED'],
                dataLabels: {
                    enabled: true,
                },
                stroke: {
                    curve: 'smooth',
                },
                grid: {
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: true } },
                    borderColor: '#CAD2FF',
                    strokeDashArray: 5,
                },
                markers: {
                    size: 1
                },
                xaxis: {
                    categories: {!! json_encode($timeline) !!}
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                },
            };
            const volumeChartEl = document.querySelector('#lead-volume-chart');
            if (volumeChartEl) {
                const chart = new ApexCharts(volumeChartEl, volumeOptions);
                chart.render();
            }

            const typeLabels = [];
            const typeValues = [];
            @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                (function () {
                    const v = {{ (int) ($leadsByType[$value] ?? 0) }};
                    typeValues.push(v);
                    typeLabels.push("{{ translate($label) }} (" + v + ")");
                })();
            @endforeach

            const typeOptions = {
                series: typeValues,
                chart: {
                    type: 'donut',
                    height: 280
                },
                labels: typeLabels,
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                }
            };
            const typeChartEl = document.querySelector('#lead-type-chart');
            if (typeChartEl) {
                const chart2 = new ApexCharts(typeChartEl, typeOptions);
                chart2.render();
            }

            // Open vs Closed leads (donut)
            (function () {
                const el = document.querySelector('#lead-open-closed-chart');
                if (!el) return;
                const values = {!! json_encode($inboundOpenClosedValues ?? [0, 0]) !!};
                const labelsRaw = {!! json_encode($inboundOpenClosedLabels ?? ['Open', 'Closed']) !!};
                const labels = labelsRaw.map(function (l, i) {
                    return (l || '—') + ' (' + (values[i] ?? 0) + ')';
                });
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 280 },
                    labels: labels,
                    colors: ['#e74a3b', '#1cc88a'],
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false },
                };
                new ApexCharts(el, options).render();
            })();

            // User wise leads (horizontal bar)
            (function () {
                const baseLabels = {!! json_encode(array_column($userWise, 'label')) !!};
                const values = {!! json_encode(array_column($userWise, 'total')) !!};
                const labels = baseLabels.map(function (name, index) {
                    const v = values[index] ?? 0;
                    const shortName = name && name.length > 20 ? name.slice(0, 17) + '...' : (name || '');
                    return shortName + ' (' + v + ')';
                });
                const el = document.querySelector('#lead-user-chart');
                if (!el) return;
                const options = {
                    series: [{ name: "{{ translate('Leads') }}", data: values }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { labels: { style: { fontSize: '11px' } } },
                    yaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
                    colors: ['#6F8AED'],
                    dataLabels: { enabled: false },
                };
                new ApexCharts(el, options).render();
            })();

            // Source wise leads (donut)
            (function () {
                const baseLabels = {!! json_encode(array_column($sourceWise, 'label')) !!};
                const values = {!! json_encode(array_column($sourceWise, 'total')) !!};
                const labels = baseLabels.map(function (name, index) {
                    const v = values[index] ?? 0;
                    return (name || '—') + ' (' + v + ')';
                });
                const el = document.querySelector('#lead-source-chart');
                if (!el) return;
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 260 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false },
                };
                new ApexCharts(el, options).render();
            })();

            // Ad Source wise leads (donut)
            (function () {
                const baseLabels = {!! json_encode(array_column($adSourceWise, 'label')) !!};
                const values = {!! json_encode(array_column($adSourceWise, 'total')) !!};
                const labels = baseLabels.map(function (name, index) {
                    const v = values[index] ?? 0;
                    return (name || '—') + ' (' + v + ')';
                });
                const el = document.querySelector('#lead-ad-source-chart');
                if (!el) return;
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 260 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false },
                };
                new ApexCharts(el, options).render();
            })();

            // Customer status summary (pie)
            (function () {
                const baseLabels = {!! json_encode(array_column($customerStatusSummary, 'name')) !!};
                const values = {!! json_encode(array_column($customerStatusSummary, 'total')) !!};
                const labels = baseLabels.map(function (name, index) {
                    const v = values[index] ?? 0;
                    return (name || '—') + ' (' + v + ')';
                });
                const el = document.querySelector('#customer-status-chart');
                if (!el) return;
                const options = {
                    series: values,
                    chart: { type: 'pie', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false },
                };
                new ApexCharts(el, options).render();
            })();

            // Provider status summary (pie)
            (function () {
                const baseLabels = {!! json_encode(array_column($providerStatusSummary, 'name')) !!};
                const values = {!! json_encode(array_column($providerStatusSummary, 'total')) !!};
                const labels = baseLabels.map(function (name, index) {
                    const v = values[index] ?? 0;
                    return (name || '—') + ' (' + v + ')';
                });
                const el = document.querySelector('#provider-status-chart');
                if (!el) return;
                const options = {
                    series: values,
                    chart: { type: 'pie', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false },
                };
                new ApexCharts(el, options).render();
            })();
        })();
        @endif
    </script>
@endpush

