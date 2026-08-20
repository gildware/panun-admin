@php($fromUser = support_inbox_peer_channel_user($chat->channelUsers, auth()->id()))
@php($supportChannelType = $chat->reference_type ?? null)
@php($showAsCustomer = $supportChannelType === 'support_customer' || ($supportChannelType === 'support' && isset($fromUser->user) && in_array($fromUser->user->user_type, CUSTOMER_USER_TYPES, true)))
@php($showAsProvider = in_array($supportChannelType, ['support_provider'], true) || ($supportChannelType === 'support' && isset($fromUser->user) && $fromUser->user->user_type === 'provider-admin'))
<div class="chat_list chat-list-class {{ $chat->is_read == 0 ? 'active' : '' }}{{ !empty($isActive) ? ' active-selected' : '' }}"
     id="chat-{{ $chat->id }}"
     data-route="{{ route('admin.chat.ajax-conversation', ['channel_id' => $chat->id, 'offset' => 1]) }}"
     data-chat="{{ $chat->id }}"
     data-updated-at="{{ $chat->updated_at?->toIso8601String() ?? '' }}">
    <div class="chat_people chat_people--support media gap-10 w-100 min-w-0" id="chat_people">
        <div class="position-relative flex-shrink-0">
            <img
                src="{{ support_chat_avatar_url($fromUser, $supportChannelType) }}"
                class="avatar rounded-circle" alt="{{ translate('image') }}">
            <span class="avatar-status bg-success"></span>
        </div>
        <div class="chat_ib chat_ib--support media-body min-w-0 flex-grow-1">
            <div class="chat-card-row chat-card-row--top d-flex align-items-start justify-content-between gap-2">
                <h5 class="mb-0 text-truncate min-w-0">{{ isset($fromUser->user) ? ($showAsProvider && $fromUser->user->provider ? $fromUser->user->provider->company_name : $fromUser->user->first_name.' '.$fromUser->user->last_name) : translate('no_user_found') }}</h5>
                <div class="chat-card-end flex-shrink-0">
                    @include('chattingmodule::admin.partials._support-chat-role-pill', ['fromUser' => $fromUser, 'chat' => $chat])
                </div>
            </div>
            <div class="chat-card-row chat-card-row--bottom d-flex align-items-center justify-content-between gap-2 mt-1">
                <div class="chat-card-start min-w-0 flex-grow-1 chat-list-preview-wrap">
                    @include('chattingmodule::admin.partials._chat-list-last-message', ['chat' => $chat, 'section' => 'preview'])
                </div>
                <div class="chat-card-end flex-shrink-0 chat-list-meta-wrap">
                    @include('chattingmodule::admin.partials._chat-list-last-message', ['chat' => $chat, 'section' => 'meta'])
                </div>
            </div>
        </div>
    </div>
    @if($chat->is_read == 0 && empty($isActive))
        <div class="bg-info text-white radius-50 px-1 fz-12" id="badge-{{ $chat->id }}">
            <span class="material-symbols-outlined">swipe_up</span>
        </div>
    @endif
</div>
