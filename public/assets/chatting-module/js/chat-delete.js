"use strict";

(function () {
    function getChannelId() {
        var el = document.getElementById("chat-channel-id");
        return el ? el.value : "";
    }

    function confirmAction(message, onConfirm) {
        var swalFn = window.Swal || window.swal;
        // SweetAlert2 v9+ exposes Swal.fire; older v7/v8 builds are callable directly.
        var fire = swalFn && typeof swalFn.fire === "function"
            ? swalFn.fire.bind(swalFn)
            : swalFn;

        if (typeof fire === "function") {
            var promise = fire({
                title: window.chatConfirmTitle || "Are you sure?",
                text: message,
                type: "warning",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                confirmButtonText: window.chatConfirmYes || "Yes",
                cancelButtonText: window.chatConfirmNo || "No",
                reverseButtons: true,
            });
            if (promise && typeof promise.then === "function") {
                promise.then(function (result) {
                    if (result && (result.isConfirmed || result.value)) {
                        onConfirm();
                    }
                });
            }
        } else if (window.confirm(message)) {
            onConfirm();
        }
    }

    function notifyError(jqXHR) {
        if (typeof toastr === "undefined") {
            return;
        }
        if (jqXHR.responseJSON && jqXHR.responseJSON.errors && jqXHR.responseJSON.errors.length) {
            toastr.error(jqXHR.responseJSON.errors[0].message);
        } else {
            toastr.error("An error occurred.");
        }
    }

    function notifySuccess(message) {
        if (typeof toastr !== "undefined") {
            toastr.options = toastr.options || {};
            toastr.options.positionClass = "toast-top-right";
            toastr.success(message);
        }
    }

    function deleteMessage(conversationId) {
        var channelId = getChannelId();
        var url = window.chatDeleteMessageUrl;
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
                $("#bubble-" + conversationId).remove();
                if (typeof response.pinned_bar !== "undefined") {
                    $("#chatPinnedBar").replaceWith(response.pinned_bar);
                }
                notifySuccess(window.chatMessageDeleted || "Message deleted");
            },
            error: notifyError,
        });
    }

    function clearConversation(channelId) {
        var url = window.chatClearConversationUrl;
        if (!channelId || !url) {
            return;
        }

        $.ajax({
            url: url,
            type: "POST",
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            data: { channel_id: channelId },
            success: function () {
                $(".inbox_msg .chat-message-bubble").remove();
                $("#chatPinnedBar").empty();
                notifySuccess(window.chatConversationCleared || "Conversation cleared");
            },
            error: notifyError,
        });
    }

    $(document).on("click", ".chat-delete-btn", function (e) {
        e.preventDefault();
        var conversationId = $(this).data("conversation-id");
        confirmAction(window.chatDeleteMessageConfirm || "Delete this message?", function () {
            deleteMessage(conversationId);
        });
    });

    $(document).on("click", ".chat-clear-btn", function (e) {
        e.preventDefault();
        var channelId = $(this).data("channel-id");
        confirmAction(window.chatClearConversationConfirm || "Clear the entire conversation? This cannot be undone.", function () {
            clearConversation(channelId);
        });
    });
})();
