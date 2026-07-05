<div class="chat_list chat-list-class staff-conversation-item staff-group-item {{ ($staffGroupChannel->is_read ?? 1) == 0 ? 'active' : '' }}"
     id="chat-{{ $staffGroupChannel->id }}"
     data-route="{{ route('admin.chat.ajax-conversation', ['channel_id' => $staffGroupChannel->id, 'offset' => 1]) }}"
     data-chat="{{ $staffGroupChannel->id }}"
     data-updated-at="{{ $staffGroupChannel->updated_at?->toIso8601String() ?? '' }}">
    <div class="chat_people media gap-10">
        <div class="position-relative d-flex align-items-center justify-content-center staff-group-avatar">
            <span class="material-symbols-outlined text-primary">groups</span>
        </div>
        <div class="chat_ib media-body">
            <h5 class="mb-0">{{ translate('General_Staff_Group') }}</h5>
            <span class="fz-12 text-muted">{{ $memberCount }} {{ translate('members') }}</span>
            <div class="chat-list-preview-wrap">
                @include('chattingmodule::admin.partials._chat-list-last-message', ['chat' => $staffGroupChannel, 'section' => 'preview'])
            </div>
            <div class="chat-list-meta-wrap">
                @include('chattingmodule::admin.partials._chat-list-last-message', ['chat' => $staffGroupChannel, 'section' => 'meta'])
            </div>
        </div>
    </div>
    @if(($staffGroupChannel->is_read ?? 1) == 0)
        <div class="bg-info text-white radius-50 px-1 fz-12" id="badge-{{ $staffGroupChannel->id }}">
            <span class="material-symbols-outlined">swipe_up</span>
        </div>
    @endif
</div>
