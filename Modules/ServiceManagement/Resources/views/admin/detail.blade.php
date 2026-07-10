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
        .service-detail-page #reviews-tab-pane .card-body.p-30 {
            padding: 1rem 1.125rem !important;
        }
        .service-detail-page #reviews-tab-pane .rating-review__title {
            font-size: 1.5rem;
        }
        .service-detail-page #reviews-tab-pane .rating-review__out-of {
            font-size: 1.75rem;
        }
        .service-detail-page #reviews-tab-pane .col-lg-5.mb-30 {
            margin-bottom: 1rem !important;
        }
        .service-detail-page #reviews-tab-pane .card.mb-30 {
            margin-bottom: 1rem !important;
        }
        .service-detail-page #reviews-tab-pane .table {
            font-size: 0.8125rem;
        }
        .service-detail-page #reviews-tab-pane .table th,
        .service-detail-page #reviews-tab-pane .table td {
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
                        <button class="nav-link {{!isset($webPage) || $webPage=='overview'?'active':''}}"
                                data-bs-toggle="tab"
                                data-bs-target="#overview-tab-pane">{{translate('overview')}}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{isset($webPage) && $webPage=='reviews'?'active':''}}"
                                data-bs-toggle="tab"
                                data-bs-target="#reviews-tab-pane">{{translate('reviews')}}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{isset($webPage) && $webPage=='preview'?'active':''}}" data-bs-toggle="tab"
                                data-bs-target="#preview-tab-pane">{{translate('mobile_preview')}}
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade {{!isset($webPage) || $webPage=='overview'?'show active':''}}"
                     id="overview-tab-pane">
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

                            @include('servicemanagement::admin.partials._service-overview-styles')

                            @if(!empty($resolvedOverviewContent))
                                <div class="mb-4">
                                    <h6 class="mb-2 fw-semibold">{{ translate('service_overview_sections') }}</h6>
                                    @include('servicemanagement::admin.partials._service-overview-sections', [
                                        'resolvedOverviewContent' => $resolvedOverviewContent,
                                        'layout' => 'readonly',
                                    ])
                                </div>
                            @endif

                            @if(!empty(trim(strip_tags((string) $service->description))))
                                <div class="{{ !empty($resolvedOverviewContent) ? 'pt-3 border-top' : '' }}">
                                    @if(!empty($resolvedOverviewContent))
                                        <h6 class="mb-2 fw-semibold">{{ translate('long_Description') }}</h6>
                                    @endif
                                    <div class="service-long-description-html">
                                        {!! $service->description !!}
                                    </div>
                                </div>
                            @elseif(empty($resolvedOverviewContent))
                                <div class="service-detail-overview-empty">
                                    {{ translate('no_service_details_added_yet') }}
                                </div>
                            @endif

                            <div class="pt-4 mt-4 border-top">
                                <h6 class="mb-3 fw-semibold">{{ translate('price_table') }}</h6>
                                @include('servicemanagement::admin.partials._service-price-table', ['service' => $service])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade {{isset($webPage) && $webPage=='reviews'?'show active':''}}" id="reviews-tab-pane">

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
                <div class="tab-pane fade {{isset($webPage) && $webPage=='preview'?'show active':''}}" id="preview-tab-pane">
                    <div class="card service-detail-card border-0 shadow-none bg-transparent">
                        <div class="card-body p-0">
                            @include('servicemanagement::admin.partials._service-detail-inline-mobile-preview', [
                                'servicePreviewPayload' => $servicePreviewPayload ?? [],
                            ])
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

    <div class="d-none" aria-hidden="true">
        @include('servicemanagement::admin.partials._service-mobile-preview-modal', [
            'service' => $service,
            'previewCurrencySymbol' => $servicePreviewPayload['currencySymbol'] ?? null,
        ])
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
    </script>
    <script src="{{ asset('assets/admin-module/js/service-mobile-preview.js') }}?v={{ $adminAssetVersion ?? time() }}"></script>

@endpush
