@extends('adminmodule::layouts.new-master')

@section('title', translate('add-on activation'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-20">
            <div class="page-title-wrap">
                <h2 class="page-title">{{translate('add-on activation')}}</h2>
            </div>
        </div>

        {{-- Provider App --}}
        @php($providerData = $data->where('key_name', 'addon_activation_provider_app')->first()->live_values ?? null)
        <div class="card view-details-container">
            <div class="card-body p-20">
                <form action="{{ route('admin.add-on-activation.update', ['provider']) }}" method="POST">
                    @csrf
                    <input type="hidden" name="addon_name" value="provider_app">
                    <input type="hidden" name="software_type" value="addon">
                    <input type="hidden" name="software_id" value="NDAyMjUwNDc==">
                    <div class="d-flex justify-content-between align-items-center mb-20">
                        <div>
                            <h5 class="mb-1">{{ translate('Provider App') }}</h5>
                            <p class="text-muted mb-0">
                                {{ translate('Activate the Provider App to manage your business through the mobile app.') }}
                            </p>
                        </div>
                        <div class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-sm-3 gap-2">
                            <div class="view-btn  order-sm-0 order-3 fz-12 text-primary cursor-pointer fw-semibold d-flex align-items-center gap-1">
                                {{ translate('View') }}
                                <i class="material-symbols-outlined fz-14">arrow_downward</i>
                            </div>
                            <div class="mb-0">
                                <label class="switcher">
                                    <input type="checkbox"
                                           class="switcher_input {{ env('APP_ENV') == 'demo' ? 'demo_check' : 'addon-status-change' }}"
                                           name="status"
                                           value="1"
                                           data-on-title="{{ translate('want_to_Turn_ON_the_Provider_App_addon') }}?"
                                           data-off-title="{{ translate('want_to_Turn_OFF_the_Provider_App_addon') }}?"
                                           data-on-description="{{ translate('Turning this on will activate the Provider App features and make them work.') }}"
                                           data-off-description="{{ translate('Turning this off will stop the Provider App features from working.') }}"
                                           data-on-image="{{ asset('assets/admin-module/img/icons/status-on.png') }}"
                                           data-off-image="{{ asset('assets/admin-module/img/icons/status-off.png') }}"
                                        {{ isset($providerData) && isset($providerData['activation_status']) && $providerData['activation_status'] == 1 ? 'checked' : '' }}
                                    >
                                    <span class="switcher_control {{ env('APP_ENV') == 'demo' ? 'disabled' : '' }}"  ></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="view-details">
                        <div class="p-12 rounded d-flex flex-md-nowrap flex-wrap align-items-center gap-1 bg-primary bg-opacity-10 mb-20">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_9464_2249)">
                                    <path d="M9.79125 1.44318L10.5117 0.270677C10.6797 -0.00465646 11.039 -0.0898231 11.3138 0.0793436C11.5885 0.247927 11.6743 0.606677 11.5051 0.881427L10.7847 2.05393C10.675 2.23359 10.4831 2.33218 10.2871 2.33218C10.1827 2.33218 10.0777 2.30418 9.98259 2.24584C9.70784 2.07726 9.62209 1.71793 9.79125 1.44318ZM3.16575 2.05334C3.27542 2.23301 3.46792 2.33218 3.66392 2.33218C3.76775 2.33218 3.87275 2.30418 3.96784 2.24643C4.24259 2.07784 4.32892 1.71909 4.16034 1.44434L3.44109 0.27126C3.2725 -0.00348977 2.91317 -0.0892398 2.639 0.0787602C2.36425 0.247344 2.27792 0.606094 2.4465 0.880844L3.16575 2.05334ZM1.89059 3.34134L0.841754 2.83909C0.552421 2.69851 0.201838 2.82218 0.0641712 3.11326C-0.0752455 3.40376 0.0478378 3.75201 0.338338 3.89084L1.38717 4.39309C1.46825 4.43218 1.554 4.45084 1.63859 4.45084C1.85617 4.45084 2.065 4.32893 2.16475 4.11951C2.30417 3.82901 2.18109 3.48018 1.89059 3.34134ZM6.92067 2.33393C3.70417 2.38701 1.155 5.87301 2.9015 9.23068C3.16692 9.74051 3.56067 10.1675 3.98359 10.5566C4.144 10.7042 4.26825 10.8868 4.37442 11.0828H6.41667V8.05876C5.73942 7.81726 5.25 7.17559 5.25 6.41609C5.25 6.09351 5.51075 5.83276 5.83334 5.83276C6.15592 5.83276 6.41667 6.09351 6.41667 6.41609C6.41667 6.73868 6.678 6.99943 7 6.99943C7.322 6.99943 7.58334 6.73809 7.58334 6.41609C7.58334 6.09409 7.84409 5.83276 8.16667 5.83276C8.48925 5.83276 8.75 6.09351 8.75 6.41609C8.75 7.17559 8.26059 7.81726 7.58334 8.05876V11.0828H9.60634C9.73934 10.8547 9.91084 10.6394 10.1354 10.4522C10.5198 10.1313 10.8815 9.77376 11.1038 9.32518C12.117 7.27593 11.7151 5.09076 10.2719 3.67209C9.373 2.78776 8.18242 2.31468 6.92067 2.33393ZM4.662 12.4122C4.61534 13.2673 5.25584 14 6.11217 14H7.87442C8.68 14 9.33275 13.3473 9.33275 12.5417V12.25H4.65559C4.65559 12.3048 4.6655 12.3562 4.662 12.4122ZM13.9493 3.09168C13.8163 2.79826 13.4715 2.66584 13.1781 2.80001L12.0511 3.30868C11.7571 3.44109 11.6264 3.78643 11.7594 4.07984C11.8568 4.29568 12.0686 4.42343 12.2914 4.42343C12.3713 4.42343 12.453 4.40709 12.5306 4.37151L13.6576 3.86284C13.9516 3.73043 14.0823 3.38509 13.9493 3.09168Z" fill="#3C76F1"></path>
                                </g>
                                <defs>
                                    <clipPath id="clip0_9464_2249">
                                        <rect width="14" height="14" fill="white"></rect>
                                    </clipPath>
                                </defs>
                            </svg>
                            <p class="fz-12 pl-2">{{ translate('Activating the Provider App allows you to manage bookings, track progress, and interact with customers directly from your mobile device for enhanced efficiency.') }}</a></p>
                        </div>

                        <div class="body-bg rounded p-20 mb-20">
                            <div class="row ">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Name') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter your real full name.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ isset($providerData) && isset($providerData['name']) ? $providerData['name'] : '' }}" placeholder="Ex: Miler" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Email') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter your valid email address.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control"  placeholder="{{ 'Ex: your-mail@example.com' }}"
                                           value="{{ isset($providerData) && isset($providerData['email']) ? $providerData['email'] : '' }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Codecanyon User Name') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter the username of your Codecanyon account where you purchased this item.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" value="{{ isset($providerData) && isset($providerData['username']) ? $providerData['username'] : '' }}" placeholder="Ex: Miler" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Codecanyon Purchase Code') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter the purchase code received from Codecanyon after buying the item.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="purchase_key" class="form-control" value="{{ isset($providerData) && isset($providerData['purchase_key']) ? $providerData['purchase_key'] : '' }}" placeholder="Ex: axSce1367k23" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end trans3 mt-4">
                            <div class="d-flex justify-content-sm-end justify-content-center gap-2 gap-sm-3 flex-grow-1 flex-grow-sm-0 bg-white trans3">
                                <button type="reset" class="btn btn--secondary rounded">{{translate('reset')}}</button>
                                <button type="submit" class="btn btn--primary demo_check rounded">{{translate('update')}}</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        @php($servicemanData = $data->where('key_name', 'addon_activation_serviceman_app')->first()->live_values ?? null)
        {{-- Serviceman App --}}
        <div class="card view-details-container mt-4">
            <div class="card-body p-20">
                <form action="{{ route('admin.add-on-activation.update', ['serviceman']) }}" method="POST">
                    @csrf
                    <input type="hidden" name="addon_name" value="serviceman_app">
                    <input type="hidden" name="software_type" value="addon">
                    <input type="hidden" name="software_id" value="NDAyMjUxNTc=">
                    <div class="d-flex justify-content-between align-items-center mb-20">
                        <div>
                            <h5 class="mb-1">{{ translate('Serviceman App') }}</h5>
                            <p class="text-muted mb-0">
                                {{ translate('Activate the Serviceman App to manage your service bookings and tasks from the mobile app.') }}
                            </p>
                        </div>
                        <div class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-sm-3 gap-2">
                            <div class="view-btn  order-sm-0 order-3 fz-12 text-primary cursor-pointer fw-semibold d-flex align-items-center gap-1">
                                {{ translate('View') }}
                                <i class="material-symbols-outlined fz-14">arrow_downward</i>
                            </div>
                            <div class="mb-0">
                                <label class="switcher">
                                    <input type="checkbox"
                                           class="switcher_input {{ env('APP_ENV') == 'demo' ? 'demo_check' : 'addon-status-change' }}"
                                           name="status"
                                           value="1"
                                           data-on-title="{{ translate('want_to_Turn_ON_the_Serviceman_App_addon') }}?"
                                           data-off-title="{{ translate('want_to_Turn_OFF_the_Serviceman_App_addon') }}?"
                                           data-on-description="{{ translate('Turning this on will activate the Serviceman App features and make them work.') }}"
                                           data-off-description="{{ translate('Turning this off will stop the Serviceman App features from working.') }}"
                                           data-on-image="{{ asset('assets/admin-module/img/icons/status-on.png') }}"
                                           data-off-image="{{ asset('assets/admin-module/img/icons/status-off.png') }}"
                                        {{ isset($servicemanData) && isset($servicemanData['activation_status']) && $servicemanData['activation_status'] == 1 ? 'checked' : '' }}
                                    >
                                    <span class="switcher_control {{ env('APP_ENV') == 'demo' ? 'disabled' : '' }}"  ></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="view-details">
                        <div class="pick-map p-12 rounded d-flex flex-md-nowrap flex-wrap align-items-center gap-1 bg-primary bg-opacity-10 mb-20">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_9464_2249)">
                                    <path d="M9.79125 1.44318L10.5117 0.270677C10.6797 -0.00465646 11.039 -0.0898231 11.3138 0.0793436C11.5885 0.247927 11.6743 0.606677 11.5051 0.881427L10.7847 2.05393C10.675 2.23359 10.4831 2.33218 10.2871 2.33218C10.1827 2.33218 10.0777 2.30418 9.98259 2.24584C9.70784 2.07726 9.62209 1.71793 9.79125 1.44318ZM3.16575 2.05334C3.27542 2.23301 3.46792 2.33218 3.66392 2.33218C3.76775 2.33218 3.87275 2.30418 3.96784 2.24643C4.24259 2.07784 4.32892 1.71909 4.16034 1.44434L3.44109 0.27126C3.2725 -0.00348977 2.91317 -0.0892398 2.639 0.0787602C2.36425 0.247344 2.27792 0.606094 2.4465 0.880844L3.16575 2.05334ZM1.89059 3.34134L0.841754 2.83909C0.552421 2.69851 0.201838 2.82218 0.0641712 3.11326C-0.0752455 3.40376 0.0478378 3.75201 0.338338 3.89084L1.38717 4.39309C1.46825 4.43218 1.554 4.45084 1.63859 4.45084C1.85617 4.45084 2.065 4.32893 2.16475 4.11951C2.30417 3.82901 2.18109 3.48018 1.89059 3.34134ZM6.92067 2.33393C3.70417 2.38701 1.155 5.87301 2.9015 9.23068C3.16692 9.74051 3.56067 10.1675 3.98359 10.5566C4.144 10.7042 4.26825 10.8868 4.37442 11.0828H6.41667V8.05876C5.73942 7.81726 5.25 7.17559 5.25 6.41609C5.25 6.09351 5.51075 5.83276 5.83334 5.83276C6.15592 5.83276 6.41667 6.09351 6.41667 6.41609C6.41667 6.73868 6.678 6.99943 7 6.99943C7.322 6.99943 7.58334 6.73809 7.58334 6.41609C7.58334 6.09409 7.84409 5.83276 8.16667 5.83276C8.48925 5.83276 8.75 6.09351 8.75 6.41609C8.75 7.17559 8.26059 7.81726 7.58334 8.05876V11.0828H9.60634C9.73934 10.8547 9.91084 10.6394 10.1354 10.4522C10.5198 10.1313 10.8815 9.77376 11.1038 9.32518C12.117 7.27593 11.7151 5.09076 10.2719 3.67209C9.373 2.78776 8.18242 2.31468 6.92067 2.33393ZM4.662 12.4122C4.61534 13.2673 5.25584 14 6.11217 14H7.87442C8.68 14 9.33275 13.3473 9.33275 12.5417V12.25H4.65559C4.65559 12.3048 4.6655 12.3562 4.662 12.4122ZM13.9493 3.09168C13.8163 2.79826 13.4715 2.66584 13.1781 2.80001L12.0511 3.30868C11.7571 3.44109 11.6264 3.78643 11.7594 4.07984C11.8568 4.29568 12.0686 4.42343 12.2914 4.42343C12.3713 4.42343 12.453 4.40709 12.5306 4.37151L13.6576 3.86284C13.9516 3.73043 14.0823 3.38509 13.9493 3.09168Z" fill="#3C76F1"></path>
                                </g>
                                <defs>
                                    <clipPath id="clip0_9464_2249">
                                        <rect width="14" height="14" fill="white"></rect>
                                    </clipPath>
                                </defs>
                            </svg>
                            <p class="fz-12 margin-left-20">{{ translate('Activating the Serviceman App allows your team to receive assigned tasks, manage service bookings, update job statuses, and communicate with customers from their mobile devices for efficient service delivery.') }}</a></p>
                        </div>
                        <div class="body-bg rounded p-20 mb-20">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Name') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter your real full name.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ isset($servicemanData) && isset($servicemanData['name']) ? $servicemanData['name'] : '' }}" placeholder="Ex: Miler" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Email') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter your valid email address.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control"  placeholder="{{ 'Ex: your-mail@example.com' }}"
                                           value="{{ isset($servicemanData) && isset($servicemanData['email']) ? $servicemanData['email'] : '' }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Codcanyon User Name') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter the username of your Codecanyon account where you purchased this item.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" value="{{ isset($servicemanData) ? $servicemanData['username'] : '' }}" placeholder="Ex: Miler" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ translate('Codcanyon Purchase Code') }}
                                        <i class="material-icons fz-14 text-light-gray" data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="{{translate('Enter the purchase code received from Codecanyon after buying the item.')}}"
                                        >info</i>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="purchase_key" class="form-control" value="{{ isset($servicemanData) ? $servicemanData['purchase_key'] : '' }}" placeholder="Ex: axSce1367k23" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end trans3 mt-4">
                            <div class="d-flex justify-content-sm-end justify-content-center gap-2 gap-sm-3 flex-grow-1 flex-grow-sm-0 bg-white trans3">
                                <button type="reset" class="btn btn--secondary rounded">{{translate('reset')}}</button>
                                <button type="submit" class="btn btn--primary demo_check rounded">{{translate('update')}}</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{--status confirmation modal--}}
    <div class="modal fade" id="addonStatusChangeModal" tabindex="-1" role="dialog" aria-labelledby="confirmChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close cancel-change" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body mb-30 pb-0 text-center">
                    <img width="80" src="{{ asset('assets/admin-module/img/icons/status-on.png') }}" alt="{{ translate('image') }}" class="mb-20">
                    <h3 class="mb-3 confirmation-title-text">{{ translate('Are you sure') }}?</h3>
                    <p class="mb-0 confirmation-description-text">{{ translate('Do you want to change the status') }}?</p>
                    <div class="btn--container mt-30 justify-content-center">
                        <button type="button" class="btn btn--secondary rounded min-w-120 cancel-change" id="cancelChange">{{ translate('No') }}</button>
                        <button type="button" class="btn btn--primary rounded min-w-120" id="confirmChange">{{ translate('Yes') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
<script>
    $(".view-btn").on("click", function () {
        var container = $(this).closest(".view-details-container");
        var details = container.find(".view-details");
        var icon = $(this).find("i");

        details.slideToggle(300);
        icon.toggleClass("rotate-180deg");

        // Toggle text between View / Hide
        const isActive = $(this).toggleClass("active").hasClass("active");
        const newText = isActive ? "{{ translate('Hide') }}" : "{{ translate('View') }}";
        $(this).html(`${newText} <i class="material-symbols-outlined fz-14 ${isActive ? 'rotate-180deg' : ''}">arrow_downward</i>`);
    });


    let selectedAddon;
    let selectedAddonInitialState;
    let userConfirmedSelectedAddonInitialState = false;

    $(document).on('change', '.addon-status-change', function (e) {
        e.preventDefault();

        selectedAddon = $(this);
        selectedAddonInitialState = selectedAddon.prop('checked');
        selectedAddon.prop('checked', !selectedAddonInitialState);

        let confirmationTitleText = selectedAddonInitialState
            ? selectedAddon.data('on-title')
            : selectedAddon.data('off-title');

        let confirmationDescriptionText = selectedAddonInitialState
            ? selectedAddon.data('on-description')
            : selectedAddon.data('off-description');

        let imgSrc = selectedAddonInitialState
            ? selectedAddon.data('on-image')
            : selectedAddon.data('off-image');

        $('.confirmation-title-text').text(confirmationTitleText);
        $('.confirmation-description-text').text(confirmationDescriptionText);
        $('#addonStatusChangeModal img').attr('src', imgSrc);

        $('#addonStatusChangeModal').modal('show');
    });

    // Confirm button clicked
    $('#confirmChange').on('click', function () {
        userConfirmedSelectedAddonInitialState = true;

        if (selectedAddon) {
            selectedAddon.prop('checked', selectedAddonInitialState);
        }

        $('#addonStatusChangeModal').modal('hide');
    });

    $('.cancel-change').on('click', function () {
        resetCheckboxState();
        $('#addonStatusChangeModal').modal('hide');
    });

    $('#addonStatusChangeModal').on('hidden.bs.modal', function () {
        if (!userConfirmedSelectedAddonInitialState) {
            resetCheckboxState();
        }
        userConfirmedSelectedAddonInitialState = false;
    });

    function resetCheckboxState() {
        if (selectedAddon) {
            selectedAddon.prop('checked', !selectedAddonInitialState);
        }
    }

</script>
@endpush
