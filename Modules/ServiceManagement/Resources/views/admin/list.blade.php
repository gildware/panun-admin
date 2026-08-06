@extends('adminmodule::layouts.master')

@section('title',translate('service_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    @include('categorymanagement::admin.partials._category-card-styles')
    <style>
        .category-page-toolbar__filter--sub.is-disabled {
            opacity: 0.65;
        }

        .category-page-toolbar__filter--sub.is-disabled .select2-container {
            pointer-events: none;
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

        .category-card--service .category-card__media--cover {
            height: 7.5rem;
            padding: 0;
            overflow: hidden;
            background: var(--bs-tertiary-bg);
        }

        .category-card--service .category-card__media--cover.is-placeholder {
            background: linear-gradient(
                135deg,
                rgba(var(--bs-primary-rgb), 0.1) 0%,
                rgba(var(--bs-primary-rgb), 0.03) 55%,
                var(--bs-tertiary-bg) 100%
            );
        }

        .category-card--service .category-card__media--cover img {
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            object-fit: cover;
            display: block;
        }

        .category-card--service .category-card__media-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: rgba(var(--bs-primary-rgb), 0.42);
        }

        .category-card--service .category-card__media-placeholder .material-icons {
            font-size: 2.75rem;
            line-height: 1;
        }

        .service-card__stats {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.125rem;
        }

        .service-card__stat {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
            min-width: 0;
        }

        .service-card__stat-label {
            font-size: 0.625rem;
            font-weight: 600;
            line-height: 1.2;
            color: var(--bs-secondary-color);
            text-transform: capitalize;
        }

        .service-card__stat-value {
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.35;
            color: var(--bs-body-color);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="category-page-toolbar">
                        <div class="category-page-toolbar__start">
                            <h2 class="category-page-toolbar__title">{{ translate('service_list') }}</h2>
                            <span class="category-page-toolbar__count" id="service-list-total-count">{{ $services->total() }}</span>
                        </div>

                        <div class="category-page-toolbar__tabs">
                            <ul class="nav nav--tabs">
                                <li class="nav-item">
                                    <a class="nav-link service-status-tab {{ $status == 'all' ? 'active' : '' }}"
                                       href="#"
                                       data-status="all">
                                        {{ translate('all') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link service-status-tab {{ $status == 'active' ? 'active' : '' }}"
                                       href="#"
                                       data-status="active">
                                        {{ translate('active') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link service-status-tab {{ $status == 'inactive' ? 'active' : '' }}"
                                       href="#"
                                       data-status="inactive">
                                        {{ translate('inactive') }}
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="category-page-toolbar__end">
                            <form action="{{ url()->current() }}"
                                  method="GET"
                                  id="service-list-toolbar-form"
                                  class="category-page-toolbar__controls">
                                <input type="hidden" name="status" value="{{ $status }}">

                                <div class="category-page-toolbar__filter">
                                    <label class="visually-hidden" for="service_list_category_select">{{ translate('category') }}</label>
                                    <select class="theme-input-style w-100 category-page-toolbar__filter-select"
                                            name="category_id"
                                            id="service_list_category_select">
                                        <option value="">{{ translate('all') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ ($category_id ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="category-page-toolbar__filter category-page-toolbar__filter--sub {{ empty($category_id) ? 'is-disabled' : '' }}"
                                     id="service_list_sub_category_wrap">
                                    <label class="visually-hidden" for="service_list_sub_category_select">{{ translate('sub_category') }}</label>
                                    <select class="subcategory-select theme-input-style w-100 category-page-toolbar__filter-select"
                                            name="sub_category_id"
                                            id="service_list_sub_category_select"
                                            @disabled(empty($category_id))>
                                        <option value="">{{ translate('all') }}</option>
                                        @foreach($subCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}"
                                                {{ ($sub_category_id ?? '') == $subCategory->id ? 'selected' : '' }}>
                                                {{ $subCategory->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @include('categorymanagement::admin.partials._catalog-toolbar-search', ['search' => $search])
                            </form>

                            <div class="category-page-toolbar__actions">
                                <button type="button"
                                        id="service-list-clear-filters-btn"
                                        class="btn btn--secondary btn-sm text-capitalize {{ ($category_id || $sub_category_id) ? '' : 'd-none' }}">
                                    {{ translate('Clear_all_Filter') }}
                                </button>

                                @can('service_add')
                                    <a href="{{ route('admin.service.create') }}"
                                       class="btn btn--primary btn-sm text-capitalize">
                                        <span class="material-icons">add</span>
                                        {{ translate('add_new') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card category-page-panel mb-0">
                        <div class="card-body">
                            <div id="service-list-results" data-turbo="false">
                                @include('servicemanagement::admin.partials._service-list-results', ['services' => $services])
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
        (function ($) {
        "use strict"

        const serviceListAllLabel = @json(translate('all'));
        const serviceListSubCategoryLabel = @json(translate('sub_category'));
        const serviceListTableUrl = @json(route('admin.service.table'));
        const serviceListIndexUrl = @json(url()->current());
        let serviceListXhr = null;
        let serviceListRequestId = 0;
        let serviceListIgnoreSubCategoryChange = false;
        let serviceListSearchTimer = null;

        function initServiceListFilterSelect($select, placeholder) {
            if (!$select.length) {
                return;
            }
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                placeholder: placeholder || serviceListAllLabel,
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                dropdownParent: $('body'),
            });
            $select.prop('disabled', $select.is(':disabled'));
        }

        function buildServiceListSubCategorySelectHtml(disabled) {
            return (
                '<label class="visually-hidden" for="service_list_sub_category_select">' + serviceListSubCategoryLabel + '</label>' +
                '<select class="subcategory-select theme-input-style w-100 category-page-toolbar__filter-select" name="sub_category_id" id="service_list_sub_category_select"' +
                (disabled ? ' disabled' : '') + '>' +
                '<option value="" selected>' + serviceListAllLabel + '</option>' +
                '</select>'
            );
        }

        function initServiceListSubCategorySelect() {
            initServiceListFilterSelect($('#service_list_sub_category_select'), serviceListAllLabel);
        }

        function initServiceListCategorySelect() {
            initServiceListFilterSelect($('#service_list_category_select'), serviceListAllLabel);
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
            const $select = $('#service_list_sub_category_select');
            if ($select.length && $select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $('#service_list_sub_category_wrap').html(buildServiceListSubCategorySelectHtml(disabled));
            if (!disabled) {
                initServiceListSubCategorySelect();
            }
            serviceListIgnoreSubCategoryChange = false;
        }

        function handleServiceListCategoryChange() {
            const categoryId = String($('#service_list_category_select').val() || '').trim();

            serviceListIgnoreSubCategoryChange = true;
            resetServiceListSubCategoryToAll(!categoryId);

            if (!categoryId) {
                setServiceListSubCategoryEnabled(false);
            } else {
                setServiceListSubCategoryEnabled(true);
                loadServiceListSubCategories(categoryId);
            }

            serviceListIgnoreSubCategoryChange = false;
            updateServiceListClearFiltersButton();
            submitServiceListToolbarForm(1);
        }

        function bindServiceListFilterHandlers() {
            $('#service_list_category_select')
                .off('change.serviceListFilter')
                .on('change.serviceListFilter', handleServiceListCategoryChange);

            $(document)
                .off('change.serviceListFilter', '#service_list_sub_category_select')
                .on('change.serviceListFilter', '#service_list_sub_category_select', function () {
                    if (serviceListIgnoreSubCategoryChange || $(this).is(':disabled')) {
                        return;
                    }
                    clearTimeout(window.__serviceListFilterSubmitTimer);
                    window.__serviceListFilterSubmitTimer = setTimeout(function () {
                        submitServiceListToolbarForm(1);
                    }, 150);
                });
        }

        function getServiceListFilterParams(page) {
            const $search = $('#catalog-toolbar-search-input');
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
            $('#service-list-clear-filters-btn').toggleClass('d-none', !(hasCategory || hasSubCategory));
        }

        function bindServiceListMetaPanels(root) {
            (root || document).querySelectorAll('.category-card__meta-view').forEach(function (btn) {
                if (btn.dataset.boundMetaView === '1') {
                    return;
                }
                btn.dataset.boundMetaView = '1';
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var wrap = btn.closest('.category-card__meta-view-wrap');
                    var panel = wrap ? wrap.querySelector('.category-card__meta-panel') : null;
                    if (!panel) {
                        return;
                    }
                    var isOpen = panel.classList.contains('is-open');
                    document.querySelectorAll('.category-card__meta-panel.is-open').forEach(function (openPanel) {
                        openPanel.classList.remove('is-open');
                    });
                    document.querySelectorAll('.category-card__meta-view.is-open').forEach(function (openBtn) {
                        openBtn.classList.remove('is-open');
                    });
                    if (!isOpen) {
                        panel.classList.add('is-open');
                        btn.classList.add('is-open');
                    }
                });
                var panel = btn.closest('.category-card__meta-view-wrap')?.querySelector('.category-card__meta-panel');
                if (panel && panel.dataset.boundMetaPanel !== '1') {
                    panel.dataset.boundMetaPanel = '1';
                    panel.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                }
            });
        }

        function initServiceListUi(root) {
            bindServiceListMetaPanels(root);
            if (typeof bootstrap !== 'undefined') {
                (root || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    bootstrap.Tooltip.getOrCreateInstance(el);
                });
            }
        }

        document.addEventListener('click', function () {
            document.querySelectorAll('.category-card__meta-panel.is-open').forEach(function (panel) {
                panel.classList.remove('is-open');
            });
            document.querySelectorAll('.category-card__meta-view.is-open').forEach(function (btn) {
                btn.classList.remove('is-open');
            });
        });

        function loadServiceListSubCategories(categoryId, selectedSubCategoryId) {
            if (!categoryId) {
                return;
            }

            $.get('{{ url('/') }}/admin/category/ajax-childes-only/' + categoryId, function (response) {
                const $wrap = $('#service_list_sub_category_wrap');
                const $ajaxSelect = $('<div>').html(response.template).find('select').first();

                serviceListIgnoreSubCategoryChange = true;
                const $existing = $('#service_list_sub_category_select');
                if ($existing.length && $existing.hasClass('select2-hidden-accessible')) {
                    $existing.select2('destroy');
                }
                $wrap.html(
                    '<label class="visually-hidden" for="service_list_sub_category_select">' + serviceListSubCategoryLabel + '</label>'
                );
                $wrap.append($ajaxSelect);

                const $select = $wrap.find('select');
                $select
                    .removeClass('js-select')
                    .addClass('subcategory-select theme-input-style w-100 category-page-toolbar__filter-select')
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
            initServiceListUi(document.getElementById('service-list-results'));

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
            const searchDebounceMs = 350;

            initServiceListCategorySelect();
            initServiceListSubCategorySelect();
            bindServiceListFilterHandlers();
            initServiceListUi(document.getElementById('service-list-results'));
            $('#service-list-results .pagination a').attr('data-turbo', 'false');

            const initialServiceCategoryId = @json($category_id ?? '');
            const initialServiceSubCategoryId = @json($sub_category_id ?? '');

            if (initialServiceCategoryId) {
                $('#service_list_category_select').val(initialServiceCategoryId);
                setServiceListSubCategoryEnabled(true);
            }
            if (initialServiceSubCategoryId) {
                $('#service_list_sub_category_select').val(initialServiceSubCategoryId);
            }
            $('#service_list_category_select').trigger('change.select2');
            $('#service_list_sub_category_select').trigger('change.select2');
            updateServiceListClearFiltersButton();
            updateServiceListBrowserUrl(getServiceListFilterParams({{ (int) request('page', 1) }}));

            $('#service-list-toolbar-form').on('submit', function (e) {
                e.preventDefault();
                submitServiceListToolbarForm(1);
            });

            $('#catalog-toolbar-search-input').off('input.catalogList keyup.catalogList keydown.catalogList');
            $('#catalog-toolbar-search-input').on('input.catalogList keyup.catalogList', function () {
                clearTimeout(serviceListSearchTimer);
                serviceListSearchTimer = setTimeout(function () {
                    submitServiceListToolbarForm(1);
                }, searchDebounceMs);
            });

            $('#catalog-toolbar-search-input').on('keydown.catalogList', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(serviceListSearchTimer);
                    submitServiceListToolbarForm(1);
                }
            });

            $('#service-list-clear-filters-btn').off('click.serviceListFilter').on('click.serviceListFilter', function () {
                $('#service_list_category_select').val('').trigger('change');
            });

            $(document).off('click.catalogList', '.category-page-toolbar__tabs .service-status-tab');
            $(document).on('click.catalogList', '.category-page-toolbar__tabs .service-status-tab', function (e) {
                e.preventDefault();
                const status = $(this).data('status') || 'all';
                $('#service-list-toolbar-form input[name="status"]').val(status);
                $('.category-page-toolbar__tabs .service-status-tab').removeClass('active');
                $(this).addClass('active');
                submitServiceListToolbarForm(1);
            });

            $(document).off('click.catalogList', '#service-list-results .category-card-pagination a, #service-list-results .service-list-pagination a');
            $(document).on('click.catalogList', '#service-list-results .category-card-pagination a, #service-list-results .service-list-pagination a', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const page = new URL(this.href, window.location.origin).searchParams.get('page') || 1;
                submitServiceListToolbarForm(page);
            });
        });

        })(jQuery);
    </script>
    @include('categorymanagement::admin.partials._catalog-list-scripts')
@endpush
