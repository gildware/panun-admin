@extends('adminmodule::layouts.master')

@section('title',translate('sub_category_update'))

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
                        <h2 class="page-title mb-0">{{translate('sub_category_update')}}</h2>
                        <a href="{{ route('admin.sub-category.create') }}" class="btn btn--secondary d-inline-flex align-items-center gap-2">
                            <span class="material-icons fs-5 lh-1">arrow_back</span>
                            {{ translate('Back_to_Sub_Category_List') }}
                        </a>
                    </div>

                    @if(session('sub_category_updated'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            {{ session('sub_category_updated') }}
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card category-setup mb-30">
                        <div class="card-body p-30">
                                @php($language= Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first())
                                @php($default_lang = str_replace('_', '-', app()->getLocale()))

                                <ul class="nav nav--tabs border-color-primary mb-4" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="subcategory-edit-tab-basic" data-bs-toggle="tab"
                                                data-bs-target="#subcategory-edit-pane-basic" type="button" role="tab"
                                                aria-controls="subcategory-edit-pane-basic" aria-selected="true">{{ translate('Basic') }}</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="subcategory-edit-tab-charges" data-bs-toggle="tab"
                                                data-bs-target="#subcategory-edit-pane-charges" type="button" role="tab"
                                                aria-controls="subcategory-edit-pane-charges" aria-selected="false">{{ translate('Charges_and_Taxes') }}</button>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="subcategory-edit-pane-basic" role="tabpanel"
                                         aria-labelledby="subcategory-edit-tab-basic" tabindex="0">
                            <form action="{{route('admin.sub-category.update',[$subCategory->id])}}" method="post"
                                  enctype="multipart/form-data" id="sub-category-edit-form">
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
                                            <select class="js-select theme-input-style w-100" name="parent_id">
                                                <option value="0" selected disabled>
                                                    {{translate('Select_Category_Name')}}
                                                </option>
                                                @foreach($mainCategories as $item)
                                                    <option
                                                        value="{{$item['id']}}" {{$subCategory->parent_id==$item->id?'selected':''}}>
                                                        {{$item->name}}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @if($language)
                                                <div class="lang-form" id="default-form">
                                                    <div class="form-floating form-floating__icon mb-30 mt-30">
                                                        <input type="text" name="name[]" class="form-control"
                                                               placeholder="{{translate('sub_category_name')}}"
                                                               value="{{$subCategory?->getRawOriginal('name')}}" required>
                                                        <label>{{translate('sub_category_name')}}
                                                            ({{ translate('default') }})</label>
                                                        <span class="material-icons">subtitles</span>
                                                    </div>

                                                    <div class="form-floating mb-30">
                                                <textarea type="text" name="short_description[]" class="form-control resize-none" required
                                                >{{$subCategory?->getRawOriginal('description')}}</textarea>
                                                        <label>{{translate('short_description')}}
                                                            ({{ translate('default') }})</label>
                                                    </div>
                                                </div>

                                                <input type="hidden" name="lang[]" value="default">
                                                @foreach ($language?->live_values as $lang)
                                                        <?php
                                                        if (count($subCategory['translations'])) {
                                                            $translate = [];
                                                            foreach ($subCategory['translations'] as $t) {
                                                                if ($t->locale == $lang['code'] && $t->key == "name") {
                                                                    $translate[$lang['code']]['name'] = $t->value;
                                                                }

                                                                if ($t->locale == $lang['code'] && $t->key == "description") {
                                                                    $translate[$lang['code']]['description'] = $t->value;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    <div class="lang-form d-none" id="{{ $lang['code'] }}-form">
                                                        <div class="form-floating form-floating__icon mb-30 mt-30">
                                                            <input type="text" name="name[]" class="form-control"
                                                                   placeholder="{{translate('sub_category_name')}} "
                                                                   value="{{$translate[$lang['code']]['name']??''}}">
                                                            <label>{{translate('sub_category_name')}}
                                                                ({{ strtoupper($lang['code']) }})</label>
                                                            <span class="material-icons">subtitles</span>
                                                        </div>

                                                        <div class="form-floating mb-30">
                                                            <textarea type="text" name="short_description[]"
                                                                      class="form-control resize-none">{{$translate[$lang['code']]['description']??''}}</textarea>
                                                            <label>{{translate('short_description')}}
                                                                ({{ strtoupper($lang['code']) }})</label>
                                                        </div>
                                                        <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="form-floating form-floating__icon mb-30 mt-30 lang-form">
                                                    <input type="text" name="name[]" class="form-control"
                                                           value="{{$subCategory->name}}"
                                                           placeholder="{{translate('sub_category_name')}}" required>
                                                    <label>{{translate('sub_category_name')}}
                                                        ({{ translate('default') }})</label>
                                                    <span class="material-icons">subtitles</span>
                                                </div>

                                                <div class="form-floating mb-30">
                                                <textarea type="text" name="short_description[]" class="form-control resize-none"
                                                          required>{{$subCategory->description}}</textarea>
                                                    <label>{{translate('short_description')}}
                                                    </label>
                                                </div>

                                                <input type="hidden" name="lang[]" value="default">
                                            @endif
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
                                                        <img src="{{onErrorImage($subCategory->image,
                                                                        Storage::disk('public')->url('category/' . $subCategory->image),
                                                                        asset('assets/admin-module/img/media/upload-file.png') ,
                                                                        'category/')}}"
                                                            alt="{{translate('image')}}">
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

                                    <div class="tab-pane fade" id="subcategory-edit-pane-charges" role="tabpanel"
                                         aria-labelledby="subcategory-edit-tab-charges" tabindex="0">
                                        <div class="row">
                                            <div class="col-12">
                                                <form action="{{ route('admin.sub-category.update.charges.tax', $subCategory->id) }}" method="post" id="subcategory-charges-tax-form">
                                                    @csrf
                                                    @method('put')
                                                    <div class="border rounded p-20 mb-30 bg-white">
                                                        @include('categorymanagement::admin.partials.category-tax-override', ['taxModel' => $subCategory, 'chargeSectionShell' => true])
                                                        <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                            <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="col-12">
                                                @can('commission_custom_sub_category_update')
                                                    <form action="{{ route('admin.sub-category.update.charges.commission', $subCategory->id) }}" method="post" id="subcategory-charges-commission-form">
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
                                                <form action="{{ route('admin.sub-category.update.charges.additional', $subCategory->id) }}" method="post" id="subcategory-charges-additional-form">
                                                    @csrf
                                                    @method('put')
                                                    <div class="border rounded p-20 mb-30 bg-white">
                                                        @include('businesssettingsmodule::admin.partials.additional-charge-entity-overrides-section', [
                                                            'additionalChargeOverrideRows' => $additionalChargeOverrideRows,
                                                            'formSelector' => '#subcategory-charges-additional-form',
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
    <script src="{{asset('assets/category-module/js/sub-category/edit.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/dataTables.select.min.js')}}"></script>
    @can('commission_custom_sub_category_update')
        @include('businesssettingsmodule::admin.partials.commission-entity-form-scripts', [
            'previewCurrencySymbol' => $previewCurrencySymbol,
            'previewCurrencyCode' => $previewCurrencyCode,
            'formSelector' => '#subcategory-charges-commission-form',
        ])
    @endcan
@endpush
