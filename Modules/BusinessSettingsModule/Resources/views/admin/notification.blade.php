@extends('adminmodule::layouts.new-master')

@section('title',translate('notification_setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/swiper/swiper-bundle.min.css"/>
    <style>
        .notification-page-section-tabs {
            margin-bottom: 1.25rem;
        }
        .notification-page-section-tabs .nav-link {
            white-space: nowrap;
        }
        .notification-scenario-accordion + .notification-scenario-accordion {
            margin-top: 12px;
        }
        .notification-scenario-accordion .notification-scenario-toggle-header {
            border-radius: 8px;
        }
        .notification-scenario-accordion .notification-scenario-toggle-header.active {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        .notification-scenario-accordion .notification-scenario-toggle-header.active .notification-scenario-toggle-chevron {
            transform: rotate(-180deg);
            background-color: var(--bs-primary) !important;
            color: var(--bs-white);
        }
        .notification-scenario-audience-table th,
        .notification-scenario-audience-table td {
            font-size: 12px;
            vertical-align: middle;
        }
        .notification-scenario-badge-audience-customer {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .notification-scenario-badge-audience-provider {
            background: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ffe0b2;
        }
        .notification-device-badge-customer {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #a5d6a7;
        }
        .notification-device-badge-provider {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
        }
        .notification-device-badge-serviceman {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }
    </style>
@endpush

@section('content')
    @php
        $activeSection = $activeSection ?? 'message_config';
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="page-title mb-3">{{ translate('Push Notification') }}</h2>

            <div class="notification-page-section-tabs">
                <ul class="nav nav--tabs nav--tabs__style2 flex-wrap gap-2 align-items-center" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeSection === 'message_config' ? 'active' : '' }}"
                           href="{{ route('admin.configuration.get-notification-setting', array_filter([
                               'section' => 'message_config',
                               'tab' => $activeModuleTab ?? null,
                           ])) }}">
                            {{ translate('notification_message_config') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeSection === 'logs' ? 'active' : '' }}"
                           href="{{ route('admin.configuration.get-notification-setting', ['section' => 'logs']) }}">
                            {{ translate('notification_logs') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeSection === 'device_check' ? 'active' : '' }}"
                           href="{{ route('admin.configuration.get-notification-setting', ['section' => 'device_check']) }}">
                            {{ translate('notification_device_check') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body p-20">
                    @if($activeSection === 'message_config')
                        @include('businesssettingsmodule::admin.partials.notification-scenario-groups', [
                            'groupedScenarios' => $groupedScenarios,
                            'dataValues' => $dataValues,
                            'language' => null,
                            'activeModuleTab' => $activeModuleTab,
                        ])
                    @elseif($activeSection === 'logs')
                        @include('businesssettingsmodule::admin.partials.notification-delivery-logs', [
                            'notificationDeliveryLogs' => $notificationDeliveryLogs ?? null,
                            'deviceStats' => $deviceStats ?? null,
                        ])
                    @elseif($activeSection === 'device_check')
                        @include('businesssettingsmodule::admin.partials.notification-device-check', [
                            'customerUsersWithDevices' => $customerUsersWithDevices ?? null,
                            'providerUsersWithDevices' => $providerUsersWithDevices ?? null,
                            'deviceStats' => $deviceStats ?? null,
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>

     <!--Status Off Modal-->
     <div class="modal fade custom-confirmation-modal" id="turnOffStatus" tabindex="-1" aria-labelledby="statusoffModelLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-30">
                    <button type="button" class="btn-close bg-light rounded-full" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="d-flex flex-column align-items-center text-center">
                        <img class="mb-20" src="{{asset('assets/admin-module')}}/img/status-of.png" alt="">
                        <h3 class="mb-15">{{ translate('Are you sure Turn Off the status?')}}</h3>
                        <p class="mb-4 fz-14">{{ translate('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam odio tellus, laoreet ')}}</p>
                        <form action="{{ route('admin.subscription.package.subscription-to-commission') }}" method="post">
                            @csrf
                            <div class="choose-option">
                                <div class="d-flex gap-3 justify-content-center flex-wrap">
                                    <button type="button" class="btn px-xl-5 px-4 btn--secondary rounded" data-bs-dismiss="modal">{{ translate('NO') }}</button>
                                    <button type="button" class="btn px-xl-5 px-4 btn--primary text-capitalize rounded">{{ translate('Yes') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade firebase-modal" id="carouselModal" tabindex="-1" aria-labelledby="carouselModal"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div dir="ltr" class="swiper modalSwiper pb-4">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2 fs-12">
                                    <img width="80" class="mb-3"
                                         src="{{asset('assets/admin-module/img/media/firebase-console.png')}}"
                                         alt="">
                                    <h5 class="modal-title text-center mb-3">{{translate('Go to Firebase Console')}}</h5>

                                    @php($firebaseLink = 'https://console.firebase.google.com')
                                    <ul class="d-flex flex-column gap-2 px-3">
                                        <li>{{translate('Open your web browser and go to the Firebase Console')}} <a
                                                href="https://console.firebase.google.com">{{$firebaseLink}}</a>
                                        </li>
                                        <li>{{translate('Select the project for which you want to configure FCM from the Firebase
                                            Console dashboard.')}}
                                        </li>
                                        <li>{{translate('If you don’t have any project before. Create one with the website name.')}}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2 fs-12">
                                    <img width="80" class="mb-3"
                                         src="{{asset('assets/admin-module/img/media/project-settings.png')}}"
                                         alt="">
                                    <h5 class="modal-title text-center mb-3">{{translate('Navigate to Project Settings')}}</h5>

                                    <ul class="d-flex flex-column gap-2 px-3">
                                        <li>{{translate('In the left-hand menu, click on the')}}
                                            <strong>"Settings"</strong> {{translate('gear icon,
                                            there you will vae a dropdown. and then select ')}}
                                            <strong>{{translate('"Project settings"')}}
                                            </strong> {{translate('from the dropdown.')}}
                                        </li>
                                        <li>{{translate('In the Project settings page, click on the "Cloud Messaging" tab from the
                                            top menu.')}}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2 fs-12">
                                    <img width="80" class="mb-3"
                                         src="{{asset('assets/admin-module/img/media/cloud-message.png')}}"
                                         alt="">
                                    <h5 class="modal-title text-center mb-3">{{translate('Cloud Messaging API')}}</h5>

                                    <ul class="d-flex flex-column gap-2 px-3">
                                        <li>{{translate('From Cloud Messaging Page there will be a section called Cloud Messaging
                                            API.')}}
                                        </li>
                                        <li>{{translate('Click on the menu icon and enable the API')}}</li>
                                        <li>{{translate('Refresh the Cloud Messaging Page - You will have your server key. Just copy
                                            the code and paste here')}}
                                        </li>
                                    </ul>

                                    <div class="d-flex justify-content-center mt-2 w-100">
                                        <button type="button" class="btn btn-primary w-100 max-w320"
                                                data-bs-dismiss="modal">{{translate('Got It')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination mb-2"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="documentationModal" tabindex="-1" aria-labelledby="documentationModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center gap-2 max-w360 mx-auto fs-12">
                        <img width="80" class="mb-3"
                             src="{{asset('assets/admin-module/img/media/documentation.png')}}" alt="">
                        <h5 class="modal-title text-center mb-3">{{translate('Documentation')}}</h5>
                        <p>{{translate('If disabled customers and provider will not receive notifications on their devices')}}</p>

                            <?php
                            $providerName = 'providerName';
                            $serviceManName = 'serviceManName';
                            $bookingId = 'bookingId';
                            $scheduleTime = 'scheduleTime';
                            $userName = 'userName';
                            $zoneName = 'zoneName';
                            ?>
                        <ul class="d-flex flex-column gap-2 px-3">
                            <li><span
                                    class="fw-medium">&#123;&#123;{{$providerName}}&#125;&#125;:</span> {{translate('the name of the provider.')}}
                            </li>
                            <li><span
                                    class="fw-medium">&#123;&#123;{{$serviceManName}}&#125;&#125;:</span> {{translate('the name of the service man name.')}}
                            </li>
                            <li><span
                                    class="fw-medium">&#123;&#123;{{$bookingId}}&#125;&#125;:</span> {{translate('the unique ID of the Booking.')}}
                            </li>
                            <li><span
                                    class="fw-medium">&#123;&#123;{{$scheduleTime}}&#125;&#125;:</span> {{translate('the expected sechedule time.')}}
                            </li>
                            <li><span
                                    class="fw-medium">&#123;&#123;{{$userName}}&#125;&#125;:</span> {{translate('the name of the user who placed the order.')}}
                            </li>
                            <li><span
                                    class="fw-medium">&#123;&#123;{{$zoneName}}&#125;&#125;:</span> {{translate('the name of the zone.')}}
                            </li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-center mt-2">
                        <button type="button" class="btn btn-primary w-100 max-w320" data-bs-dismiss="modal">
                            {{translate('Got It')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            if (!window.__notificationExtrasToggleBound) {
                window.__notificationExtrasToggleBound = true;

                document.addEventListener('click', function (e) {
                    const btn = e.target.closest('.notification-toggle-btn, .notification-trigger-info-btn, .notification-scenario-edit-btn');
                    if (!btn) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();

                    const selector = btn.getAttribute('data-toggle-target');
                    if (!selector) {
                        return;
                    }

                    const scope = btn.closest('.notification-message-form');
                    let panel = scope ? scope.querySelector(selector) : null;
                    if (!panel) {
                        panel = document.querySelector(selector);
                    }
                    if (!panel) {
                        return;
                    }

                    panel.classList.toggle('d-none');
                    const isOpen = !panel.classList.contains('d-none');

                    if (btn.classList.contains('notification-toggle-btn') || btn.classList.contains('notification-scenario-edit-btn')) {
                        btn.classList.toggle('active', isOpen);
                        const hideLabel = btn.getAttribute('data-hide-label');
                        const showLabel = btn.getAttribute('data-show-label');
                        if (hideLabel && showLabel) {
                            btn.textContent = isOpen ? hideLabel : showLabel;
                        }
                    } else {
                        btn.classList.toggle('active', isOpen);
                        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    }
                });
            }
        })();
    </script>

    <script src="{{asset('assets/admin-module')}}/plugins/swiper/swiper-bundle.min.js"></script>

    <script>
        "use strict";

        (function () {
            function whenJQueryReady(callback) {
                if (window.jQuery) {
                    callback(window.jQuery);
                    return;
                }

                setTimeout(function () {
                    whenJQueryReady(callback);
                }, 50);
            }

            if (document.querySelector('.modalSwiper') && typeof Swiper !== 'undefined') {
                new Swiper('.modalSwiper', {
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                        dynamicBullets: true,
                        autoHeight: true,
                    },
                });
            }

            const notificationPreviewSamples = @json(notification_message_preview_samples());

            whenJQueryReady(function ($) {
                function applyNotificationPreview(text) {
                    let output = text || '';
                    Object.keys(notificationPreviewSamples).forEach(function (name) {
                        const token = '{' + '{' + name + '}' + '}';
                        output = output.split(token).join(notificationPreviewSamples[name]);
                    });
                    return output;
                }

                function refreshNotificationPreviewForField(fieldId) {
                    const $field = $('#' + fieldId);
                    if (!$field.length) return;

                    const role = $field.attr('data-preview-role');
                    const previewText = applyNotificationPreview($field.val());
                    if (role === 'title') {
                        $('[data-preview-title-for="' + fieldId + '"]').text(previewText);
                    } else if (role === 'description') {
                        $('[data-preview-desc-for="' + fieldId + '"]').text(previewText);
                    }
                }

                function initNotificationMessagePreview() {
                    $(document).off('input.notificationExtras', '.notification-message-input');
                    $(document).on('input.notificationExtras', '.notification-message-input', function () {
                        refreshNotificationPreviewForField($(this).attr('id'));
                    });

                    $(document).off('click.notificationExtras', '.notification-var-chip');
                    $(document).on('click.notificationExtras', '.notification-var-chip', function (e) {
                        e.preventDefault();
                        const targetId = $(this).attr('data-target');
                        const variable = $(this).attr('data-var');
                        const $target = $('#' + targetId);
                        if (!$target.length) return;

                        const el = $target.get(0);
                        const start = el.selectionStart ?? $target.val().length;
                        const end = el.selectionEnd ?? start;
                        const value = $target.val();
                        $target.val(value.slice(0, start) + variable + value.slice(end));
                        $target.trigger('input').focus();
                        el.selectionStart = el.selectionEnd = start + String(variable).length;
                    });
                }

                function bindNotificationPageActions() {
                    $('.js-select').select2();
                    initNotificationMessagePreview();

                    $('.update-message').off('click.notificationPage').on('click.notificationPage', function () {
                        update_message($(this).attr('data-key'));
                    });

                    $(".lang_link").off('click.notificationPage').on('click.notificationPage', function (e) {
                        e.preventDefault();
                        $(".lang_link").removeClass('active');
                        $(".lang-form").addClass('d-none');
                        $(this).addClass('active');

                        let form_id = this.id;
                        let lang = form_id.substring(0, form_id.length - 5);
                        $("." + lang + "-form").removeClass('d-none');
                    });

                    $('#notification_type').off('change.notificationPage').on('change.notificationPage', function () {
                        const url = new URL(window.location.href);
                        url.searchParams.set('type', $(this).val());
                        window.location.href = url.toString();
                    });
                }

                function update_action_status(key_name, value) {
                    Swal.fire({
                        title: "{{translate('are_you_sure')}}?",
                        text: '{{translate('want_to_update_status')}}',
                        type: 'warning',
                        showCloseButton: true,
                        showCancelButton: true,
                        cancelButtonColor: 'var(--bs-secondary)',
                        confirmButtonColor: 'var(--bs-primary)',
                        cancelButtonText: 'Cancel',
                        confirmButtonText: 'Yes',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.value) {
                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                            });
                            $.ajax({
                                url: "{{route('admin.configuration.set-notification-setting')}}",
                                data: {
                                    key: key_name,
                                    value: value,
                                },
                                type: 'put',
                                success: function (response) {
                                    console.log(response)
                                    toastr.success('{{translate('successfully_updated')}}')
                                },
                                error: function () {

                                }
                            });
                        }
                    })
                }

                function update_message(id) {
                    var $status = $('#' + id + '_status');
                    var messageType = $status.data('message-type') || 'customers';
                    Swal.fire({
                        title: "{{translate('are_you_sure')}}?",
                        text: '{{translate('want_to_update')}}',
                        type: 'warning',
                        showCloseButton: true,
                        showCancelButton: true,
                        cancelButtonColor: 'var(--bs-secondary)',
                        confirmButtonColor: 'var(--bs-primary)',
                        cancelButtonText: 'Cancel',
                        confirmButtonText: 'Yes',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.value) {

                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                            });
                            $.ajax({
                                url: "{{route('admin.configuration.set-message-setting')}}",
                                data: {
                                    id: id,
                                    status: $('#' + id + '_status').is(':checked') === true ? 1 : 0,
                                    message: $('#' + id + '_message').val(),
                                    type: messageType,
                                    change_type: "status"
                                },
                                type: 'post',
                                success: function (response) {
                                    console.log(response)
                                    toastr.success('{{translate('successfully_updated')}}')
                                },
                                error: function () {

                                }
                            });
                        }
                    })
                }

                function bootNotificationPage() {
                    bindNotificationPageActions();

                    $('#business-info-update-form').off('submit.notificationPage').on('submit.notificationPage', function (event) {
                        event.preventDefault();

                        var form = $('#business-info-update-form')[0];
                        var formData = new FormData(form);

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        $.ajax({
                            url: "{{route('admin.business-settings.set-business-information')}}",
                            data: formData,
                            processData: false,
                            contentType: false,
                            type: 'POST',
                            success: function (response) {
                                toastr.success('{{translate('successfully_updated')}}')
                            },
                            error: function () {

                            }
                        });
                    });

                    $(".push-notification-update-action-status").off('click.notificationPage').on('click.notificationPage', function () {
                        let keyName = $(this).attr('data-keyname');
                        let value = $(this).is(':checked') === true ? 1 : 0
                        update_action_status(keyName, value);
                    });
                }

                $(document).ready(bootNotificationPage);
                document.addEventListener('admin:page-loaded', bootNotificationPage);

                document.addEventListener('click', function (e) {
                    var header = e.target.closest('.notification-user-device-accordion .notification-scenario-toggle-header');
                    if (!header) {
                        return;
                    }
                    e.preventDefault();
                    var body = header.nextElementSibling;
                    if (!body || !body.classList.contains('notification-scenario-toggle-body')) {
                        return;
                    }
                    var isOpen = window.getComputedStyle(body).display !== 'none';
                    body.style.display = isOpen ? 'none' : 'block';
                    header.classList.toggle('active', !isOpen);
                });
            });
        })();
    </script>
@endpush
