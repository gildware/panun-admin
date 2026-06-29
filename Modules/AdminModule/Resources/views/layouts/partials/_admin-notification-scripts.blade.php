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
                    return { icon: 'info' };
                case 'provider_withdrawal':
                    return { icon: 'warning' };
                case 'chat_message':
                    return { icon: 'info' };
                case 'provider_request':
                    return { icon: 'info' };
                case 'withdraw_request':
                    return { icon: 'warning' };
                case 'advertisement':
                    return { icon: 'info' };
                default:
                    return { icon: 'info' };
            }
        }

        function markNotificationRead(notificationId) {
            if (!notificationId) {
                return;
            }
            $.ajax({
                url: '{{ url('admin/notifications') }}/' + notificationId + '/read',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            });
        }

        function hideNotificationDropdown(el) {
            var dropdown = el && el.closest ? el.closest('.dropdown') : null;
            if (dropdown) {
                var toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle && typeof bootstrap !== 'undefined') {
                    bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                }
            }
        }

        function refreshNotificationUi() {
            if (typeof window.pkAdminRefreshWhatsAppUnread === 'function') {
                window.pkAdminRefreshWhatsAppUnread({ skipSound: true });
            }
        }

        window.pkOpenAdminNotificationModal = function (notificationId) {
            if (!notificationId) {
                return;
            }

            var modalEl = document.getElementById('adminNotificationDetailModal');
            if (!modalEl || typeof bootstrap === 'undefined') {
                return;
            }

            var bodyEl = modalEl.querySelector('.js-notification-detail-body');
            if (bodyEl) {
                bodyEl.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ translate('Loading') }}...</span></div></div>';
            }

            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            $.ajax({
                url: '{{ url('admin/notifications') }}/' + notificationId + '/detail',
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res.status === 1 && bodyEl) {
                        bodyEl.innerHTML = res.html;
                    }
                    if (res.was_unread) {
                        refreshNotificationUi();
                    }
                },
                error: function () {
                    if (bodyEl) {
                        bodyEl.innerHTML = '<div class="text-center text-danger py-4">{{ translate('failed_to_load') }}</div>';
                    }
                },
            });
        };

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
                    showCancelButton: true,
                    cancelButtonText: '{{ translate('Dismiss') }}',
                    focusConfirm: false,
                    confirmButtonText: '{{ translate('View_Details') }}',
                }).then(function (result) {
                    if (result.value && alert.id) {
                        window.pkOpenAdminNotificationModal(alert.id);
                    }
                });
            });
        };

        $(document).on('click', '.js-admin-notification-item', function (e) {
            e.preventDefault();
            e.stopPropagation();
            hideNotificationDropdown(this);
            var notificationId = $(this).data('notification-id');
            window.pkOpenAdminNotificationModal(notificationId);
        });

        $(document).on('click', '.js-admin-notification-list-item', function (e) {
            e.preventDefault();
            var notificationId = $(this).data('notification-id');
            window.pkOpenAdminNotificationModal(notificationId);
        });

        $(document).on('click', '.js-view-all-notifications', function () {
            hideNotificationDropdown(this);
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
                    refreshNotificationUi();
                },
            });
        });
    })();
</script>
