<div id="chatReplyBar" class="chat-reply-bar d-none border rounded bg-light px-3 py-2 mb-2">
    <div class="d-flex align-items-start justify-content-between gap-2">
        <div class="min-w-0">
            <div class="fz-11 text-muted">{{ translate('Replying_to') }}</div>
            <div class="fw-semibold chat-reply-author text-truncate"></div>
            <div class="fz-12 text-muted chat-reply-preview-text text-truncate"></div>
        </div>
        <button type="button" class="btn btn-sm btn-link text-muted p-0 flex-shrink-0" id="chatReplyCancel" aria-label="{{ translate('close') }}">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <input type="hidden" name="reply_to_conversation_id" id="replyToConversationId" value="">
</div>
