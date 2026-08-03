@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Comments'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/chatting-module/css/staff-chat-entity-badges.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-comments-styles')
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }}</h2>
            </div>

            <div class="row">
                @php
                    $__detailStatusClass = booking_admin_status_css_class($booking);
                @endphp
                <div class="col-12 booking-detail-v2 booking-detail-v2--{{ $__detailStatusClass }}">
                    <div class="booking-detail-v2__wrap">
                        @include('bookingmodule::admin.booking.partials._booking-detail-compact-topbar', ['booking' => $booking])
                        @include('bookingmodule::admin.booking.partials._booking-detail-pipeline', ['booking' => $booking])
                        @include('bookingmodule::admin.booking.partials._booking-detail-subpage-header', ['booking' => $booking])

                        @include('bookingmodule::admin.booking.partials.details._special-financial-settlement-banner', ['booking' => $booking])

                        <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-3 booking-detail-nav-wrap">
                            @include('bookingmodule::admin.booking.partials._booking-detail-nav-tabs', [
                                'booking' => $booking,
                                'webPage' => $webPage,
                                'commentCount' => $sortedComments->count(),
                            ])
                        </div>

                        <div class="card booking-subpage-panel" id="booking-comments">
                <div class="card-header">
                    <h5 class="mb-0">{{ translate('Comments') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ translate('Internal_booking_comments_help') }}</p>

                    <div class="lead-comments-list-wrap" id="bookingCommentsListWrap">
                        <div class="lead-comments-list" id="bookingCommentsList">
                            @forelse($sortedComments as $comment)
                                @include('bookingmodule::admin.booking.partials._comment-item', [
                                    'comment' => $comment,
                                    'commentParser' => $commentParser,
                                ])
                            @empty
                                <div class="lead-comments-empty text-muted small" id="bookingCommentsEmpty">
                                    {{ translate('No_comments_yet') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @can('booking_view')
                        <form method="POST"
                              action="{{ route('admin.booking.comments.store', $booking->id) }}"
                              id="bookingCommentForm"
                              class="lead-comment-compose staff-chat-compose-wrap position-relative mt-3">
                            @csrf
                            <input type="hidden" name="redirect_web_page" value="comments">
                            @include('leadmanagement::admin.leads.partials._comment-compose')
                            <textarea name="body"
                                      id="bookingCommentBody"
                                      class="form-control form-control-sm staff-chat-message-input lead-comment-compose__input w-100"
                                      rows="2"
                                      required
                                      maxlength="5000"
                                      placeholder="{{ translate('Write_a_comment') }}"></textarea>
                            <div class="lead-comment-compose__footer d-flex flex-wrap align-items-center gap-2 pt-2">
                                <div class="lead-comment-compose__tags d-flex flex-wrap align-items-center gap-1">
                                    <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-staff" data-tag-type="staff">
                                        {{ translate('Staff') }}
                                    </button>
                                    <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-provider" data-tag-type="provider">
                                        {{ translate('Provider') }}
                                    </button>
                                    <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-service" data-tag-type="service">
                                        {{ translate('Service') }}
                                    </button>
                                </div>
                                <span class="small text-muted lead-comment-compose__hint">{{ translate('Booking_comment_mention_hint') }}</span>
                                <button type="submit" class="btn btn-primary ms-auto flex-shrink-0">{{ translate('Add_Comment') }}</button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>
                    @include('bookingmodule::admin.booking.partials._booking-detail-delete-footer', ['booking' => $booking])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('bookingmodule::admin.booking.partials._booking-comment-tagging-scripts')
    <script>
        (function () {
            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            document.querySelectorAll('.lead-comment-pin-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var url = btn.getAttribute('data-url');
                    if (!url) return;
                    btn.disabled = true;
                    fetch(url, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (r) { return r.json(); })
                        .then(function () { window.location.reload(); })
                        .catch(function () {
                            btn.disabled = false;
                            if (typeof toastr !== 'undefined') toastr.error(@json(translate('Failed_to_update')));
                        });
                });
            });

            document.querySelectorAll('.lead-comment-delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm(@json(translate('Are_you_sure')))) return;
                    var url = btn.getAttribute('data-url');
                    if (!url) return;
                    btn.disabled = true;
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (r) {
                            if (!r.ok) throw new Error('delete failed');
                            return r.json();
                        })
                        .then(function () { window.location.reload(); })
                        .catch(function () {
                            btn.disabled = false;
                            if (typeof toastr !== 'undefined') toastr.error(@json(translate('Failed_to_update')));
                        });
                });
            });

            var commentsWrap = document.getElementById('bookingCommentsListWrap');
            if (commentsWrap) {
                commentsWrap.scrollTop = commentsWrap.scrollHeight;
            }
        })();
    </script>
@endpush
