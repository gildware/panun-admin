"use strict";

(function ($) {
    var POLL_MS = 4000;

    function sidebarContainer() {
        return document.getElementById("admin-chat-sidebar-list")
            || document.querySelector(".staff-conversations-list");
    }

    function unreadBadgeHtml(channelId) {
        return (
            '<div class="bg-info text-white radius-50 px-1 fz-12" id="badge-' +
            channelId +
            '"><span class="material-symbols-outlined">swipe_up</span></div>'
        );
    }

    var ChatLiveSync = {
        activeChannelId: null,
        lastMessageAt: null,
        readFingerprint: null,
        pollTimer: null,

        init: function () {
            if (!window.chatLiveSyncUrl) {
                return;
            }

            var channelInput = document.getElementById("chat-channel-id");
            if (channelInput && channelInput.value) {
                this.activeChannelId = String(channelInput.value);
            } else if (window.chatActiveChannelId) {
                this.activeChannelId = String(window.chatActiveChannelId);
            }

            this.startPolling();
        },

        setActiveChannel: function (channelId, options) {
            options = options || {};
            this.activeChannelId = channelId ? String(channelId) : null;

            if (!options.keepCursor) {
                this.lastMessageAt = null;
                this.readFingerprint = null;
            }
        },

        startPolling: function () {
            var self = this;

            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }

            this.poll();
            this.pollTimer = setInterval(function () {
                self.poll();
            }, POLL_MS);
        },

        poll: function () {
            var self = this;
            var params = {
                mode: window.chatSidebarMode || "support",
                filter: window.chatSidebarFilter || "all",
            };

            if (this.activeChannelId) {
                params.active_channel_id = this.activeChannelId;

                if (this.lastMessageAt) {
                    params.last_message_at = this.lastMessageAt;
                }

                if (this.readFingerprint) {
                    params.read_fingerprint = this.readFingerprint;
                }
            }

            $.get(window.chatLiveSyncUrl, params)
                .done(function (data) {
                    if (data.channels && data.channels.length) {
                        data.channels.forEach(function (channel) {
                            self.applySidebarChannel(
                                channel,
                                self.activeChannelId === String(channel.id)
                            );
                        });
                    }

                    if (data.order) {
                        self.reorderSidebar(data.order);
                    }

                    if (data.active_conversation) {
                        self.applyActiveConversation(data.active_conversation);
                    }
                });
        },

        captureConversationCursor: function () {
            var lastAt = document.getElementById("chat-last-message-at");
            var readFp = document.getElementById("chat-read-fingerprint");

            if (lastAt && lastAt.value) {
                this.lastMessageAt = lastAt.value;
            }

            if (readFp) {
                this.readFingerprint = readFp.value || "";
            }
        },

        onSendSuccess: function (response) {
            if (response.sidebar) {
                this.applySidebarChannel(response.sidebar, true);
                this.moveChannelToTop(response.sidebar.id);
            }

            if (response.active_conversation) {
                this.applyActiveConversation(response.active_conversation);
            }
        },

        applySidebarChannel: function (channel, isActive) {
            if (!channel || !channel.id) {
                return;
            }

            var el = document.getElementById("chat-" + channel.id);
            if (!el) {
                return;
            }

            el.setAttribute("data-updated-at", channel.updated_at || "");

            var previewWrap = el.querySelector(".chat-list-preview-wrap");
            var metaWrap = el.querySelector(".chat-list-meta-wrap");

            if (previewWrap && typeof channel.preview_html === "string") {
                previewWrap.innerHTML = channel.preview_html;
            }

            if (metaWrap && typeof channel.meta_html === "string") {
                metaWrap.innerHTML = channel.meta_html;
            }

            var badge = document.getElementById("badge-" + channel.id);

            if (channel.show_unread_badge) {
                el.classList.add("active");

                if (!badge) {
                    el.insertAdjacentHTML("beforeend", unreadBadgeHtml(channel.id));
                }
            } else {
                if (badge) {
                    badge.remove();
                }

                if (!isActive && !el.classList.contains("active-selected")) {
                    el.classList.remove("active");
                }
            }
        },

        reorderSidebar: function (order) {
            var container = sidebarContainer();
            if (!container || !order || !order.length) {
                return;
            }

            order.forEach(function (channelId) {
                var el = document.getElementById("chat-" + channelId);
                if (el) {
                    container.appendChild(el);
                }
            });
        },

        moveChannelToTop: function (channelId) {
            var container = sidebarContainer();
            var el = document.getElementById("chat-" + channelId);

            if (container && el) {
                container.prepend(el);
            }
        },

        applyActiveConversation: function (payload) {
            if (!payload || !payload.changed || !payload.messages_html) {
                if (payload && payload.last_message_at) {
                    this.lastMessageAt = payload.last_message_at;
                }

                if (payload && payload.read_fingerprint) {
                    this.readFingerprint = payload.read_fingerprint;
                }

                return;
            }

            var channelInput = document.getElementById("chat-channel-id");

            if (
                !channelInput ||
                !this.activeChannelId ||
                String(channelInput.value) !== String(this.activeChannelId)
            ) {
                return;
            }

            $(".inbox_msg").html(payload.messages_html);
            this.lastMessageAt = payload.last_message_at || this.lastMessageAt;
            this.readFingerprint = payload.read_fingerprint || this.readFingerprint;

            var lastAtEl = document.getElementById("chat-last-message-at");
            var readFpEl = document.getElementById("chat-read-fingerprint");

            if (lastAtEl && payload.last_message_at) {
                lastAtEl.value = payload.last_message_at;
            }

            if (readFpEl && payload.read_fingerprint) {
                readFpEl.value = payload.read_fingerprint;
            }
        },
    };

    window.ChatLiveSync = ChatLiveSync;

    $(document).ready(function () {
        ChatLiveSync.init();
    });
})(jQuery);
