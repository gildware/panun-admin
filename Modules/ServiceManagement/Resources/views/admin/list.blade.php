@extends('adminmodule::layouts.master')

@section('title',translate('service_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <style>
        #ServiceListTableContainer a.category-list-name-link:hover,
        #ServiceListTableContainer a.category-list-name-link:focus {
            color: var(--bs-dark) !important;
        }

        .service-list-toolbar .search-form {
            min-width: 12rem;
        }

        .service-list-toolbar .service-list-filter-select-wrap {
            width: min(14rem, 100%);
        }

        .service-list-toolbar .service-list-filter-select-wrap.is-disabled {
            opacity: 0.65;
        }

        #service-list-results {
            position: relative;
            min-height: 8rem;
        }

        #service-list-results.is-loading {
            opacity: 0.55;
            pointer-events: none;
        }

        #service-list-results.is-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.35);
        }

        #service-list-results .service-list-pagination {
            margin-top: 1rem;
        }
    </style>
@endpush

@section('content')
    @php
        $listQuery = array_filter([
            'category_id' => $category_id ?? null,
            'sub_category_id' => $sub_category_id ?? null,
            'search' => $search ?: null,
        ], fn ($value) => !is_null($value) && $value !== '');
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div
                        class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{translate('service_list')}}</h2>
                        <div>
                            @can('service_add')
                                <a href="{{route('admin.service.create')}}" class="btn btn--primary">
                                    <span class="material-icons">add</span>
                                    {{translate('add_service')}}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs service-list-status-tabs">
                            <li class="nav-item">
                                <a class="nav-link {{$status=='all'?'active':''}}"
                                   href="{{ url()->current() }}?{{ http_build_query(array_merge($listQuery, ['status' => 'all'])) }}"
                                   data-status="all">
                                    {{translate('all')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='active'?'active':''}}"
                                   href="{{ url()->current() }}?{{ http_build_query(array_merge($listQuery, ['status' => 'active'])) }}"
                                   data-status="active">
                                    {{translate('active')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='inactive'?'active':''}}"
                                   href="{{ url()->current() }}?{{ http_build_query(array_merge($listQuery, ['status' => 'inactive'])) }}"
                                   data-status="inactive">
                                    {{translate('inactive')}}
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total_Services')}}:</span>
                            <span class="title-color" id="service-list-total-count">{{$services->total()}}</span>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all-tab-pane">
                            <div class="card">
                                <div class="card-body">
                                    <form action="{{ url()->current() }}"
                                          method="GET"
                                          id="service-list-toolbar-form"
                                          class="data-table-top service-list-toolbar d-flex flex-wrap align-items-center gap-2 gap-sm-3 mb-3 w-100">
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <div class="search-form search-form_style-two d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                                            <div class="input-group search-form__input_group flex-grow-1" style="max-width: 28rem;">
                                                <span class="search-form__icon">
                                                    <span class="material-icons">search</span>
                                                </span>
                                                <input type="search" class="theme-input-style search-form__input"
                                                       value="{{ $search }}" name="search"
                                                       id="service_list_search_input"
                                                       placeholder="{{ translate('search_here') }}"
                                                       autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="service-list-filter-select-wrap flex-shrink-0">
                                            <label class="visually-hidden" for="service_list_category_select">{{ translate('category') }}</label>
                                            <select class="category-select theme-input-style w-100" name="category_id"
                                                    id="service_list_category_select">
                                                <option value="" {{ empty($category_id) ? 'selected' : '' }}>{{ translate('all') }}</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ ($category_id ?? '') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="service-list-filter-select-wrap flex-shrink-0 {{ empty($category_id) ? 'is-disabled' : '' }}"
                                             id="service_list_sub_category_wrap">
                                            <label class="visually-hidden" for="service_list_sub_category_select">{{ translate('sub_category') }}</label>
                                            <select class="subcategory-select theme-input-style w-100" name="sub_category_id"
                                                    id="service_list_sub_category_select"
                                                    @disabled(empty($category_id))>
                                                <option value="" {{ empty($sub_category_id) ? 'selected' : '' }}>{{ translate('all') }}</option>
                                                @foreach($subCategories as $subCategory)
                                                    <option value="{{ $subCategory->id }}"
                                                        {{ ($sub_category_id ?? '') == $subCategory->id ? 'selected' : '' }}>
                                                        {{ $subCategory->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="service-list-clear-filters-wrap" class="{{ ($category_id || $sub_category_id) ? '' : 'd-none' }}">
                                            <button type="button"
                                                    id="service-list-clear-filters-btn"
                                                    class="btn btn--secondary text-capitalize flex-shrink-0">
                                                {{ translate('Clear_all_Filter') }}
                                            </button>
                                        </div>
                                    </form>

                                    <div id="service-list-results" data-turbo="false">
                                        @include('servicemanagement::admin.partials._service-list-results', ['services' => $services])
                                    </div>
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
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        "use strict"

        const serviceListAllLabel = @json(translate('all'));
        const serviceListSubCategoryLabel = @json(translate('sub_category'));
        const serviceListTableUrl = @json(route('admin.service.table'));
        const serviceListIndexUrl = @json(url()->current());
        let serviceListXhr = null;
        let serviceListRequestId = 0;
        let serviceListIgnoreSubCategoryChange = false;

        function buildServiceListSubCategorySelectHtml(disabled) {
            return (
                '<label class="visually-hidden" for="service_list_sub_category_select">' + serviceListSubCategoryLabel + '</label>' +
                '<select class="subcategory-select theme-input-style w-100" name="sub_category_id" id="service_list_sub_category_select"' +
                (disabled ? ' disabled' : '') + '>' +
                '<option value="" selected>' + serviceListAllLabel + '</option>' +
                '</select>'
            );
        }

        function initServiceListSubCategorySelect() {
            const $select = $('#service_list_sub_category_select');
            if (!$select.length) {
                return;
            }
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                placeholder: serviceListAllLabel,
                allowClear: false,
                width: '100%',
            });
            $select.prop('disabled', $select.is(':disabled'));
        }

        function setServiceListSubCategoryEnabled(enabled) {
            const $wrap = $('#service_list_sub_category_wrap');
            const $select = $('#service_list_sub_category_select');

            $wrap.toggleClass('is-disabled', !enabled);
            $select.prop('disabled', !enabled);

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
                initServiceListSubCategorySelect();
            }
        }

        function resetServiceListSubCategoryToAll(disabled) {
            serviceListIgnoreSubCategoryChange = true;
            $('#service_list_sub_category_wrap').html(buildServiceListSubCategorySelectHtml(disabled));
            initServiceListSubCategorySelect();
            serviceListIgnoreSubCategoryChange = false;
        }

        function getServiceListFilterParams(page) {
            const $search = $('#service_list_search_input');
            const $category = $('#service_list_category_select');
            const $subCategory = $('#service_list_sub_category_select');
            const $status = $('#service-list-toolbar-form input[name="status"]');
            const params = {
                status: $status.val() || 'all',
                page: page || 1,
            };

            const search = String($search.val() ?? '').trim();
            const categoryId = String($category.val() ?? '').trim();
            const subCategoryId = String($subCategory.val() ?? '').trim();

            if (search) {
                params.search = search;
            }
            if (categoryId) {
                params.category_id = categoryId;
            }
            if (categoryId && subCategoryId) {
                params.sub_category_id = subCategoryId;
            }

            return params;
        }

        function updateServiceListBrowserUrl(params) {
            const urlParams = new URLSearchParams();
            Object.keys(params).forEach(function (key) {
                const value = params[key];
                if (key === 'page' && Number(value) <= 1) {
                    return;
                }
                if (value !== null && value !== undefined && String(value).trim() !== '') {
                    urlParams.set(key, value);
                }
            });
            const query = urlParams.toString();
            const nextUrl = query ? (serviceListIndexUrl + '?' + query) : serviceListIndexUrl;
            window.history.replaceState({}, '', nextUrl);
        }

        function updateServiceListClearFiltersButton() {
            const hasCategory = $('#service_list_category_select').val() !== '';
            const hasSubCategory = $('#service_list_sub_category_select').val() !== '';
            $('#service-list-clear-filters-wrap').toggleClass('d-none', !(hasCategory || hasSubCategory));
        }

        function loadServiceListSubCategories(categoryId, selectedSubCategoryId) {
            if (!categoryId) {
                return;
            }

            $.get('{{ url('/') }}/admin/category/ajax-childes-only/' + categoryId, function (response) {
                const $wrap = $('#service_list_sub_category_wrap');
                const $ajaxSelect = $('<div>').html(response.template).find('select').first();

                serviceListIgnoreSubCategoryChange = true;
                $wrap.html(
                    '<label class="visually-hidden" for="service_list_sub_category_select">' + serviceListSubCategoryLabel + '</label>'
                );
                $wrap.append($ajaxSelect);

                const $select = $wrap.find('select');
                $select
                    .removeClass('js-select')
                    .addClass('subcategory-select theme-input-style w-100')
                    .attr('id', 'service_list_sub_category_select')
                    .attr('name', 'sub_category_id')
                    .prop('disabled', false);
                $select.prepend('<option value="">' + serviceListAllLabel + '</option>');

                if (selectedSubCategoryId) {
                    $select.val(selectedSubCategoryId);
                } else {
                    $select.val('');
                }

                initServiceListSubCategorySelect();
                serviceListIgnoreSubCategoryChange = false;
            });
        }

        function applyServiceListResults(response) {
            if (!response || typeof response.view !== 'string') {
                return false;
            }

            $('#service-list-results').html(response.view);
            $('#service-list-results .pagination a').attr('data-turbo', 'false');

            if (typeof response.totalServices !== 'undefined') {
                $('#service-list-total-count').text(response.totalServices);
            }

            return true;
        }

        function loadServiceList(page) {
            const params = getServiceListFilterParams(page);
            const requestId = ++serviceListRequestId;

            if (serviceListXhr) {
                serviceListXhr.abort();
            }

            updateServiceListBrowserUrl(params);
            updateServiceListClearFiltersButton();
            $('#service-list-results').addClass('is-loading');

            serviceListXhr = $.ajax({
                url: serviceListTableUrl,
                type: 'GET',
                data: params,
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function (response) {
                    if (requestId !== serviceListRequestId) {
                        return;
                    }

                    if (!applyServiceListResults(response)) {
                        return;
                    }

                    if (response.page && Number(response.page) !== Number(params.page)) {
                        updateServiceListBrowserUrl(Object.assign({}, params, { page: response.page }));
                    }
                },
                error: function (xhr) {
                    if (xhr.statusText === 'abort' || requestId !== serviceListRequestId) {
                        return;
                    }
                },
                complete: function () {
                    if (requestId === serviceListRequestId) {
                        $('#service-list-results').removeClass('is-loading');
                    }
                }
            });
        }

        function submitServiceListToolbarForm(page) {
            loadServiceList(page || 1);
        }

        $(document).ready(function () {
            let filterSubmitTimer;
            let searchSubmitTimer;
            const searchDebounceMs = 250;

            $('.js-select').select2();
            $('#service_list_category_select').select2({
                placeholder: serviceListAllLabel,
                allowClear: false,
                width: '100%',
            });
            initServiceListSubCategorySelect();
            $('#service-list-results .pagination a').attr('data-turbo', 'false');

            $('#service-list-toolbar-form').on('submit', function (e) {
                e.preventDefault();
                submitServiceListToolbarForm(1);
            });

            $('#service_list_search_input').on('input', function () {
                clearTimeout(searchSubmitTimer);
                searchSubmitTimer = setTimeout(function () {
                    submitServiceListToolbarForm(1);
                }, searchDebounceMs);
            });

            $('#service_list_category_select').on('change', function () {
                const id = this.value;

                if (!id) {
                    resetServiceListSubCategoryToAll(true);
                    setServiceListSubCategoryEnabled(false);
                    submitServiceListToolbarForm(1);
                    return;
                }

                resetServiceListSubCategoryToAll(false);
                setServiceListSubCategoryEnabled(true);
                submitServiceListToolbarForm(1);
                loadServiceListSubCategories(id);
            });

            $(document).on('change', '#service_list_sub_category_select', function () {
                if (serviceListIgnoreSubCategoryChange || $(this).is(':disabled')) {
                    return;
                }
                clearTimeout(filterSubmitTimer);
                filterSubmitTimer = setTimeout(function () {
                    submitServiceListToolbarForm(1);
                }, 150);
            });

            $('.service-list-status-tabs a').on('click', function (e) {
                e.preventDefault();
                const status = $(this).data('status') || 'all';
                $('#service-list-toolbar-form input[name="status"]').val(status);
                $('.service-list-status-tabs .nav-link').removeClass('active');
                $(this).addClass('active');
                submitServiceListToolbarForm(1);
            });

            $('#service-list-clear-filters-btn').on('click', function () {
                $('#service_list_category_select').val('').trigger('change');
            });

            $(document).on('click', '#service-list-results .pagination a', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const page = new URL(this.href, window.location.origin).searchParams.get('page') || 1;
                submitServiceListToolbarForm(page);
            });
        });
    </script>
@endpush
