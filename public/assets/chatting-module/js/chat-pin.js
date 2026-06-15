"use strict";

(function () {
    function getChannelId() {
        var el = document.getElementById("chat-channel-id");
        return el ? el.value : "";
    }

    function updateBubbleState(conversationId, isPinned) {
        var $btn = $('.chat-message-bubble[data-conversation-id="' + conversationId + '"]')
            .find(".chat-pin-btn");
        if (!$btn.length) {
            return;
        }
        $btn.attr("data-pinned", isPinned ? 1 : 0);
        $btn.attr("title", isPinned
            ? (window.chatPinUnpinLabel || "Unpin")
            : (window.chatPinPinLabel || "Pin"));
        $btn.toggleClass("text-primary", !!isPinned);
        $btn.toggleClass("text-muted", !isPinned);
        $btn.closest(".chat-message-bubble").toggleClass("is-pinned", !!isPinned);
    }

    function togglePin(conversationId) {
        var channelId = getChannelId();
        var url = window.chatTogglePinUrl;
        if (!conversationId || !channelId || !url) {
            return;
        }

        $.ajax({
            url: url,
            type: "POST",
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            data: { channel_id: channelId, conversation_id: conversationId },
            success: function (response) {
                if (typeof response.pinned_bar !== "undefined") {
                    $("#chatPinnedBar").replaceWith(response.pinned_bar);
                }
                updateBubbleState(conversationId, response.is_pinned);
                if (typeof toastr !== "undefined") {
                    toastr.options = toastr.options || {};
                    toastr.options.positionClass = "toast-top-right";
                    toastr.success(response.is_pinned
                        ? (window.chatPinnedMessage || "Message pinned")
                        : (window.chatUnpinnedMessage || "Message unpinned"));
                }
            },
            error: function (jqXHR) {
                if (typeof toastr === "undefined") {
                    return;
                }
                if (jqXHR.responseJSON && jqXHR.responseJSON.errors && jqXHR.responseJSON.errors.length) {
                    toastr.error(jqXHR.responseJSON.errors[0].message);
                } else {
                    toastr.error("An error occurred.");
                }
            },
        });
    }

    function jumpToMessage(conversationId) {
        var bubble = document.getElementById("bubble-" + conversationId);
        if (!bubble) {
            return;
        }
        bubble.scrollIntoView({ behavior: "smooth", block: "center" });

        document.querySelectorAll(".chat-message-bubble.bubble-pinned-active")
            .forEach(function (el) {
                el.classList.remove("bubble-pinned-active");
            });
        bubble.classList.add("bubble-pinned-active");

        bubble.classList.remove("bubble-highlight");
        // Force reflow so the animation can restart on repeated clicks.
        void bubble.offsetWidth;
        bubble.classList.add("bubble-highlight");
    }

    window.chatJumpToMessage = jumpToMessage;

    $(document).on("click keydown", ".chat-pinned-toggle", function (e) {
        if (e.type === "keydown" && e.key !== "Enter" && e.key !== " ") {
            return;
        }
        e.preventDefault();
        var $accordion = $(this).closest(".chat-pinned-accordion");
        var $list = $accordion.find(".chat-pinned-list");
        var isOpen = !$list.hasClass("d-none");
        $list.toggleClass("d-none", isOpen);
        $(this).attr("aria-expanded", isOpen ? "false" : "true");
        $accordion.toggleClass("is-open", !isOpen);
    });

    $(document).on("click", ".chat-pin-btn, .chat-unpin-btn", function (e) {
        e.preventDefault();
        togglePin($(this).data("conversation-id"));
    });

    $(document).on("click", ".chat-pinned-jump", function (e) {
        e.preventDefault();
        jumpToMessage($(this).data("target-id"));
    });
})();
