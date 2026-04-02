@extends('adminmodule::layouts.master')

@section('title',translate('service_update'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/wysiwyg-editor/froala_editor.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/tags-input.min.css"/>

    {{--AI--}}
    <link rel="stylesheet" href="{{asset('assets/admin-module/css/ai-sidebar.css') }}"/>

@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <h2 class="page-title mb-0">{{translate('update_service')}}</h2>
                        <a href="{{ route('admin.service.index') }}" class="btn btn--secondary d-inline-flex align-items-center gap-2">
                            <span class="material-icons fs-5 lh-1">arrow_back</span>
                            {{ translate('Back_to_Service_List') }}
                        </a>
                    </div>

                    @if(session('service_updated'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            {{ session('service_updated') }}
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card category-setup mb-30">
                        <div class="card-body p-30">
                            @php
                                $lang = $lang ?? ['code' => 'default'];
                                $language = Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first();
                                $default_lang = str_replace('_', '-', app()->getLocale());
                            @endphp
                            <ul class="nav nav--tabs border-color-primary mb-4" id="service-edit-main-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="service-edit-tab-basic" data-bs-toggle="tab"
                                            data-bs-target="#service-edit-pane-basic" type="button" role="tab"
                                            aria-controls="service-edit-pane-basic" aria-selected="true">{{ translate('Basic') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="service-edit-tab-charges" data-bs-toggle="tab"
                                            data-bs-target="#service-edit-pane-charges" type="button" role="tab"
                                            aria-controls="service-edit-pane-charges" aria-selected="false">{{ translate('Charges_and_Taxes') }}</button>
                                </li>
                            </ul>
                            <form action="{{route('admin.service.update',[$service->id])}}" method="post"
                                  enctype="multipart/form-data"
                                  id="service-add-form">
                                @csrf
                                @method('PUT')

                                <div id="form-wizard">
                                    <h3>{{translate('service_information')}}</h3>
                                    <section class="">
                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="service-edit-pane-basic" role="tabpanel"
                                                 aria-labelledby="service-edit-tab-basic" tabindex="0">
                                        <div class="row service-description-wrapper">
                                            <div class="col-xxl-9 col-lg-8 mb-5 mb-lg-0">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="mb-20">
                                                            <h3 class="mb-1 text-dark">{{ translate('Basic Setup') }}</h3>
                                                            <p class="fs-12 text-color">{{ translate('Provide essential service details') }}</p>
                                                        </div>
                                                        <div class="bg-light p-xxl-20 p-12px rounded">
                                                            @if($language)
                                                                <ul class="nav nav--tabs border-color-primary mb-5 flex-nowrap text-nowrap overflow-auto">
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
                                                            <!-- Language End -->
                                                            @if($language)
                                                                <div class="mb-30 lang-form" id="default-form">
                                                                    <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_title title-btn-wrapper"
                                                                            id="title-default-action-btn"
                                                                            data-lang="default"
                                                                            data-item='@json(["name" => $service?->getRawOriginal('name') ?? ''])'
                                                                            data-route="{{ route('admin.product.title-auto-fill') }}">
                                                                        <div class="btn-svg-wrapper">
                                                                            <img width="18" height="18" class="" src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                        </div>
                                                                        <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                        <span class="btn-text">{{ translate('Generate') }}</span>
                                                                    </button>
                                                                    <div class="form-floating form-floating__icon outline-wrapper title-container-default">
                                                                        <input type="text" name="name[]" id="default_name" class="form-control default-name"
                                                                               placeholder="{{translate('service_name')}}"
                                                                               value="{{$service?->getRawOriginal('name')}}" required>
                                                                        <label>{{translate('service_name')}} ({{ translate('default') }})</label>
                                                                        <span class="material-icons">subtitles</span>
                                                                    </div>
                                                                </div>
                                                            <input type="hidden" name="lang[]" value="default">
                                                            @foreach ($language?->live_values as $lang)
                                                                    <?php
                                                                    $translate = [];
                                                                    if (count($service['translations'])) {
                                                                        foreach ($service['translations'] as $t) {
                                                                            if ($t->locale == $lang['code'] && $t->key == "name") {
                                                                                $translate[$lang['code']]['name'] = $t->value;
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>

                                                                    <div class="mb-30 d-none lang-form" id="{{$lang['code']}}-form">
                                                                        <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_title title-btn-wrapper"
                                                                                id="title-{{ $lang['code'] }}-action-btn"
                                                                                data-route="{{ route('admin.product.title-auto-fill') }}"
                                                                                data-lang="{{ $lang['code'] }}"
                                                                                data-item='@json(["name" => $translate[$lang['code']]['name'] ?? ''])'
                                                                        >
                                                                            <div class="btn-svg-wrapper">
                                                                                <img width="18" height="18" class="" src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                            </div>
                                                                            <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                                                        </button>
                                                                        <div class="form-floating form-floating__icon outline-wrapper title-container-{{$lang['code']}}">
                                                                            <input type="text" name="name[]" id="{{$lang['code']}}_name"
                                                                                   class="form-control"
                                                                                   placeholder="{{translate('service_name')}}"
                                                                                   value="{{$translate[$lang['code']]['name']??''}}">
                                                                            <label>{{translate('service_name')}}({{strtoupper($lang['code'])}})</label>
                                                                            <span class="material-icons">subtitles</span>
                                                                        </div>
                                                                    </div>
                                                                <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                                            @endforeach
                                                            @else
                                                                <div class="lang-form">
                                                                    <div class="mb-30">
                                                                        <div class="form-floating form-floating__icon">
                                                                            <input type="text" class="form-control" name="name[]"
                                                                                placeholder="{{translate('service_name')}} *"
                                                                                required value="{{$service->name}}">
                                                                            <label>{{translate('service_name')}} *</label>
                                                                            <span class="material-icons">subtitles</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="lang[]" value="default">
                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_title title-btn-wrapper"
                                                                        id="title-en-action-btn"
                                                                        data-lang="en"
                                                                        data-route="{{ route('admin.product.title-auto-fill') }}">
                                                                    <div class="btn-svg-wrapper">
                                                                        <img width="18" height="18" class="" src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                    </div>
                                                                    <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                    <span class="btn-text">{{ translate('Generate') }}</span>
                                                                </button>
                                                            @endif
                                                            <!-- Service Name End -->

                                                            @if($language)
                                                            <div class="lang-form2" id="default-form2">
                                                                <div class="mb-30">
                                                                    <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap mb-3">
                                                                        <label class="m-0 lh-1">{{translate('short_description')}}({{translate('default')}}) *</label>
                                                                        <button type="button" class="btn bg-white mb-0 text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_short_description short-description-btn-wrapper"
                                                                                id="short-description-default-action-btn"
                                                                                data-lang="default"
                                                                                data-item='@json(["short_description" => $service?->getRawOriginal('short_description') ?? ''])'
                                                                                data-route="{{ route('admin.product.short-description-auto-fill') }}">
                                                                            <div class="btn-svg-wrapper">
                                                                                <img width="18" height="18" class=""
                                                                                     src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                            </div>
                                                                            <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="outline-wrapper">
                                                                        <textarea type="text" class="form-control default_short_description" required name="short_description[]">{{$service?->getRawOriginal('short_description')}}</textarea>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-30">
                                                                    <div class="form-error-wrap">
                                                                        <div class="d-flex align-items-end justify-content-between flex-wrap gap-1 mb-3">
                                                                            <label for="editor" class="mb-0 lh-1 fs-14">{{translate('long_Description')}}({{translate('default')}})<span class="text-danger">*</span></label>
                                                                            <button type="button" class="btn bg-white mb-0 text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_description description-btn-wrapper"
                                                                                    id="description-default-action-btn"
                                                                                    data-lang="default"
                                                                                    data-item='@json(["description" => $service?->getRawOriginal("description") ?? ""])'
                                                                                    data-route="{{ route('admin.product.description-auto-fill') }}">
                                                                                <div class="btn-svg-wrapper">
                                                                                    <img width="18" height="18" class=""
                                                                                         src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                                </div>
                                                                                <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                                <span class="btn-text">{{ translate('Generate') }} </span>
                                                                            </button>
                                                                        </div>
                                                                        <section id="editor" class="dark-support dark-support-02 outline-wrapper header-light body-customize-editor rounded-10">
                                                                            <textarea class="ckeditor default_description" name="description[]" id="default_description" required>{!! $service?->getRawOriginal('description') !!}</textarea>
                                                                        </section>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @foreach ($language?->live_values as $lang)
                                                                    <?php
                                                                    $translate = [];
                                                                    if (count($service['translations'])) {
                                                                        foreach ($service['translations'] as $t) {
                                                                            if ($t->locale == $lang['code'] && $t->key == "short_description") {
                                                                                $translate[$lang['code']]['short_description'] = $t->value;
                                                                            }

                                                                            if ($t->locale == $lang['code'] && $t->key == "description") {
                                                                                $translate[$lang['code']]['description'] = $t->value;
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
                                                                <div class="d-none lang-form2" id="{{$lang['code']}}-form2">
                                                                    <div class="col-lg-12 mt-5">
                                                                        <div class="mb-30">
                                                                            <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap mb-3">
                                                                                <label class="m-0">{{translate('short_description')}}({{strtoupper($lang['code'])}}) *</label>
                                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 mb-0 opacity-1 generate_btn_wrapper p-0 auto_fill_short_description short-description-btn-wrapper"
                                                                                        id="short-description-{{ $lang['code'] }}-action-btn"
                                                                                        data-lang="{{ $lang['code'] }}"
                                                                                        data-item='@json(["description" => $translate[$lang['code']]['description'] ?? $service?->getRawOriginal('description') ?? ""])'
                                                                                        data-route="{{ route('admin.product.short-description-auto-fill') }}">
                                                                                    <div class="btn-svg-wrapper">
                                                                                        <img width="18" height="18" class=""
                                                                                             src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                                    </div>
                                                                                    <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                                    <span class="btn-text">{{ translate('Generate') }}</span>
                                                                                </button>
                                                                            </div>

                                                                            <div class="form-floating outline-wrapper">
                                                                                <textarea type="text" class="form-control {{ $lang['code'] }}_short_description" name="short_description[]">{{$translate[$lang['code']]['short_description']??''}}</textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 mt-4">
                                                                        <div class="form-error-wrap">
                                                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-1 mb-3">
                                                                                <label for="editor" class="mb-0">{{translate('long_Description')}}({{strtoupper($lang['code'])}})<span class="text-danger">*</span></label>
                                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 mb-0 opacity-1 generate_btn_wrapper p-0 auto_fill_description description-btn-wrapper"
                                                                                        id="description-{{ $lang['code'] }}-action-btn"  data-lang="{{ $lang['code'] }}"
                                                                                        data-item='@json(["description" => $translate[$lang['code']]['description'] ?? ''])'
                                                                                        data-route="{{ route('admin.product.description-auto-fill') }}">
                                                                                    <div class="btn-svg-wrapper">
                                                                                        <img width="18" height="18" class="" src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                                    </div>
                                                                                    <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                                    <span class="btn-text">{{ translate('Generate') }}</span>
                                                                                </button>
                                                                            </div>

                                                                            <section id="editor" class="dark-support dark-support-02 outline-wrapper header-light body-customize-editor rounded-10">
                                                                                <textarea class="ckeditor {{ $lang['code'] }}_description" name="description[]" id="{{ $lang['code'] }}_description">{!! $translate[$lang['code']]['description']??'' !!}</textarea>
                                                                            </section>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            @else
                                                            <div class="normal-form">
                                                                <div class="col-lg-12 mt-5">
                                                                    <div class="mb-30">
                                                                        <div class="form-floating">
                                                                            <textarea type="text" class="form-control" required
                                                                                    name="short_description[]">{{old('short_description')}}</textarea>
                                                                            <label>{{translate('short_description')}} *</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_short_description short-description-btn-wrapper"
                                                                        id="short-description-en-action-btn"  data-lang="en"
                                                                        data-route="{{ route('admin.product.short-description-auto-fill') }}">
                                                                    <div class="btn-svg-wrapper">
                                                                        <img width="18" height="18" class=""
                                                                             src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                    </div>
                                                                    <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                    <span class="btn-text">{{ translate('Generate') }}</span>
                                                                </button>

                                                                <div class="col-12 mt-4">
                                                                    <label for="editor"
                                                                        class="mb-2">{{translate('long_Description')}}
                                                                        <span class="text-danger">*</span></label>
                                                                    <section id="editor" class="dark-support body-customize-editor">
                                                                        <textarea class="ckeditor" required
                                                                                name="description[]">{{old('description')}}</textarea>
                                                                    </section>
                                                                </div>
                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_description description-btn-wrapper"
                                                                        id="description-en-action-btn"  data-lang="en"
                                                                        data-route="{{ route('admin.product.description-auto-fill') }}">
                                                                    <div class="btn-svg-wrapper">
                                                                        <img width="18" height="18" class=""
                                                                             src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                    </div>
                                                                    <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                    <span class="btn-text">{{ translate('Generate') }}</span>
                                                                </button>
                                                            </div>
                                                            @endif
                                                            <!-- ShotDescription End -->
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>
                                            <div class="col-xxl-3 col-lg-4 mb-5 mb-sm-0">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="bg-light rounded w-100 mb-30">
                                                            <div class="d-flex flex-column align-items-center gap-0 text-center px-2 py-5">
                                                                <div class="mb-30">
                                                                    <h5 class="mb-1 fs-14 font-semibold text-dark">{{translate('thumbnail_image')}}</h5>
                                                                    <span class="fs-12 text-color">{{ translate('Upload your thumbnail Image') }}</span>
                                                                </div>
                                                                <div class="mb-30">
                                                                    <div class="upload-file ratio-1 w-100px">
                                                                        <input type="file" class="upload-file__input"
                                                                               name="thumbnail"
                                                                               accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                                               data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                                        <div class="upload-file__img border-dashed-1-gray rounded">
                                                                            <img src="{{$service->thumbnail_full_path}}"
                                                                                alt="{{translate('image')}}" class="w-100">
                                                                        </div>
                                                                        <span class="upload-file__edit">
                                                                            <span class="material-icons">edit</span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <p class="text-center fs-10 text-color mb-0">
                                                                    {{ translate('Image format')}} - {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                                    {{ translate("Image Size") }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                                    {{ translate('Image Ratio') }} - 1:1
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="bg-light rounded w-100">
                                                            <div class="d-flex flex-column align-items-center gap-0 text-center px-2 py-5">
                                                                 <div class="mb-30">
                                                                    <p class="mb-1 fs-14 font-semibold text-dark">{{translate('cover_image')}}</p>
                                                                    <span class="fs-12 text-color">{{ translate('Upload your cover Image') }}</span>
                                                                </div>
                                                                <div class="mb-30">
                                                                    <div class="upload-file h-100px">
                                                                        <input type="file" class="upload-file__input"
                                                                               name="cover_image"
                                                                               accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                                               data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                                        <div class="upload-file__img h-100px  border-dashed-1-gray rounded upload-file__img_banner">
                                                                            <img alt="{{ translate('cover-image') }}"
                                                                                src="{{$service->cover_image_full_path}}" class="w-100 h-100">
                                                                        </div>
                                                                        <span class="upload-file__edit">
                                                                            <span class="material-icons">edit</span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <p class="text-center fs-10 text-color mb-0">
                                                                    {{ translate('Image format')}} - {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                                    {{ translate("Image Size") }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                                    {{ translate('Image Ratio') }} - 3:1
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="general_wrapper mt-4">
                                            <div class="outline-wrapper">
                                                <div class="card bg-animate">
                                                    <div class="card-body">
                                                        <button type="button"
                                                                class="btn bg-white text-primary mt-0 mb-md-0 mb-2 bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 general_setup_auto_fill"
                                                                id="general_setup_auto_fill"
                                                                data-route="{{ route('admin.product.general-setup-auto-fill') }}"  data-lang="default">
                                                            <div class="btn-svg-wrapper">
                                                                <img width="18" height="18" class=""
                                                                     src="{{ asset(path: 'assets/admin-module/img/ai//blink-right-small.svg') }}" alt="">
                                                            </div>
                                                            <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                                        </button>
                                                        <div class="mb-20 max-w-500">
                                                            <h3 class="mb-1 text-dark">{{ translate('General Setup') }}</h3>
                                                            <p class="fs-12 text-color m-0">{{ translate('Here you can set up the foundational details required for service creation.') }}</p>
                                                        </div>
                                                        <div class="bg-light rounded p-xxl-20 p-12px">
                                                            <div class="row g-lg-4 g-3">
                                                                <div class="col-lg-4 col-md-6">
                                                                    <select class="js-select theme-input-style w-100" name="category_id"
                                                                            id="category-id">
                                                                        <option value="0" selected
                                                                                disabled>{{translate('choose_category')}}</option>
                                                                        @foreach($categories as $category)
                                                                            <option
                                                                                value="{{$category->id}}" {{$category->id==$service->category_id?'selected':''}}>
                                                                                {{$category->name}}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-lg-4 col-md-6">
                                                                    <div class="m-0" id="sub-category-selector">
                                                                        <select class="js-select theme-input-style w-100"
                                                                                name="sub_category_id"></select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-4 col-md-5">
                                                                    <div class="form-floating form-floating__icon">
                                                                        <input type="number" class="form-control"
                                                                               name="min_bidding_price" min="0"
                                                                               max="100" step="any"
                                                                               placeholder="{{translate('min_bidding_price')}} *"
                                                                               required="" value="{{$service->min_bidding_price}}">
                                                                        <label>{{translate('min_bidding_price')}} *</label>
                                                                        <span class="material-icons">price_change</span>
                                                                    </div>
                                                                </div>
                                                                <div class="ol-lg-8 col-md-7">
                                                                    <div class="form-floating taginput-dark-support">
                                                                        <input type="text" class="form-control" name="tags"
                                                                               placeholder="{{translate('Enter tags')}}"
                                                                               value="{{implode(",",$tagNames)}}"
                                                                               data-role="tagsinput">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                            </div>

                                            <div class="tab-pane fade" id="service-edit-pane-charges" role="tabpanel"
                                                 aria-labelledby="service-edit-tab-charges" tabindex="0">
                                                <div class="row mt-2">
                                                    <div class="col-12">
                                                        <div id="service-charge-tax-section" class="border rounded p-20 mb-30 bg-white">
                                                            @include('categorymanagement::admin.partials.entity-tax-override', ['mode' => 'service', 'taxModel' => $service, 'chargeSectionShell' => true])
                                                            <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                                <button type="button" class="btn btn--primary js-service-charge-section-save"
                                                                        data-action-url="{{ route('admin.service.update.charges.tax', $service->id) }}"
                                                                        data-container-id="service-charge-tax-section">{{ translate('save') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        @can('commission_custom_service_update')
                                                            <div id="service-charge-commission-section" class="border rounded p-20 mb-30 bg-white">
                                                                @include('businesssettingsmodule::admin.partials.commission-entity-form-section', ['chargeSectionShell' => true])
                                                                <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                                    <button type="button" class="btn btn--primary js-service-charge-section-save"
                                                                            data-action-url="{{ route('admin.service.update.charges.commission', $service->id) }}"
                                                                            data-container-id="service-charge-commission-section">{{ translate('save') }}</button>
                                                                </div>
                                                            </div>
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
                                                        <div id="service-charge-additional-section" class="border rounded p-20 mb-30 bg-white">
                                                            @include('businesssettingsmodule::admin.partials.additional-charge-entity-overrides-section', [
                                                                'additionalChargeOverrideRows' => $additionalChargeOverrideRows,
                                                                'formSelector' => '#service-add-form',
                                                                'chargeSectionShell' => true,
                                                            ])
                                                            <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                                                <button type="button" class="btn btn--primary js-service-charge-section-save"
                                                                        data-action-url="{{ route('admin.service.update.charges.additional', $service->id) }}"
                                                                        data-container-id="service-charge-additional-section">{{ translate('save') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </section>

                                    <h3>{{translate('price_variation')}}</h3>
                                    <section>
                                        <div class="general_wrapper mb-20">
                                            <div class="outline-wrapper">
                                                <div class="card bg-animate">
                                                    <div class="card-body">
                                                        <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 variation_setup_auto_fill"
                                                                id="description-en-action-btn"  data-lang="en"
                                                                data-route="{{ route('admin.product.variation-setup-auto-fill') }}">
                                                            <div class="btn-svg-wrapper">
                                                                <img width="18" height="18" class=""
                                                                     src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                            </div>
                                                            <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                                        </button>
                                                        <div class="p-xxl-20 p-12px bg-light rounded">
                                                            <div class="d-flex flex-wrap gap-20 mb-01">
                                                                <div class="form-floating flex-grow-1">
                                                                    <input type="text" class="form-control" name="variant_name"
                                                                           id="variant-name"
                                                                           placeholder="{{translate('add_variant')}} *" required="">
                                                                    <label>{{translate('add_variant')}} *</label>
                                                                </div>
                                                                <div class="form-floating flex-grow-1">
                                                                    <input type="number" class="form-control" name="variant_price"
                                                                           id="variant-price"
                                                                           placeholder="{{translate('price')}} *" required="" value="0">
                                                                    <label>{{translate('price')}} *</label>
                                                                </div>
                                                                <button type="button" class="btn rounded btn--primary" id="service-ajax-variation">
                                                                    <span class="material-icons">add</span>
                                                                    {{translate('add')}}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive p-01">
                                            <table class="table align-middle table-variation">
                                                <thead id="category-wise-zone" class="text-nowrap">
                                                <tr>
                                                    <th scope="col">{{translate('variations')}}</th>
                                                    <th scope="col">{{translate('default_price')}}</th>
                                                    <th scope="col">{{translate('action')}}</th>
                                                </tr>
                                                </thead>
                                                <tbody id="variation-update-table">
                                                @include('servicemanagement::admin.partials._update-variant-data',['variants'=>$service->variations,'zones'=>$zones,'service'=>$service])
                                                </tbody>
                                            </table>

                                            <div id="new-variations-table"
                                                 class="{{session()->has('variations') && count(session('variations'))>0?'':'hide-div'}}">
                                                <label class="badge badge-primary mb-10">{{translate('new_variations')}}</label>
                                                <table class="table align-middle table-variation">
                                                    <tbody id="variation-table">
                                                    @include('servicemanagement::admin.partials._variant-data',['zones'=>$zones])
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include("servicemanagement::admin.partials.ai-sidebar")

        {{--AI assistant--}}
        <div class="floating-ai-button">
            <button type="button" class="btn btn-lg rounded-circle shadow-lg position-relative" data-bs-toggle="modal" data-bs-target="#aiAssistantModal"
                    data-action="main" title="AI Assistant">
                <span class="ai-btn-animation">
                    <span class="gradientCirc"></span>
                </span>
                <span class="position-relative z-1 text-white-absolute d-flex flex-column gap-1 align-items-center">
                    <img width="16" height="17" src="{{ asset(path: 'assets/admin-module/img/ai/hexa-ai.svg') }}" alt="">
                    <span class="fs-12 fw-semibold">{{ translate('Use_AI') }}</span>
                </span>
            </button>
            <div class="ai-tooltip">
                <span>{{translate("AI_Assistant")}}</span>
            </div>
        </div>

        {{-- Service zone pricing modal (per-zone overrides) --}}
        @php
            $zoneTreeForPricing = [];
            $selectedZoneIdsForPricingTree = [];
            $zonesForPricingTree = $zones ?? collect();
            $zonesForPricingTree = $zonesForPricingTree instanceof \Illuminate\Support\Collection ? $zonesForPricingTree : collect($zonesForPricingTree);

            // Ensure parent nodes exist in the tree (categories/services may store only leaf zones).
            $allZonesById = $zonesForPricingTree->keyBy(fn ($z) => (string) $z->id);
            $stack = $zonesForPricingTree->values()->all();
            while (!empty($stack)) {
                $current = array_pop($stack);
                $parentId = $current->parent_id ?? null;
                if (!$parentId) {
                    continue;
                }

                $parentIdStr = (string) $parentId;
                if ($allZonesById->has($parentIdStr)) {
                    continue;
                }

                // Include parent zones even if they are inactive; otherwise the tree may become empty.
                $parentZone = \Modules\ZoneManagement\Entities\Zone::where('id', $parentId)->first();
                if ($parentZone) {
                    $allZonesById->put($parentIdStr, $parentZone);
                    $stack[] = $parentZone;
                }
            }

            $zonesForPricingTreeExpanded = $allZonesById->values();

            $selectedZoneIdsForPricingTree = $zonesForPricingTreeExpanded->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

            $byParent = $zonesForPricingTreeExpanded->groupBy(fn ($z) => (string) ($z->parent_id ?? ''));

            $build = function (string $parentKey) use (&$build, $byParent): array {
                $rows = $byParent->get($parentKey, collect());
                return $rows->map(function ($z) use ($build): array {
                    $id = (string) ($z->id ?? '');
                    return [
                        'id' => $id,
                        'name' => (string) ($z->name ?? $id),
                        'children' => $build($id),
                    ];
                })->values()->all();
            };

            // Pick roots as "top-level" nodes where parent is missing from the dataset.
            $rootNodes = $zonesForPricingTreeExpanded->filter(function ($z) use ($allZonesById) {
                $pid = $z->parent_id ?? null;
                if (!$pid) {
                    return true;
                }

                return ! $allZonesById->has((string) $pid);
            });

            $zoneTreeForPricing = $rootNodes->values()->map(function ($z) use ($build) {
                $id = (string) ($z->id ?? '');

                return [
                    'id' => $id,
                    'name' => (string) ($z->name ?? $id),
                    'children' => $build($id),
                ];
            })->values()->all();
        @endphp

        <div class="modal fade" id="serviceZonePricingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="serviceZonePricingModalTitle">Set different pricing for zones</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="service-zone-price-tree" class="border rounded overflow-hidden p-2">
                            @include('servicemanagement::admin.partials._service-zone-price-tree-branch', [
                                'nodes' => $zoneTreeForPricing ?? [],
                                'level' => 0,
                                'selectedZoneIds' => $selectedZoneIdsForPricingTree ?? [],
                            ])
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/js//tags-input.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/jquery-steps/jquery.steps.min.js"></script>
    <script src="{{asset('assets/admin-module/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('assets/ckeditor/jquery.js')}}"></script>

    {{--AI--}}
    <script src="{{ asset('assets/admin-module/js/AI/products/ai-sidebar.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/general-setup.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-short-description-autofill.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-description-autofill.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-title-autofill.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-variation-setup.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/image-compressor/image-compressor.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/image-compressor/compressor.min.js') }}"></script>

    <script>
        "use strict";

        $(document).ready(function () {
            $('.js-select').select2();
        });

        $("#form-wizard").steps({
            headerTag: "h3",
            bodyTag: "section",
            transitionEffect: "slideLeft",
            autoFocus: true,
            onStepChanged: function (event, currentIndex, priorIndex) {
                $("#service-edit-main-tabs").toggleClass("d-none", currentIndex !== 0);
                let nextBtn = $(".actions a[href='#next']");
                if (nextBtn.hasClass("proceed-to-next")) {
                    setTimeout(function () {
                        $(".variation_setup_auto_fill").trigger("click");
                    }, 1000);
                }
            },
            onFinished: function (event, currentIndex) {
                event.preventDefault();

                let isValid = true;
                $(".desc-err").remove(); // Remove previous error messages

                let variationSections = $("#variation-update-table, #variation-table");

                // Loop through all number inputs
                variationSections.find('input[type="number"]').each(function () {
                    let value = parseFloat($(this).val());
                    let minValue = parseFloat($(this).attr('min'));

                    if (isNaN(value) || value === "") {
                        toastr.error('Please enter a valid number');
                        isValid = false;
                    } else if (value <= 0) {
                        toastr.error('Value must be greater than zero');
                        isValid = false;
                    } else if (!isNaN(minValue) && value < minValue) {
                        toastr.error(`Minimum allowed value is ${minValue}`);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    return false; // Stop form submission if validation fails
                }

                $("#service-add-form")[0].submit();

            }
        });

        (function () {
            function syncServiceEditWizardChromeVisible() {
                var chargesPane = document.getElementById('service-edit-pane-charges');
                var chargesActive = chargesPane && chargesPane.classList.contains('active');
                var $wiz = $('#form-wizard');
                $wiz.find('.steps').first().add($wiz.find('.actions').first()).toggleClass('d-none', chargesActive);
            }
            syncServiceEditWizardChromeVisible();
            $('#service-edit-tab-basic, #service-edit-tab-charges').on('shown.bs.tab', syncServiceEditWizardChromeVisible);
        })();

        ajax_get('{{url('/')}}/admin/category/ajax-childes-only/{{$service->category_id}}?sub_category_id={{$service->sub_category_id}}', 'sub-category-selector')

        $("#service-ajax-variation").on('click', function () {
            let route = "{{route('admin.service.ajax-add-variant')}}";
            let id = "variation-table";
            ajax_variation(route, id);
        })

        function ajax_variation(route, id) {

            let name = $('#variant-name').val();
            let price = $('#variant-price').val();

            if (name.length > 0 && price > 0) {
                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        name: $('#variant-name').val(),
                        price: $('#variant-price').val(),
                    },
                    success: function (response) {
                        if (response.flag == 0) {
                            toastr.info('Already added');
                        } else {
                            $('#new-variations-table').show();
                            $('#' + id).html(response.template);
                            $('#variant-name').val("");
                            $('#variant-price').val(0);
                        }
                    },
                });
            } else {
                if(price <= 0){
                    toastr.warning('{{translate('price can not be 0 or negative')}}');
                }else{
                    toastr.warning('{{translate('fields_are_required')}}');
                }
            }
        }

        document.addEventListener('click', function(event) {
            if (event.target.closest('.service-ajax-remove-variant')) {
                const btn = event.target.closest('.service-ajax-remove-variant');
                if (!btn) return;
                var route = event.target.closest('.service-ajax-remove-variant').getAttribute('data-route');
                var id = event.target.closest('.service-ajax-remove-variant').getAttribute('data-id');
                const count = parseInt(btn.getAttribute('data-item'));
                if (count <= 1) {
                    Swal.fire({
                        title: "{{translate('Warning')}}",
                        text: "{{translate('Minimum variant cannot be less than one')}}",
                        icon: 'warning',
                        confirmButtonColor: 'var(--bs-primary)',
                    });
                    return;
                }
                ajax_remove_variant(route, id);
            }
        });


        function ajax_remove_variant(route, id) {
            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: "{{translate('want_to_remove_this_variation')}}",
                type: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        dataType: 'json',
                        data: {},
                        beforeSend: function () {
                        },
                        success: function (response) {
                            console.log(response.template)
                            $('#' + id).html(response.template);
                            if (window.initZonePricingRowControls) {
                                window.initZonePricingRowControls('#' + id);
                            }
                        },
                        complete: function () {
                        },
                    });
                }
            })
        }


        $("#category-id").change(function () {
            let id = this.value;
            let route = "{{ url('/admin/category/ajax-childes/') }}/" + id;
            ajax_switch_category(route)
        });

        function ajax_switch_category(route) {
            $.get({
                url: route + '?service_id={{$service->id}}',
                dataType: 'json',
                data: {},
                beforeSend: function () {
                },
                success: function (response) {
                    console.log(response);
                    $('#sub-category-selector').html(response.template);
                    $('#category-wise-zone').html(response.template_for_zone);
                    $('#variation-table').html(response.template_for_variant);
                    $('#variation-update-table').html(response.template_for_update_variant);
                    if (window.initZonePricingRowControls) {
                        window.initZonePricingRowControls('#variation-update-table');
                    }
                },
                complete: function () {
                },
            });
        }

        $(document).ready(function () {
            tinymce.init({
                selector: 'textarea.ckeditor'
            });
            if (window.initZonePricingRowControls) {
                window.initZonePricingRowControls('#variation-update-table');
            }
        });

        // Per-zone pricing modal (parent -> children propagation)
        window.serviceZonePricingCustomMode = window.serviceZonePricingCustomMode || {};
        window.serviceZonePricingActiveVariantKey = null;
        window.serviceZonePricingActiveVariantIndex = null;
        (function () {
            function getAllZoneIdsFromModal() {
                var modalEl = document.getElementById('serviceZonePricingModal');
                if (!modalEl) return [];
                var ids = [];
                modalEl.querySelectorAll('.service-zone-price-input[data-zone-id]').forEach(function (inp) {
                    if (inp.dataset.zoneId) ids.push(inp.dataset.zoneId);
                });
                return Array.from(new Set(ids));
            }

            function getHiddenZoneIdsForVariant(variantKey) {
                var ids = [];
                var prefix = variantKey + '_';
                var suffix = '_price';
                document.querySelectorAll('input[type="hidden"][name]').forEach(function (inp) {
                    var n = inp.name;
                    if (n.indexOf(prefix) !== 0 || !n.endsWith(suffix)) return;
                    var mid = n.substring(prefix.length, n.length - suffix.length);
                    if (mid) ids.push(mid);
                });
                return ids;
            }

            function setVariantAllZonePricesToDefault(variantKey) {
                if (!variantKey) return;
                var btn = document.querySelector('.service-zone-pricing-btn[data-variant-key="' + variantKey + '"]');
                var defaultInput = null;
                if (btn) {
                    var tr = btn.closest('tr');
                    if (tr) {
                        defaultInput = tr.querySelector('input[name^="variant_default_price"]')
                            || tr.querySelector('input[type="number"][id^="default-set-"]');
                    }
                }

                var defaultPrice = defaultInput ? defaultInput.value : null;
                if (defaultPrice === null || defaultPrice === '' || isNaN(parseFloat(defaultPrice))) return;

                var zoneIds = getHiddenZoneIdsForVariant(variantKey);
                if (!zoneIds.length) zoneIds = getAllZoneIdsFromModal();
                zoneIds.forEach(function (zoneId) {
                    var name = variantKey + '_' + zoneId + '_price';
                    var inp = document.querySelector('input[name="' + name + '"]');
                    if (inp) inp.value = defaultPrice;
                });
            }

            function updateTablePrice(variantKey, zoneId, value) {
                if (!variantKey || !zoneId) return;
                var selector = 'input[name="' + variantKey + '_' + zoneId + '_price"]';
                var tableInput = document.querySelector(selector);
                if (tableInput) tableInput.value = value;
            }

            /** Push modal field values into form hiddens (source of truth on submit). */
            function flushActiveVariantModalToHidden() {
                var vk = window.serviceZonePricingActiveVariantKey;
                var modalEl = document.getElementById('serviceZonePricingModal');
                if (!vk || !modalEl) return;
                modalEl.querySelectorAll('.service-zone-price-input[data-zone-id]').forEach(function (inp) {
                    if (inp.disabled) return;
                    var zid = inp.dataset.zoneId;
                    if (!zid) return;
                    updateTablePrice(vk, zid, inp.value);
                });
            }

            var _zonePricingModalEl = document.getElementById('serviceZonePricingModal');
            if (_zonePricingModalEl && !_zonePricingModalEl.dataset.flushBound) {
                _zonePricingModalEl.dataset.flushBound = '1';
                _zonePricingModalEl.addEventListener('hidden.bs.modal', function () {
                    flushActiveVariantModalToHidden();
                });
            }

            function propagatePriceToDescendants(inputEl, variantKey) {
                var nodeItem = inputEl.closest('.service-zone-price-tree-item');
                if (!nodeItem) return;

                var zoneId = inputEl.dataset.zoneId;
                var price = inputEl.value;

                updateTablePrice(variantKey, zoneId, price);

                var descendantInputs = nodeItem.querySelectorAll('.service-zone-price-input[data-zone-id]');
                descendantInputs.forEach(function (descInput) {
                    var descZoneId = descInput.dataset.zoneId;
                    if (!descZoneId || descZoneId === zoneId) return;

                    var cb = nodeItem.querySelector('.service-zone-price-node-cb[data-zone-id="' + descZoneId + '"]');
                    if (cb && cb.checked) {
                        descInput.value = price;
                        updateTablePrice(variantKey, descZoneId, price);
                    }
                });
            }

            document.addEventListener('change', function (e) {
                var target = e.target;
                if (!(target && target.matches && target.matches('input.service-zone-price-node-cb'))) return;

                var nodeItem = target.closest('.service-zone-price-tree-item');
                if (!nodeItem) return;

                // Parent checkbox selects all descendants
                var subtreeCbs = nodeItem.querySelectorAll('.service-zone-price-node-cb');
                subtreeCbs.forEach(function (subCb) {
                    subCb.checked = target.checked;
                    var subZoneId = subCb.dataset.zoneId;
                    var subInput = nodeItem.querySelector('.service-zone-price-input[data-zone-id="' + subZoneId + '"]');
                    if (subInput) subInput.disabled = !subCb.checked;
                });
            });

            function onModalZonePriceInput(e) {
                var inp = e.target;
                if (!(inp && inp.classList && inp.classList.contains('service-zone-price-input'))) return;
                if (!window.serviceZonePricingActiveVariantKey) return;

                var nodeItem = inp.closest('.service-zone-price-tree-item');
                if (!nodeItem) return;
                var zoneId = inp.dataset.zoneId;

                var cb = nodeItem.querySelector('.service-zone-price-node-cb[data-zone-id="' + zoneId + '"]');
                if (cb && !cb.checked) return;

                propagatePriceToDescendants(inp, window.serviceZonePricingActiveVariantKey);
            }

            document.addEventListener('input', onModalZonePriceInput);
            document.addEventListener('change', onModalZonePriceInput);

            // Expand / collapse nodes inside the modal
            document.addEventListener('click', function (e) {
                var toggle = e.target && e.target.closest ? e.target.closest('.service-zone-price-tree-toggle') : null;
                if (!toggle) return;

                var nodeItem = toggle.closest('.service-zone-price-tree-item');
                if (!nodeItem) return;

                var childrenEl = nodeItem.querySelector('.service-zone-price-tree-children');
                if (!childrenEl) return;

                var shouldShow = childrenEl.classList.contains('d-none');
                childrenEl.classList.toggle('d-none', !shouldShow);
                toggle.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');

                var icon = toggle.querySelector('.service-zone-price-chevron');
                if (icon) icon.textContent = shouldShow ? 'remove' : 'add';
            });

            document.addEventListener('click', function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('.service-zone-pricing-btn') : null;
                if (!btn) return;
                if (btn.disabled) {
                    if (window.toastr) toastr.warning('Enable zone pricing for this variation first');
                    return;
                }

                var variantKey = btn.dataset.variantKey;
                var variantIndex = btn.dataset.variantIndex;

                window.serviceZonePricingActiveVariantKey = variantKey;
                window.serviceZonePricingActiveVariantIndex = variantIndex;
                window.serviceZonePricingCustomMode[variantKey] = true;

                var titleEl = document.getElementById('serviceZonePricingModalTitle');
                if (titleEl) titleEl.textContent = 'Set different pricing for ' + variantKey;

                // Fill modal from existing hiddens only — do NOT call setVariantAllZonePricesToDefault here;
                // that overwrote every hidden with default price and discarded per-zone edits.
                var modalEl = document.getElementById('serviceZonePricingModal');
                if (!modalEl) return;

                var rowDefaultPrice = '';
                var row = btn.closest('tr');
                if (row) {
                    var defaultInput = row.querySelector('input[name^="variant_default_price"], input[type="number"][id^="default-set-"]');
                    if (defaultInput) rowDefaultPrice = defaultInput.value || '';
                }

                modalEl.querySelectorAll('.service-zone-price-input[data-zone-id]').forEach(function (inp) {
                    var zoneId = inp.dataset.zoneId;
                    var selector = 'input[name="' + variantKey + '_' + zoneId + '_price"]';
                    var tableInput = document.querySelector(selector);
                    if (tableInput) {
                        inp.value = (tableInput.value !== '' && tableInput.value !== null) ? tableInput.value : rowDefaultPrice;
                    } else {
                        inp.value = rowDefaultPrice;
                    }
                });

                // Keep modal inputs disabled/enabled based on checkbox state
                modalEl.querySelectorAll('.service-zone-price-node-cb').forEach(function (cb) {
                    var zoneId = cb.dataset.zoneId;
                    var inp = modalEl.querySelector('.service-zone-price-input[data-zone-id="' + zoneId + '"]');
                    if (inp) inp.disabled = !cb.checked;
                });

                // Expand all nodes for easier editing
                modalEl.querySelectorAll('.service-zone-price-tree-children').forEach(function (ch) {
                    ch.classList.remove('d-none');
                });
                modalEl.querySelectorAll('.service-zone-price-tree-toggle').forEach(function (t) {
                    t.setAttribute('aria-expanded', 'true');
                    var icon = t.querySelector('.service-zone-price-chevron');
                    if (icon) icon.textContent = 'remove';
                });

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });

            document.addEventListener('change', function (e) {
                var t = e.target;
                if (!(t && t.classList && t.classList.contains('service-zone-pricing-toggle'))) return;
                var variantKey = t.dataset.variantKey;
                if (!variantKey) return;

                var tr = t.closest('tr');
                var btn = tr ? tr.querySelector('.service-zone-pricing-btn[data-variant-key="' + variantKey + '"]') : null;
                if (btn) {
                    btn.disabled = !t.checked;
                    btn.setAttribute('aria-disabled', (!t.checked).toString());
                }

                if (!t.checked) {
                    var defaultPriceInput = tr ? tr.querySelector('input[name^="variant_default_price"], input[type="number"][id^="default-set-"]') : null;
                    var defaultPrice = defaultPriceInput ? defaultPriceInput.value : null;
                    if (defaultPrice !== null && defaultPrice !== '' && !isNaN(parseFloat(defaultPrice))) {
                        getHiddenZoneIdsForVariant(variantKey).forEach(function (zoneId) {
                            var name = variantKey + '_' + zoneId + '_price';
                            var inp = document.querySelector('input[name="' + name + '"]');
                            if (inp) inp.value = defaultPrice;
                        });
                    }
                    window.serviceZonePricingCustomMode[variantKey] = false;
                } else {
                    setVariantAllZonePricesToDefault(variantKey);
                }
            });
        })();

        window.initZonePricingRowControls = function (tableSelector) {
            var root = tableSelector ? document.querySelector(tableSelector) : document;
            if (!root) return;
            root.querySelectorAll('.service-zone-pricing-toggle').forEach(function (cb) {
                var vk = cb.dataset.variantKey;
                var tr = cb.closest('tr');
                var btn = tr && tr.querySelector('.service-zone-pricing-btn[data-variant-key="' + vk + '"]');
                if (btn) {
                    btn.disabled = !cb.checked;
                    btn.setAttribute('aria-disabled', (!cb.checked).toString());
                }
                if (!cb.checked && tr) {
                    var defInp = tr.querySelector('input[name^="variant_default_price"]');
                    if (defInp) defInp.dispatchEvent(new Event('keyup'));
                }
            });
        };

        $(".lang_link").on('click', function (e) {
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang-form").addClass('d-none');
            $(".lang-form2").addClass('d-none')

            $(".title-btn-wrapper").addClass('d-none');
            $(".short-description-btn-wrapper").addClass('d-none');
            $(".description-btn-wrapper").addClass('d-none');

            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.substring(0, form_id.length - 5);

            $("#" + lang + "-form").removeClass('d-none');
            $("#" + lang + "-form2").removeClass('d-none');

            // show the right button
            $("#title-" + lang + "-action-btn").removeClass('d-none');
            $("#short-description-" + lang + "-action-btn").removeClass('d-none');
            $("#description-" + lang + "-action-btn").removeClass('d-none');

            if (lang == '{{ $default_lang ?? str_replace('_', '-', app()->getLocale()) }}') {
                $(".from_part_2").removeClass('d-none');
            } else {
                $(".from_part_2").addClass('d-none');
            }
        });

        window.submitServiceChargeSection = function (actionUrl, containerId) {
            var root = document.getElementById(containerId);
            if (!root || !actionUrl) return;
            var tokenInput = document.querySelector('#service-add-form input[name="_token"]');
            if (!tokenInput) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            form.style.display = 'none';
            var t = document.createElement('input');
            t.type = 'hidden';
            t.name = '_token';
            t.value = tokenInput.value;
            form.appendChild(t);
            var m = document.createElement('input');
            m.type = 'hidden';
            m.name = '_method';
            m.value = 'PUT';
            form.appendChild(m);

            var prevDisabled = [];
            root.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (!el.name) return;
                prevDisabled.push([el, el.disabled]);
                el.disabled = false;
            });

            root.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (!el.name) return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (!el.checked) return;
                }
                if (el.type === 'file') return;
                var c = el.cloneNode(true);
                c.removeAttribute('id');
                form.appendChild(c);
            });

            prevDisabled.forEach(function (pair) {
                pair[0].disabled = pair[1];
            });

            document.body.appendChild(form);
            form.submit();
        };

        document.querySelectorAll('.js-service-charge-section-save').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-action-url');
                var cid = btn.getAttribute('data-container-id');
                window.submitServiceChargeSection(url, cid);
            });
        });
    </script>
    @can('commission_custom_service_update')
        @include('businesssettingsmodule::admin.partials.commission-entity-form-scripts', [
            'previewCurrencySymbol' => $previewCurrencySymbol,
            'previewCurrencyCode' => $previewCurrencyCode,
            'formSelector' => '#service-add-form',
        ])
    @endcan
@endpush
