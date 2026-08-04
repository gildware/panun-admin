<script>
    window.staffChatEntitySearchUrl = @json(route('admin.chat.entity-search'));
</script>
<script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}?v={{ @filemtime(public_path('assets/chatting-module/js/staff-chat-compose.js')) ?: time() }}"></script>
<script>
    window.commentAttachmentsEmptyMessage = @json(translate('Please_write_a_comment_or_attach_a_file'));
    window.commentAttachmentsLoadingMessage = @json(translate('Adding'));
</script>
<script src="{{ asset('assets/common/js/comment-attachments.js') }}?v={{ @filemtime(public_path('assets/common/js/comment-attachments.js')) ?: time() }}"></script>
<script>
    (function () {
        if (typeof window.initStaffChatComposeHighlights === 'function') {
            window.initStaffChatComposeHighlights(document);
        }
    })();
</script>