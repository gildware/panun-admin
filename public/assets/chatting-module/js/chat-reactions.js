"use strict";

(function () {
    function getChannelId() {
        var el = document.getElementById("chat-channel-id");
        return el ? el.value : "";
    }

    function closeAllPickers() {
        document.querySelectorAll(".chat-reaction-wrap.is-open")
            .forEach(function (el) {
                el.classList.remove("is-open");
            });
    }

    function toggleReaction(conversationId, emoji) {
        var channelId = getChannelId();
        var url = window.chatToggleReactionUrl;
        if (!conversationId || !channelId || !emoji || !url) {
            return;
        }

        $.ajax({
            url: url,
            type: "POST",
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            data: { channel_id: channelId, conversation_id: conversationId, emoji: emoji },
            success: function (response) {
                if (typeof response.reactions_html === "undefined") {
                    return;
                }
                var $existing = $('.chat-message-reactions[data-conversation-id="' + conversationId + '"]');
                if ($existing.length) {
                    $existing.replaceWith(response.reactions_html);
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

    $(document).on("click", ".chat-react-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $wrap = $(this).closest(".chat-reaction-wrap");
        var willOpen = !$wrap.hasClass("is-open");
        closeAllPickers();
        $wrap.toggleClass("is-open", willOpen);
    });

    $(document).on("click", ".chat-reaction-option", function (e) {
        e.preventDefault();
        closeAllPickers();
        toggleReaction($(this).data("conversation-id"), $(this).data("emoji"));
    });

    $(document).on("click", ".chat-reaction-chip", function (e) {
        e.preventDefault();
        toggleReaction($(this).data("conversation-id"), $(this).data("emoji"));
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(".chat-reaction-wrap").length) {
            closeAllPickers();
        }
    });
})();
