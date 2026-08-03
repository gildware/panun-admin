<div class="staff-chat-entity-picker card shadow-sm border d-none" id="staffChatEntityPicker">
    <div class="card-body p-2">
        <input type="search" class="form-control form-control-sm mb-2" id="staffChatEntitySearchInput"
               placeholder="{{ translate('Search_by_name_phone_or_id') }}" autocomplete="off">
        <div class="small text-muted mb-2" id="staffChatEntityPickerHint">{{ translate('Staff_chat_tag_hint') }}</div>
        <div class="staff-chat-entity-results list-group list-group-flush" id="staffChatEntityResults"></div>
    </div>
</div>
