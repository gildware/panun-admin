@extends('adminmodule::layouts.new-master')

@section('title', translate('Lead_Management'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/dataTables/select.dataTables.min.css') }}"/>
    <style>
        #leadDetailModal .lead-detail-modal-dialog {
            margin: 20px;
            max-width: calc(100% - 40px);
            width: calc(100% - 40px);
            max-height: calc(100vh - 40px);
            height: calc(100vh - 40px);
        }
        #leadDetailModal .modal-content { max-height: 100%; }
        .table-leads-fixed-layout { min-width: 1100px; }
        .table-leads-fixed-layout th,
        .table-leads-fixed-layout td { white-space: nowrap; }
        .lead-filter-btn { overflow: visible; }
        .lead-filter-btn-margin { margin-right: 1rem; }
        .lead-filter-offcanvas { display: flex; flex-direction: column; }
        .lead-filter-offcanvas .lead-filter-form-flex { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .lead-filter-offcanvas .lead-filter-body { flex: 1; min-height: 0; }
        .lead-filter-offcanvas .lead-filter-footer { flex-shrink: 0; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Lead_Management') }}</h2>
                        <div>
                            <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#leadCreateModal">
                                <span class="material-icons">add</span>
                                {{ translate('Add_New_Lead') }}
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        @php
                            $baseQuery = request()->only(['tab']);
                        @endphp
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'all' ? 'active' : '' }}"
                                   href="{{ route('admin.lead.index', ['tab' => 'all']) }}">
                                    {{ translate('All_Leads') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'unknown' ? 'active' : '' }}"
                                   href="{{ route('admin.lead.index', ['tab' => 'unknown']) }}">
                                    {{ translate('Unknown_Leads') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'customer' ? 'active' : '' }}"
                                   href="{{ route('admin.lead.index', ['tab' => 'customer']) }}">
                                    {{ translate('Customer_Lead') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'future_customer' ? 'active' : '' }}"
                                   href="{{ route('admin.lead.index', ['tab' => 'future_customer']) }}">
                                    {{ translate('Future_Customer_Lead') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'provider' ? 'active' : '' }}"
                                   href="{{ route('admin.lead.index', ['tab' => 'provider']) }}">
                                    {{ translate('Provider_Leads') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab == 'invalid' ? 'active' : '' }}"
                                   href="{{ route('admin.lead.index', ['tab' => 'invalid']) }}">
                                    {{ translate('Invalid_Leads') }}
                                </a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{ translate('Total_Leads') }}:</span>
                            <span class="title-color" id="lead-total-count">{{ $leads->total() }}</span>
                        </div>
                    </div>

                    @php
                        $sourceIds = $sourceIds ?? [];
                        $adSourceIds = $adSourceIds ?? [];
                        $handledByFilterIds = $handledByFilterIds ?? [];
                        $filterStatusIds = $filterStatusIds ?? [];
                        $filterDistrictIds = $filterDistrictIds ?? [];
                        $filterZoneIds = $filterZoneIds ?? [];
                        $filterCategoryIds = $filterCategoryIds ?? [];
                        $filterCustomerStatusIds = $filterCustomerStatusIds ?? [];
                        $filterCustomerZoneIds = $filterCustomerZoneIds ?? [];
                        $filterCustomerCategoryIds = $filterCustomerCategoryIds ?? [];
                        $filterCustomerSubCategoryIds = $filterCustomerSubCategoryIds ?? [];
                        $estimatedDateFrom = $estimatedDateFrom ?? '';
                        $estimatedDateTo = $estimatedDateTo ?? '';
                        $filtersAppliedCount = count($sourceIds) + count($adSourceIds) + count($handledByFilterIds)
                            + (!empty($dateFrom) && !empty($dateTo) ? 1 : 0);
                        if ($tab === 'provider') {
                            $filtersAppliedCount += count($filterStatusIds) + count($filterDistrictIds) + count($filterZoneIds) + count($filterCategoryIds);
                        }
                        if ($tab === 'customer') {
                            $filtersAppliedCount += count($filterCustomerStatusIds) + count($filterCustomerZoneIds) + count($filterCustomerCategoryIds) + count($filterCustomerSubCategoryIds) + (!empty($estimatedDateFrom) && !empty($estimatedDateTo) ? 1 : 0);
                        }
                    @endphp

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6 col-lg-8">
                                    <input type="text"
                                           name="search"
                                           id="lead-search-input"
                                           class="form-control"
                                           value="{{ $search ?? '' }}"
                                           placeholder="{{ translate('Search_by_name_phone_or_lead_id') }}">
                                </div>
                                <div class="col-md-6 col-lg-4 d-flex justify-content-md-end">
                                    <button type="button"
                                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2 position-relative lead-filter-btn lead-filter-btn-margin"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#leadFilterDrawer"
                                            aria-controls="leadFilterDrawer">
                                        <span class="material-icons">filter_list</span>
                                        {{ translate('Filter') }}
                                        @if($filtersAppliedCount > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger lead-filter-count" id="lead-filter-count-badge">{{ $filtersAppliedCount }}</span>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="offcanvas offcanvas-end lead-filter-offcanvas" tabindex="-1" id="leadFilterDrawer" aria-labelledby="leadFilterDrawerLabel" style="width: 560px; max-width: 95vw;">
                        <div class="offcanvas-header border-bottom">
                            <h5 class="offcanvas-title" id="leadFilterDrawerLabel">{{ translate('Filters') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
                        </div>
                        <form action="{{ route('admin.lead.index') }}" method="GET" id="lead-filter-form" class="lead-filter-form-flex">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <div class="offcanvas-body pt-3 overflow-auto flex-grow-1 lead-filter-body">
                                <div class="lead-filter-section mb-4">
                                    <h6 class="text-muted text-uppercase small fw-semibold mb-3 pb-2 border-bottom">{{ translate('Basic_Filter') }}</h6>
                                    <div class="d-flex flex-column gap-3">
                                        <div>
                                            <label class="form-label">{{ translate('Source') }}</label>
                                            <select name="source_id[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterSources as $source)
                                                    <option value="{{ $source->id }}" {{ in_array((string)$source->id, array_map('strval', $sourceIds)) ? 'selected' : '' }}>{{ $source->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Ad_Source') }}</label>
                                            <select name="ad_source_id[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterAdSources as $adSource)
                                                    <option value="{{ $adSource->id }}" {{ in_array((string)$adSource->id, array_map('strval', $adSourceIds)) ? 'selected' : '' }}>{{ $adSource->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Handled_By') }}</label>
                                            <select name="handled_by[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterEmployees as $employee)
                                                    @php $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')); $label = $fullName ?: $employee->email; @endphp
                                                    <option value="{{ $employee->id }}" {{ in_array((string)$employee->id, array_map('strval', $handledByFilterIds)) ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('From_Date') }}</label>
                                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('To_Date') }}</label>
                                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                @if($tab === 'provider')
                                <div class="lead-filter-section mb-4">
                                    <h6 class="text-muted text-uppercase small fw-semibold mb-3 pb-2 border-bottom">{{ translate('Provider_Related_Filter') }}</h6>
                                    <div class="d-flex flex-column gap-3">
                                        <div>
                                            <label class="form-label">{{ translate('Status') }}</label>
                                            <select name="status_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterProviderStatuses as $status)
                                                    <option value="{{ $status->id }}" {{ in_array((string)$status->id, array_map('strval', $filterStatusIds)) ? 'selected' : '' }}>{{ $status->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('District') }}</label>
                                            <select name="district_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterDistricts as $district)
                                                    <option value="{{ $district->id }}" {{ in_array((string)$district->id, array_map('strval', $filterDistrictIds)) ? 'selected' : '' }}>{{ $district->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Zone') }}</label>
                                            <select name="zone_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterZones as $zone)
                                                    <option value="{{ $zone->id }}" {{ in_array((string)$zone->id, array_map('strval', $filterZoneIds)) ? 'selected' : '' }}>{{ $zone->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Service_Category') }}</label>
                                            <select name="category_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterCategories as $cat)
                                                    <option value="{{ $cat->id }}" {{ in_array((string)$cat->id, array_map('strval', $filterCategoryIds)) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($tab === 'customer')
                                <div class="lead-filter-section mb-4">
                                    <h6 class="text-muted text-uppercase small fw-semibold mb-3 pb-2 border-bottom">{{ translate('Customer_Related_Filter') }}</h6>
                                    <div class="d-flex flex-column gap-3">
                                        <div>
                                            <label class="form-label">{{ translate('Status') }}</label>
                                            <select name="customer_status_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterCustomerStatuses as $status)
                                                    <option value="{{ $status->id }}" {{ in_array((string)$status->id, array_map('strval', $filterCustomerStatusIds)) ? 'selected' : '' }}>{{ $status->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Zone') }}</label>
                                            <select name="customer_zone_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterZones as $zone)
                                                    <option value="{{ $zone->id }}" {{ in_array((string)$zone->id, array_map('strval', $filterCustomerZoneIds)) ? 'selected' : '' }}>{{ $zone->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Category') }}</label>
                                            <select name="customer_category_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterCategories as $cat)
                                                    <option value="{{ $cat->id }}" {{ in_array((string)$cat->id, array_map('strval', $filterCustomerCategoryIds)) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Sub_Category') }}</label>
                                            <select name="customer_sub_category_ids[]" class="form-select js-select-multi" multiple>
                                                @foreach($filterSubCategories as $subCat)
                                                    <option value="{{ $subCat->id }}" {{ in_array((string)$subCat->id, array_map('strval', $filterCustomerSubCategoryIds)) ? 'selected' : '' }}>{{ $subCat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Estimated_Date_Time_of_Service') }} ({{ translate('From_Date') }})</label>
                                            <input type="date" name="estimated_date_from" class="form-control" value="{{ $estimatedDateFrom }}">
                                        </div>
                                        <div>
                                            <label class="form-label">{{ translate('Estimated_Date_Time_of_Service') }} ({{ translate('To_Date') }})</label>
                                            <input type="date" name="estimated_date_to" class="form-control" value="{{ $estimatedDateTo }}">
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="lead-filter-footer border-top bg-body p-3 flex-shrink-0">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.lead.index', ['tab' => $tab]) }}" class="btn btn--secondary flex-grow-1">{{ translate('Reset') }}</a>
                                    <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('Filter') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="lead-list-wrapper">
                            @include('leadmanagement::admin.leads.partials._table')
                        </div>
                    </div>

                    <!-- Lead Detail Modal (fullscreen with margin) -->
                    <div class="modal fade" id="leadDetailModal" tabindex="-1" aria-labelledby="leadDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog lead-detail-modal-dialog">
                            <div class="modal-content d-flex flex-column h-100">
                                <div class="modal-body p-0 flex-grow-1 overflow-hidden position-relative" style="min-height: 0;">
                                    <button type="button" class="btn btn-sm btn--secondary position-absolute top-0 end-0 m-2 z-1" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}" title="{{ translate('Close') }}">
                                        <span class="material-icons" style="font-size: 1.25rem;">close</span>
                                    </button>
                                    <iframe id="leadDetailIframe" name="leadDetailIframe" style="width:100%; height:100%; min-height: 400px; border: none;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Create Modal -->
                    <div class="modal fade" id="leadCreateModal" tabindex="-1" aria-labelledby="leadCreateModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="leadCreateModalLabel">{{ translate('Add_New_Lead') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('admin.lead.store') }}" method="post" id="lead-add-form-modal">
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Name') }} *</label>
                                                    <input type="text" class="form-control" name="name"
                                                           placeholder="{{ translate('Name') }} *"
                                                           required>
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Phone_Number') }} *</label>
                                                    <input type="text" class="form-control" name="phone_number"
                                                           placeholder="{{ translate('Phone_Number') }} *"
                                                           required>
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Source') }}</label>
                                                    <select class="form-select js-select" name="source_id">
                                                        <option value="">{{ translate('Select_Source') }}</option>
                                                        @foreach($filterSources as $source)
                                                            <option value="{{ $source->id }}">
                                                                {{ $source->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Lead_Type') }} *</label>
                                                    <select class="form-select js-select" name="lead_type" required>
                                                        @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                                                            <option value="{{ $value }}" {{ $value === \Modules\LeadManagement\Entities\Lead::TYPE_UNKNOWN ? 'selected' : '' }}>
                                                                {{ translate($label) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Date_Time_Of_Lead_Received') }}</label>
                                                    <input type="datetime-local" class="form-control" name="date_time_of_lead_received"
                                                           value="{{ now()->format('Y-m-d\TH:i') }}">
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Leads_Ad_Source') }}</label>
                                                    <select class="form-select js-select" name="ad_source_id">
                                                        <option value="">{{ translate('Select_Ad_Source') }}</option>
                                                        @foreach($filterAdSources as $adSource)
                                                            <option value="{{ $adSource->id }}">
                                                                {{ $adSource->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Handled_By') }} ({{ translate('name_of_employee') }})</label>
                                                    <select class="form-select js-select" name="handled_by">
                                                        <option value="">{{ translate('Select_employee') }}</option>
                                                        @foreach($filterEmployees as $employee)
                                                            @php
                                                                $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                                                $label = $fullName ?: $employee->email;
                                                            @endphp
                                                            <option value="{{ $employee->id }}" {{ auth()->id() === $employee->id ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Next_Follow_up_Date') }}</label>
                                                    <input type="datetime-local" class="form-control" name="next_followup_at"
                                                           value="{{ now()->addDay()->format('Y-m-d\TH:i') }}">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Remarks') }}</label>
                                                    <textarea class="form-control" name="remarks" rows="3" placeholder="{{ translate('Remarks') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                                                {{ translate('Cancel') }}
                                            </button>
                                            <button type="submit" class="btn btn--primary">
                                                {{ translate('Submit') }}
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
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        (function ($) {
            let leadSearchTimeout = null;

            function reloadLeads() {
                const $form = $('#lead-filter-form');
                const url = $form.attr('action');
                const searchVal = ($('#lead-search-input').val() || '').trim();
                const data = $form.serialize() + '&ajax=1&search=' + encodeURIComponent(searchVal);

                $.get(url, data, function (response) {
                    if (response.html) {
                        $('#lead-list-wrapper').html(response.html);
                    }
                    if (typeof response.total !== 'undefined') {
                        $('#lead-total-count').text(response.total);
                    }
                    var count = typeof response.filters_applied_count !== 'undefined' ? response.filters_applied_count : 0;
                    var $badge = $('#lead-filter-count-badge');
                    var $btn = $('button[data-bs-target="#leadFilterDrawer"]');
                    if (count > 0) {
                        if ($badge.length) {
                            $badge.text(count);
                        } else if ($btn.length) {
                            $btn.append('<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger lead-filter-count" id="lead-filter-count-badge">' + count + '</span>');
                        }
                    } else {
                        $badge.remove();
                    }
                    var drawerEl = document.getElementById('leadFilterDrawer');
                    if (drawerEl) {
                        var bs = bootstrap.Offcanvas.getInstance(drawerEl);
                        if (bs) bs.hide();
                    }
                });
            }

            $(document).on('submit', '#lead-filter-form', function (e) {
                e.preventDefault();
                reloadLeads();
            });

            $(document).on('keyup', '#lead-search-input', function () {
                const value = $(this).val().trim();

                if (leadSearchTimeout) {
                    clearTimeout(leadSearchTimeout);
                }

                leadSearchTimeout = setTimeout(function () {
                    reloadLeads();
                }, 400);
            });

            // Lead detail modal: open with iframe to show page
            $(document).on('click', '.btn-lead-view', function (e) {
                e.preventDefault();
                var url = $(this).data('lead-url');
                if (url) {
                    $('#leadDetailIframe').attr('src', url);
                    $('#leadDetailModal').modal('show');
                }
            });

            $('#leadDetailModal').on('hidden.bs.modal', function () {
                $('#leadDetailIframe').attr('src', 'about:blank');
                reloadLeads();
            });

            window.closeLeadDetailModal = function () {
                $('#leadDetailModal').modal('hide');
            };

            $(function () {
                $('.js-select-multi').select2({ width: '100%', placeholder: '{{ translate('All') }}' });
            });
            $('#leadFilterDrawer').on('shown.bs.offcanvas', function () {
                $(this).find('.js-select-multi').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ width: '100%', placeholder: '{{ translate('All') }}' });
                    }
                });
            });
        })(jQuery);
    </script>
@endpush
