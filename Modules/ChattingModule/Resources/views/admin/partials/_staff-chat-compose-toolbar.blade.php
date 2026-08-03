<div class="staff-chat-compose-wrap position-relative">
    <div class="staff-chat-compose-tools d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-service" data-tag-type="service">
            {{ translate('Service') }}
        </button>
        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-provider" data-tag-type="provider">
            {{ translate('Provider') }}
        </button>
        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-customer" data-tag-type="customer">
            {{ translate('customer') }}
        </button>
        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-booking" data-tag-type="booking">
            {{ translate('booking') }}
        </button>
        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-lead" data-tag-type="lead">
            {{ translate('Lead') }}
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
