@extends('adminmodule::layouts.master')

@section('title',translate('sub_category_setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/select2/select2.min.css')}}"/>
    @include('categorymanagement::admin.partials._category-card-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @can('category_add')
                        <div id="sub-category-add-form-panel"
                             class="sub-category-add-form-panel mb-30 {{ $errors->any() ? '' : 'd-none' }}">
                        <div class="card category-setup mb-0">
                            <div class="card-body p-30">
                                <form action="{{route('admin.sub-category.store')}}" method="post"
                                      enctype="multipart/form-data"
                                      id="sub-category-form">
                                    @csrf
                                    @php($language= Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first())
                                    @php($default_lang = str_replace('_', '-', app()->getLocale()))
                                    @if($language)
                                        <ul class="nav nav--tabs border-color-primary mb-4">
                                            <li class="nav-item">
                                                <a class="nav-link lang_link active"
                                                   href="#"
                                                   id="default-link">{{translate('default')}}</a>
                                            </li>
                                            @foreach ($language?->live_values as $lang)
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link"
                                                       href="#"
                                                       id="{{ $lang['code'] }}-link">{{ get_language_name($lang['code']) }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <div class="row">
                                        <div class="col-lg-8 mb-5 mb-lg-0">
                                            <div class="d-flex flex-column">
                                                <select class="js-select theme-input-style w-100" name="parent_id" id="category_selector" required>
                                                    <option value="" selected
                                                            disabled>{{translate('Select_Category_Name')}}</option>
                                                    @foreach($mainCategories as $item)
                                                        <option value="{{$item['id']}}">{{$item->name}}</option>
                                                    @endforeach
                                                </select>

                                                @if($language)
                                                    <div class="lang-form" id="default-form">
                                                        <div class="form-floating form-floating__icon mb-30 mt-30">
                                                            <input type="text" name="name[]" class="form-control"
                                                                   placeholder="{{translate('sub_category_name')}}" value="{{old('name.0')}}"
                                                                   required>
                                                            <label>{{translate('sub_category_name')}}({{ translate('default') }})</label>
                                                            <span class="material-icons">subtitles</span>
                                                        </div>

                                                        <div class="form-floating mb-30">
                                                            <textarea type="text" name="short_description[]" class="form-control resize-none" required>{{ old('short_description.0') }}</textarea>
                                                            <label>{{translate('short_description')}}({{ translate('default') }})</label>
                                                        </div>
                                                    </div>

                                                    <input type="hidden" name="lang[]" value="default">
                                                    @foreach ($language?->live_values as $index => $lang)
                                                        <div class="lang-form d-none" id="{{ $lang['code'] }}-form">
                                                            <div class="form-floating form-floating__icon mb-30 mt-30">
                                                                <input type="text" name="name[]" class="form-control"
                                                                       placeholder="{{translate('sub_category_name')}}" value="{{ old('name.' . ($index + 1)) }}">
                                                                <label>{{translate('sub_category_name')}}({{ strtoupper($lang['code']) }})</label>
                                                                <span class="material-icons">subtitles</span>
                                                            </div>

                                                            <div class="form-floating mb-30">
                                                            <textarea type="text" name="short_description[]"
                                                                      class="form-control resize-none">{{ old('short_description.' . ($index + 1)) }}</textarea>
                                                                <label>{{translate('short_description')}}
                                                                    ({{ strtoupper($lang['code']) }})</label>
                                                            </div>
                                                            <input type="hidden" name="lang[]"
                                                                   value="{{$lang['code']}}">
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="form-floating mb-30 mt-30 lang-form">
                                                        <input type="text" name="name[]" class="form-control"
                                                               value="{{old('name.0')}}"
                                                               placeholder="{{translate('sub_category_name')}}"
                                                               required>
                                                        <label>{{translate('sub_category_name')}}
                                                            ({{ translate('default') }})</label>
                                                        <span class="material-icons">subtitles</span>
                                                    </div>

                                                    <div class="form-floating form-floating__icon mb-30">
                                                <textarea type="text" name="short_description[]"
                                                          class="form-control resize-none"
                                                          required></textarea>
                                                        <label>{{translate('short_description')}}
                                                            ({{ translate('default') }})</label>
                                                    </div>

                                                    <input type="hidden" name="lang[]" value="default">
                                                @endif
                                                @include('categorymanagement::admin.partials.category-tax-override', ['taxModel' => null])
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="d-flex gap-3 gap-xl-5">
                                                <p class="opacity-75 max-w220">
                                                    {{ translate('Image format')}} - {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                    {{ translate("Image Size") }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                    {{ translate('Image Ratio') }} - 1:1
                                                </p>
                                                <div class="d-flex flex-column align-items-center">
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input" name="image"
                                                               accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                               data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
                                                               required>
                                                        <div class="upload-file__img">
                                                            <img
                                                                src="{{asset('assets/admin-module')}}/img/media/upload-file.png"
                                                                alt="">
                                                        </div>
                                                        <span class="upload-file__edit">
                                                        <span class="material-icons">edit</span>
                                                    </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-end gap-20 mt-30 flex-wrap">
                                                <button class="btn btn--secondary" type="button"
                                                        id="sub-category-add-cancel">{{translate('cancel')}}</button>
                                                <button class="btn btn--secondary"
                                                        type="reset">{{translate('reset')}}</button>
                                                <button class="btn btn--primary" type="submit">{{translate('submit')}}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </div>
                    @endcan

                    <div class="category-page-toolbar">
                        <div class="category-page-toolbar__start">
                            <h2 class="category-page-toolbar__title">{{ translate('sub_category_setup') }}</h2>
                            <span class="category-page-toolbar__count" id="totalSubCategoryCount">{{ $subCategories->total() }}</span>
                        </div>

                        <div class="category-page-toolbar__tabs">
                            <ul class="nav nav--tabs">
                                <li class="nav-item">
                                    <a class="nav-link sub-category-status-tab {{ $status == 'all' ? 'active' : '' }}"
                                       href="#"
                                       data-status="all">
                                        {{ translate('all') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link sub-category-status-tab {{ $status == 'active' ? 'active' : '' }}"
                                       href="#"
                                       data-status="active">
                                        {{ translate('active') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link sub-category-status-tab {{ $status == 'inactive' ? 'active' : '' }}"
                                       href="#"
                                       data-status="inactive">
                                        {{ translate('inactive') }}
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="category-page-toolbar__end">
                            <div class="category-page-toolbar__controls">
                                <div class="category-page-toolbar__filter">
                                    <label class="visually-hidden" for="sub-category-parent-filter">{{ translate('category') }}</label>
                                    <select id="sub-category-parent-filter"
                                            class="theme-input-style w-100 category-page-toolbar__filter-select"
                                            aria-label="{{ translate('category') }}">
                                        <option value="">{{ translate('all') }}</option>
                                        @foreach($mainCategories as $item)
                                            <option value="{{ $item->id }}"
                                                {{ (string) ($parentId ?? '') === (string) $item->id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @include('categorymanagement::admin.partials._catalog-toolbar-search', ['search' => $search])
                            </div>

                            <div class="category-page-toolbar__actions">
                                @can('category_add')
                                    <button type="button"
                                            class="btn btn--primary btn-sm text-capitalize {{ $errors->any() ? 'd-none' : '' }}"
                                            id="btn-show-sub-category-add-form">
                                        <span class="material-icons">add</span>
                                        {{ translate('add_new') }}
                                    </button>
                                @endcan

                                @can('category_export')
                                    <div class="dropdown">
                                        <button type="button"
                                                class="btn btn--secondary btn-sm text-capitalize dropdown-toggle"
                                                data-bs-toggle="dropdown"
                                                title="{{ translate('download') }}">
                                            <span class="material-icons">file_download</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                   id="sub-category-download-link"
                                                   href="{{ route('admin.sub-category.download') }}?search={{ $search }}{{ filled($parentId ?? null) ? '&parent_id=' . urlencode($parentId) : '' }}">{{ translate('excel') }}</a>
                                            </li>
                                        </ul>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card category-page-panel mb-0">
                        <div class="card-body">
                            <div id="SubCategoryListTableContainer">
                                @include('categorymanagement::admin.partials._sub_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="offset" value="{{ request()->page }}">
@endsection

@push('script')
    <script src="{{asset('assets/admin-module/plugins/select2/select2.min.js')}}"></script>
    <script src="{{asset('assets/category-module/js/sub-category/create.js')}}"></script>

    <script>
        (function () {
            function bindSubCategoryAddFormToggle() {
                var panel = document.getElementById('sub-category-add-form-panel');
                var btnShow = document.getElementById('btn-show-sub-category-add-form');
                var btnCancel = document.getElementById('sub-category-add-cancel');
                var form = document.getElementById('sub-category-form');

                function ensureSelect2() {
                    if (!window.jQuery) return;
                    var $s = jQuery('#category_selector');
                    if ($s.length && !$s.data('select2')) {
                        $s.select2();
                    }
                }

                function showPanel() {
                    if (panel) panel.classList.remove('d-none');
                    if (btnShow) btnShow.classList.add('d-none');
                    ensureSelect2();
                }

                function hidePanel() {
                    if (panel) panel.classList.add('d-none');
                    if (btnShow) btnShow.classList.remove('d-none');
                    if (form) {
                        var resetBtn = form.querySelector('button[type="reset"]');
                        if (resetBtn) resetBtn.click();
                    }
                }

                if (btnShow) btnShow.addEventListener('click', showPanel);
                if (btnCancel) btnCancel.addEventListener('click', hidePanel);

                var params = new URLSearchParams(window.location.search);
                var parentId = params.get('parent_id');
                if (parentId) {
                    var sel = document.getElementById('category_selector');
                    if (sel) {
                        sel.value = parentId;
                        if (window.jQuery) {
                            jQuery(sel).val(parentId).trigger('change');
                        }
                    }
                }
                if (params.get('open_add') === '1') {
                    showPanel();
                }

                if (panel && !panel.classList.contains('d-none')) {
                    ensureSelect2();
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindSubCategoryAddFormToggle);
            } else {
                bindSubCategoryAddFormToggle();
            }
        })();
    </script>

    <script>
        (function ($) {
        "use strict"

        let currentStatus = "{{ request('status', 'all') }}";
        let subCategorySearchTimer = null;
        const subCategoryAllLabel = @json(translate('all'));

        function initSubCategoryParentFilter() {
            const $filter = $('#sub-category-parent-filter');
            if (!$filter.length) {
                return;
            }

            if ($filter.hasClass('select2-hidden-accessible')) {
                $filter.off('change.subCategoryFilter');
                $filter.select2('destroy');
            }

            $filter.select2({
                placeholder: subCategoryAllLabel,
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                dropdownParent: $('body')
            });

            $filter.on('change.subCategoryFilter', function () {
                reloadSubCategoryTable(currentStatus, 1);
                updateSubCategoryDownloadLink();
            });
        }

        function getSubCategoryParentId() {
            return $('#sub-category-parent-filter').val() || '';
        }

        function updateSubCategoryDownloadLink() {
            let search = $('#catalog-toolbar-search-input').val() || '';
            let parentId = getSubCategoryParentId();
            let href = "{{ route('admin.sub-category.download') }}?search=" + encodeURIComponent(search);
            if (parentId) {
                href += '&parent_id=' + encodeURIComponent(parentId);
            }
            $('#sub-category-download-link').attr('href', href);
        }

        initSubCategoryParentFilter();

        const initialSubCategoryParentId = @json($parentId ?? '');
        if (initialSubCategoryParentId) {
            $('#sub-category-parent-filter').val(initialSubCategoryParentId).trigger('change.select2');
        }

        updateSubCategoryBrowserUrl(
            currentStatus,
            $('#catalog-toolbar-search-input').val() || '',
            {{ (int) request('page', 1) }},
            getSubCategoryParentId()
        );

        function bindSubCategoryMetaPanels(root) {
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

        document.addEventListener('click', function () {
            document.querySelectorAll('.category-card__meta-panel.is-open').forEach(function (panel) {
                panel.classList.remove('is-open');
            });
            document.querySelectorAll('.category-card__meta-view.is-open').forEach(function (btn) {
                btn.classList.remove('is-open');
            });
        });

        function initSubCategoryListUi(root) {
            bindSubCategoryMetaPanels(root);
            if (typeof bootstrap !== 'undefined') {
                (root || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    bootstrap.Tooltip.getOrCreateInstance(el);
                });
            }
        }

        initSubCategoryListUi(document.getElementById('SubCategoryListTableContainer'));

        $(document).off('click.catalogList', '.category-page-toolbar__tabs .sub-category-status-tab');
        $(document).on('click.catalogList', '.category-page-toolbar__tabs .sub-category-status-tab', function (e) {
            e.preventDefault();
            currentStatus = $(this).data('status') || 'all';
            $('.category-page-toolbar__tabs .sub-category-status-tab').removeClass('active');
            $(this).addClass('active');
            reloadSubCategoryTable(currentStatus, 1);
        });

        $('#catalog-toolbar-search-input').off('input.catalogList keyup.catalogList keydown.catalogList');
        $('#catalog-toolbar-search-input').on('input.catalogList keyup.catalogList', function () {
            clearTimeout(subCategorySearchTimer);
            subCategorySearchTimer = setTimeout(function () {
                reloadSubCategoryTable(currentStatus, 1);
                updateSubCategoryDownloadLink();
            }, 350);
        });

        $('#catalog-toolbar-search-input').on('keydown.catalogList', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(subCategorySearchTimer);
                reloadSubCategoryTable(currentStatus, 1);
            }
        });

        $(document).on('click', '.sub-category-status-update', function (e) {
            e.preventDefault();
            let itemId = $(this).data('id');
            let route = '{{ route('admin.sub-category.status-update', ['id' => ':itemId']) }}'.replace(':itemId', itemId);
            route_alert(route, @json(translate('want_to_update_status')));
        });

        $('#sub-category-form button[type="reset"]').on('click', function () {
            $('#category_selector').val('').trigger('change');
        });

        function reloadSubCategoryTable(status, page) {
            let search = $('#catalog-toolbar-search-input').val() || '';
            let parentId = getSubCategoryParentId();
            let requestData = {
                status: status,
                search: search,
                page: page
            };
            if (parentId) {
                requestData.parent_id = parentId;
            }

            $.ajax({
                url: "{{ route('admin.sub-category.table') }}",
                type: "GET",
                data: requestData,
                success: function (response) {
                    if (response.page != page) {
                        updateSubCategoryBrowserUrl(status, search, response.page, parentId);
                        $('#offset').val((response.page - 1) * {{ pagination_limit() }});
                    } else {
                        $('#offset').val(response.offset);
                        updateSubCategoryBrowserUrl(status, search, page, parentId);
                    }

                    $('#totalSubCategoryCount').html(response.totalSubCategory);
                    $('#SubCategoryListTableContainer').empty().html(response.view);
                    initSubCategoryListUi(document.getElementById('SubCategoryListTableContainer'));
                },
                error: function () {
                    toastr.error('Failed to update table. Please reload the page.', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        }

        function updateSubCategoryBrowserUrl(status, search, page, parentId) {
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (status) params.set('status', status);
            if (parentId) params.set('parent_id', parentId);
            if (page > 1) params.set('page', page);

            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', newUrl);
        }

        $(document).off('click.catalogList', '#SubCategoryListTableContainer .category-card-pagination a');
        $(document).on('click.catalogList', '#SubCategoryListTableContainer .category-card-pagination a', function (e) {
            e.preventDefault();
            let href = $(this).attr('href');
            if (!href) return;
            let url = new URL(href, window.location.origin);
            let page = url.searchParams.get('page') || 1;
            reloadSubCategoryTable(currentStatus, page);
        });

        })(jQuery);
    </script>
    @include('categorymanagement::admin.partials._catalog-list-scripts')
@endpush
