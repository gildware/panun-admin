<script>
    (function () {
        var shownAlertIdsKey = 'admin_shown_notification_alert_ids';

        function getShownAlertIds() {
            try {
                var raw = sessionStorage.getItem(shownAlertIdsKey);
                return raw ? JSON.parse(raw) : [];
            } catch (e) {
                return [];
            }
        }

        function rememberShownAlertId(id) {
            var ids = getShownAlertIds();
            if (ids.indexOf(id) === -1) {
                ids.push(id);
                if (ids.length > 100) {
                    ids = ids.slice(-100);
                }
                sessionStorage.setItem(shownAlertIdsKey, JSON.stringify(ids));
            }
        }

        function alertConfigForType(type) {
            switch (type) {
                case 'booking':
                    return {
                        icon: 'info',
                        confirmText: '{{ translate('Show_Bookings') }}',
                        fallbackUrl: '{{ route('admin.booking.list', ['booking_status' => 'pending', 'type' => 'pending']) }}',
                    };
                case 'chat_message':
                    return {
                        icon: 'info',
                        confirmText: '{{ translate('Open_Chat') }}',
                        fallbackUrl: '{{ route('admin.chat.index', ['user_type' => 'customer']) }}',
                    };
                case 'provider_request':
                    return {
                        icon: 'info',
                        confirmText: '{{ translate('View_Provider') }}',
                        fallbackUrl: '{{ route('admin.provider.onboarding_request', ['status' => 'onboarding']) }}',
                    };
                case 'withdraw_request':
                    return {
                        icon: 'warning',
                        confirmText: '{{ translate('View_Requests') }}',
                        fallbackUrl: '{{ route('admin.withdraw.request.list', ['status' => 'pending']) }}',
                    };
                default:
                    return {
                        icon: 'info',
                        confirmText: '{{ translate('View') }}',
                        fallbackUrl: '{{ route('admin.dashboard') }}',
                    };
            }
        }

        window.pkHandleAdminInboxNotifications = function (data, opts) {
            opts = opts || {};
            var skipSound = !!opts.skipSound;

            var countEl = document.getElementById('notification_count');
            var listEl = document.getElementById('show-notification-list');
            var unread = parseInt(data.notification_unread_count, 10);
            if (isNaN(unread)) unread = 0;

            if (countEl) {
                countEl.innerHTML = unread;
                countEl.style.display = unread > 0 ? 'flex' : 'none';
            }

            if (listEl && data.notification_template) {
                listEl.innerHTML = data.notification_template;
            }

            if (!skipSound && unread > 0) {
                var prevKey = 'admin_notification_unread_count';
                var prevRaw = sessionStorage.getItem(prevKey);
                if (prevRaw !== null && prevRaw !== '') {
                    var prev = parseInt(prevRaw, 10) || 0;
                    if (unread > prev && typeof window.pkPlayStaffNotificationSound === 'function') {
                        window.pkPlayStaffNotificationSound();
                    }
                }
                sessionStorage.setItem(prevKey, String(unread));
            } else if (skipSound) {
                sessionStorage.setItem('admin_notification_unread_count', String(unread));
            }

            var alerts = data.new_notification_alerts || [];
            var shownIds = getShownAlertIds();
            alerts.forEach(function (alert) {
                if (!alert || !alert.id || shownIds.indexOf(alert.id) !== -1) {
                    return;
                }
                rememberShownAlertId(alert.id);

                var cfg = alertConfigForType(alert.type);
                if (typeof Swal === 'undefined') {
                    return;
                }

                Swal.fire({
                    title: alert.title || '{{ translate('New_Notification') }}',
                    text: alert.body || '',
                    icon: cfg.icon,
                    showCloseButton: true,
                    showCancelButton: false,
                    focusConfirm: false,
                    confirmButtonText: cfg.confirmText,
                }).then(function (result) {
                    if (result.value) {
                        var url = alert.action_url || cfg.fallbackUrl;
                        if (url) {
                            window.location.href = url;
                        }
                    }
                });
            });
        };

        $(document).on('click', '.js-admin-notification-item', function (e) {
            e.preventDefault();
            var $item = $(this);
            var notificationId = $item.data('notification-id');
            var actionUrl = $item.data('action-url');

            $.ajax({
                url: '{{ url('admin/notifications') }}/' + notificationId + '/read',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                complete: function () {
                    if (actionUrl) {
                        window.location.href = actionUrl;
                    } else if (typeof window.pkAdminRefreshWhatsAppUnread === 'function') {
                        window.pkAdminRefreshWhatsAppUnread({ skipSound: true });
                    }
                },
            });
        });

        $(document).on('click', '.js-mark-all-notifications-read', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $.ajax({
                url: '{{ route('admin.notifications.mark_all_read') }}',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function () {
                    if (typeof window.pkAdminRefreshWhatsAppUnread === 'function') {
                        window.pkAdminRefreshWhatsAppUnread({ skipSound: true });
                    }
                },
            });
        });
    })();
</script>
