<script>
    window.staffChatEntitySearchUrl = @json(route('admin.chat.entity-search'));
</script>
{{-- Cache-bust must change when CDN keeps an immutable old ?v= filemtime URL. --}}
<script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}?v={{ @filemtime(public_path('assets/chatting-module/js/staff-chat-compose.js')) ?: time() }}-commentfix2"></script>
<script>
    window.commentAttachmentsEmptyMessage = @json(translate('Please_write_a_comment_or_attach_a_file'));
    window.commentAttachmentsLoadingMessage = @json(translate('Adding_comment'));
    window.commentAttachmentsSuccessMessage = @json(translate('Comment_added_successfully'));
</script>
<script src="{{ asset('assets/common/js/comment-attachments.js') }}?v={{ @filemtime(public_path('assets/common/js/comment-attachments.js')) ?: time() }}-commentfix2"></script>
<script>
    (function () {
        if (typeof window.initStaffChatComposeHighlights === 'function') {
            window.initStaffChatComposeHighlights(document);
        }
    })();
</script>