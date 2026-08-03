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
                case 'booking_assigned':
                    return { icon: 'info' };
                case 'provider_withdrawal':
                    return { icon: 'warning' };
                case 'chat_message':
                case 'whatsapp_assigned':
                case 'whatsapp_human_support':
                case 'lead_followup_due':
                    return { icon: 'info' };
                case 'provider_request':
                    return { icon: 'info' };
                case 'withdraw_request':
                    return { icon: 'warning' };
                case 'advertisement':
                    return { icon: 'info' };
                case 'service_request':
                    return { icon: 'info' };
                case 'web_booking':
                case 'lead':
                    return { icon: 'info' };
                case 'app_custom_request':
                    return { icon: 'info' };
                case 'showcase':
                    return { icon: 'info' };
                case 'ticket_assigned':
                case 'ticket_comment':
                case 'lead_assigned':
                case 'booking_assigned':
                case 'lead_comment':
                case 'booking_comment':
                    return { icon: 'info' };
                default:
                    return { icon: 'info' };
            }
        }

        function typesWithDirectNavigation() {
            return ['chat_message', 'whatsapp_assigned', 'whatsapp_human_support', 'ticket_assigned', 'ticket_comment', 'lead_assigned', 'booking_assigned', 'lead_comment', 'booking_comment', 'lead', 'lead_followup_due'];
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

        function openNotificationTarget(notificationId, notificationType, actionUrl, dropdownEl) {
            if (typesWithDirectNavigation().indexOf(notificationType) !== -1 && actionUrl) {
                hideNotificationDropdown(dropdownEl);
                markNotificationRead(notificationId);
                window.location.href = actionUrl;
                return;
            }

            window.pkOpenAdminNotificationModal(notificationId);
        }

        function updateNotificationBadge(countEl, unread) {
            if (!countEl) {
                return;
            }
            countEl.innerHTML = unread > 0 ? (unread > 99 ? '99+' : unread) : '';
            countEl.style.display = unread > 0 ? 'flex' : 'none';
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

            var externalUnread = parseInt(data.notification_external_unread_count, 10);
            var internalUnread = parseInt(data.notification_internal_unread_count, 10);
            if (isNaN(externalUnread)) externalUnread = 0;
            if (isNaN(internalUnread)) internalUnread = 0;

            updateNotificationBadge(document.getElementById('notification_external_count'), externalUnread);
            updateNotificationBadge(document.getElementById('notification_internal_count'), internalUnread);

            var externalListEl = document.getElementById('show-notification-list-external');
            if (externalListEl && data.notification_external_template) {
                externalListEl.innerHTML = data.notification_external_template;
            }

            var internalListEl = document.getElementById('show-notification-list-internal');
            if (internalListEl && data.notification_internal_template) {
                internalListEl.innerHTML = data.notification_internal_template;
            }

            var totalUnread = externalUnread + internalUnread;

            if (!skipSound && totalUnread > 0) {
                var prevKey = 'admin_notification_unread_count';
                var prevRaw = sessionStorage.getItem(prevKey);
                if (prevRaw !== null && prevRaw !== '') {
                    var prev = parseInt(prevRaw, 10) || 0;
                    if (totalUnread > prev && typeof window.pkPlayStaffNotificationSound === 'function') {
                        window.pkPlayStaffNotificationSound();
                    }
                }
                sessionStorage.setItem(prevKey, String(totalUnread));
            } else if (skipSound) {
                sessionStorage.setItem('admin_notification_unread_count', String(totalUnread));
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

                var directNav = typesWithDirectNavigation().indexOf(alert.type) !== -1 && alert.action_url;

                Swal.fire({
                    title: alert.title || '{{ translate('New_Notification') }}',
                    text: alert.body || '',
                    icon: cfg.icon,
                    showCloseButton: true,
                    showCancelButton: true,
                    cancelButtonText: '{{ translate('Dismiss') }}',
                    focusConfirm: false,
                    confirmButtonText: directNav
                        ? (alert.action_label || '{{ translate('View_Details') }}')
                        : '{{ translate('View_Details') }}',
                }).then(function (result) {
                    if (!result.value || !alert.id) {
                        return;
                    }

                    if (directNav) {
                        markNotificationRead(alert.id);
                        window.location.href = alert.action_url;
                        return;
                    }

                    window.pkOpenAdminNotificationModal(alert.id);
                });
            });
        };

        $(document).on('click', '.js-admin-notification-item', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openNotificationTarget(
                $(this).data('notification-id'),
                $(this).data('notification-type'),
                $(this).data('action-url'),
                this
            );
        });

        $(document).on('click', '.js-admin-notification-list-item', function (e) {
            e.preventDefault();
            openNotificationTarget(
                $(this).data('notification-id'),
                $(this).data('notification-type'),
                $(this).data('action-url'),
                this
            );
        });

        $(document).on('click', '.js-view-all-notifications', function () {
            hideNotificationDropdown(this);
        });

        $(document).on('click', '.js-mark-all-notifications-read', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var category = $(this).data('notification-category') || null;
            $.ajax({
                url: '{{ route('admin.notifications.mark_all_read') }}',
                type: 'POST',
                dataType: 'json',
                data: category ? { category: category } : {},
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function () {
                    refreshNotificationUi();
                },
            });
        });

        $(document).on('show.bs.dropdown', '.notification.update-notification, .top-utility-item', function (e) {
            if (!this.querySelector('[id^="show-notification-list-"]')) {
                return;
            }
            refreshNotificationUi();
        });
    })();
</script>
