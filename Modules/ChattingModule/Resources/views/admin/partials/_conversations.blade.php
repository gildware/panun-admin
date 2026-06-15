@php
    $isStaffGroup = $isStaffGroup ?? false;
    $isStaffChat = ! $isStaffGroup && isset($fromUser->user) && in_array($fromUser->user->user_type, ADMIN_USER_TYPES, true);
@endphp
<div
    class="inbox_msg_header d-flex flex-wrap gap-3 justify-content-between align-items-center border px-3 py-2 rounded mb-4">
    <div class="media align-items-center gap-3">
        <div class="position-relative">
            <img class="avatar rounded-circle {{ $isStaffGroup ? 'd-none' : '' }}"
                 @if(isset($fromUser->user) && in_array($fromUser->user->user_type, ADMIN_USER_TYPES, true))
                     src="{{ $fromUser->user->profile_image_full_path }}"
                 @elseif(isset($fromUser->user) && $fromUser->user->user_type == 'customer')
                     src="{{$fromUser->user->profile_image_full_path}}"
                 @elseif(isset($fromUser->user) && $fromUser->user->user_type == 'provider-admin')
                     src="{{$fromUser->user->provider->logo_full_path}}"
                 @elseif(isset($fromUser->user) && $fromUser->user->user_type == 'provider-serviceman')
                     src="{{onErrorImage(
                                $fromUser->user->profile_image,
                                asset('storage/app/public/serviceman/profile').'/' .$fromUser->user->profile_image,
                                asset('assets/admin-module/img/media/user.png') ,
                                'serviceman/profile/'
                                )}}"
                 @else
                     src="{{onErrorImage('null',
                        asset('storage/app/public/serviceman/profile').'/',
                        asset('assets/admin-module/img/media/user.png') ,
                        'serviceman/profile/')}}"
                 @endif
                 alt="{{ translate('profile_image') }}">
            @if($isStaffGroup)
                <span class="staff-group-header-icon d-inline-flex align-items-center justify-content-center rounded-circle bg-light">
                    <span class="material-symbols-outlined text-primary">groups</span>
                </span>
            @elseif($isStaffChat && ($staffPresence ?? null) && ($presenceService ?? null))
                <span class="avatar-status {{ $presenceService->statusDotClass($staffPresence['presence_status']) }}"></span>
            @else
                <span class="avatar-status bg-success"></span>
            @endif
        </div>
        <div class="media-body">
            @if($isStaffGroup)
                <h5 class="profile-name mb-1">{{ translate('General_Staff_Group') }}</h5>
                <span class="fz-12 text-muted">{{ $memberCount ?? 0 }} {{ translate('members') }}</span>
            @elseif(isset($fromUser->user) && isset($fromUser->user->provider))
                <h5 class="profile-name">{{ $fromUser->user->provider->company_name }}</h5>
                <span class="fz-12">{{$fromUser->user->provider->company_phone}}</span>
            @elseif($isStaffChat && ($staffPresence ?? null) && ($presenceService ?? null))
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h5 class="profile-name mb-0">{{ $staffPresence['name'] }}</h5>
                    <span class="badge rounded-pill {{ $presenceService->statusBadgeClass($staffPresence['presence_status']) }}">
                        {{ $staffPresence['presence_label'] }}
                    </span>
                </div>
                <span class="fz-12 text-muted staff-chat-last-seen d-block">
                    {{ translate('Last_Seen') }}:
                    @if(!empty($staffPresence['last_seen_at']))
                        {{ \Carbon\Carbon::parse($staffPresence['last_seen_at'])->diffForHumans() }}
                    @else
                        —
                    @endif
                </span>
            @else
                <h5 class="profile-name">{{ isset($fromUser->user) ? $fromUser->user->first_name : translate('no_user_found') }}</h5>
                <span class="fz-12">{{isset($fromUser->user)?$fromUser->user->phone:''}}</span>
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button"
                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 chat-clear-btn"
                data-channel-id="{{ $channelId }}"
                title="{{ translate('Clear_Conversation') }}">
            <span class="material-symbols-outlined fs-16">delete_sweep</span>
            <span class="d-none d-sm-inline">{{ translate('Clear_Conversation') }}</span>
        </button>
    </div>
</div>

@include('chattingmodule::admin.partials._chat-pinned-bar', ['pinnedMessages' => $pinnedMessages ?? collect()])

