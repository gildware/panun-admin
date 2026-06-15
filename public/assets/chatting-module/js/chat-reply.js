"use strict";

(function () {
    function clearReply() {
        $("#replyToConversationId").val("");
        $("#chatReplyBar").addClass("d-none");
        $("#chatReplyBar .chat-reply-author").text("");
        $("#chatReplyBar .chat-reply-preview-text").text("");
    }

    window.clearChatReply = clearReply;

    $(document).on("click", ".chat-reply-btn", function (e) {
        e.preventDefault();
        var bubble = $(this).closest(".chat-message-bubble");
        $("#replyToConversationId").val(bubble.data("conversation-id") || "");
        $("#chatReplyBar .chat-reply-author").text(bubble.data("author-name") || "");
        $("#chatReplyBar .chat-reply-preview-text").text(bubble.data("message-preview") || "");
        $("#chatReplyBar").removeClass("d-none");
        var input = document.getElementById("msgInputValue");
        if (input) {
            input.focus();
        }
    });

    $(document).on("click", "#chatReplyCancel", function (e) {
        e.preventDefault();
        clearReply();
    });

    $(document).on("click keydown", ".chat-reply-jump", function (e) {
        if (e.type === "keydown" && e.key !== "Enter" && e.key !== " ") {
            return;
        }
        e.preventDefault();
        var targetId = $(this).data("reply-target-id");
        if (!targetId) {
            return;
        }
        if (typeof window.chatJumpToMessage === "function") {
            window.chatJumpToMessage(targetId);
            return;
        }
        var bubble = document.getElementById("bubble-" + targetId);
        if (bubble) {
            bubble.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    });
})();
