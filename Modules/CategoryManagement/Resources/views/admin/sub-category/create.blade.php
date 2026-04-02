@extends('adminmodule::layouts.master')

@section('title',translate('sub_category_setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/select2/select2.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/select.dataTables.min.css')}}"/>
    <style>
        #SubCategoryListTableContainer a.category-list-name-link:hover,
        #SubCategoryListTableContainer a.category-list-name-link:focus {
            color: var(--bs-dark) !important;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('sub_category_setup')}}</h2>
                    </div>

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

                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{$status=='all'?'active':''}}"
                                   href="{{url()->current()}}?status=all">
                                    {{translate('all')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='active'?'active':''}}"
                                   href="{{url()->current()}}?status=active">
                                    {{translate('active')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='inactive'?'active':''}}"
                                   href="{{url()->current()}}?status=inactive">
                                    {{translate('inactive')}}
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="d-flex gap-2 fw-medium">
                                <span class="opacity-75">{{translate('Total_Sub_Categories')}}:</span>
                                <span class="title-color" id="totalSubCategoryCount">{{$subCategories->total()}}</span>
                            </div>
                            @can('category_add')
                                <button type="button"
                                        class="btn btn--primary btn-sm text-capitalize {{ $errors->any() ? 'd-none' : '' }}"
                                        id="btn-show-sub-category-add-form">{{translate('add_new')}} {{translate('sub_category')}}</button>
                            @endcan
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all-tab-pane">
                            <div class="card">
                                <div class="card-body">
                                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                        <form action="{{url()->current()}}?status={{$status}}"
                                              class="search-form search-form_style-two"
                                              method="POST">
                                            @csrf
                                            <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                                <input type="search" class="theme-input-style search-form__input"
                                                       value="{{$search}}" name="search"
                                                       placeholder="{{translate('search_here')}}">
                                            </div>
                                            <button type="submit"
                                                    class="btn btn--primary">{{translate('search')}}</button>
                                        </form>

                                        @can('category_export')
                                            <div class="d-flex flex-wrap align-items-center gap-3">
                                                <div class="dropdown">
                                                    <button type="button"
                                                            class="btn btn--secondary text-capitalize dropdown-toggle"
                                                            data-bs-toggle="dropdown">
                                                        <span class="material-icons">file_download</span> download
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                        <li><a class="dropdown-item"
                                                               href="{{route('admin.sub-category.download')}}?search={{$search}}">{{translate('excel')}}</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        @endcan
                                    </div>

                                    <div class="table-responsive" id="SubCategoryListTableContainer">
                                        <table id="example" class="table align-middle">
                                            <thead class="text-nowrap">
                                            <tr>
                                                <th>{{translate('name')}}</th>
                                                <th>{{translate('parent_category')}}</th>
                                                <th>{{translate('service_count')}}</th>
                                                @can('category_manage_status')
                                                    <th>{{translate('status')}}</th>
                                                @endcan
                                                @canany(['category_delete', 'category_update'])
                                                    <th>{{translate('action')}}</th>
                                                @endcan
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($subCategories as $key=>$category)
                                                <tr>
                                                    <td>
                                                        @can('category_update')
                                                            <a href="{{ route('admin.sub-category.edit', [$category->id]) }}"
                                                               class="category-list-name-link d-flex align-items-center gap-3 text-decoration-none demo_check title-color">
                                                                <div class="avatar avatar-sm flex-shrink-0">
                                                                    <img class="avatar-img radius-5"
                                                                         src="{{ $category->image_full_path }}"
                                                                         alt="{{ $category->name }}">
                                                                </div>
                                                                <span class="fw-medium">{{ $category->name }}</span>
                                                            </a>
                                                        @else
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="avatar avatar-sm flex-shrink-0">
                                                                    <img class="avatar-img radius-5"
                                                                         src="{{ $category->image_full_path }}"
                                                                         alt="{{ $category->name }}">
                                                                </div>
                                                                <span>{{ $category->name }}</span>
                                                            </div>
                                                        @endcan
                                                    </td>
                                                    <td>{{$category->parent->name??translate('not_found')}}</td>
                                                    <td>{{$category->services_count}}</td>
                                                    @can('category_manage_status')
                                                        <td>
                                                            <label class="switcher" data-bs-toggle="modal"
                                                                   data-bs-target="#deactivateAlertModal">
                                                                <input class="switcher_input status-update"
                                                                       type="checkbox"
                                                                       {{$category->is_active?'checked':''}} data-status="{{$category->id}}">
                                                                <span class="switcher_control"></span>
                                                            </label>
                                                        </td>
                                                    @endcan
                                                    @canany(['category_delete', 'category_update'])
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                @can('category_update')
                                                                    <a href="{{route('admin.sub-category.edit',[$category->id])}}"
                                                                       class="action-btn btn--light-primary demo_check"
                                                                       style="--size: 30px">
                                                                        <span class="material-icons">edit</span>
                                                                    </a>
                                                                @endcan
                                                                @can('category_delete')
                                                                    <button type="button"
                                                                            class="action-btn btn--danger demo_check"
                                                                            data-delete="{{$category->id}}"
                                                                            style="--size: 30px">
                                                                    <span
                                                                        class="material-symbols-outlined">delete</span>
                                                                    </button>
                                                                    <form
                                                                        action="{{route('admin.sub-category.delete',[$category->id])}}"
                                                                        method="post" id="delete-{{$category->id}}"
                                                                        class="hidden">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                    </form>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    @endcan
                                                </tr>
                                            @empty
                                                <tr class="text-center">
                                                    <td colspan="5">{{translate('no data available')}}</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        {!! $subCategories->links() !!}
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
    <script src="{{asset('assets/category-module/js/sub-category/create.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/dataTables.select.min.js')}}"></script>

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
        "use strict"

        $('.status-update').on('click', function () {
            let itemId = $(this).data('status');
            let route = '{{route('admin.sub-category.status-update',['id' => ':itemId'])}}';
            route = route.replace(':itemId', itemId);
            route_alert(route, @json(translate('want_to_update_status')));
        })

        $('.action-btn.btn--danger').on('click', function () {
            let itemId = $(this).data('delete');
            @if(env('APP_ENV')!='demo')
            form_alert('delete-' + itemId, @json(translate('want_to_delete_this') . '?'))
            @endif
        })

        $('#sub-category-form button[type="reset"]').on('click', function (e) {
            $('#category_selector').val('').trigger('change');
        });
    </script>
@endpush
