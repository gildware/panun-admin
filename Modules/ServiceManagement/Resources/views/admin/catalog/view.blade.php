@extends('adminmodule::layouts.new-master')

@section('title', translate('View_Catalog'))

@push('css_or_js')
    <style>
        .catalog-columns {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
            min-height: 520px;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            overflow: hidden;
            background: var(--bs-body-bg);
        }
        @media (max-width: 1199.98px) {
            .catalog-columns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .catalog-column:nth-child(3),
            .catalog-column:nth-child(4) {
                border-top: 1px solid var(--bs-border-color);
            }
        }
        @media (max-width: 575.98px) {
            .catalog-columns {
                grid-template-columns: 1fr;
            }
            .catalog-column {
                border-right: 0 !important;
                border-bottom: 1px solid var(--bs-border-color);
            }
            .catalog-column:last-child {
                border-bottom: 0;
            }
        }
        .catalog-column {
            display: flex;
            flex-direction: column;
            min-width: 0;
            border-right: 1px solid var(--bs-border-color);
        }
        .catalog-column:last-child {
            border-right: 0;
        }
        .catalog-column-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg);
            flex-shrink: 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .catalog-column-header-main {
            min-width: 0;
            flex: 1;
        }
        .catalog-column-header h6 {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--bs-secondary-color);
        }
        .catalog-col-count {
            flex-shrink: 0;
            min-width: 1.75rem;
            height: 1.75rem;
            padding: 0 0.5rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .catalog-column-header .col-subtitle {
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
            margin-top: 0.125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .catalog-column-body {
            flex: 1;
            overflow-y: auto;
            max-height: calc(100vh - 20rem);
            padding: 0.375rem;
        }
        .catalog-col-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            color: inherit;
            transition: background-color .15s ease;
            cursor: pointer;
        }
        .catalog-col-item:hover {
            background: rgba(var(--bs-primary-rgb), 0.06);
        }
        .catalog-col-item.is-selected {
            background: transparent;
            box-shadow: none;
        }
        .catalog-col-row.is-selected {
            background: rgba(var(--bs-primary-rgb), 0.1);
            box-shadow: inset 3px 0 0 var(--bs-primary);
        }
        .catalog-col-thumb {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.375rem;
            object-fit: cover;
            flex-shrink: 0;
            background: var(--bs-tertiary-bg);
        }
        .catalog-col-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.375rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.125rem;
        }
        .catalog-col-icon--category { background: rgba(var(--bs-primary-rgb), .12); color: var(--bs-primary); }
        .catalog-col-icon--subcategory { background: rgba(var(--bs-info-rgb), .12); color: var(--bs-info); }
        .catalog-col-icon--service { background: rgba(var(--bs-success-rgb), .12); color: var(--bs-success); }
        .catalog-col-icon--variation { background: rgba(var(--bs-warning-rgb), .12); color: var(--bs-warning); }
        .catalog-col-label {
            min-width: 0;
            flex: 1;
        }
        .catalog-col-name {
            font-size: 0.875rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .catalog-col-name-link {
            color: inherit;
            text-decoration: none;
        }
        .catalog-col-name-link:hover {
            color: var(--bs-primary);
            text-decoration: underline;
        }
        .catalog-col-meta {
            font-size: 0.6875rem;
            color: var(--bs-secondary-color);
            margin-top: 0.125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .catalog-col-desc {
            font-size: 0.6875rem;
            color: var(--bs-secondary-color);
            margin-top: 0.125rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
        }
        .catalog-empty-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem 1rem;
            color: var(--bs-secondary-color);
            height: 100%;
            min-height: 12rem;
        }
        .catalog-empty-col .material-icons {
            font-size: 2rem;
            opacity: 0.35;
            margin-bottom: 0.5rem;
        }
        .catalog-empty-col p {
            font-size: 0.8125rem;
            margin: 0;
        }
        .catalog-search-wrap {
            position: relative;
        }
        .catalog-search-wrap .material-icons {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.125rem;
            color: var(--bs-secondary-color);
            pointer-events: none;
        }
        .catalog-search-wrap input {
            padding-left: 2.25rem;
        }
        .catalog-col-item.is-hidden,
        .catalog-col-row.is-hidden {
            display: none;
        }
        .catalog-col-row {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            border-radius: 0.5rem;
        }
        .catalog-col-row .catalog-col-item {
            flex: 1;
            min-width: 0;
        }
        .catalog-col-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 0.25rem;
            flex-shrink: 0;
        }
        .catalog-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.4375rem;
            border-radius: 999px;
            font-size: 0.625rem;
            font-weight: 600;
            line-height: 1.2;
            text-transform: capitalize;
            white-space: nowrap;
        }
        .catalog-status-pill--active {
            background: rgba(var(--bs-success-rgb), 0.12);
            color: var(--bs-success);
        }
        .catalog-status-pill--inactive {
            background: rgba(var(--bs-secondary-rgb), 0.12);
            color: var(--bs-secondary-color);
        }
        .catalog-col-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.625rem;
            height: 1.625rem;
            border-radius: 0.375rem;
            color: var(--bs-secondary-color);
            text-decoration: none;
            transition: color .15s ease, background-color .15s ease;
        }
        .catalog-col-edit:hover {
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.08);
        }
        .catalog-col-edit .material-icons {
            font-size: 1rem;
        }
        .catalog-col-drag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            color: var(--bs-secondary-color);
            cursor: grab;
            user-select: none;
            flex-shrink: 0;
            border-radius: 0.375rem;
        }
        .catalog-col-drag:active { cursor: grabbing; }
        .catalog-col-drag .material-icons { font-size: 1.125rem; }
        .catalog-col-row.is-dragging { opacity: 0.5; }
        .catalog-col-row.is-drag-over {
            outline: 2px dashed var(--bs-primary);
            outline-offset: 1px;
        }
        .catalog-column-header-tools {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            flex-shrink: 0;
        }
        .catalog-col-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.375rem;
            color: var(--bs-primary);
            text-decoration: none;
            transition: color .15s ease, background-color .15s ease;
        }
        .catalog-col-add:hover {
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
        }
        .catalog-col-add.is-disabled {
            color: var(--bs-secondary-color);
            opacity: 0.45;
            pointer-events: none;
            cursor: default;
        }
        .catalog-col-add .material-icons {
            font-size: 1.125rem;
        }
        .catalog-page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .catalog-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
            flex: 1;
            justify-content: flex-end;
            min-width: 0;
        }
        .catalog-toolbar-form {
            display: flex;
            align-items: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .catalog-toolbar-field {
            min-width: 0;
        }
        .catalog-toolbar-field label {
            display: block;
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
            color: var(--bs-secondary-color);
        }
        .catalog-toolbar-field--zone {
            min-width: 14rem;
            max-width: 20rem;
        }
        .catalog-toolbar-field--zone .select2-container {
            min-width: 14rem;
        }
        .catalog-toolbar-search {
            flex: 1;
            min-width: 12rem;
            max-width: 20rem;
        }
        .catalog-zone-prompt .material-icons {
            font-size: 2.5rem;
            color: var(--bs-secondary-color);
            margin-bottom: 0.5rem;
        }
    </style>
@endpush

@include('zonemanagement::admin.partials._zone-select2-assets')

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="catalog-page-header mb-3">
                <h2 class="page-title mb-0">{{ translate('View_Catalog') }}</h2>
                <div class="catalog-toolbar">
                    <form method="get" action="{{ route('admin.catalog.view') }}" class="catalog-toolbar-form" id="catalog-zone-form">
                        <div class="catalog-toolbar-field catalog-toolbar-field--zone">
                            <label class="mb-0">{{ translate('zone') }} <span class="text-danger">*</span></label>
                            <select name="zone_id" id="catalog-zone-select" class="form-select theme-input-style zone-tree-select" required>
                                <option value="" @selected(!filled($zoneId))>{{ translate('Select_zone') }}</option>
                                @include('zonemanagement::admin.partials._zone-select-options', [
                                    'zoneTreeOptions' => $zoneTreeOptions,
                                    'selected' => $zoneId,
                                ])
                            </select>
                        </div>
                        <div class="catalog-toolbar-field">
                            <label class="mb-0">{{ translate('status') }}</label>
                            <select name="status" class="form-select theme-input-style" id="catalog-status-filter">
                                <option value="all" @selected($status === 'all')>{{ translate('all') }}</option>
                                <option value="active" @selected($status === 'active')>{{ translate('active') }}</option>
                                <option value="inactive" @selected($status === 'inactive')>{{ translate('inactive') }}</option>
                            </select>
                        </div>
                    </form>
                    <div class="catalog-toolbar-field catalog-toolbar-search">
                        <label class="mb-0">{{ translate('search') }}</label>
                        <div class="catalog-search-wrap">
                            <span class="material-icons">search</span>
                            <input type="text" class="form-control" id="catalog-search" placeholder="{{ translate('Catalog_search_placeholder') }}" autocomplete="off" @disabled(!filled($zoneId))>
                        </div>
                    </div>
                </div>
            </div>

            @if(filled($zoneId))
            <div class="catalog-columns">
                {{-- Column 1: Categories --}}
                <div class="catalog-column" data-col="category">
                    <div class="catalog-column-header">
                        <div class="catalog-column-header-main">
                            <h6>{{ translate('categories') }}</h6>
                        </div>
                        <div class="catalog-column-header-tools">
                            @if($canCategoryAdd)
                                <a href="{{ route('admin.category.create', ['open_add' => 1]) }}"
                                   class="catalog-col-add"
                                   id="add-category"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="{{ translate('add_new') }} {{ translate('categories') }}">
                                    <span class="material-icons">add</span>
                                </a>
                            @endif
                            <span class="catalog-col-count" id="count-categories">{{ $stats['categories'] }}</span>
                        </div>
                    </div>
                    <div class="catalog-column-body" id="col-categories" data-column-type="category"></div>
                </div>

                {{-- Column 2: Sub-categories --}}
                <div class="catalog-column" data-col="subcategory">
                    <div class="catalog-column-header">
                        <div class="catalog-column-header-main">
                            <h6>{{ translate('sub_categories') }}</h6>
                            <div class="col-subtitle" id="col-subcategory-subtitle"></div>
                        </div>
                        <div class="catalog-column-header-tools">
                            @if($canCategoryAdd)
                                <a href="#"
                                   class="catalog-col-add is-disabled"
                                   id="add-subcategory"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="{{ translate('Catalog_pick_category') }}">
                                    <span class="material-icons">add</span>
                                </a>
                            @endif
                            <span class="catalog-col-count" id="count-subcategories">0</span>
                        </div>
                    </div>
                    <div class="catalog-column-body" id="col-subcategories" data-column-type="subcategory">
                        <div class="catalog-empty-col">
                            <span class="material-icons">touch_app</span>
                            <p>{{ translate('Catalog_pick_category') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Column 3: Services --}}
                <div class="catalog-column" data-col="service">
                    <div class="catalog-column-header">
                        <div class="catalog-column-header-main">
                            <h6>{{ translate('services') }}</h6>
                            <div class="col-subtitle" id="col-service-subtitle"></div>
                        </div>
                        <div class="catalog-column-header-tools">
                            @if($canServiceAdd)
                                <a href="#"
                                   class="catalog-col-add is-disabled"
                                   id="add-service"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="{{ translate('Catalog_pick_subcategory') }}">
                                    <span class="material-icons">add</span>
                                </a>
                            @endif
                            <span class="catalog-col-count" id="count-services">0</span>
                        </div>
                    </div>
                    <div class="catalog-column-body" id="col-services" data-column-type="service">
                        <div class="catalog-empty-col">
                            <span class="material-icons">touch_app</span>
                            <p>{{ translate('Catalog_pick_subcategory') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Column 4: Variations --}}
                <div class="catalog-column" data-col="variation">
                    <div class="catalog-column-header">
                        <div class="catalog-column-header-main">
                            <h6>{{ translate('price_variation') }}</h6>
                            <div class="col-subtitle" id="col-variation-subtitle"></div>
                        </div>
                        <div class="catalog-column-header-tools">
                            @if($canServiceUpdate)
                                <a href="#"
                                   class="catalog-col-add is-disabled"
                                   id="add-variation"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="{{ translate('Catalog_pick_service') }}">
                                    <span class="material-icons">add</span>
                                </a>
                            @endif
                            <span class="catalog-col-count" id="count-variations">0</span>
                        </div>
                    </div>
                    <div class="catalog-column-body" id="col-variations" data-column-type="variation">
                        <div class="catalog-empty-col">
                            <span class="material-icons">touch_app</span>
                            <p>{{ translate('Catalog_pick_service') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 catalog-zone-prompt">
                    <span class="material-icons d-block">map</span>
                    <p class="mb-0 text-muted">{{ translate('Select_zone') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const zoneForm = document.getElementById('catalog-zone-form');
            const catalogViewUrl = @json(route('admin.catalog.view'));

            function submitCatalogZoneForm() {
                if (!zoneForm) {
                    return;
                }
                const zoneSelect = zoneForm.querySelector('[name="zone_id"]');
                const zoneId = zoneSelect ? zoneSelect.value : '';
                if (!zoneId) {
                    return;
                }
                const params = new URLSearchParams(new FormData(zoneForm));
                window.location.href = catalogViewUrl + '?' + params.toString();
            }

            if (zoneForm) {
                zoneForm.addEventListener('change', function (e) {
                    if (e.target.name === 'status' && zoneForm.querySelector('[name="zone_id"]')?.value) {
                        submitCatalogZoneForm();
                    }
                });
            }

            function bindCatalogZoneSelectChange() {
                if (typeof jQuery === 'undefined') {
                    return;
                }
                jQuery(document)
                    .off('change.catalogZone select2:select.catalogZone', '#catalog-zone-select')
                    .on('change.catalogZone select2:select.catalogZone', '#catalog-zone-select', function () {
                        if (this.value) {
                            submitCatalogZoneForm();
                        }
                    });
            }

            function initCatalogZoneTreeSelect() {
                if (typeof jQuery === 'undefined' || typeof initZoneTreeSelect2 !== 'function') {
                    bindCatalogZoneSelectChange();
                    return;
                }
                const $zoneSelect = jQuery('#catalog-zone-select');
                if (!$zoneSelect.length) {
                    return;
                }
                if ($zoneSelect.hasClass('select2-hidden-accessible')) {
                    try {
                        $zoneSelect.select2('destroy');
                    } catch (e) {
                        $zoneSelect.removeClass('select2-hidden-accessible');
                        $zoneSelect.removeAttr('aria-hidden');
                        $zoneSelect.removeAttr('tabindex');
                        $zoneSelect.next('.select2').remove();
                    }
                }
                initZoneTreeSelect2($zoneSelect, { width: '100%', hideDescription: true });
                bindCatalogZoneSelectChange();
            }

            if (document.readyState === 'complete') {
                initCatalogZoneTreeSelect();
            } else {
                window.addEventListener('load', initCatalogZoneTreeSelect);
            }

            const zoneSelected = @json(filled($zoneId));
            if (!zoneSelected) {
                return;
            }

            const catalogTree = @json($tree);
            const labels = {
                active: @json(translate('active')),
                inactive: @json(translate('inactive')),
                subCategories: @json(translate('sub_categories')),
                services: @json(translate('services')),
                variations: @json(translate('price_variation')),
                zones: @json(translate('zone')),
                noVariations: @json(translate('Catalog_no_variations')),
                fromPrice: @json(translate('Catalog_from_price')),
                variation: @json(translate('Catalog_variation')),
                price: @json(translate('price')),
                pickCategory: @json(translate('Catalog_pick_category')),
                pickSubcategory: @json(translate('Catalog_pick_subcategory')),
                pickService: @json(translate('Catalog_pick_service')),
                noData: @json(translate('no_data_found')),
                edit: @json(translate('edit')),
                viewDetails: @json(translate('View_Details')),
                dragToReorder: @json(translate('Drag_to_reorder')),
            };
            const canReorderCategories = @json(\Illuminate\Support\Facades\Gate::allows('category_update'));
            const canReorderServices = @json(\Illuminate\Support\Facades\Gate::allows('service_update'));
            const reorderUrls = {
                categories: @json(route('admin.catalog.reorder.categories')),
                subcategories: @json(route('admin.catalog.reorder.subcategories')),
                services: @json(route('admin.catalog.reorder.services')),
                variations: @json(route('admin.catalog.reorder.variations')),
            };
            const csrfToken = @json(csrf_token());
            const currencySymbol = @json($currencySymbol);
            const currencyPosition = @json($currencyPosition);
            const currencyDecimalPoint = {{ (int) $currencyDecimalPoint }};
            const placeholderImage = @json(asset('assets/admin-module/img/media/upload-file.png'));

            const usePartialNav = @json(admin_uses_partial_nav());
            const partialNavAttrs = usePartialNav
                ? ' data-turbo-frame="admin-main" data-turbo-action="advance"'
                : '';

            const colCategories = document.getElementById('col-categories');
            const colSubcategories = document.getElementById('col-subcategories');
            const colServices = document.getElementById('col-services');
            const colVariations = document.getElementById('col-variations');
            const subcategorySubtitle = document.getElementById('col-subcategory-subtitle');
            const serviceSubtitle = document.getElementById('col-service-subtitle');
            const variationSubtitle = document.getElementById('col-variation-subtitle');
            const searchInput = document.getElementById('catalog-search');
            const addSubcategoryBtn = document.getElementById('add-subcategory');
            const addServiceBtn = document.getElementById('add-service');
            const addVariationBtn = document.getElementById('add-variation');
            const createRoutes = {
                subCategory: @json(route('admin.sub-category.create')),
                service: @json(route('admin.service.create')),
                variation: @json(url('/admin/service')),
            };

            function setColumnCount(elementId, value) {
                const el = document.getElementById(elementId);
                if (el) {
                    el.textContent = value;
                }
            }

            function setAddLink(button, url, enabledTitle) {
                if (!button) {
                    return;
                }
                if (!url) {
                    button.href = '#';
                    button.classList.add('is-disabled');
                    return;
                }
                button.href = url;
                button.classList.remove('is-disabled');
                if (enabledTitle) {
                    button.title = enabledTitle;
                }
            }

            function updateAddLinks() {
                if (addSubcategoryBtn) {
                    const url = selectedCategoryId
                        ? createRoutes.subCategory + '?open_add=1&parent_id=' + encodeURIComponent(selectedCategoryId)
                        : null;
                    setAddLink(
                        addSubcategoryBtn,
                        url,
                        @json(translate('add_new')) + ' ' + @json(translate('sub_categories'))
                    );
                }

                if (addServiceBtn) {
                    let url = null;
                    if (selectedSubcategoryId) {
                        const entry = subcategoryMap.get(selectedSubcategoryId);
                        if (entry) {
                            const params = new URLSearchParams({ open_add: '1', category_id: entry.parentId });
                            if (!entry.node.synthetic) {
                                params.set('sub_category_id', selectedSubcategoryId);
                            }
                            url = createRoutes.service + '?' + params.toString();
                        }
                    }
                    setAddLink(
                        addServiceBtn,
                        url,
                        @json(translate('add_new')) + ' ' + @json(translate('services'))
                    );
                }

                if (addVariationBtn) {
                    const url = selectedServiceId
                        ? createRoutes.variation + '/' + encodeURIComponent(selectedServiceId) + '/variants/create'
                        : null;
                    setAddLink(
                        addVariationBtn,
                        url,
                        @json(translate('add_new')) + ' ' + @json(translate('price_variation'))
                    );
                }
            }

            let selectedCategoryId = null;
            let selectedSubcategoryId = null;
            let selectedServiceId = null;

            const categoryMap = new Map();
            const subcategoryMap = new Map();
            const serviceMap = new Map();

            catalogTree.forEach(function (cat) {
                categoryMap.set(cat.id, cat);
                (cat.children || []).forEach(function (sub) {
                    subcategoryMap.set(sub.id, { node: sub, parentId: cat.id });
                    (sub.children || []).forEach(function (svc) {
                        serviceMap.set(svc.id, { node: svc, parentId: sub.id });
                    });
                });
            });

            function escapeHtml(str) {
                if (str == null) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function formatPrice(value) {
                if (value == null || value === '') return '—';
                const num = Number(value);
                if (Number.isNaN(num)) return escapeHtml(value);
                const formatted = num.toLocaleString(undefined, {
                    minimumFractionDigits: currencyDecimalPoint,
                    maximumFractionDigits: currencyDecimalPoint,
                });
                return escapeHtml(currencyPosition === 'left' ? currencySymbol + formatted : formatted + currencySymbol);
            }

            function thumbOrIcon(node, type) {
                const icons = { category: 'category', subcategory: 'folder', service: 'design_services', variation: 'tune' };
                const image = node.image || null;
                if (image) {
                    return '<img src="' + escapeHtml(image) + '" alt="" class="catalog-col-thumb" onerror="this.outerHTML=\'<span class=\\\'catalog-col-icon catalog-col-icon--' + type + '\\\'><span class=\\\'material-icons\\\'>' + icons[type] + '</span></span>\'">';
                }
                return '<span class="catalog-col-icon catalog-col-icon--' + type + '"><span class="material-icons">' + (icons[type] || 'circle') + '</span></span>';
            }

            function emptyCol(message) {
                return '<div class="catalog-empty-col"><span class="material-icons">inbox</span><p>' + escapeHtml(message) + '</p></div>';
            }

            function statusPill(isActive) {
                if (isActive === undefined || isActive === null) {
                    return '';
                }
                const active = !!isActive;
                const text = active ? labels.active : labels.inactive;
                const modifier = active ? 'active' : 'inactive';
                return '<span class="catalog-status-pill catalog-status-pill--' + modifier + '">' + escapeHtml(text) + '</span>';
            }

            function editLink(url) {
                if (!url) {
                    return '';
                }
                return '<a href="' + escapeHtml(url) + '" class="catalog-col-edit"' + partialNavAttrs + ' aria-label="' + escapeHtml(labels.edit) + '" title="' + escapeHtml(labels.edit) + '"><span class="material-icons">edit</span></a>';
            }

            function canDragType(type) {
                if (type === 'category' || type === 'subcategory') {
                    return canReorderCategories;
                }
                if (type === 'service' || type === 'variation') {
                    return canReorderServices;
                }
                return false;
            }

            function dragHandleHtml(type) {
                if (!canDragType(type)) {
                    return '';
                }
                return '<span class="catalog-col-drag" draggable="true" title="' + escapeHtml(labels.dragToReorder) + '" aria-label="' + escapeHtml(labels.dragToReorder) + '"><span class="material-icons">drag_indicator</span></span>';
            }

            function listItemHtml(node, type, id, meta, options) {
                options = options || {};
                const name = node.name || node.label || '';
                const editUrl = options.editUrl || node.edit_url || '';
                const detailUrl = options.detailUrl || node.detail_url || '';
                const isActive = options.isActive !== undefined ? options.isActive : node.is_active;
                const isSynthetic = !!node.synthetic;
                const reorderable = options.reorderable !== undefined
                    ? !!options.reorderable
                    : (!isSynthetic && (type !== 'variation' || node.reorderable !== false));
                const searchParts = [name, meta, options.description, options.searchExtra].filter(Boolean);
                const useDivItem = type === 'service' && !!detailUrl;
                const itemTag = useDivItem ? 'div' : 'button';
                const itemTypeAttr = useDivItem ? '' : ' type="button"';
                let html = '<div class="catalog-col-row" data-type="' + escapeHtml(type) + '" data-id="' + escapeHtml(id) + '" data-search="' + escapeHtml(searchParts.join(' ').toLowerCase()) + '"'
                    + (isSynthetic ? ' data-synthetic="1"' : '')
                    + (reorderable ? ' data-reorderable="1"' : '')
                    + '>';
                if (reorderable && canDragType(type)) {
                    html += dragHandleHtml(type);
                } else {
                    html += '<span style="width:1.5rem;flex-shrink:0" aria-hidden="true"></span>';
                }
                html += '<' + itemTag + itemTypeAttr + ' class="catalog-col-item" data-type="' + escapeHtml(type) + '" data-id="' + escapeHtml(id) + '">';
                html += thumbOrIcon(node, type);
                html += '<span class="catalog-col-label">';
                if (detailUrl && type === 'service') {
                    html += '<div class="catalog-col-name"><a href="' + escapeHtml(detailUrl) + '" class="catalog-col-name-link"' + partialNavAttrs + ' title="' + escapeHtml(labels.viewDetails) + '">' + escapeHtml(name) + '</a></div>';
                } else {
                    html += '<div class="catalog-col-name">' + escapeHtml(name) + '</div>';
                }
                if (meta) html += '<div class="catalog-col-meta">' + meta + '</div>';
                if (options.description) {
                    html += '<div class="catalog-col-desc">' + escapeHtml(options.description) + '</div>';
                }
                html += '</span>';
                html += '</' + itemTag + '>';
                html += '<div class="catalog-col-actions">';
                html += statusPill(isActive);
                html += editLink(editUrl);
                html += '</div>';
                html += '</div>';
                return html;
            }

            let catalogDragItem = null;
            let catalogReorderSaving = false;

            function collectColumnOrder(container) {
                return Array.from(container.querySelectorAll('.catalog-col-row[data-reorderable="1"]'))
                    .map(function (row) {
                        return row.getAttribute('data-id');
                    })
                    .filter(Boolean);
            }

            function reorderArrayByIds(items, orderedIds, getId) {
                const byId = new Map();
                items.forEach(function (item) {
                    byId.set(String(getId(item)), item);
                });
                const next = [];
                orderedIds.forEach(function (id) {
                    const item = byId.get(String(id));
                    if (item) {
                        next.push(item);
                        byId.delete(String(id));
                    }
                });
                byId.forEach(function (item) {
                    next.push(item);
                });
                return next;
            }

            function applyLocalOrder(type, orderedIds) {
                if (type === 'category') {
                    catalogTree.splice(0, catalogTree.length, ...reorderArrayByIds(catalogTree, orderedIds, function (c) {
                        return c.id;
                    }));
                    return;
                }
                if (type === 'subcategory' && selectedCategoryId) {
                    const cat = categoryMap.get(selectedCategoryId);
                    if (!cat || !Array.isArray(cat.children)) {
                        return;
                    }
                    const reorderable = cat.children.filter(function (c) {
                        return !c.synthetic;
                    });
                    const synthetic = cat.children.filter(function (c) {
                        return !!c.synthetic;
                    });
                    cat.children = reorderArrayByIds(reorderable, orderedIds, function (c) {
                        return c.id;
                    }).concat(synthetic);
                    return;
                }
                if (type === 'service' && selectedSubcategoryId) {
                    const entry = subcategoryMap.get(selectedSubcategoryId);
                    if (!entry || !entry.node || !Array.isArray(entry.node.children)) {
                        return;
                    }
                    entry.node.children = reorderArrayByIds(entry.node.children, orderedIds, function (s) {
                        return s.id;
                    });
                    return;
                }
                if (type === 'variation' && selectedServiceId) {
                    const entry = serviceMap.get(selectedServiceId);
                    if (!entry || !entry.node || !Array.isArray(entry.node.children)) {
                        return;
                    }
                    entry.node.children = reorderArrayByIds(entry.node.children, orderedIds, function (v) {
                        return v.id;
                    });
                }
            }

            function buildReorderPayload(type, orderedIds) {
                if (type === 'category') {
                    return { url: reorderUrls.categories, data: { order: orderedIds } };
                }
                if (type === 'subcategory') {
                    return {
                        url: reorderUrls.subcategories,
                        data: { parent_id: selectedCategoryId, order: orderedIds },
                    };
                }
                if (type === 'service') {
                    const entry = subcategoryMap.get(selectedSubcategoryId);
                    if (!entry) {
                        return null;
                    }
                    const data = { order: orderedIds };
                    if (entry.node && entry.node.synthetic) {
                        data.category_id = entry.parentId;
                    } else {
                        data.sub_category_id = selectedSubcategoryId;
                    }
                    return { url: reorderUrls.services, data: data };
                }
                if (type === 'variation') {
                    return {
                        url: reorderUrls.variations,
                        data: { service_id: selectedServiceId, order: orderedIds },
                    };
                }
                return null;
            }

            function saveColumnOrder(container) {
                const type = container.getAttribute('data-column-type');
                const orderedIds = collectColumnOrder(container);
                if (!type || orderedIds.length < 1 || catalogReorderSaving) {
                    return;
                }
                const payload = buildReorderPayload(type, orderedIds);
                if (!payload || !payload.url) {
                    return;
                }

                catalogReorderSaving = true;
                fetch(payload.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload.data),
                })
                    .then(function (res) {
                        return res.json().then(function (body) {
                            return { ok: res.ok, body: body };
                        });
                    })
                    .then(function (result) {
                        if (result.ok && result.body && Number(result.body.flag) === 1) {
                            applyLocalOrder(type, orderedIds);
                            if (window.toastr) {
                                toastr.success(@json(translate('successfully_updated')));
                            }
                            return;
                        }
                        if (window.toastr) {
                            toastr.error(@json(translate('something_went_wrong')));
                        }
                    })
                    .catch(function () {
                        if (window.toastr) {
                            toastr.error(@json(translate('something_went_wrong')));
                        }
                    })
                    .finally(function () {
                        catalogReorderSaving = false;
                    });
            }

            function initColumnSortable(container) {
                if (!container) {
                    return;
                }

                container.querySelectorAll('.catalog-col-drag').forEach(function (handle) {
                    if (handle.dataset.catalogDragInit === '1') {
                        return;
                    }
                    handle.dataset.catalogDragInit = '1';

                    handle.addEventListener('dragstart', function (e) {
                        catalogDragItem = handle.closest('.catalog-col-row[data-reorderable="1"]');
                        if (!catalogDragItem) {
                            return;
                        }
                        catalogDragItem.classList.add('is-dragging');
                        e.dataTransfer.effectAllowed = 'move';
                        try {
                            e.dataTransfer.setData('text/plain', catalogDragItem.getAttribute('data-id') || '');
                        } catch (err) {}
                        e.stopPropagation();
                    });

                    handle.addEventListener('dragend', function () {
                        if (catalogDragItem) {
                            catalogDragItem.classList.remove('is-dragging');
                        }
                        container.querySelectorAll('.catalog-col-row.is-drag-over').forEach(function (el) {
                            el.classList.remove('is-drag-over');
                        });
                        catalogDragItem = null;
                        saveColumnOrder(container);
                    });

                    handle.addEventListener('mousedown', function (e) {
                        e.stopPropagation();
                    });
                    handle.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });

                if (container.dataset.catalogListDragInit === '1') {
                    return;
                }
                container.dataset.catalogListDragInit = '1';

                container.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    const target = e.target.closest('.catalog-col-row[data-reorderable="1"]');
                    if (!catalogDragItem || !target || target === catalogDragItem || !container.contains(target)) {
                        return;
                    }
                    if (catalogDragItem.parentElement !== container || target.parentElement !== container) {
                        return;
                    }

                    container.querySelectorAll('.catalog-col-row.is-drag-over').forEach(function (el) {
                        if (el !== target) {
                            el.classList.remove('is-drag-over');
                        }
                    });
                    target.classList.add('is-drag-over');

                    const rect = target.getBoundingClientRect();
                    const before = (e.clientY - rect.top) < (rect.height / 2);
                    if (before) {
                        container.insertBefore(catalogDragItem, target);
                    } else {
                        container.insertBefore(catalogDragItem, target.nextSibling);
                    }
                });

                container.addEventListener('drop', function (e) {
                    e.preventDefault();
                });

                container.addEventListener('dragleave', function (e) {
                    const related = e.relatedTarget;
                    if (related && container.contains(related)) {
                        return;
                    }
                    container.querySelectorAll('.catalog-col-row.is-drag-over').forEach(function (el) {
                        el.classList.remove('is-drag-over');
                    });
                });
            }

            function renderCategories() {
                if (!catalogTree.length) {
                    colCategories.innerHTML = emptyCol(labels.noData);
                    setColumnCount('count-categories', 0);
                    return;
                }
                let html = '';
                catalogTree.forEach(function (cat) {
                    const meta = (cat.sub_count || 0) + ' ' + labels.subCategories.toLowerCase();
                    html += listItemHtml(cat, 'category', cat.id, meta);
                });
                colCategories.innerHTML = html;
                setColumnCount('count-categories', catalogTree.length);
                initColumnSortable(colCategories);
            }

            function renderSubcategories(categoryId) {
                const cat = categoryMap.get(categoryId);
                if (!cat) {
                    colSubcategories.innerHTML = emptyCol(labels.noData);
                    setColumnCount('count-subcategories', 0);
                    return;
                }
                subcategorySubtitle.textContent = cat.name;
                const subs = cat.children || [];
                if (!subs.length) {
                    colSubcategories.innerHTML = emptyCol(labels.noData);
                    setColumnCount('count-subcategories', 0);
                    return;
                }
                let html = '';
                subs.forEach(function (sub) {
                    const meta = (sub.service_count || 0) + ' ' + labels.services.toLowerCase();
                    html += listItemHtml(sub, 'subcategory', sub.id, meta);
                });
                colSubcategories.innerHTML = html;
                setColumnCount('count-subcategories', subs.length);
                setColumnCount('count-services', 0);
                setColumnCount('count-variations', 0);
                updateAddLinks();
                initColumnSortable(colSubcategories);
            }

            function renderServices(subcategoryId) {
                const entry = subcategoryMap.get(subcategoryId);
                if (!entry) {
                    colServices.innerHTML = emptyCol(labels.noData);
                    setColumnCount('count-services', 0);
                    return;
                }
                const sub = entry.node;
                serviceSubtitle.textContent = sub.name;
                const services = sub.children || [];
                if (!services.length) {
                    colServices.innerHTML = emptyCol(labels.noData);
                    setColumnCount('count-services', 0);
                    return;
                }
                let html = '';
                services.forEach(function (svc) {
                    let meta = (svc.variation_count || 0) + ' ' + labels.variations.toLowerCase();
                    if (svc.min_price != null) {
                        meta += ' · ' + labels.fromPrice + ' ' + formatPrice(svc.min_price);
                    }
                    html += listItemHtml(svc, 'service', svc.id, meta);
                });
                colServices.innerHTML = html;
                setColumnCount('count-services', services.length);
                setColumnCount('count-variations', 0);
                updateAddLinks();
                initColumnSortable(colServices);
            }

            function renderVariations(serviceId) {
                const entry = serviceMap.get(serviceId);
                if (!entry) {
                    colVariations.innerHTML = emptyCol(labels.noData);
                    setColumnCount('count-variations', 0);
                    return;
                }
                const svc = entry.node;
                variationSubtitle.textContent = svc.name;
                const variations = svc.children || [];
                if (!variations.length) {
                    colVariations.innerHTML = emptyCol(labels.noVariations);
                    setColumnCount('count-variations', 0);
                    return;
                }
                let html = '';
                variations.forEach(function (v) {
                    const meta = formatPrice(v.price);
                    html += listItemHtml(
                        {
                            label: v.label,
                            image: v.image,
                            is_active: v.is_active,
                            edit_url: v.edit_url,
                            reorderable: v.reorderable !== false,
                        },
                        'variation',
                        v.id,
                        meta,
                        {
                            description: v.description || '',
                            reorderable: v.reorderable !== false,
                        }
                    );
                });
                colVariations.innerHTML = html;
                setColumnCount('count-variations', variations.length);
                updateAddLinks();
                initColumnSortable(colVariations);
            }

            function resetFromColumn(col) {
                if (col === 'category') {
                    selectedCategoryId = null;
                    selectedSubcategoryId = null;
                    selectedServiceId = null;
                    subcategorySubtitle.textContent = '';
                    serviceSubtitle.textContent = '';
                    variationSubtitle.textContent = '';
                    colSubcategories.innerHTML = emptyCol(labels.pickCategory);
                    colServices.innerHTML = emptyCol(labels.pickSubcategory);
                    colVariations.innerHTML = emptyCol(labels.pickService);
                    setColumnCount('count-subcategories', 0);
                    setColumnCount('count-services', 0);
                    setColumnCount('count-variations', 0);
                    updateAddLinks();
                } else if (col === 'subcategory') {
                    selectedSubcategoryId = null;
                    selectedServiceId = null;
                    serviceSubtitle.textContent = '';
                    variationSubtitle.textContent = '';
                    colServices.innerHTML = emptyCol(labels.pickSubcategory);
                    colVariations.innerHTML = emptyCol(labels.pickService);
                    setColumnCount('count-services', 0);
                    setColumnCount('count-variations', 0);
                    updateAddLinks();
                } else if (col === 'service') {
                    selectedServiceId = null;
                    variationSubtitle.textContent = '';
                    colVariations.innerHTML = emptyCol(labels.pickService);
                    setColumnCount('count-variations', 0);
                    updateAddLinks();
                }
            }

            function setSelected(container, id) {
                container.querySelectorAll('.catalog-col-row').forEach(function (row) {
                    const btn = row.querySelector('.catalog-col-item');
                    const match = btn && btn.getAttribute('data-id') === id;
                    row.classList.toggle('is-selected', !!match);
                    if (btn) {
                        btn.classList.toggle('is-selected', !!match);
                    }
                });
            }

            function applySearch(query) {
                const q = (query || '').trim().toLowerCase();
                document.querySelectorAll('.catalog-col-row').forEach(function (el) {
                    const text = el.getAttribute('data-search') || '';
                    el.classList.toggle('is-hidden', q !== '' && !text.includes(q));
                });

                const visible = function (container, selector) {
                    return container.querySelectorAll(selector + ':not(.is-hidden)').length;
                };
                setColumnCount('count-categories', visible(colCategories, '.catalog-col-row'));
                setColumnCount('count-subcategories', visible(colSubcategories, '.catalog-col-row'));
                setColumnCount('count-services', visible(colServices, '.catalog-col-row'));
                setColumnCount('count-variations', visible(colVariations, '.catalog-col-row'));
            }

            document.addEventListener('click', function (e) {
                if (
                    e.target.closest('.catalog-col-edit')
                    || e.target.closest('.catalog-col-name-link')
                    || e.target.closest('.catalog-col-drag')
                ) {
                    e.stopPropagation();
                }
            });

            colCategories.addEventListener('click', function (e) {
                const btn = e.target.closest('.catalog-col-item[data-type="category"]');
                if (!btn) return;
                const id = btn.getAttribute('data-id');
                if (selectedCategoryId !== id) {
                    resetFromColumn('category');
                }
                selectedCategoryId = id;
                setSelected(colCategories, id);
                renderSubcategories(id);
                updateAddLinks();
            });

            colSubcategories.addEventListener('click', function (e) {
                const btn = e.target.closest('.catalog-col-item[data-type="subcategory"]');
                if (!btn) return;
                const id = btn.getAttribute('data-id');
                if (selectedSubcategoryId !== id) {
                    resetFromColumn('subcategory');
                }
                selectedSubcategoryId = id;
                setSelected(colSubcategories, id);
                renderServices(id);
                updateAddLinks();
            });

            colServices.addEventListener('click', function (e) {
                if (e.target.closest('.catalog-col-name-link')) {
                    return;
                }
                const btn = e.target.closest('.catalog-col-item[data-type="service"]');
                if (!btn) return;
                const id = btn.getAttribute('data-id');
                selectedServiceId = id;
                setSelected(colServices, id);
                renderVariations(id);
                updateAddLinks();
            });

            colVariations.addEventListener('click', function (e) {
                const btn = e.target.closest('.catalog-col-item[data-type="variation"]');
                if (!btn) return;
                setSelected(colVariations, btn.getAttribute('data-id'));
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    applySearch(searchInput.value);
                });
            }

            renderCategories();
            updateAddLinks();
        })();
    </script>
@endpush
