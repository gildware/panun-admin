<?php

namespace Modules\LeadManagement\Services;

use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadHuntingInterest;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Throwable;

class LeadHuntingNotificationService
{
    public function notifyProvidersJobPublished(Lead $lead): void
    {
        if ($lead->hunting_status !== Lead::HUNTING_PUBLISHED) {
            return;
        }

        $board = app(LeadHuntingBoardService::class);
        $data = $board->latestCustomerData($lead);
        $public = $board->publicJobFields($data);
        $providers = $board->matchingProvidersForLead($lead);

        $serviceName = $this->displayName($public['service_name'] ?? '')
            ?: $this->displayName($public['subcategory_name'] ?? '');
        $schedule = $public['estimated_at']?->format('d M Y, h:i A') ?? '';

        foreach ($providers as $provider) {
            try {
                $this->sendProviderOpenRequestPush($provider, [
                    'provider_name' => (string) ($provider->company_name ?? ''),
                    'service_name' => $serviceName,
                    'subcategory_name' => $this->displayName($public['subcategory_name'] ?? ''),
                    'area_name' => $this->displayName($public['area_name'] ?? ''),
                    'zone_name' => $this->displayName($public['zone_name'] ?? ''),
                    'schedule_time' => $schedule,
                ], 'open_request_published');
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    public function notifyProvidersJobReminder(Lead $lead, string $message): int
    {
        if ($lead->hunting_status !== Lead::HUNTING_PUBLISHED) {
            return 0;
        }

        $message = trim($message);
        if ($message === '') {
            return 0;
        }

        $board = app(LeadHuntingBoardService::class);
        $data = $board->latestCustomerData($lead);
        $public = $board->publicJobFields($data);
        $providers = $board->pendingActionProvidersForLead($lead);

        $serviceName = $this->displayName($public['service_name'] ?? '')
            ?: $this->displayName($public['subcategory_name'] ?? '');
        $schedule = $public['estimated_at']?->format('d M Y, h:i A') ?? '';
        $sent = 0;

        foreach ($providers as $provider) {
            try {
                $this->sendProviderOpenRequestPush($provider, [
                    'provider_name' => (string) ($provider->company_name ?? ''),
                    'service_name' => $serviceName,
                    'subcategory_name' => $this->displayName($public['subcategory_name'] ?? ''),
                    'area_name' => $this->displayName($public['area_name'] ?? ''),
                    'zone_name' => $this->displayName($public['zone_name'] ?? ''),
                    'schedule_time' => $schedule,
                    'reminder_message' => $message,
                ], 'open_request_reminder');
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }

    public function notifyAdminProviderInterest(
        Lead $lead,
        Provider $provider,
        LeadHuntingInterest $interest,
    ): void {
        try {
            admin_inbox_notify_hunting_interest($lead, $provider, $interest->note);
            $this->sendAdminOpenRequestPush(
                translate('Provider_interested_in_open_request'),
                $this->adminOpenRequestBody($lead, $provider, $interest->note),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function notifyAdminProviderInterestRevoked(Lead $lead, Provider $provider): void
    {
        try {
            admin_inbox_notify_hunting_interest_revoked($lead, $provider);
            $this->sendAdminOpenRequestPush(
                translate('Provider_revoked_open_request_interest'),
                $this->adminOpenRequestBody($lead, $provider),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function notifyAdminProviderRejected(Lead $lead, Provider $provider, string $reason): void
    {
        try {
            admin_inbox_notify_hunting_rejected($lead, $provider, $reason);
            $this->sendAdminOpenRequestPush(
                translate('Provider_rejected_open_request'),
                $this->adminOpenRequestBody($lead, $provider, $reason),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, string>  $templateData
     */
    private function sendProviderOpenRequestPush(Provider $provider, array $templateData, string $messageKey): void
    {
        $this->ensureProviderMessageSettings($messageKey);

        $owner = $provider->owner ?: $provider->user;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        $title = get_push_notification_message($messageKey, 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description($messageKey, 'provider_notification', $owner->current_language_key);

        if ($title === 0) {
            return;
        }

        if ($title === false) {
            $default = get_notification_default_message($messageKey, 'provider_notification');
            if ($default) {
                $title = $default['title'];
                $description = $default['description'];
            }
        }

        if (! is_string($title) || trim($title) === '') {
            return;
        }

        scenario_push_notification(
            $owner,
            $title,
            (string) ($description ?? ''),
            null,
            'open_request',
            $owner->id,
            $templateData,
            null,
            null,
            'provider-admin',
            null,
        );
    }

    private function sendAdminOpenRequestPush(string $title, string $body): void
    {
        $admins = User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->get();

        foreach ($admins as $admin) {
            if (! function_exists('user_has_fcm_devices') || ! user_has_fcm_devices($admin)) {
                continue;
            }

            scenario_push_notification(
                $admin,
                $title,
                $body,
                null,
                'open_request',
                $admin->id,
            );
        }
    }

    private function adminOpenRequestBody(Lead $lead, Provider $provider, ?string $note = null): string
    {
        $providerName = trim((string) ($provider->company_name ?? '')) ?: translate('Provider');
        $leadLabel = trim((string) ($lead->name ?? ''));
        if ($leadLabel === '') {
            $leadLabel = translate('Lead') . ' #' . $lead->id;
        }

        $body = $providerName . ' — ' . $leadLabel . ' #' . $lead->id;
        $note = trim((string) $note);
        if ($note !== '') {
            $body .= ' · ' . $note;
        }

        return $body;
    }

    private function ensureProviderMessageSettings(string $messageKey): void
    {
        $exists = \Modules\BusinessSettingsModule\Entities\BusinessSettings::query()
            ->where('key_name', $messageKey)
            ->where('settings_type', 'provider_notification')
            ->exists();
        if ($exists) {
            return;
        }

        $default = get_notification_default_message($messageKey, 'provider_notification');
        if (! $default) {
            return;
        }

        $liveValues = [
            $messageKey . '_status' => '1',
            $messageKey . '_message' => $default['title'],
            $messageKey . '_description' => $default['description'],
        ];

        \Modules\BusinessSettingsModule\Entities\BusinessSettings::query()->create([
            'key_name' => $messageKey,
            'live_values' => $liveValues,
            'test_values' => $liveValues,
            'settings_type' => 'provider_notification',
            'mode' => 'live',
            'is_active' => 1,
        ]);
    }

    private function displayName(string $value): string
    {
        $trimmed = trim($value);

        return ($trimmed === '' || $trimmed === '—') ? '' : $trimmed;
    }
}
