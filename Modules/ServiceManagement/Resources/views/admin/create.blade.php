@extends('adminmodule::layouts.master')

@section('title',translate('service_setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/wysiwyg-editor/froala_editor.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/tags-input.min.css"/>

    {{--AI--}}
    <link rel="stylesheet" href="{{asset('assets/admin-module/css/ai-sidebar.css') }}"/>

    <style>
        .body-customize-editor textarea.ckeditor { width: 100%; min-height: 300px; display: block; }
        .body-customize-editor .tox-tinymce { width: 100% !important; min-height: 300px !important; }
        .body-customize-editor .tox .tox-edit-area { min-height: 240px !important; }
        .body-customize-editor .tox .tox-edit-area__iframe { min-height: 240px !important; }
    </style>

@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('add_new_service')}}</h2>
                    </div>
                    <div class="card-wrap">
                        <div class="card-body-inner">
                            <div>
                                <form action="{{route('admin.service.store')}}" method="post" enctype="multipart/form-data" id="service-create-form">
                                    @csrf
                                    <input type="hidden" name="active_lang" id="service-active-lang" value="default">

                                    <div class="card-offset-animation">
                                        <div class="row service-description-wrapper">
                                            <div class="col-xxl-9 col-lg-8 mb-5 mb-lg-0">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="mb-20">
                                                            <h3 class="mb-1 text-dark">{{ translate('Basic Setup') }}</h3>
                                                            <p class="fs-12 text-color">{{ translate('Provide essential service details') }}</p>
                                                        </div>
                                                        <div class="bg-light p-xxl-20 p-12px rounded">
                                                            @php
                                                                $language = Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name', 'system_language')->first();
                                                            @endphp
                                                            @if($language)
                                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                                                                    <ul class="nav nav--tabs text-nowrap overflow-auto flex-nowrap border-color-primary mb-0">
                                                                        <li class="nav-item">
                                                                            <a class="nav-link lang_link active" href="#"
                                                                            id="default-link">{{translate('default')}}</a>
                                                                        </li>
                                                                        @foreach ($language?->live_values as $lang)
                                                                            <li class="nav-item">
                                                                                <a class="nav-link lang_link" href="#"
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
                                                            @if($language)
                                                                <div class="mb-30 lang-form" id="default-form">
                                                                    <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_title title-btn-wrapper"
                                                                            id="title-default-action-btn"
                                                                            data-lang="default"
                                                                            data-route="{{ route('admin.product.title-auto-fill') }}">
                                                                        <div class="btn-svg-wrapper">
                                                                            <img width="18" height="18" class="" src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                        </div>
                                                                        <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                        <span class="btn-text">{{ translate('Generate') }}</span>
                                                                    </button>
                                                                    <div class="form-floating form-floating__icon outline-wrapper title-container-default">
                                                                        <input type="text" name="name[]" id="default_name" class="form-control default-name" required placeholder="{{translate('service_name')}}">
                                                                        <label>{{translate('service_name')}} ({{ translate('default') }})</label>
                                                                        <span class="material-icons">subtitles</span>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="lang[]" value="default">
                                                                @foreach ($language?->live_values as $lang)
                                                                    <div class="mb-30 d-none lang-form" id="{{$lang['code']}}-form">
                                                                        <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_title title-btn-wrapper"
                                                                                id="title-{{ $lang['code'] }}-action-btn"
                                                                                data-route="{{ route('admin.product.title-auto-fill') }}"
                                                                                data-lang="{{ $lang['code'] }}">
                                                                            <div class="btn-svg-wrapper">
                                                                                <img width="18" height="18" class="" src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                            </div>
                                                                            <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                                                        </button>
                                                                        <div class="form-floating form-floating__icon outline-wrapper title-container-{{$lang['code']}}">

                                                                            <input type="text" name="name[]" id="{{$lang['code']}}_name" class="form-control input-language" placeholder="{{translate('service_name')}}">
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
                                                                                <input type="text" class="form-control" name="name[]" placeholder="{{translate('service_name')}} *" required>
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

                                                            <!-- shortDescription -->
                                                            @if($language)
                                                            <div class="lang-form2" id="default-form2">
                                                                <div class="mb-30">
                                                                    <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap mb-3">
                                                                        <label class="m-0 lh-1">{{translate('short_description')}}({{translate('default')}}) *</label>
                                                                        <button type="button" class="btn bg-white mb-0 text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_short_description short-description-btn-wrapper"
                                                                                id="short-description-default-action-btn"
                                                                                data-lang="default"
                                                                                data-route="{{ route('admin.product.short-description-auto-fill') }}">
                                                                            <div class="btn-svg-wrapper">
                                                                                <img width="18" height="18" class=""
                                                                                    src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                            </div>
                                                                            <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="outline-wrapper" id="">
                                                                        <textarea type="text" class="form-control default_short_description" name="short_description[]" required></textarea>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-30">
                                                                    <div class="form-error-wrap">
                                                                        <div class="d-flex align-items-end justify-content-between flex-wrap gap-1 mb-3">
                                                                            <label for="editor" class="mb-0 lh-1 fs-14">{{translate('long_Description')}}({{translate('default')}})<span class="text-danger">*</span></label>
                                                                            <button type="button" class="btn bg-white mb-0 text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 auto_fill_description description-btn-wrapper"
                                                                                    id="description-default-action-btn"
                                                                                    data-lang="default"
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
                                                                            <textarea class="ckeditor default_description" name="description[]" id="default_description" required></textarea>
                                                                        </section>
                                                                    </div>
                                                                </div>

                                                            </div>

                                                            @foreach ($language?->live_values as $lang)
                                                                <div class="d-none lang-form2" id="{{$lang['code']}}-form2">
                                                                    <div class="col-lg-12 mt-5">
                                                                        <div class="mb-30">
                                                                            <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap mb-3">
                                                                                <label class="m-0">{{translate('short_description')}}({{strtoupper($lang['code'])}}) *</label>
                                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 mb-0 opacity-1 generate_btn_wrapper p-0 auto_fill_short_description short-description-btn-wrapper"
                                                                                        id="short-description-{{ $lang['code'] }}-action-btn"  data-lang="{{ $lang['code'] }}"
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
                                                                                <textarea type="text" class="form-control {{ $lang['code'] }}_short_description" name="short_description[]"></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-12 mt-4 mt-md-5">
                                                                        <div class="form-error-wrap">
                                                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-1 mb-3">
                                                                                <label for="editor" class="mb-0">{{translate('long_Description')}}({{strtoupper($lang['code'])}})<span class="text-danger">*</span></label>
                                                                                <button type="button" class="btn bg-white text-primary bg-transparent shadow-none border-0 mb-0 opacity-1 generate_btn_wrapper p-0 auto_fill_description description-btn-wrapper"
                                                                                        id="description-{{ $lang['code'] }}-action-btn"  data-lang="{{ $lang['code'] }}"
                                                                                        data-route="{{ route('admin.product.description-auto-fill') }}">
                                                                                    <div class="btn-svg-wrapper">
                                                                                        <img width="18" height="18" class=""
                                                                                             src="{{ asset(path: 'assets/admin-module/img/ai/blink-right-small.svg') }}" alt="">
                                                                                    </div>
                                                                                    <span class="ai-text-animation d-none" role="status">{{ translate('Just_a_second') }}</span>
                                                                                    <span class="btn-text">{{ translate('Generate') }}</span>
                                                                                </button>
                                                                            </div>

                                                                            <section id="editor" class="dark-support dark-support-02 outline-wrapper header-light body-customize-editor rounded-10">
                                                                                <textarea class="ckeditor {{ $lang['code'] }}_description" name="description[]" id="{{ $lang['code'] }}_description"></textarea>
                                                                            </section>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                            @else
                                                                <div class="normal-form">
                                                                    <div class="col-lg-12 mt-5">
                                                                        <div class="mb-30">
                                                                            <div class="">
                                                                                <textarea type="text" class="form-control en_short_description" name="short_description[]" required></textarea>
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
                                                                        <div class="form-error-wrap m-0">
                                                                            <label for="editor" class="mb-2">{{translate('long_Description')}}
                                                                                <span class="text-danger">*</span>
                                                                            </label>
                                                                            <section id="editor" class="dark-support header-light body-customize-editor">
                                                                                <textarea class="ckeditor en_description" name="description[]" id="en_description" required></textarea>
                                                                            </section>
                                                                        </div>
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
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xxl-3 col-lg-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="bg-light rounded w-100 mb-30">
                                                            <div class="d-flex flex-column align-items-center gap-0 text-center px-2 py-5">
                                                                <div class="mb-30">
                                                                    <h5 class="mb-1 fs-14 font-semibold text-dark">{{translate('thumbnail_image')}} <span class="text-danger">*</span></h5>
                                                                    <span class="fs-12 text-color">{{ translate('Upload your thumbnail Image') }}</span>
                                                                </div>
                                                                <div class="d-flex flex-column align-items-center mb-30">
                                                                    <div class="upload-file ratio-1 w-100px form-error-wrap">
                                                                        <input type="file" class="upload-file__input"
                                                                               name="thumbnail"
                                                                               accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                                               data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
                                                                               required>
                                                                        <div class="upload-file__img border-dashed-1-gray rounded">
                                                                            <img src="{{asset('assets/admin-module/img/img-upload-new-small.png')}}"
                                                                                    alt="{{ translate('service') }}" class="w-100">
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
                                                        <div class="bg-light rounded w-100 text-center">
                                                            <div class="d-flex flex-column align-items-center gap-0 px-2 py-5">
                                                                <div class="mb-30">
                                                                    <p class="mb-1 fs-14 font-semibold text-dark">{{translate('cover_image')}} <span class="text-danger">*</span></p>
                                                                    <span class="fs-12 text-color">{{ translate('Upload your cover Image') }}</span>
                                                                </div>
                                                                <div class="mb-30">
                                                                    <div class="upload-file h-100px form-error-wrap">
                                                                        <input type="file" class="upload-file__input"
                                                                               name="cover_image"
                                                                               accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                                               data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
                                                                               required>
                                                                        <div class="upload-file__img h-100px  border-dashed-1-gray rounded upload-file__img_banner">
                                                                            <img src="{{asset('assets/admin-module/img/img-upload-new.png')}}"
                                                                                 alt="{{ translate('service-cover-image') }}" class="w-100 h-100">
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
                                                        <div class="row g-3">
                                                            <div class="bg-light rounded p-xxl-20 p-12px">
                                                                <div class="row g-lg-4 g-3">
                                                                    <div class="col-lg-4 col-md-6">
                                                                        <div class="form-error-wrap m-0">
                                                                            <select class="js-select theme-input-style w-100 form-error-wrap" name="category_id" id="category-id">
                                                                                <option value="0" selected disabled>{{translate('choose_Category')}} *</option>
                                                                                @foreach($categories as $category)
                                                                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-4 col-md-6">
                                                                        <div class="m-0 form-error-wrap" id="sub-category-selector">
                                                                            <div class="m-0 form-error-wrap">
                                                                                <select class="subcategory-select theme-input-style w-100"
                                                                                        name="sub_category_id" id="sub-category-id">
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-4 col-md-5">
                                                                        <div class="m-0 form-floating form-floating__icon">
                                                                            <input type="number" class="form-control"
                                                                                name="min_bidding_price" min="0" step="any"
                                                                                placeholder="{{translate('Minimum bidding price')}} *"
                                                                                required="" value="{{old('min_bidding_price')}}">
                                                                            <label>{{translate('Minimum bidding price')}} *</label>
                                                                            <span class="material-icons">price_change</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-8 col-md-7">
                                                                        <div class="m-0 form-floating taginput-dark-support">
                                                                            <input type="text" class="form-control w-100" name="tags"
                                                                                placeholder="{{translate('Enter_tags')}}"
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

                                    <div class="d-flex justify-content-end mt-4 pt-3 border-top border-light">
                                        <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                                    </div>
                                </form>
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

        {{-- Service zone pricing modal (per-zone overrides) --}}
        @php
            $zoneTreeForPricing = [];
            $selectedZoneIdsForPricingTree = [];
            $zonesForPricingTree = session()->has('category_wise_zones') ? session('category_wise_zones') : [];
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

        @include('servicemanagement::admin.partials._service-mobile-preview-modal')
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/js//tags-input.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script src="{{asset('assets/provider-module')}}/plugins/jquery-validation/jquery.validate.min.js"></script>
    <script src="{{asset('assets/admin-module/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/js/service-html-editor.js')}}"></script>
    <script src="{{asset('assets/admin-module/js/service-mobile-preview.js')}}"></script>

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
        (function ($) {
            "use strict";

            let createForm = $("#service-create-form");

            $('body').on('click', function (event) {
                if (!$(event.target).closest('#editor').length) {
                    if($("#editor iframe").contents().find("body").text() !== ""){
                        createForm.find('.desc-err').remove();
                    };
                }

                if (!$(event.target).closest('[name=category_id], [name=category_id] + .select2').length) {
                    if($('[name=category_id]').val()) {
                        $('[name=category_id]').parents('.form-error-wrap').siblings('[for="category-id"]').remove();
                    }
                }
            });

            createForm.validate({
                errorPlacement: function (error, element) {
                    element.parents('.form-floating, .form-error-wrap').after(error);
                },
                rules: {
                    "name[]": "required",
                    category_id: "required",
                    sub_category_id: "required",
                    min_bidding_price: "required",
                    "short_description[]": "required",
                    thumbnail: "required",
                    cover_image: "required",
                    "description[]": "required",
                },
                messages: {
                    "name[]": "Please enter name",
                    category_id: "Please enter category id",
                    sub_category_id: "Please select sub category",
                    min_bidding_price: "Please enter min bidding price",
                    "short_description[]": "Please enter short description",
                    thumbnail: "Please enter thumbnail",
                    cover_image: "Please upload cover image",
                    "description[]": "Please enter description",
                },
            });

            createForm.on('submit', function (e) {
                createForm.validate().settings.ignore = ":disabled,:hidden";

                var errorMessageElement = createForm.find(".desc-err");
                if ($("#editor iframe").contents().find("body").text() == "") {
                    e.preventDefault();
                    if (errorMessageElement.length > 0) {
                        errorMessageElement.text("Please Add Description");
                    } else {
                        createForm.find("#editor").after(
                            `<span class="text-danger desc-err mt-2">Please Add Description</span>`
                        );
                    }
                    return false;
                }
                createForm.find(".desc-err").remove();

                if (!createForm.valid()) {
                    e.preventDefault();
                    return false;
                }

                if (window.syncServiceDescriptionEditors) {
                    window.syncServiceDescriptionEditors();
                }
            });

        })(jQuery);
    </script>

    <script>
        "use strict";

        $(document).ready(function () {
            $('.js-select').select2();
            $('.subcategory-select').select2({
                placeholder: "Choose Subcategory"
            });

            var params = new URLSearchParams(window.location.search);
            var categoryId = params.get('category_id');
            var subCategoryId = params.get('sub_category_id');
            if (categoryId) {
                $('#category-id').val(categoryId).trigger('change');

                if (subCategoryId) {
                    var attempts = 0;
                    var timer = setInterval(function () {
                        attempts++;
                        var $sub = $('#sub-category-id');
                        if ($sub.length && $sub.find('option[value="' + subCategoryId + '"]').length) {
                            $sub.val(subCategoryId).trigger('change');
                            clearInterval(timer);
                        }
                        if (attempts > 50) {
                            clearInterval(timer);
                        }
                    }, 200);
                }
            }
        });

        $("#category-id").change(function (){
            let id = this.value;
            let route = "{{ url('/admin/category/ajax-childes/') }}/" + id;
            ajax_switch_category(route)
        });

        function ajax_switch_category(route, onLoaded) {
            $.get({
                url: route,
                dataType: 'json',
                data: {},
                beforeSend: function () {
                },
                success: function (response) {
                    $('#sub-category-selector').html(response.template);
                    $('#category-wise-zone').html(response.template_for_zone);
                    if (typeof onLoaded === 'function') {
                        onLoaded();
                    }
                },
                complete: function () {
                },
            });
        }

        $(document).ready(function () {
            $(".lang_link").on('click', function (e) {
                e.preventDefault();
                $(".lang_link").removeClass('active');
                $(".lang-form").addClass('d-none');
                $(".lang-form2").addClass('d-none');

                $(".title-btn-wrapper").addClass('d-none');
                $(".short-description-btn-wrapper").addClass('d-none');
                $(".description-btn-wrapper").addClass('d-none');

                $(this).addClass('active');

                let form_id = this.id;
                let lang = form_id.substring(0, form_id.length - 5);

                $('#service-active-lang').val(lang);

                // show the right input(s)
                $("#" + lang + "-form").removeClass('d-none');
                $("#" + lang + "-form2").removeClass('d-none');

                // show the right button
                $("#title-" + lang + "-action-btn").removeClass('d-none');
                $("#short-description-" + lang + "-action-btn").removeClass('d-none');
                $("#description-" + lang + "-action-btn").removeClass('d-none');

                if (window.showServiceDescriptionEditorForLang) {
                    setTimeout(function () {
                        window.showServiceDescriptionEditorForLang(lang);
                    }, 50);
                }

            });
        });



        $(document).ready(function () {
            if (window.initZonePricingRowControls) {
                window.initZonePricingRowControls('#variation-table');
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
                // unique
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
                    var tr = btn.closest('.service-variant-card');
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

            var _zonePricingModalElCreate = document.getElementById('serviceZonePricingModal');
            if (_zonePricingModalElCreate && !_zonePricingModalElCreate.dataset.flushBound) {
                _zonePricingModalElCreate.dataset.flushBound = '1';
                _zonePricingModalElCreate.addEventListener('hidden.bs.modal', function () {
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
                var cb = e.target && e.target.matches ? e.target.matches('input.service-zone-price-node-cb') ? e.target : null : null;
                if (!cb) return;

                var nodeItem = cb.closest('.service-zone-price-tree-item');
                if (!nodeItem) return;

                // Parent checkbox selects all descendants
                var subtreeCbs = nodeItem.querySelectorAll('.service-zone-price-node-cb');
                subtreeCbs.forEach(function (subCb) {
                    subCb.checked = cb.checked;
                    var subZoneId = subCb.dataset.zoneId;
                    var subInput = nodeItem.querySelector('.service-zone-price-input[data-zone-id="' + subZoneId + '"]');
                    if (subInput) subInput.disabled = !subCb.checked;
                });
            });

            function onModalZonePriceInputCreate(e) {
                var inp = e.target;
                if (!(inp && inp.classList && inp.classList.contains('service-zone-price-input'))) return;
                if (!window.serviceZonePricingActiveVariantKey) return;

                var nodeItem = inp.closest('.service-zone-price-tree-item');
                if (!nodeItem) return;
                var cb = nodeItem.querySelector('.service-zone-price-node-cb[data-zone-id="' + inp.dataset.zoneId + '"]');
                if (cb && !cb.checked) return;

                propagatePriceToDescendants(inp, window.serviceZonePricingActiveVariantKey);
            }

            document.addEventListener('input', onModalZonePriceInputCreate);
            document.addEventListener('change', onModalZonePriceInputCreate);

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

                var modalEl = document.getElementById('serviceZonePricingModal');
                if (!modalEl) return;

                var rowDefaultPrice = '';
                var row = btn.closest('.service-variant-card');
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

                // Show modal
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });

            document.addEventListener('change', function (e) {
                var t = e.target;
                if (!(t && t.classList && t.classList.contains('service-zone-pricing-toggle'))) return;
                var variantKey = t.dataset.variantKey;
                if (!variantKey) return;

                var tr = t.closest('.service-variant-card');
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
                var card = cb.closest('.service-variant-card');
                var btn = card && card.querySelector('.service-zone-pricing-btn[data-variant-key="' + vk + '"]');
                if (btn) {
                    btn.disabled = !cb.checked;
                    btn.setAttribute('aria-disabled', (!cb.checked).toString());
                }
                if (!cb.checked && card) {
                    var defInp = card.querySelector('input[name^="variant_default_price"]');
                    if (defInp) defInp.dispatchEvent(new Event('keyup'));
                }
            });
        };

    </script>
@endpush
