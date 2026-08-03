<script>
    window.staffChatEntitySearchUrl = @json(route('admin.chat.entity-search'));
</script>
<script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}?v={{ @filemtime(public_path('assets/chatting-module/js/staff-chat-compose.js')) ?: time() }}"></script>
<script>
    (function () {
        document.querySelectorAll('#bookingCommentForm').forEach(function (commentForm) {
            if (commentForm.dataset.tagSubmitBound === '1') {
                return;
            }
            commentForm.dataset.tagSubmitBound = '1';
            commentForm.addEventListener('submit', function () {
                var body = commentForm.querySelector('.staff-chat-message-input, #bookingCommentBody');
                if (body && typeof window.resolveStaffChatTags === 'function') {
                    body.value = window.resolveStaffChatTags(body.value);
                }
            });
        });

        if (typeof window.initStaffChatComposeHighlights === 'function') {
            window.initStaffChatComposeHighlights(document);
        }
    })();
</script>