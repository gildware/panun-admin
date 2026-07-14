<?php

use Modules\BookingModule\Entities\AppCustomRequest;
use Modules\BookingModule\Entities\AppCustomRequestMessage;
use Modules\UserManagement\Entities\User;

if (! function_exists('app_custom_request_notification_fallbacks')) {
    /**
     * @return array{0: string, 1: string}|null
     */
    function app_custom_request_notification_fallbacks(string $messageKey): ?array
    {
        return match ($messageKey) {
            'app_custom_request_submitted' => [
                translate('Custom_request_submitted'),
                translate('We_received_your_custom_request'),
            ],
            'app_custom_request_accepted' => [
                translate('Custom_request_accepted'),
                translate('Your_custom_request_was_accepted'),
            ],
            'app_custom_request_rejected' => [
                translate('Custom_request_rejected'),
                translate('Your_custom_request_was_rejected'),
            ],
            'app_custom_request_admin_reply' => [
                translate('Admin_replied_to_custom_request'),
                translate('Open_your_custom_request_to_read_the_reply'),
            ],
            default => null,
        };
    }
}

if (! function_exists('app_custom_request_notification_data')) {
    /**
     * @return array<string, string>
     */
    function app_custom_request_notification_data(AppCustomRequest $customRequest): array
    {
        $customRequest->loadMissing('customer');

        return [
            'user_name' => $customRequest->name,
            'userName' => $customRequest->name,
            'reference_id' => $customRequest->reference_id,
            'referenceId' => $customRequest->reference_id,
            'category_name' => $customRequest->category_name ?? '',
            'categoryName' => $customRequest->category_name ?? '',
            'request_status' => ucfirst($customRequest->status),
            'requestStatus' => ucfirst($customRequest->status),
        ];
    }
}

if (! function_exists('send_app_custom_request_customer_notification')) {
    function send_app_custom_request_customer_notification(
        AppCustomRequest $customRequest,
        string $messageKey,
        ?string $bookingType = null,
    ): void {
        $customRequest->loadMissing('customer');
        $customer = $customRequest->customer;
        if (! $customer || ! $customer->is_active) {
            return;
        }

        $languageKey = $customer->current_language_key;
        $title = get_push_notification_message($messageKey, 'customer_notification', $languageKey);
        $description = get_push_notification_description($messageKey, 'customer_notification', $languageKey);

        if (! $title) {
            $fallbacks = app_custom_request_notification_fallbacks($messageKey);
            if (! $fallbacks) {
                return;
            }
            [$title, $description] = $fallbacks;
        }

        scenario_push_notification(
            $customer,
            $title,
            $description,
            (string) $customRequest->id,
            'app_custom_request',
            (string) $customer->id,
            app_custom_request_notification_data($customRequest),
            $bookingType ?? $messageKey,
            null,
            'customer',
            config('zone_id'),
            null,
            null,
            'admin',
            null,
        );
    }
}

if (! function_exists('send_app_custom_request_submitted_notifications')) {
    function send_app_custom_request_submitted_notifications(AppCustomRequest $customRequest): void
    {
        try {
            admin_inbox_notify_app_custom_request_submitted($customRequest);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            send_app_custom_request_customer_notification($customRequest, 'app_custom_request_submitted', 'submitted');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

if (! function_exists('send_app_custom_request_status_change_notification')) {
    function send_app_custom_request_status_change_notification(
        AppCustomRequest $customRequest,
        string $previousStatus,
    ): void {
        if ($previousStatus === $customRequest->status) {
            return;
        }

        $messageKey = match ($customRequest->status) {
            AppCustomRequest::STATUS_ACCEPTED => 'app_custom_request_accepted',
            AppCustomRequest::STATUS_REJECTED => 'app_custom_request_rejected',
            default => null,
        };

        if (! $messageKey) {
            return;
        }

        send_app_custom_request_customer_notification($customRequest, $messageKey, 'status_change');
    }
}

if (! function_exists('send_app_custom_request_admin_reply_notification')) {
    function send_app_custom_request_admin_reply_notification(AppCustomRequest $customRequest): void
    {
        send_app_custom_request_customer_notification($customRequest, 'app_custom_request_admin_reply', 'admin_reply');
    }
}

if (! function_exists('admin_inbox_notify_app_custom_request_customer_reply')) {
    function admin_inbox_notify_app_custom_request_customer_reply(
        AppCustomRequest $customRequest,
        AppCustomRequestMessage $message,
    ): void {
        $label = trim($customRequest->name . ' — ' . ($customRequest->reference_id ?: ('#' . $customRequest->id)));

        admin_inbox_notify_all(
            UserNotification::TYPE_APP_CUSTOM_REQUEST,
            translate('Customer_replied_to_custom_request'),
            $label,
            route('admin.booking.app-custom-requests.show', $customRequest->id),
            'app_custom_request_customer_reply',
            (string) $customRequest->id,
        );
    }
}