<div class="messaging">
    <div class="inbox_msg d-flex flex-column-reverse" data-trigger="scrollbar">
        <div class="upload_img"></div>
        <div class="upload_file"></div>
        @include('chattingmodule::admin.partials._chat-messages-list', [
            'conversation' => $conversation,
            'enableStaffMessaging' => $enableStaffMessaging ?? false,
            'isStaffGroup' => $isStaffGroup ?? false,
        ])

    </div>

    <div class="type_msg">
        <form class="mt-4" id="send-sms-form">
            <div class="input_msg_write border rounded p-3">
                @include('chattingmodule::admin.partials._chat-reply-bar')
                <input name="channel_id" class="hide-div" value="{{$channelId}}"
                       id="chat-channel-id">
                @if($enableStaffMessaging ?? false)
                    @include('chattingmodule::admin.partials._staff-chat-compose-toolbar')
                @endif
                <textarea class="border-0 w-100 resize-none pb-0 {{ ($enableStaffMessaging ?? false) ? 'staff-chat-message-input' : '' }}" id="msgInputValue" type="text"
                          placeholder="{{ ($enableStaffMessaging ?? false) ? translate('Type_@_to_tag_staff_or_use_buttons_above') : translate('type_here...') }}"
                          aria-label="Search" name="message"></textarea>


                <div class="d-flex justify-content-between gap-3">
                    <div class="">
                        <div class="d-flex gap-3 flex-wrap filearray"></div>
                        <div id="selected-files-container"></div>
                    </div>
                    <div class="send-msg-btns d-flex justify-content-end mt-3 gap-3">
                        <div class="position-relative">
                            <label class="cursor-pointer">
                                <img src="{{asset('assets/admin-module/img/icons/img-icon.svg')}}" alt="">
                                <input type="file" id="msgfilesValue" class="h-100 position-absolute w-100 " hidden multiple
                                       data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
                                       accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*">
                            </label>
                        </div>
                        <div class="add-attatchment">
                            <img src="{{asset('assets/admin-module/img/icons/clip-icon.svg')}}" alt="">
                            <input type="file" class="file_input document_input" name="files[]" multiple
                                   data-maxFileSize="{{ readableUploadMaxFileSize('file') }}"
                                   accept=".{{ implode(',.', array_column(ALLOWED_FILE_TYPE, 'key')) }},">
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="p-0 lh-1" type="button" id="btnSendData">
                                <span class="material-icons">send</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    if (typeof selectedFiles === 'undefined') {
        var selectedFiles = [];
    }
    $("#msgfilesValue").on('change', function () {
        for (let i = 0; i < this.files.length; ++i) {
            selectedFiles.push(this.files[i]);
        }
        displaySelectedFiles();
    });

    function displaySelectedFiles() {
        /*start*/
        const container = document.getElementById("selected-files-container");
        container.innerHTML = ""; // Clear previous content
        selectedFiles.forEach((file, index) => {
            const input = document.createElement("input");
            input.type = "file";
            input.name = `files[${index}]`;
            input.classList.add(`image_index${index}`);
            input.hidden = true;
            container.appendChild(input);

            const blob = new Blob([file], {type: file.type});
            const file_obj = new File([file], file.name);
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file_obj);
            input.files = dataTransfer.files;
        });

        $(".filearray").empty();
        for (let i = 0; i < selectedFiles.length; ++i) {
            let filereader = new FileReader();
            let $uploadDiv = jQuery.parseHTML("<div class='upload_img_box'><span class='img-clear'><span class='material-icons m-0 fs-10'>close</span></span><img src='' alt=''></div>");

            filereader.onload = function () {
                // Set the src attribute of the img tag within the created div
                $($uploadDiv).find('img').attr('src', this.result);
                let imageData = this.result;
            };

            filereader.readAsDataURL(selectedFiles[i]);
            $(".filearray").append($uploadDiv);
            // Attach a click event handler to the "tio-clear" icon to remove the associated div and file from the array
            $($uploadDiv).find('.img-clear').on('click', function () {
                $(this).closest('.upload_img_box').remove();

                selectedFiles.splice(i, 1);
                $('.image_index' + i).remove();
            });
        }
    }
</script>

<script>
    "use strict";
    var selectedFiles = [];

    $('#btnSendData').on('click', function () {
        let $btn = $(this);
        // Disable button to prevent multiple clicks
        $btn.prop('disabled', true);

        var form = $('#send-sms-form')[0];
        var formData = new FormData(form);
        var messageInput = document.getElementById('msgInputValue');
        if (messageInput && typeof window.resolveStaffChatTags === 'function') {
            formData.set('message', window.resolveStaffChatTags(messageInput.value));
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: "{{route('admin.chat.send-message')}}",
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            success: function (response) {
                $('.inbox_msg').html(response.template);
                $(".file_input").val("");
                $("#send-sms-form")[0].reset();
                $('.upload__img-wrap').html('')
                $(".filearray").empty();
                selectedFiles = [];
                window.staffChatTagRegistry = [];
                if (typeof window.clearChatReply === 'function') {
                    window.clearChatReply();
                }
                toastr.success("{{translate('Message sent successfully')}}", {
                    CloseButton: true,
                    ProgressBar: true
                });
            },
            error: function (jqXHR, exception) {
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    toastr.error(jqXHR.responseJSON.errors[0]['message']);
                } else {
                    toastr.error("An unexpected error occurred.");
                }
            },
            complete: function () {
                // Re-enable button after AJAX call finishes
                $btn.prop('disabled', false);
            }
        });
    });

    $(".type_msg .document_input").on("change", function (e) {
        var filename = $(e.target).val().split('\\').pop();
        $(".messaging .upload_file").html("<div class='d-flex justify-content-between gap-2 align-items-center show-upload-file'><span class=''>" + filename + "</span><span class='material-icons upload-file-close'>close</span></div>");
        $(".messaging .inbox_msg").scrollTop(0);
        $('.upload-file-close').on('click', function () {
            $(this).parents('.show-upload-file').remove();
            $(".type_msg .document_input").val(null);
        });
    });

</script>
