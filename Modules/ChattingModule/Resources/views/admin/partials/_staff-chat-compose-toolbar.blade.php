<div class="staff-chat-compose-wrap position-relative">
    <div class="staff-chat-compose-tools d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary staff-tag-trigger" data-tag-type="service">
            {{ translate('Service') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary staff-tag-trigger" data-tag-type="provider">
            {{ translate('Provider') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary staff-tag-trigger" data-tag-type="customer">
            {{ translate('customer') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary staff-tag-trigger" data-tag-type="booking">
            {{ translate('booking') }}
        </button>
    </div>
    <div class="staff-chat-entity-picker card shadow-sm border d-none" id="staffChatEntityPicker">
        <div class="card-body p-2">
            <input type="search" class="form-control form-control-sm mb-2" id="staffChatEntitySearchInput"
                   placeholder="{{ translate('Search_by_name_phone_or_id') }}" autocomplete="off">
            <div class="small text-muted mb-2" id="staffChatEntityPickerHint">{{ translate('Staff_chat_tag_hint') }}</div>
            <div class="staff-chat-entity-results list-group list-group-flush" id="staffChatEntityResults"></div>
        </div>
    </div>
</div>
