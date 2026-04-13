@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'special_bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=special_bookings">{{ translate('Special_Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card mb-30">
                <div class="card-body p-30">
                    <h4 class="mb-3">{{ translate('Provider_Settings') }}</h4>

                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1">{{ translate('Service_Availability') }}</h5>
                                <p class="mb-0 text-muted">
                                    {{ translate('If_on_provider_can_receive_service_bookings._If_off_provider_cannot_take_new_service_bookings.') }}
                                </p>
                            </div>
                            @can('provider_manage_status')
                                <label class="switcher" data-bs-toggle="modal" data-bs-target="#deactivateAlertModal">
                                    <input class="switcher_input route-alert"
                                           data-route="{{ route('admin.provider.service_availability', [$provider->id]) }}"
                                           data-message="{{ translate('want_to_update_status') }}"
                                           type="checkbox" {{ $provider->service_availability ? 'checked' : '' }}>
                                    <span class="switcher_control"></span>
                                </label>
                            @endcan
                        </div>

                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1">{{ translate('Status') }}</h5>
                                <p class="mb-0 text-muted">
                                    {{ translate('If_off_provider_is_disabled_and_cannot_login_to_the_app_or_perform_any_action.') }}
                                </p>
                            </div>
                            @can('provider_manage_status')
                                <label class="switcher" data-bs-toggle="modal" data-bs-target="#deactivateAlertModal">
                                    <input class="switcher_input route-alert"
                                           data-route="{{ route('admin.provider.status_update', [$provider->id]) }}"
                                           data-message="{{ translate('want_to_update_status') }}"
                                           type="checkbox" {{ $provider?->owner?->is_active ? 'checked' : '' }}>
                                    <span class="switcher_control"></span>
                                </label>
                            @endcan
                        </div>

                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1">{{ translate('App_Availability') }}</h5>
                                <p class="mb-0 text-muted">
                                    {{ translate('If_off_provider_will_not_appear_in_mobile_app_provider_lists_or_assignment_APIs.') }}
                                </p>
                            </div>
                            @can('provider_manage_status')
                                <label class="switcher" data-bs-toggle="modal" data-bs-target="#deactivateAlertModal">
                                    <input class="switcher_input route-alert"
                                           data-route="{{ route('admin.provider.app_availability', [$provider->id]) }}"
                                           data-message="{{ translate('want_to_update_status') }}"
                                           type="checkbox" {{ $provider->app_availability ? 'checked' : '' }}>
                                    <span class="switcher_control"></span>
                                </label>
                            @endcan
                        </div>

                        @can('provider_delete')
                            <div class="border-top pt-4 mt-2">
                                <h5 class="mb-2 text-danger">{{ translate('This_action_will_permanently_delete_data') }}</h5>
                                <p class="text-muted mb-3">
                                    {{ translate('provider_delete_all_data_warning') }}
                                </p>
                                <button type="button"
                                        class="btn btn-danger provider-delete-settings"
                                        data-form-id="delete-provider-{{ $provider->id }}"
                                        data-message="{{ translate('provider_delete_all_data_warning') }}">
                                    {{ translate('delete') }} {{ translate('Provider') }}
                                </button>
                                <form action="{{ route('admin.provider.delete', [$provider->id]) }}"
                                      method="post"
                                      id="delete-provider-{{ $provider->id }}"
                                      class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@can('provider_delete')
    @push('script')
        <script>
            $('.provider-delete-settings').on('click', function () {
                var formId = $(this).data('form-id');
                var message = $(this).data('message');
                if ('{{ env('APP_ENV') == 'demo' }}') {
                    toastr.info('This function is disabled for demo mode', {
                        closeButton: true,
                        progressBar: true
                    });
                } else {
                    form_alert(formId, message);
                }
            });
        </script>
    @endpush
@endcan
