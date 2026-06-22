@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Review'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Review') }}</h2>
                <p class="text-muted mb-0">{{ translate('Booking_review_pending_help') }}</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{ translate('Pending_Reviews') }}:</span>
                            <span class="title-color">{{ $pendingCount }}</span>
                        </div>

                        <form action="{{ route('admin.booking.reviews.list') }}"
                              class="search-form search-form_style-two"
                              method="GET">
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon">
                                    <span class="material-icons">search</span>
                                </span>
                                <input type="search"
                                       class="theme-input-style search-form__input"
                                       value="{{ $search ?? '' }}"
                                       name="search"
                                       placeholder="{{ translate('search_here') }}">
                            </div>
                            <button type="submit" class="btn btn--primary">
                                {{ translate('search') }}
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="align-middle text-nowrap">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Review_Type') }}</th>
                                <th>{{ translate('Given_By') }}</th>
                                <th>{{ translate('Given_To') }}</th>
                                <th>{{ translate('Booking_ID') }}</th>
                                <th>{{ translate('Ratings') }}</th>
                                <th>{{ translate('Submitted_At') }}</th>
                                <th class="text-nowrap">{{ translate('status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($reviews as $key => $review)
                                <tr>
                                    <td>{{ $key + $reviews->firstItem() }}</td>
                                    <td>{{ $review['review_type_label'] }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 align-items-center">
                                            @if(!empty($review['given_by']['profile_url']))
                                                <a href="{{ $review['given_by']['profile_url'] }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="c1 fw-medium">
                                                    {{ $review['given_by']['name'] }}
                                                </a>
                                            @else
                                                <span>{{ $review['given_by']['name'] }}</span>
                                            @endif
                                            <span class="badge-pill badge-info p-1 rounded fz-12">
                                                {{ $review['given_by']['role_label'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 align-items-center">
                                            @if(!empty($review['given_to']['profile_url']))
                                                <a href="{{ $review['given_to']['profile_url'] }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="c1 fw-medium">
                                                    {{ $review['given_to']['name'] }}
                                                </a>
                                            @else
                                                <span>{{ $review['given_to']['name'] }}</span>
                                            @endif
                                            <span class="badge-pill badge-info p-1 rounded fz-12">
                                                {{ $review['given_to']['role_label'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if(!empty($review['booking_uuid']))
                                            <a href="{{ route('admin.booking.details', [$review['booking_uuid'], 'web_page' => 'details']) }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="c1 fw-medium">
                                                {{ $review['booking_id'] }}
                                            </a>
                                        @else
                                            {{ $review['booking_id'] }}
                                        @endif
                                    </td>
                                    <td>{{ $review['rating'] }}</td>
                                    <td>{{ $review['created_at'] ? date('d-M-y H:iA', strtotime($review['created_at'])) : 'N/A' }}</td>
                                    <td class="text-nowrap">
                                        @if(($review['rating'] ?? 0) > 0)
                                            @include('reviewmodule::admin.partials._review-actions', [
                                                'isApproved' => $review['is_active'] ?? false,
                                                'approveRoute' => $review['approve_route'] ?? null,
                                                'deleteRoute' => $review['delete_route'] ?? null,
                                            ])
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td colspan="8" class="pt-0 pb-3">
                                        <span class="text-muted small fw-medium">{{ translate('Description') }}:</span>
                                        <span class="text-muted small">
                                            {{ $review['description'] ?: translate('No review yet') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        {{ translate('no_data_found') }}
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
@endsection
