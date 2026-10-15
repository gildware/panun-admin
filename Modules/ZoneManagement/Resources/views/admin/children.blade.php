@extends('adminmodule::layouts.new-master')

@section('title', $parentZone->name.' Zone')

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/css/zone-module.css')}}?v={{ @filemtime(public_path('assets/admin-module/css/zone-module.css')) ?: time() }}"/>
@endpush

@section('content')
    @php
        $backUrl = filled($parentZone->parent_id)
            ? route('admin.zone.children', $parentZone->parent_id)
            : route('admin.zone.create');
    @endphp
    <div class="main-content zone-setup-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card mb-30 zone-list-card">
                        <div class="card-body">
                            <div class="data-table-top zone-list-toolbar zone-children-toolbar d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <a href="{{ $backUrl }}" class="zone-children-back" aria-label="{{ translate('back') }}">
                                        <span class="material-icons" aria-hidden="true">arrow_back</span>
                                    </a>
                                    <span class="zone-children-title text-truncate">{{ $parentZone->name }} Zone</span>
                                </div>
                                <div class="zone-children-toolbar__actions">
                                    <form action="{{ url()->current() }}" class="search-form search-form_style-two zone-list-search mb-0" method="GET">
                                        <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                            <input type="search" class="theme-input-style search-form__input zone-search-input"
                                                   value="{{ $search }}" name="search"
                                                   placeholder="{{ translate('search_here') }}"
                                                   autocomplete="off">
                                        </div>
                                    </form>
                                    <button type="button" class="btn btn--secondary text-nowrap zone-map-view-btn" id="zone-map-view-btn" aria-pressed="false" aria-controls="zone-coverage-panel">
                                        <span class="material-icons" aria-hidden="true">map</span>
                                        <span class="zone-map-view-btn__label">{{ translate('Map_view') }}</span>
                                    </button>
                                    @can('zone_add')
                                        <a href="{{ route('admin.zone.create', ['add_parent' => $parentZone->id, 'open' => 1]) }}"
                                           class="btn btn--primary text-nowrap">
                                            {{ translate('add_new') }} {{ translate('zone') }}
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div id="ListTableContainer">
                                @include('zonemanagement::admin.partials._table')
                            </div>
                            @include('zonemanagement::admin.partials._zone-coverage-map', ['zoneCoverageParentId' => $parentZone->id])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="offset" value="{{ request()->page }}">
@endsection

@push('script')
    <script>
        (function ($) {
            'use strict';
            if (!$) {
                return;
            }

            const zoneListParentId = @json($parentZone->id);
            let statusSelectedItem;
            let statusSelectedRoute;
            let statusInitialState;
            let zoneStatusPending = false;

            $(document).on('change', '.status-update', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                statusSelectedItem = $(this);
                statusInitialState = statusSelectedItem.prop('checked');
                statusSelectedItem.prop('checked', !statusInitialState);
                zoneStatusPending = true;

                let itemId = statusSelectedItem.data('id');
                statusSelectedRoute = '{{ route('admin.zone.status-update', ['id' => ':itemId']) }}'.replace(':itemId', itemId);

                $('.confirmation-title-text').text(statusInitialState
                    ? '{{ translate('Are you sure to Turn On the Zone Status') }}?'
                    : '{{ translate('Are you sure to Turn Off the Zone Status') }}?');
                $('.confirmation-description-text').text(statusInitialState
                    ? '{{ translate('Once you turn on the Zone Status, the user can find the category, services, and location in that zone') }}.'
                    : '{{ translate('Once you turn off the Zone Status it will impact the category, services, and location finding for customers') }}.');
                $('#confirmChangeModal img').attr('src', statusInitialState
                    ? "{{ asset('assets/admin-module/img/icons/status-on.png') }}"
                    : "{{ asset('assets/admin-module/img/icons/status-off.png') }}");
                $('#confirmChangeModal').modal('show');
            });

            $('#confirmChange').on('click', function () {
                if (!zoneStatusPending || !statusSelectedRoute) {
                    return;
                }
                zoneStatusPending = false;
                const route = statusSelectedRoute;
                statusSelectedRoute = null;
                $.ajax({
                    url: route,
                    type: 'POST',
                    data: {_token: '{{ csrf_token() }}'},
                    dataType: 'json',
                    success: function (data) {
                        toastr.success(data.message, {CloseButton: true, ProgressBar: true});
                        reloadZoneChildren($('#offset').val());
                        $('#confirmChangeModal').modal('hide');
                    },
                    error: function () {
                        if (statusSelectedItem) {
                            statusSelectedItem.prop('checked', !statusInitialState);
                        }
                        toastr.error('Something went wrong! Please try again.');
                    }
                });
            });

            $('.cancel-change').on('click', function () {
                if (zoneStatusPending && statusSelectedItem) {
                    statusSelectedItem.prop('checked', !statusInitialState);
                }
                zoneStatusPending = false;
                statusSelectedRoute = null;
                $('#confirmChangeModal').modal('hide');
            });

            $('#confirmChangeModal').on('hidden.bs.modal', function () {
                if (zoneStatusPending && statusSelectedItem) {
                    statusSelectedItem.prop('checked', !statusInitialState);
                }
                zoneStatusPending = false;
                statusSelectedRoute = null;
            });

            function reloadZoneChildren(page) {
                const search = $('.zone-search-input').val();
                $.ajax({
                    url: "{{ route('admin.zone.table') }}",
                    type: 'GET',
                    data: {
                        search: search,
                        page: page,
                        parent_id: zoneListParentId
                    },
                    success: function (response) {
                        $('#offset').val(response.offset);
                        const params = new URLSearchParams();
                        if (search) params.set('search', search);
                        if (response.page > 1) params.set('page', response.page);
                        const next = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                        window.history.replaceState({}, '', next);
                        $('#ListTableContainer').empty().html(response.view);
                    },
                    error: function () {
                        toastr.error('Failed to update table. Please reload the page.', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                });
            }

            let zoneSearchTimer = null;
            $(document).on('input search', '.zone-search-input', function () {
                clearTimeout(zoneSearchTimer);
                zoneSearchTimer = setTimeout(function () {
                    $('#offset').val(1);
                    reloadZoneChildren(1);
                }, 300);
            });
            $(document).on('submit', '.zone-list-search', function (event) {
                event.preventDefault();
                clearTimeout(zoneSearchTimer);
                $('#offset').val(1);
                reloadZoneChildren(1);
            });
        })(window.jQuery);
    </script>
@endpush
