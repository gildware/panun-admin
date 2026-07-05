<div class="chat_list chat-list-class staff-conversation-item {{ ($chat->is_read ?? 1) == 0 ? 'active' : '' }}{{ !empty($isActive) ? ' active-selected' : '' }}"
     id="chat-{{ $chat->id }}"
     data-route="{{ route('admin.chat.ajax-conversation', ['channel_id' => $chat->id, 'offset' => 1]) }}"
     data-chat="{{ $chat->id }}"
     data-updated-at="{{ $chat->updated_at?->toIso8601String() ?? '' }}">
    <div class="chat_people media gap-10">
        <div class="position-relative">
            <img src="{{ $fromUser->user->profile_image_full_path ?? asset('assets/admin-module/img/media/user.png') }}"
                 class="avatar rounded-circle" alt="">
            @if($staffPresence ?? null)
                <span class="avatar-status {{ $presenceService->statusDotClass($staffPresence['presence_status']) }}"></span>
            @endif
        </div>
        <div class="chat_ib media-body">
            <h5 class="mb-0">{{ $fromUser->user ? trim($fromUser->user->first_name.' '.$fromUser->user->last_name) : translate('no_user_found') }}</h5>
            @if($staffPresence ?? null)
                <span class="fz-12 text-muted">{{ $staffPresence['presence_label'] }}</span>
            @endif
            <div class="chat-list-preview-wrap">
                @include('chattingmodule::admin.partials._chat-list-last-message', ['chat' => $chat, 'section' => 'preview'])
            </div>
            <div class="chat-list-meta-wrap">
                @include('chattingmodule::admin.partials._chat-list-last-message', ['chat' => $chat, 'section' => 'meta'])
            </div>
        </div>
    </div>
    @if(($chat->is_read ?? 1) == 0 && empty($isActive))
        <div class="bg-info text-white radius-50 px-1 fz-12" id="badge-{{ $chat->id }}">
            <span class="material-symbols-outlined">swipe_up</span>
        </div>
    @endif
</div>
