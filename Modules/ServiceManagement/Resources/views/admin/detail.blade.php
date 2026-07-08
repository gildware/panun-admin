@extends('adminmodule::layouts.master')

@section('title',translate('service_details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/wysiwyg-editor/froala_editor.min.css"/>
    <style>
        .service-detail-page .page-title-wrap { margin-bottom: 0.75rem !important; }
        .service-detail-page .page-title { font-size: 1.125rem; }
        .service-detail-page .service-detail-stats .statistics-card {
            padding: 0.75rem 0.875rem;
            min-height: 0;
        }
        .service-detail-page .service-detail-stats .statistics-card h2 {
            font-size: 1.25rem;
            margin-bottom: 0.125rem;
        }
        .service-detail-page .service-detail-stats .statistics-card h3 {
            font-size: 0.75rem;
            margin: 0;
            line-height: 1.3;
        }
        .service-detail-page .service-detail-stats .statistics-card .absolute-img {
            width: 2.5rem;
            opacity: 0.35;
        }
        .service-detail-page .service-detail-card .card-body {
            padding: 1rem 1.125rem;
        }
        .service-detail-page .service-detail-hero {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            margin-bottom: 0.875rem;
            padding-bottom: 0.875rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .service-detail-page .service-detail-hero-thumb {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 0.5rem;
            object-fit: cover;
            flex-shrink: 0;
            background: var(--bs-tertiary-bg);
        }
        .service-detail-page .service-detail-hero-cover {
            width: 7.5rem;
            height: 4.5rem;
            border-radius: 0.5rem;
            object-fit: cover;
            flex-shrink: 0;
            background: var(--bs-tertiary-bg);
        }
        .service-detail-page .service-detail-hero-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.25rem;
            line-height: 1.35;
        }
        .service-detail-page .service-detail-hero-meta {
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
            margin-bottom: 0.25rem;
        }
        .service-detail-page .service-detail-hero-desc {
            font-size: 0.8125rem;
            color: var(--bs-body-color);
            margin: 0;
            line-height: 1.45;
        }
        .service-detail-page .nav--tabs .nav-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }
        .service-detail-page .nav--tabs__style2 {
            margin-bottom: 0.75rem;
        }
        .service-detail-page .service-long-description-html {
            font-size: 0.875rem;
            line-height: 1.55;
        }
        .service-detail-page .service-long-description-html img { max-width: 100%; height: auto; }
        .service-detail-page .service-long-description-html table { width: 100%; border-collapse: collapse; }
        .service-detail-page .service-long-description-html table td,
        .service-detail-page .service-long-description-html table th {
            border: 1px solid var(--bs-border-color);
            padding: 0.375rem 0.5rem;
            font-size: 0.8125rem;
        }
        .service-detail-page .service-detail-price-table table {
            font-size: 0.8125rem;
        }
        .service-detail-page .service-detail-price-table table th,
        .service-detail-page .service-detail-price-table table td {
            vertical-align: middle;
            padding: 0.375rem 0.5rem;
        }
        .service-detail-page .service-detail-price-table thead th {
            background: var(--bs-tertiary-bg);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .service-detail-page .service-detail-price-table img {
            width: 2rem;
            height: 2rem;
        }
        .service-detail-page #faq-tab-pane .card-body {
            padding: 1rem 1.125rem;
        }
        .service-detail-page #faq-tab-pane .service-detail-faq-compose {
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .service-detail-page #faq-tab-pane .service-detail-faq-compose-title {
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0 0 0.75rem;
            color: var(--bs-body-color);
        }
        .service-detail-page #faq-tab-pane #faq-form .form-floating {
            margin-bottom: 0.75rem;
        }
        .service-detail-page #faq-tab-pane #faq-form .form-floating > .form-control {
            border-radius: 0.5rem;
        }
        .service-detail-page #faq-tab-pane #faq-form .form-floating > textarea {
            min-height: 6.5rem;
        }
        .service-detail-page #faq-tab-pane #faq-submit-btn {
            min-width: 9.5rem;
            min-height: 2.75rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
        }
        .service-detail-page #faq-tab-pane #faq-submit-btn .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        .service-detail-page #faq-tab-pane .service-detail-faq-empty {
            text-align: center;
            padding: 2rem 1rem;
        }
        .service-detail-page #faq-tab-pane .service-detail-faq-empty img {
            max-width: 4.5rem;
            opacity: 0.45;
            margin-bottom: 0.75rem;
        }
        .service-detail-page #faq-tab-pane .service-detail-faq-empty p {
            font-size: 0.875rem;
            margin: 0;
        }
        .service-detail-page #faq-tab-pane .accordion.mb-30 {
            margin-bottom: 0.75rem !important;
        }
        .service-detail-page #faq-tab-pane .accordion-item {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.625rem !important;
            overflow: hidden;
            margin-bottom: 0;
            background: #fff;
        }
        .service-detail-page #faq-tab-pane .service-faq-item {
            margin-bottom: 0.625rem;
        }
        .service-detail-page #faq-tab-pane .service-faq-item.is-dragging {
            opacity: 0.55;
        }
        .service-detail-page #faq-tab-pane .service-faq-item.is-drag-over > .accordion-item {
            outline: 2px dashed var(--bs-primary);
            outline-offset: 1px;
        }
        .service-detail-page #faq-tab-pane .service-faq-drag-handle {
            flex-shrink: 0;
            color: var(--bs-secondary-color);
            cursor: grab;
            user-select: none;
            padding: 0.25rem;
            font-size: 1.25rem;
            line-height: 1;
        }
        .service-detail-page #faq-tab-pane .service-faq-drag-handle:active {
            cursor: grabbing;
        }
        .service-detail-page #faq-tab-pane .accordion-header {
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.5rem 0.25rem 0.375rem;
            background: transparent;
        }
        .service-detail-page #faq-tab-pane .accordion-header .accordion-button {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
            padding: 0.75rem 0.875rem 0.75rem 2.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: none;
            background: transparent;
            text-align: start;
        }
        .service-detail-page #faq-tab-pane .accordion-header .accordion-button::after {
            inset-inline-start: 0.625rem;
            margin: 0;
        }
        .service-detail-page #faq-tab-pane .accordion-header .accordion-button:not(.collapsed) {
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.04);
        }
        .service-detail-page #faq-tab-pane .accordion-body {
            padding: 0.75rem 0.875rem 1rem;
            font-size: 0.8125rem;
            color: var(--bs-secondary-color);
            line-height: 1.5;
            border-top: 1px solid var(--bs-border-color);
        }
        .service-detail-page #faq-tab-pane .accordion-header .btn-group {
            flex-shrink: 0;
            padding-right: 0.5rem;
        }
        .service-detail-page #faq-tab-pane .service-faq-edit-form {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.625rem;
            padding: 0.875rem;
            margin-bottom: 0.75rem;
            background: var(--bs-tertiary-bg);
        }
        .service-detail-page #faq-tab-pane .service-faq-edit-form .form-floating {
            margin-bottom: 0.75rem !important;
        }
        .service-detail-page #faq-tab-pane .service-faq-update {
            min-width: 8.5rem;
            min-height: 2.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .service-detail-page #review-tab-pane .card-body.p-30 {
            padding: 1rem 1.125rem !important;
        }
        .service-detail-page #review-tab-pane .rating-review__title {
            font-size: 1.5rem;
        }
        .service-detail-page #review-tab-pane .rating-review__out-of {
            font-size: 1.75rem;
        }
        .service-detail-page #review-tab-pane .col-lg-5.mb-30 {
            margin-bottom: 1rem !important;
        }
        .service-detail-page #review-tab-pane .card.mb-30 {
            margin-bottom: 1rem !important;
        }
        .service-detail-page #review-tab-pane .table {
            font-size: 0.8125rem;
        }
        .service-detail-page #review-tab-pane .table th,
        .service-detail-page #review-tab-pane .table td {
            padding: 0.5rem 0.625rem;
        }
        .service-detail-page .btn.btn-sm-compact {
            padding: 0.3125rem 0.75rem;
            font-size: 0.8125rem;
        }
        .service-detail-page .btn.btn-sm-compact .material-icons {
            font-size: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="main-content service-detail-page">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="page-title mb-0">{{translate('service_details')}}</h2>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @can('service_update')
                        <a href="{{route('admin.service.edit',[$service->id])}}"
                           class="btn btn--primary btn-sm-compact d-inline-flex align-items-center gap-1"
                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                            <span class="material-icons">border_color</span>
                            {{translate('edit')}}
                        </a>
                    @endcan
                    <a href="{{ route('admin.service.index') }}"
                       class="btn btn--secondary btn-sm-compact d-inline-flex align-items-center gap-1"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        <span class="material-icons">arrow_back</span>
                        {{ translate('Back_to_Service_List') }}
                    </a>
                </div>
            </div>

            <div class="row g-2 mb-3 service-detail-stats">
                <div class="col-md-4 col-sm-4">
                    <div class="statistics-card statistics-card__total-orders">
                        <h2>{{$service->bookings_count}}</h2>
                        <h3>{{translate('total_bookings')}}</h3>
                        <img src="{{asset('assets/admin-module/img/icons/total-orders.png')}}"
                             class="absolute-img" alt="{{ translate('total-orders') }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="statistics-card statistics-card__ongoing">
                        <h2>{{$service['ongoing_count']??0}}</h2>
                        <h3>{{translate('ongoing')}}</h3>
                        <img src="{{asset('assets/admin-module/img/icons/ongoing.png')}}"
                             class="absolute-img" alt="{{ translate('ongoing-orders') }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="statistics-card statistics-card__canceled">
                        <h2>{{$service['canceled_count']??0}}</h2>
                        <h3>{{translate('canceled')}}</h3>
                        <img src="{{asset('assets/admin-module/img/icons/canceled.png')}}"
                             class="absolute-img" alt="{{ translate('canceled-orders') }}">
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <button class="nav-link {{!isset($webPage) || $webPage=='general'?'active':''}}"
                                data-bs-toggle="tab"
                                data-bs-target="#general-tab-pane">{{translate('general_info')}}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{isset($webPage) && $webPage=='faq'?'active':''}}" data-bs-toggle="tab"
                                data-bs-target="#faq-tab-pane">{{translate('faq')}}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{isset($webPage) && $webPage=='review'?'active':''}}"
                                data-bs-toggle="tab"
                                data-bs-target="#review-tab-pane">{{translate('reviews')}}
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade {{!isset($webPage) || $webPage=='general'?'show active':''}}"
                     id="general-tab-pane">
                    <div class="card service-detail-card">
                        <div class="card-body">
                            <div class="service-detail-hero">
                                <img class="service-detail-hero-thumb d-none d-sm-block"
                                     src="{{ $service->thumbnail_full_path }}"
                                     alt="{{ $service->name }}">
                                <img class="service-detail-hero-cover d-sm-none"
                                     src="{{ $service->cover_image_full_path }}"
                                     alt="{{ $service->name }}">
                                <div class="min-w-0 flex-grow-1">
                                    <h3 class="service-detail-hero-title c1">{{ $service->name }}</h3>
                                    <div class="service-detail-hero-meta">
                                        @if($service?->category)
                                            {{ translate('category') }}: {{ $service->category->name ?? translate('Unavailable') }}
                                        @endif
                                        @if($service?->subCategory)
                                            @if($service?->category) · @endif
                                            {{ translate('sub-category') }}: {{ $service->subCategory->name ?? translate('Unavailable') }}
                                        @endif
                                    </div>
                                    @if($service->short_description)
                                        <p class="service-detail-hero-desc">{{ $service->short_description }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-2">
                                <ul class="nav nav--tabs">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab"
                                                data-bs-target="#long-description-tab-pane">{{translate('details')}}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab"
                                                data-bs-target="#price-table-tab-pane">{{translate('price_table')}}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="long-description-tab-pane">
                                    <div class="service-long-description-html">
                                        {!! $service->description !!}
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="price-table-tab-pane">
                                    @include('servicemanagement::admin.partials._service-price-table', ['service' => $service])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade {{isset($webPage) && $webPage=='faq'?'show active':''}}" id="faq-tab-pane">
                    <div class="card service-detail-card mb-3">
                        <div class="card-body">
                            <div class="service-detail-faq-compose">
                                <h6 class="service-detail-faq-compose-title">{{ translate('add_faq') }}</h6>
                                <form action="javascript:void(0)" method="POST" id="faq-form" novalidate>
                                    @csrf
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="faq-question-input"
                                               placeholder="{{translate('question')}}"
                                               name="question" required maxlength="500" autocomplete="off">
                                        <label for="faq-question-input">{{translate('question')}}</label>
                                    </div>
                                    <div class="form-floating">
                                        <textarea class="form-control" id="faq-answer-input"
                                                  placeholder="{{translate('answer')}}" name="answer"
                                                  required></textarea>
                                        <label for="faq-answer-input">{{translate('answer')}}</label>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn--primary" id="faq-submit-btn"
                                                data-label-idle="{{ translate('add_faq') }}"
                                                data-label-loading="{{ translate('Loading') }}...">
                                            <span class="faq-submit-label">{{ translate('add_faq') }}</span>
                                            <span class="spinner-border text-light d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div id="faq-list"
                                 data-service-id="{{ $service->id }}"
                                 data-reorder-url="{{ route('admin.faq.reorder', $service->id) }}">
                                @include('servicemanagement::admin.partials._faq-list',['faqs'=>$faqs])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade {{isset($webPage) && $webPage=='review'?'show active':''}}" id="review-tab-pane">

                    @if($reviews->total() > 0)
                        <div class="card service-detail-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center g-3">
                                    <div class="col-lg-4 d-flex justify-content-center">
                                        <div class="rating-review">
                                            <h2 class="rating-review__title">
                                                <span class="rating-review__out-of">{{$service->avg_rating}}</span>/5
                                            </h2>
                                            <div class="rating">
                                            <span
                                                class="{{$service->avg_rating>=1?'material-icons':'material-symbols-outlined'}}">{{$service->avg_rating>=1?'star':'grade'}}</span>
                                                <span
                                                    class="{{$service->avg_rating>=2?'material-icons':'material-symbols-outlined'}}">{{$service->avg_rating>=2?'star':'grade'}}</span>
                                                <span
                                                    class="{{$service->avg_rating>=3?'material-icons':'material-symbols-outlined'}}">{{$service->avg_rating>=3?'star':'grade'}}</span>
                                                <span
                                                    class="{{$service->avg_rating>=4?'material-icons':'material-symbols-outlined'}}">{{$service->avg_rating>=4?'star':'grade'}}</span>
                                                <span
                                                    class="{{$service->avg_rating>=5?'material-icons':'material-symbols-outlined'}}">{{$service->avg_rating>=5?'star':'grade'}}</span>
                                            </div>
                                            <div class="rating-review__info d-flex flex-wrap gap-3">
                                                @php($total_review_count = $service->reviews->where('is_active', 1)->whereNotNull('review_rating')->whereNotNull('review_comment')->count())
                                                @php($totalReviews = $service->reviews->where('is_active', 1)->whereNotNull('review_rating')->count())
                                                <span>{{ $totalReviews }} {{ translate('ratings') }}</span>
                                                <span>{{$total_review_count}} {{translate('reviews')}}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-8">
                                        <ul class="common-list common-list__style2 after-none gap-10">
                                            <li>
                                                <span class="review-name">{{translate('excellent')}}</span>
                                                @php($excellent_count=$service->reviews->where('is_active', 1)->where('review_rating',5)->count())
                                                @php($excellent=(divnum($excellent_count,$total_review_count))*100)
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: {{$excellent}}%"
                                                         aria-valuenow="{{$excellent}}" aria-valuemin="0"
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="review-count">{{$excellent_count}}</span>
                                            </li>
                                            <li>
                                                <span class="review-name">{{translate('good')}}</span>
                                                @php($good_count=$service->reviews->where('is_active', 1)->where('review_rating',4)->count())
                                                @php($good=(divnum($good_count,$total_review_count))*100)
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: {{$good}}%"
                                                         aria-valuenow="{{$good}}" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="review-count">{{$good_count}}</span>
                                            </li>
                                            <li>
                                                <span class="review-name">{{translate('avarage')}}</span>
                                                @php($average_count=$service->reviews->where('is_active', 1)->where('review_rating',3)->count())
                                                @php($average=(divnum($average_count,$total_review_count))*100)
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: {{$average}}%"
                                                         aria-valuenow="{{$average}}" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="review-count">{{$average_count}}</span>
                                            </li>
                                            <li>
                                                <span class="review-name">{{translate('below_avarage')}}</span>
                                                @php($below_average_count=$service->reviews->where('is_active', 1)->where('review_rating',2)->count())
                                                @php($below_average=(divnum($below_average_count,$total_review_count))*100)
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: {{$below_average}}%"
                                                         aria-valuenow="{{$below_average}}" aria-valuemin="0"
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="review-count">{{$below_average_count}}</span>
                                            </li>
                                            <li>
                                                <span class="review-name">{{translate('poor')}}</span>
                                                @php($poor_count=$service->reviews->where('is_active', 1)->where('review_rating',1)->count())
                                                @php($poor=(divnum($poor_count,$total_review_count))*100)
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: {{$poor}}%"
                                                         aria-valuenow="{{$poor}}" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="review-count">{{$poor_count}}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-end border-bottom pb-2 mb-2">
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('total_reviews')}}:</span>
                            <span class="title-color">{{$reviews->total()}}</span>
                        </div>
                    </div>

                    <div class="card service-detail-card">
                        <div class="card-body py-2 px-3">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <div class="title-here"></div>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <form action="{{url()->current()}}" class="d-flex align-items-center gap-0 border rounded" method="POST">
                                        @csrf
                                        <input type="search" class="theme-input-style border-0 rounded block-size-36" name="review_search" value="{{$search}}" placeholder="{{translate('search_review_id')}}">
                                        <button type="submit" class="bg-light border-0 px-2 block-size-36 rounded-end d-flex align-items-center justify-content-center">
                                            <span class="material-symbols-outlined fz-20 opacity-75">
                                                search
                                            </span>
                                        </button>
                                    </form>
                                    <div class="dropdown">
                                        <button type="button"
                                                class="btn btn--secondary rounded text-capitalize dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                            <span class="material-icons">file_download</span> {{translate('download')}}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                            <li><a class="dropdown-item"
                                                   href="{{route('admin.service.reviews.download',['review_search'=>$search, 'service_id' => request()->id])}}">{{translate('excel')}}</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
                                    <thead class="text-capitalize">
                                        <tr>
                                            <th>{{translate('SL')}}</th>
                                            <th class="text-nowrap">{{translate('Review ID')}}</th>
                                            <th>{{translate('reviewer')}}</th>
                                            <th>{{translate('date')}}</th>
                                            <th>{{translate('ratings')}}</th>
                                            <th>{{translate('reviews')}}</th>
                                            <th>{{translate('reply')}}</th>
                                            <th>{{translate('status')}}</th>
                                            <th>{{translate('action')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($reviews as $key => $review)
                                        <tr>
                                            <td>{{$key+$reviews?->firstItem()}}</td>
                                            <td>{{ $review->readable_id == 0 ? 'N/A' : $review->readable_id }}</td>
                                            <td>
                                                @if(isset($review->customer))
                                                    <span>{{$review->customer->first_name . ' ' .$review->customer->last_name}}</span><br>
                                                    <span>{{ translate('Booking ID #') . $review?->booking?->readable_id }}</span>
                                                @else
                                                    <span class="opacity-50">{{translate('Customer_not_available')}}</span>
                                                @endif
                                            </td>
                                            <td>{{$review->created_at}}</td>
                                            <td>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" viewBox="0 0 14 15" fill="none">
                                                    <path d="M7 1.81445L8.854 5.76398L13 6.4012L10 9.47376L10.708 13.8145L7 11.764L3.292 13.8145L4 9.47376L1 6.4012L5.146 5.76398L7 1.81445Z" fill="#FFB900" stroke="#FFB900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                {{$review->review_rating}}
                                            </td>
                                            <td data-bs-custom-class="review-tooltip" data-bs-toggle="tooltip" title="{{$review->review_comment}}">{{ Str::limit($review->review_comment, 100) ?? translate('No review yet') }}</td>
                                            <td data-bs-custom-class="review-tooltip" data-bs-toggle="tooltip" title="{{$review->reviewReply?->reply}}">{{ Str::limit($review->reviewReply?->reply, 100) ?? translate('No reply yet') }}</td>
                                            <td>
                                                @if($review->review_rating > 0)
                                                <label class="switcher">
                                                    <input class="switcher_input route-alert"
                                                           data-route="{{ route('admin.service.review-status-update', $review->id) }}"
                                                           data-message="{{translate('want_to_update_status')}}"
                                                           type="checkbox" {{ $review->is_active ? 'checked' : '' }}>
                                                    <span class="switcher_control"></span>
                                                </label>
                                                @endif
                                            </td>
                                            <td>
                                                @if($review->review_rating > 0)
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <button class="action-btn btn--light-primary fw-medium text-capitalize fz-14" data-bs-toggle="modal" id="replyModalBtn"
                                                            data-bs-target="#replyModal"
                                                            data-booking_id ="{{$review->booking->readable_id}}"
                                                            data-readable_id ="{{$review->readable_id}}"
                                                            data-service_name="{{$review->service->name}}"
                                                            data-service_img="{{$review->service->cover_image_full_path}}"
                                                            data-review="{{$review->review_comment ?? translate('No review yet')}}"
                                                            data-review_reply="{{$review->reviewReply?->reply ?? translate('No reply yet')}}"
                                                            data-variant_key="{{ $review->booking?->detail[0]?->variant_key }}">
                                                        <span class="material-icons">visibility</span>
                                                    </button>
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                    <tr>
                                        <td colspan="12">
                                            <div class="review-empty-state py-5">
                                                <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2">
                                                    <img src="{{asset('assets/admin-module/img/review-empty-state.svg')}}" alt="No data">
                                                    <h5 class="m-0 text-muted opacity-50">{{translate('You don’t have any reviews yet.')}}</h5>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                {!! $reviews->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="p-3 pt-0">
                        <div class="d-flex gap-3">
                            <img src="" class="rounded aspect-square object-fit-cover" width="80" alt="Service Image">
                            <div class="w-0 flex-grow-1">
                                <div class="mb-2">
                                    <span>{{translate('Booking ID #')}}</span> <label class="booking_id"></label>
                                </div>
                                <h5 class="service_name"></h5>
                                <div class="mt-2">
                                    <span class="variant_key"></span>
                                </div>
                            </div>
                        </div>
                        <div class="review_section mb-3 mt-3">
                            <h4 class="mb-2">{{translate('Review')}}</h4>
                            <div class="p-3 rounded bg--secondary">
                                <p class="review_content"></p>
                            </div>
                        </div>
                        <div class="reply_section">
                            <div>
                                <h4 class="mb-3">{{translate('Reply')}}</h4>
                                <div class="form-group">
                                    <textarea id="reply_content" class="form-control" name="reply_content" rows="4" readonly disabled></textarea>
                                    <input type="hidden" class="form-control" name="readable_id" value="">
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
    <script>
        "use strict"

        document.addEventListener('DOMContentLoaded', function () {
            var clickableRows = document.querySelectorAll('.clickable-row');
            clickableRows.forEach(function (row) {
                row.addEventListener('click', function () {
                    var target = row.getAttribute('data-target');
                    var collapseElement = document.querySelector(target);
                    collapseElement.classList.toggle('show');
                });
            });
        });


        $('#replyModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const modal = $(this);
            const serviceImg = button.data('service_img');
            const serviceName = button.data('service_name');
            const bookingID = button.data('booking_id');
            const readableID = button.data('readable_id');
            const review = button.data('review');
            const reviewReply = button.data('review_reply');
            const variantKey = button.data('variant_key');
            const action = button.data('action');

            modal.find('.service_name').text(serviceName);
            modal.find('.variant_key').text(variantKey);
            modal.find('.booking_id').text(bookingID);
            modal.find('.review_content').text(review);
            modal.find('img').attr('src', serviceImg);

            modal.find('textarea[name=reply_content]').val(reviewReply);
            modal.find('input[name=readable_id]').val(readableID);
            modal.find('form').attr('action',action);
        });

        let faqSubmitting = false;
        let faqDragItem = null;
        let faqReorderSaving = false;

        function getFaqReorderUrl() {
            return $('#faq-list').data('reorder-url') || '';
        }

        function collectFaqOrder() {
            return $('#faqAccordionList .service-faq-item').map(function () {
                return $(this).data('faq-id');
            }).get().filter(Boolean);
        }

        function saveFaqOrder() {
            const url = getFaqReorderUrl();
            const order = collectFaqOrder();
            if (!url || order.length < 1 || faqReorderSaving) {
                return;
            }

            faqReorderSaving = true;
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: url,
                data: { order: order },
                success: function () {
                    toastr.success('{{ translate('successfully_updated') }}');
                },
                error: function () {
                    toastr.error('{{ translate('something_went_wrong') }}');
                },
                complete: function () {
                    faqReorderSaving = false;
                }
            });
        }

        function initFaqSortable() {
            const list = document.getElementById('faqAccordionList');
            if (!list) {
                return;
            }
            list.dataset.faqSortInit = '1';

            list.querySelectorAll('.service-faq-drag-handle').forEach(function (handle) {
                if (handle.dataset.faqDragInit === '1') {
                    return;
                }
                handle.dataset.faqDragInit = '1';

                handle.addEventListener('dragstart', function (e) {
                    faqDragItem = handle.closest('.service-faq-item');
                    if (!faqDragItem) {
                        return;
                    }
                    faqDragItem.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    try {
                        e.dataTransfer.setData('text/plain', faqDragItem.dataset.faqId || '');
                    } catch (err) {}
                    e.stopPropagation();
                });

                handle.addEventListener('dragend', function () {
                    if (faqDragItem) {
                        faqDragItem.classList.remove('is-dragging');
                    }
                    list.querySelectorAll('.service-faq-item.is-drag-over').forEach(function (el) {
                        el.classList.remove('is-drag-over');
                    });
                    faqDragItem = null;
                    saveFaqOrder();
                });

                handle.addEventListener('mousedown', function (e) {
                    e.stopPropagation();
                });
                handle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            if (list.dataset.faqListDragInit === '1') {
                return;
            }
            list.dataset.faqListDragInit = '1';

            list.addEventListener('dragover', function (e) {
                e.preventDefault();
                const target = e.target.closest('.service-faq-item');
                if (!faqDragItem || !target || target === faqDragItem || !list.contains(target)) {
                    return;
                }

                list.querySelectorAll('.service-faq-item.is-drag-over').forEach(function (el) {
                    if (el !== target) {
                        el.classList.remove('is-drag-over');
                    }
                });
                target.classList.add('is-drag-over');

                const rect = target.getBoundingClientRect();
                const before = (e.clientY - rect.top) < (rect.height / 2);
                if (before) {
                    list.insertBefore(faqDragItem, target);
                } else {
                    list.insertBefore(faqDragItem, target.nextSibling);
                }
            });

            list.addEventListener('drop', function (e) {
                e.preventDefault();
                list.querySelectorAll('.service-faq-item.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
            });
        }

        function setFaqSubmitLoading(isLoading) {
            const $btn = $('#faq-submit-btn');
            const idleLabel = $btn.data('label-idle') || '{{ translate('add_faq') }}';
            const loadingLabel = $btn.data('label-loading') || '{{ translate('Loading') }}...';

            faqSubmitting = isLoading;
            $btn.prop('disabled', isLoading);
            $btn.find('.faq-submit-label').text(isLoading ? loadingLabel : idleLabel);
            $btn.find('.spinner-border').toggleClass('d-none', !isLoading);
            $('#faq-form').find('input[name="question"], textarea[name="answer"]').prop('disabled', isLoading);
        }

        $('#faq-form').on('submit', function (e) {
            e.preventDefault();

            const form = this;
            const question = (form.question.value || '').trim();
            const answer = (form.answer.value || '').trim();

            if (!question || !answer) {
                form.reportValidity();
                toastr.error('{{ translate('Please_complete_all_required_fields_before_proceeding') }}');
                return;
            }

            if (faqSubmitting) {
                return;
            }

            form.question.value = question;
            form.answer.value = answer;
            setFaqSubmitLoading(true);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = new FormData();
            data.append('_token', $('meta[name="csrf-token"]').attr('content') || form._token?.value || '');
            data.append('question', question);
            data.append('answer', answer);

            $.post({
                url: '{{route('admin.faq.store',[$service->id])}}',
                data: data,
                processData: false,
                contentType: false,
                cache: false,
                timeout: 800000,
                success: function (response) {
                    $('#faq-list').empty().html(response.template);
                    form.reset();
                    toastr.success('{{translate('successfully_added')}}');
                    initFaqSortable();
                },
                error: function () {
                    toastr.error('{{ translate('something_went_wrong') }}');
                },
                complete: function () {
                    setFaqSubmitLoading(false);
                }
            });
        });

        $('#faq-list').on('click', '.service-faq-update', function () {
            let id = $(this).data('id');
            ajax_post(id, this);
        });

        function ajax_post(form_id, triggerBtn) {
            "use strict";

            const $btn = $(triggerBtn);
            if ($btn.data('busy')) {
                return;
            }

            const form = $('#' + form_id)[0];
            if (!form) {
                return;
            }

            const question = (form.question?.value || '').trim();
            const answer = (form.answer?.value || '').trim();
            if (!question || !answer) {
                form.reportValidity();
                toastr.error('{{ translate('Please_complete_all_required_fields_before_proceeding') }}');
                return;
            }

            form.question.value = question;
            form.answer.value = answer;

            $btn.data('busy', true).prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>{{ translate('Loading') }}...');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: $('#' + form_id).attr('action'),
                data: new FormData(form),
                processData: false,
                contentType: false,
                cache: false,
                timeout: 800000,
                success: function (response) {
                    $('#faq-list').empty().html(response.template);
                    toastr.success('{{translate('successfully_updated')}}');
                    initFaqSortable();
                },
                error: function () {
                    toastr.error('{{ translate('something_went_wrong') }}');
                    $btn.data('busy', false).prop('disabled', false).html(originalHtml);
                }
            });
        }

        $('#faq-list').on('click', '.faq-list-ajax-delete', function () {
            let route = $(this).data('route');
            ajax_delete(route)
        });

        $('#faq-list').on('click', '.show-service-edit-section', function () {
            let id = $(this).data('id');
            $(`#edit-${id}`).toggle();
        });

        function ajax_delete(route) {
            "use strict";

            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: '{{translate('want_to_delete_this_faq')}}',
                type: 'warning',
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
                            $('#faq-list').empty().html(response.template);
                            toastr.success('{{translate('successfully_deleted')}}');
                            initFaqSortable();
                        },
                        complete: function () {
                        },
                    });
                }
            })
        }

        $('#faq-list').on('click', '.service-ajax-status-update', function () {
            let route = $(this).data('route');
            let id = $(this).data('id');
            ajax_status_update(route, id)
        });

        function ajax_status_update(route, id) {
            "use strict";
            Swal.fire({
                title: "{{translate('are_you_sure')}}?",
                text: '{{translate('want_to_update_status_of_this_faq')}}',
                type: 'warning',
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
                            toastr.success('{{translate('successfully_updated')}}');
                        },
                        complete: function () {
                        },
                    });
                }
            })
        }

        initFaqSortable();
    </script>

@endpush
