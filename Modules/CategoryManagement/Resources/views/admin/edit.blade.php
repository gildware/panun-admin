@extends('adminmodule::layouts.master')

@section('title',translate('category_update'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <h2 class="page-title mb-0">{{translate('category_update')}}</h2>
                        <a href="{{ route('admin.category.create') }}" class="btn btn--secondary d-inline-flex align-items-center gap-2">
                            <span class="material-icons fs-5 lh-1">arrow_back</span>
                            {{ translate('Back_to_Category_List') }}
                        </a>
                    </div>

                    @if(session('category_updated'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            {{ session('category_updated') }}
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card category-setup mb-30">
                        <div class="card-body p-30">
                                @php($language= Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first())
                                @php($default_lang = str_replace('_', '-', app()->getLocale()))

                                <ul class="nav nav--tabs border-color-primary mb-4" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="category-edit-tab-basic" data-bs-toggle="tab"
                                                data-bs-target="#category-edit-pane-basic" type="button" role="tab"
                                                aria-controls="category-edit-pane-basic" aria-selected="true">{{ translate('Basic') }}</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="category-edit-tab-charges" data-bs-toggle="tab"
                                                data-bs-target="#category-edit-pane-charges" type="button" role="tab"
                                                aria-controls="category-edit-pane-charges" aria-selected="false">{{ translate('Charges_and_Taxes') }}</button>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="category-edit-pane-basic" role="tabpanel"
                                         aria-labelledby="category-edit-tab-basic" tabindex="0">
                            <form action="{{route('admin.category.update',[$category->id])}}" method="post"
                                  enctype="multipart/form-data" id="category-form">
                                @csrf
                                @method('put')
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
                                            @if ($language)
                                                <div class="form-floating form-floating__icon mb-30 lang-form" id="default-form">
                                                    <input type="text" name="name[]" class="form-control"
                                                           placeholder="{{translate('category_name')}}"
                                                           value="{{$category?->getRawOriginal('name')}}" required>
                                                    <label>{{translate('category_name')}} ({{ translate('default') }}
                                                        )</label>
                                                    <span class="material-icons">subtitles</span>
                                                </div>
                                                <input type="hidden" name="lang[]" value="default">
                                                @foreach ($language?->live_values as $lang)
                                                        <?php
                                                        if (count($category['translations'])) {
                                                            $translate = [];
                                                            foreach ($category['translations'] as $t) {
                                                                if ($t->locale == $lang['code'] && $t->key == "name") {
                                                                    $translate[$lang['code']]['name'] = $t->value;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    <div class="form-floating form-floating__icon mb-30 d-none lang-form"
                                                         id="{{$lang['code']}}-form">
                                                        <input type="text" name="name[]" class="form-control"
                                                               placeholder="{{translate('category_name')}}"
                                                               value="{{$translate[$lang['code']]['name']??''}}">
                                                        <label>{{translate('category_name')}}
                                                            ({{strtoupper($lang['code'])}})</label>
                                                        <span class="material-icons">subtitles</span>
                                                    </div>
                                                    <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                                @endforeach
                                            @else
                                                <div class="form-floating form-floating__icon mb-30">
                                                    <input type="text" name="name[]" class="form-control"
                                                           placeholder="{{translate('category_name')}}"
                                                           value="{{$category['name']}}" required>
                                                    <label>{{translate('category_name')}}</label>
                                                    <span class="material-icons">subtitles</span>
                                                </div>
                                                <input type="hidden" name="lang[]" value="default">
                                            @endif


                                        @php($selectedZoneIds = old('zone_ids', $selectedZoneIds ?? []))
                                        <div class="mb-30">
                                            <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                                                <h4 class="c1 mb-0">{{ translate('Service_Zones') }}</h4>
                                            </div>
                                            <p class="text-muted fz-12 mb-20 mx-1 mt-1" style="line-height: 1.55;">{{ translate('provider_form_zone_tree_hint') }}</p>

                                            @if(count($zoneTree) > 0)
                                                <div class="category-zone-tree border rounded overflow-hidden mx-1 px-2">
                                                    @foreach($zoneTree as $rootNode)
                                                        <div class="category-zone-tree-root border-bottom border-light">
                                                            @include('categorymanagement::admin.partials.category-zone-tree-branch', [
                                                                'nodes' => [$rootNode],
                                                                'level' => 0,
                                                                'selectedZoneIds' => $selectedZoneIds,
                                                            ])
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="alert alert-info mb-0 mx-1">{{ translate('no_data_found') }}</div>
                                            @endif
                                        </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="d-flex  gap-3 gap-xl-5">
                                            <p class="opacity-75 max-w220">
                                                {{ translate('Image format')}} - {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                {{ translate("Image Size") }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                {{ translate('Image Ratio') }} - 1:1
                                            </p>
                                            <div>
                                                <div class="upload-file">
                                                    <input type="file" class="upload-file__input" name="image"
                                                           accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                           data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                    <div class="upload-file__img">
                                                        <img src="{{$category->image_full_path}}" alt="{{translate('category image')}}">
                                                    </div>
                                                    <span class="upload-file__edit">
                                                        <span class="material-icons">edit</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-20 mt-30">
                                            <button class="btn btn--secondary"
                                                    type="reset">{{translate('reset')}}</button>
                                            <button class="btn btn--primary" type="submit">{{translate('update')}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                                    </div>

                                    <div class="tab-pane fade" id="category-edit-pane-charges" role="tabpanel"
                                         aria-labelledby="category-edit-tab-charges" tabindex="0">
                                        <div class="row">
                                            <div class="col-12">
                                                <form action="{{ route('admin.category.update.charges.tax', $category->id) }}" method="post" id="category-charges-tax-form">
                                                    @csrf
                                                    @method('put')
                                                    <div class="border rounded p-20 mb-30 bg-white">
                                                        @include('categorymanagement::admin.partials.category-tax-override', ['taxModel' => $category, 'chargeSectionShell' => true])
                                                        <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                            <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="col-12">
                                                @can('commission_custom_category_update')
                                                    <form action="{{ route('admin.category.update.charges.commission', $category->id) }}" method="post" id="category-charges-commission-form">
                                                        @csrf
                                                        @method('put')
                                                        <div class="border rounded p-20 mb-30 bg-white">
                                                            @include('businesssettingsmodule::admin.partials.commission-entity-form-section', ['chargeSectionShell' => true])
                                                            <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                                <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @else
                                                    <div class="border rounded p-20 mb-30 bg-white">
                                                        <div class="mb-3 pb-3 border-bottom border-light">
                                                            <h5 class="mb-0 text-dark">{{ translate('Commission_Settings') }}</h5>
                                                        </div>
                                                        <div class="alert alert-soft-primary fz-12 mb-0" role="alert">
                                                            {{ translate('Commission_customization_no_permission_note') }}
                                                        </div>
                                                    </div>
                                                @endcan
                                            </div>
                                            <div class="col-12">
                                                <form action="{{ route('admin.category.update.charges.additional', $category->id) }}" method="post" id="category-charges-additional-form">
                                                    @csrf
                                                    @method('put')
                                                    <div class="border rounded p-20 mb-30 bg-white">
                                                        @include('businesssettingsmodule::admin.partials.additional-charge-entity-overrides-section', [
                                                            'additionalChargeOverrideRows' => $additionalChargeOverrideRows,
                                                            'formSelector' => '#category-charges-additional-form',
                                                            'chargeSectionShell' => true,
                                                        ])
                                                        <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                            <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                                                        </div>
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
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module/plugins/select2/select2.min.js')}}"></script>
    <script src="{{asset('assets/category-module/js/category/edit.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/dataTables.select.min.js')}}"></script>

    <script>
        "use strict"

        $('#zone_selector__select').on('change', function() {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

        $(document).ready(function () {
            let originalSelection = $('#zone_selector__select').val();

            $('button[type="reset"]').on('click', function (e) {
                $('#zone_selector__select').val(originalSelection).trigger('change');
            });
        });

        // Category zone tree selection (parent selects all children)
        (function () {
            function syncCategoryZoneParentsFromLeaves() {
                document.querySelectorAll("input.category-zone-parent-cb").forEach(function (cb) {
                    var item = cb.closest(".category-zone-tree-item");
                    var panel = item ? item.querySelector(".category-zone-tree-children") : null;
                    var leaves = panel ? panel.querySelectorAll("input.category-zone-leaf-cb") : [];
                    var leavesArr = Array.from(leaves);

                    if (!leavesArr.length) {
                        cb.checked = false;
                        cb.indeterminate = false;
                        return;
                    }

                    var checkedCount = leavesArr.filter(function (l) { return l.checked; }).length;
                    cb.checked = checkedCount === leavesArr.length;
                    cb.indeterminate = checkedCount > 0 && checkedCount < leavesArr.length;
                });
            }

            function syncCategoryZoneLabelStyles() {
                document.querySelectorAll("input.category-zone-leaf-cb").forEach(function (cb) {
                    var label = cb.id ? document.querySelector('label[for="' + cb.id + '"]') : null;
                    if (!label) return;
                    var isSelected = cb.checked === true;
                    label.classList.toggle("text-primary", isSelected);
                    label.classList.toggle("text-muted", !isSelected);
                });

                document.querySelectorAll("input.category-zone-parent-cb").forEach(function (cb) {
                    var label = cb.id ? document.querySelector('label[for="' + cb.id + '"]') : null;
                    if (!label) return;
                    var isSelected = cb.checked === true && cb.indeterminate === false;
                    label.classList.toggle("text-primary", isSelected);
                    label.classList.toggle("text-muted", !isSelected);
                });
            }

            function expandCategoryZoneAncestorsOfChecked() {
                document.querySelectorAll("input.category-zone-leaf-cb:checked").forEach(function (cb) {
                    var panel = cb.closest(".category-zone-tree-children");
                    while (panel) {
                        panel.classList.remove("d-none");
                        var toggle = panel.parentElement ? panel.parentElement.querySelector(".category-zone-tree-toggle") : null;
                        if (toggle) {
                            toggle.setAttribute("aria-expanded", "true");
                            var ic = toggle.querySelector(".category-zone-chevron");
                            if (ic) ic.textContent = "remove";
                        }
                        panel = panel.parentElement && panel.parentElement.closest
                            ? panel.parentElement.closest(".category-zone-tree-children")
                            : null;
                    }
                });
            }

            document.addEventListener("click", function (e) {
                var t = e.target && e.target.closest ? e.target.closest(".category-zone-tree-toggle") : null;
                if (!t) return;
                e.preventDefault();
                var item = t.closest(".category-zone-tree-item");
                if (!item) return;
                var panel = item.querySelector(".category-zone-tree-children");
                if (!panel) return;

                var open = panel.classList.toggle("d-none") === false;
                t.setAttribute("aria-expanded", open ? "true" : "false");
                var icon = t.querySelector(".category-zone-chevron");
                if (icon) icon.textContent = open ? "remove" : "add";
            });

            document.addEventListener("change", function (e) {
                var input = e.target;
                if (!(input && input.matches && input.matches("input.category-zone-parent-cb"))) return;

                var item = input.closest(".category-zone-tree-item");
                if (!item) return;

                var leaves = item.querySelectorAll("input.category-zone-leaf-cb");
                leaves.forEach(function (l) { l.checked = input.checked; });

                syncCategoryZoneParentsFromLeaves();
                syncCategoryZoneLabelStyles();
                expandCategoryZoneAncestorsOfChecked();
            });

            document.addEventListener("change", function (e) {
                var input = e.target;
                if (!(input && input.matches && input.matches("input.category-zone-leaf-cb"))) return;

                syncCategoryZoneParentsFromLeaves();
                syncCategoryZoneLabelStyles();
                expandCategoryZoneAncestorsOfChecked();
            });

            syncCategoryZoneParentsFromLeaves();
            syncCategoryZoneLabelStyles();
            expandCategoryZoneAncestorsOfChecked();

            var formEl = document.getElementById("category-form");
            if (formEl) {
                formEl.addEventListener("submit", function (e) {
                    var anyChecked = formEl.querySelectorAll("input.category-zone-leaf-cb:checked").length > 0;
                    if (!anyChecked) {
                        e.preventDefault();
                        var msg = "{{ addslashes(translate('Select_Zone')) }}";
                        if (typeof toastr !== "undefined") toastr.error(msg);
                        var tree = formEl.querySelector(".category-zone-tree");
                        if (tree && tree.scrollIntoView) tree.scrollIntoView({ behavior: "smooth", block: "nearest" });
                    }
                });
            }
        })();

    </script>
    @can('commission_custom_category_update')
        @include('businesssettingsmodule::admin.partials.commission-entity-form-scripts', [
            'previewCurrencySymbol' => $previewCurrencySymbol,
            'previewCurrencyCode' => $previewCurrencyCode,
            'formSelector' => '#category-charges-commission-form',
        ])
    @endcan
@endpush
