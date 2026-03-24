@extends('adminmodule::layouts.master')

@section('title',translate('Reviews'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-4">
                @php
                    $customerDisplayName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    $customerDisplayName = $customerDisplayName !== '' ? $customerDisplayName : ($customer->email ?? translate('Customer'));
                    $customerStatus = (string) ($customer->manual_performance_status ?? 'active');
                    $customerStatusLabel = match($customerStatus) {
                        'blacklisted' => translate('Blacklisted'),
                        'suspended' => translate('Suspended'),
                        default => translate('Active'),
                    };
                    $customerStatusClass = match($customerStatus) {
                        'blacklisted' => 'bg-danger',
                        'suspended' => 'bg-warning text-dark',
                        default => 'bg-success',
                    };
                @endphp
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="page-title mb-2">{{ $customerDisplayName }}</h2>
                        <div>{{translate('Joined_on')}} {{date('d-M-y H:iA', strtotime($customer?->created_at))}}</div>
                    </div>
                    <span class="badge {{ $customerStatusClass }}">{{ $customerStatusLabel }}</span>
                </div>
            </div>

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'reviews'])

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
                                <form action="{{url()->current()}}?web_page=reviews"
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
                                        <th>{{translate('Booking_ID')}}</th>
                                        <th>{{translate('Booking_Date')}}</th>
                                        <th>{{translate('Ratings')}}</th>
                                        <th>{{translate('Reviews')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($reviews as $review)
                                        <tr>
                                            <td>
                                                <a href="{{route('admin.booking.details', [$review->booking->id,'web_page'=>'details'])}}">{{$review->booking->readable_id}}</a>
                                            </td>
                                            <td>{{date('d-M-y H:iA', strtotime($review->booking->created_at))}}</td>
                                            <td>{{$review->review_rating}}</td>
                                            <td>{{Str::limit($review->review_comment, 100)}}</td>
                                        </tr>
                                    @endforeach
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

