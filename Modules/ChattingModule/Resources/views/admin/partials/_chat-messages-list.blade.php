@php
    $format = ['jpg', 'png', 'jpeg', 'JPG', 'PNG', 'JPEG'];
    $parser = app(\Modules\ChattingModule\Services\StaffChatMessageParser::class);
@endphp
@foreach($conversation as $chat)
    @php
        $authorName = $chat->user
            ? trim(($chat->user->first_name ?? '').' '.($chat->user->last_name ?? ''))
            : translate('no_user_found');
        $messagePreview = $parser->plainPreview($chat->message, 120);
        if ($messagePreview === '' && $chat->conversationFiles && $chat->conversationFiles->count() > 0) {
            $messagePreview = translate('Attachment');
        }
        $isOutgoing = $chat->user->id == auth()->user()->id;
    @endphp
    <div class="chat-message-bubble {{ $isOutgoing ? 'outgoing_msg' : 'received_msg' }} {{ $chat->is_pinned ? 'is-pinned' : '' }}"
         id="bubble-{{ $chat->id }}"
         data-conversation-id="{{ $chat->id }}"
         data-author-name="{{ $authorName }}"
         data-message-preview="{{ e($messagePreview) }}"
         data-pinned="{{ $chat->is_pinned ? 1 : 0 }}">
        @if(!$isOutgoing && ($isStaffGroup ?? false) && isset($chat->user))
            <span class="fz-12 fw-semibold text-primary d-block mb-1">{{ $authorName }}</span>
        @endif

        @include('chattingmodule::admin.partials._chat-message-reply-quote', ['reply' => $chat->replyTo])

        @if($chat->message != null)
            <p class="message_text mb-0">
                @include('chattingmodule::admin.partials._chat-message-text', [
                    'message' => $chat->message,
                    'enableStaffMessaging' => $enableStaffMessaging ?? false,
                ])
            </p>
        @endif

        @if(count($chat->conversationFiles) > 0)
            @if($isOutgoing)
                <div class="inbox-img-grid">
                    @foreach($chat->conversationFiles as $file)
                        @if(in_array($file->file_type, $format))
                            <div class="conv-img-wrap">
                                <a data-lightbox="mygallery" href="{{ $file->stored_file_name_full_path }}">
                                    <img width="150" src="{{ $file->stored_file_name_full_path }}" alt="">
                                </a>
                            </div>
                        @else
                            <div class="d-flex align-items-center flex-column gap-1">
                                <img width="50" src="{{ asset('assets/admin-module/img/icons/folder.png') }}" alt="">
                                <a class="fs-12" href="{{ $file->stored_file_name_full_path }}" download>
                                    {{ $file->original_file_name }}
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                @foreach($chat->conversationFiles as $file)
                    @if(in_array($file->file_type, $format))
                        <a data-lightbox="mygallery" href="{{ $file->stored_file_name_full_path }}">
                            <img width="150" src="{{ $file->stored_file_name_full_path }}" alt="">
                        </a>
                    @else
                        <a href="{{ $file->stored_file_name_full_path }}" download>{{ $file->original_file_name }}</a>
                    @endif
                @endforeach
            @endif
        @endif

        <div class="d-flex align-items-center gap-2 mt-1 {{ $isOutgoing ? 'justify-content-end' : '' }}">
            <button type="button" class="btn btn-link btn-sm p-0 chat-reply-btn text-muted" title="{{ translate('Reply') }}">
                <span class="material-symbols-outlined fs-16">reply</span>
            </button>
            <div class="chat-reaction-wrap">
                <button type="button"
                        class="btn btn-link btn-sm p-0 chat-react-btn text-muted"
                        data-conversation-id="{{ $chat->id }}"
                        title="{{ translate('React') }}">
                    <span class="material-symbols-outlined fs-16">add_reaction</span>
                </button>
                <div class="chat-reaction-picker shadow-sm">
                    @foreach(\Modules\ChattingModule\Http\Controllers\Web\Admin\ChattingController::REACTION_EMOJIS as $emoji)
                        <button type="button"
                                class="chat-reaction-option"
                                data-conversation-id="{{ $chat->id }}"
                                data-emoji="{{ $emoji }}">{{ $emoji }}</button>
                    @endforeach
                </div>
            </div>
            <button type="button"
                    class="btn btn-link btn-sm p-0 chat-pin-btn {{ $chat->is_pinned ? 'text-primary' : 'text-muted' }}"
                    data-conversation-id="{{ $chat->id }}"
                    title="{{ $chat->is_pinned ? translate('Unpin') : translate('Pin') }}">
                <span class="material-symbols-outlined fs-16">push_pin</span>
            </button>
            <span class="time_date mb-0">{{ date('H:i a | M d Y', strtotime($chat->created_at)) }}</span>
        </div>

        @include('chattingmodule::admin.partials._chat-message-reactions', ['chat' => $chat])
    </div>
@endforeach
