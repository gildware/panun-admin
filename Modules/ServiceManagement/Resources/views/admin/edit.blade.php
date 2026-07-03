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

    <style>
        .service-variations-workspace.is-loading { opacity: .55; pointer-events: none; transition: opacity .15s ease; }
        .service-variants-table .badge { font-size: 10px; padding: 3px 6px; }
    </style>

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

                    @if(session('service_updated') || session('service_created'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            {{ session('service_updated') ?? session('service_created') }}
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card category-setup mb-30">
                        <div class="card-body p-30">
                            @php
                                $lang = $lang ?? ['code' => 'default'];
                                $language = Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name','system_language')->first();
                                $default_lang = str_replace('_', '-', app()->getLocale());
                                $activeTab = request('tab', 'info');
                                $infoTabActive = $activeTab === 'info' ? 'active' : '';
                                $infoPaneActive = $activeTab === 'info' ? 'show active' : '';
                                $variationsTabActive = $activeTab === 'variations' ? 'active' : '';
                                $variationsPaneActive = $activeTab === 'variations' ? 'show active' : '';
                                $chargesTabActive = $activeTab === 'charges' ? 'active' : '';
                                $chargesPaneActive = $activeTab === 'charges' ? 'show active' : '';
                            @endphp
                            <ul class="nav nav--tabs border-color-primary mb-4" id="service-edit-main-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $infoTabActive }}" id="service-edit-tab-info" data-bs-toggle="tab"
                                            data-bs-target="#service-edit-pane-info" type="button" role="tab"
                                            aria-controls="service-edit-pane-info" aria-selected="{{ $activeTab === 'info' ? 'true' : 'false' }}">{{ translate('service_information') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $variationsTabActive }}" id="service-edit-tab-variations" data-bs-toggle="tab"
                                            data-bs-target="#service-edit-pane-variations" type="button" role="tab"
                                            aria-controls="service-edit-pane-variations" aria-selected="{{ $activeTab === 'variations' ? 'true' : 'false' }}">{{ translate('price_variation') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $chargesTabActive }}" id="service-edit-tab-charges" data-bs-toggle="tab"
                                            data-bs-target="#service-edit-pane-charges" type="button" role="tab"
                                            aria-controls="service-edit-pane-charges" aria-selected="{{ $activeTab === 'charges' ? 'true' : 'false' }}">{{ translate('Charges_and_Taxes') }}</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="service-edit-tab-content">
                                <div class="tab-pane fade {{ $infoPaneActive }}" id="service-edit-pane-info" role="tabpanel"
                                     aria-labelledby="service-edit-tab-info" tabindex="0">
                            <form action="{{ route('admin.service.update.basic', $service->id) }}" method="post"
                                  enctype="multipart/form-data"
                                  id="service-edit-info-form">
                                @csrf
                                @method('PUT')
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
                                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                                                                    <ul class="nav nav--tabs border-color-primary mb-0 flex-nowrap text-nowrap overflow-auto">
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
                                                                    <button type="button" class="btn btn-sm btn-outline-primary js-service-mobile-preview flex-shrink-0">
                                                                        <span class="material-icons fs-16 align-middle">phone_iphone</span>
                                                                        {{ translate('Preview_in_mobile_app') }}
                                                                    </button>
                                                                </div>
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
                                                                                name="sub_category_id" id="sub-category-id" required>
                                                                            <option value="">{{ translate('choose_sub_category') }}</option>
                                                                            @foreach(($service->category?->children ?? []) as $subCategory)
                                                                                <option value="{{ $subCategory->id }}"
                                                                                    {{ $subCategory->id === $service->sub_category_id ? 'selected' : '' }}>
                                                                                    {{ $subCategory->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
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

                                <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                    <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                                </div>
                            </form>
                                </div>

                                <div class="tab-pane fade {{ $variationsPaneActive }}" id="service-edit-pane-variations" role="tabpanel"
                                     aria-labelledby="service-edit-tab-variations" tabindex="0">
                                    <div id="service-variations-workspace"
                                         class="border rounded p-3 bg-white service-variations-workspace"
                                         data-list-url="{{ route('admin.service.variants.panel', $service->id) }}">
                                        @include('servicemanagement::admin.partials._variants-panel-list', ['service' => $service])
                                    </div>
                                </div>

                                <div class="tab-pane fade {{ $chargesPaneActive }}" id="service-edit-pane-charges" role="tabpanel"
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
                                                                'formSelector' => '#service-edit-info-form',
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

        @include('servicemanagement::admin.partials._service-mobile-preview-modal', [
            'service' => $service,
            'previewCurrencySymbol' => $previewCurrencySymbol ?? null,
        ])
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/js//tags-input.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script src="{{asset('assets/admin-module/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/js/service-html-editor.js')}}"></script>
    <script src="{{asset('assets/admin-module/js/service-mobile-preview.js')}}"></script>
    <script src="{{asset('assets/admin-module/js/service-variations-panel.js')}}?v={{$adminAssetVersion ?? time()}}"></script>

    {{--AI--}}
    <script src="{{ asset('assets/admin-module/js/AI/products/ai-sidebar.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/general-setup.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-short-description-autofill.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-description-autofill.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/products/product-title-autofill.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/image-compressor/image-compressor.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/AI/image-compressor/compressor.min.js') }}"></script>

    <script>
        (function ($) {
        "use strict";

        var serviceInfoForm = $("#service-edit-info-form");

        serviceInfoForm.on('submit', function () {
            if (window.syncServiceDescriptionEditors) {
                window.syncServiceDescriptionEditors();
            }
        });

        $(document).ready(function () {
            $('.js-select').select2();
            if (typeof ajax_get === 'function') {
                ajax_get('{{url('/')}}/admin/category/ajax-childes-only/{{$service->category_id}}?sub_category_id={{$service->sub_category_id}}', 'sub-category-selector');
            }
        });

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
                success: function (response) {
                    $('#sub-category-selector').html(response.template);
                    $('#category-wise-zone').html(response.template_for_zone);
                },
            });
        }

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

            if (window.showServiceDescriptionEditorForLang) {
                setTimeout(function () {
                    window.showServiceDescriptionEditorForLang(lang);
                }, 50);
            }
        });

        window.submitServiceChargeSection = function (actionUrl, containerId) {
            var root = document.getElementById(containerId);
            if (!root || !actionUrl) return;
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!tokenMeta) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            form.style.display = 'none';
            var t = document.createElement('input');
            t.type = 'hidden';
            t.name = '_token';
            t.value = tokenMeta.getAttribute('content');
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
        })(jQuery);
    </script>
    @can('commission_custom_service_update')
        @include('businesssettingsmodule::admin.partials.commission-entity-form-scripts', [
            'previewCurrencySymbol' => $previewCurrencySymbol,
            'previewCurrencyCode' => $previewCurrencyCode,
            'formSelector' => '#service-edit-info-form',
        ])
    @endcan
@endpush
