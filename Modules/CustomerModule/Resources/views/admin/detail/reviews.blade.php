@extends('adminmodule::layouts.master')

@section('title',translate('Reviews'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-4">
                @include('customermodule::admin.detail.partials.page-header', ['customer' => $customer])
            </div>

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'reviews'])

            @php
                $totalReviews = ($reviewTab ?? 'received') === 'given'
                    ? $customer->reviews()->where('is_active', 1)->count()
                    : $customer->receivedProviderReviews()->where('is_active', 1)->count();
                $averageRating = ($reviewTab ?? 'received') === 'given'
                    ? round((float) $customer->reviews()->where('is_active', 1)->avg('review_rating'), 2)
                    : ($customer->received_avg_rating ?? 0);
            @endphp

            <ul class="nav nav--tabs nav--tabs__style2 mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ ($reviewTab ?? 'received') === 'received' ? 'active' : '' }}"
                       href="{{ url()->current() }}?web_page=reviews&review_tab=received">
                        {{ translate('Reviews_Received') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($reviewTab ?? 'received') === 'given' ? 'active' : '' }}"
                       href="{{ url()->current() }}?web_page=reviews&review_tab=given">
                        {{ translate('Reviews_Given') }}
                    </a>
                </li>
            </ul>

            <div class="card mb-30">
                <div class="card-body p-30">
                    <div class="row align-items-center">
                        <div class="col-lg-5 mb-30 mb-lg-0 d-flex justify-content-center">
                            <div class="rating-review">
                                <h2 class="rating-review__title">
                                    <span class="rating-review__out-of">{{ $averageRating }}</span>/5
                                </h2>
                                <div class="rating-review__info d-flex flex-wrap gap-3">
                                    <span>{{ $totalReviews }} {{ translate('approved_reviews') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <p class="text-muted mb-0">
                                @if(($reviewTab ?? 'received') === 'given')
                                    {{ translate('Customer_to_provider_reviews_help') }}
                                @else
                                    {{ translate('Provider_to_customer_reviews_help') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="boookings-tab-pane">
                    <div class="d-flex justify-content-end border-bottom mb-10">
                        <div class="d-flex gap-2 fw-medium me-4">
                            <span class="opacity-75">{{translate('Total_Reviews')}}:</span>
                            <span class="title-color">{{$reviews->total()}}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{ url()->current() }}?web_page=reviews&review_tab={{ $reviewTab ?? 'received' }}"
                                      class="search-form search-form_style-two"
                                      method="POST">
                                    @csrf
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{$search??''}}" name="search"
                                               placeholder="{{translate('search_here')}}">
                                    </div>
                                    <button type="submit" class="btn btn--primary">
                                        {{translate('search')}}
                                    </button>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
                                    <thead class="align-middle text-nowrap">
                                    <tr>
                                        <th>{{translate('Review ID')}}</th>
                                        <th>{{translate('Booking_ID')}}</th>
                                        <th>{{translate('provider')}}</th>
                                        <th>{{translate('Booking_Date')}}</th>
                                        <th>{{translate('Ratings')}}</th>
                                        <th class="text-nowrap">{{translate('status')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($reviews as $review)
                                        <tr>
                                            <td>{{ $review->readable_id ?? 'N/A' }}</td>
                                            <td>
                                                @if($review->booking)
                                                    <a href="{{ route('admin.booking.details', [$review->booking->id, 'web_page' => 'details']) }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer">
                                                        {{ $review->booking->readable_id }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                            @if($review->provider)
                                                <a href="{{ route('admin.provider.details', [$review->provider->id, 'web_page' => 'overview']) }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer">
                                                    {{ $review->provider->company_name }}
                                                </a>
                                            @else
                                                {{ translate('Provider_not_found') }}
                                            @endif
                                            </td>
                                            <td>{{ $review->booking ? date('d-M-y H:iA', strtotime($review->booking->created_at)) : 'N/A' }}</td>
                                            <td>{{ $review->review_rating }}</td>
                                            <td class="text-nowrap">
                                                @if($review->review_rating > 0)
                                                    @include('reviewmodule::admin.partials._review-actions', [
                                                        'isApproved' => (bool) $review->is_active,
                                                        'approveRoute' => ($reviewTab ?? 'received') === 'given'
                                                            ? route('admin.service.review-approve', $review->id)
                                                            : route('admin.customer.customer-review-approve', $review->id),
                                                        'deleteRoute' => ($reviewTab ?? 'received') === 'given'
                                                            ? route('admin.service.review-delete', $review->id)
                                                            : route('admin.customer.customer-review-delete', $review->id),
                                                    ])
                                                @endif
                                            </td>
                                        </tr>
                                        @include('reviewmodule::admin.partials._review-description-row', [
                                            'colspan' => 6,
                                            'description' => $review->review_comment,
                                        ])
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
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
@endsection
