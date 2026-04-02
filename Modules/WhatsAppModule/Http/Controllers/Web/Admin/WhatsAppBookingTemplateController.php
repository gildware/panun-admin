<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;

class WhatsAppBookingTemplateController extends Controller
{
    use AuthorizesRequests;

    public function edit(): View
    {
        $this->authorize('whatsapp_message_template_view');

        $service = app(BookingWhatsAppNotificationService::class);
        $config = $service->getConfig();
        $placeholders = BookingWhatsAppNotificationService::PLACEHOLDER_HINTS;
        $statusTemplateSegments = BookingWhatsAppNotificationService::statusTemplateSegmentKeys();

        return view('whatsappmodule::admin.booking-message-templates', compact('config', 'placeholders', 'statusTemplateSegments'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $service = app(BookingWhatsAppNotificationService::class);
        $segments = BookingWhatsAppNotificationService::statusTemplateSegmentKeys();

        $rules = [
            'booking_confirmation_customer' => 'nullable|string|max:4096',
            'booking_confirmation_provider' => 'nullable|string|max:4096',
            'booking_status_customer' => 'nullable|string|max:4096',
            'booking_status_provider' => 'nullable|string|max:4096',
            'provider_change_customer' => 'nullable|string|max:4096',
            'provider_change_previous_provider' => 'nullable|string|max:4096',
            'provider_change_new_provider' => 'nullable|string|max:4096',
            'booking_schedule_customer' => 'nullable|string|max:4096',
            'booking_schedule_provider' => 'nullable|string|max:4096',
            'booking_payment_customer' => 'nullable|string|max:4096',
            'booking_payment_provider' => 'nullable|string|max:4096',
            'booking_serviceman_customer' => 'nullable|string|max:4096',
            'booking_serviceman_provider' => 'nullable|string|max:4096',
            'booking_verification_customer' => 'nullable|string|max:4096',
            'booking_verification_provider' => 'nullable|string|max:4096',
        ];
        foreach ($segments as $segment) {
            $rules['booking_status_customer_' . $segment] = 'nullable|string|max:4096';
            $rules['booking_status_provider_' . $segment] = 'nullable|string|max:4096';
        }

        $data = $request->validate($rules);

        $current = $service->getConfig();

        $liveValues = [
            'enabled' => (bool) ($current['enabled'] ?? false),
            'default_phone_prefix' => '91',
            'apply_default_phone_prefix' => true,
            'booking_confirmation_customer' => (string) ($data['booking_confirmation_customer'] ?? ''),
            'booking_confirmation_provider' => (string) ($data['booking_confirmation_provider'] ?? ''),
            'booking_status_customer' => (string) ($data['booking_status_customer'] ?? ''),
            'booking_status_provider' => (string) ($data['booking_status_provider'] ?? ''),
            'provider_change_customer' => (string) ($data['provider_change_customer'] ?? ''),
            'provider_change_previous_provider' => (string) ($data['provider_change_previous_provider'] ?? ''),
            'provider_change_new_provider' => (string) ($data['provider_change_new_provider'] ?? ''),
            'booking_schedule_customer' => (string) ($data['booking_schedule_customer'] ?? ''),
            'booking_schedule_provider' => (string) ($data['booking_schedule_provider'] ?? ''),
            'booking_payment_customer' => (string) ($data['booking_payment_customer'] ?? ''),
            'booking_payment_provider' => (string) ($data['booking_payment_provider'] ?? ''),
            'booking_serviceman_customer' => (string) ($data['booking_serviceman_customer'] ?? ''),
            'booking_serviceman_provider' => (string) ($data['booking_serviceman_provider'] ?? ''),
            'booking_verification_customer' => (string) ($data['booking_verification_customer'] ?? ''),
            'booking_verification_provider' => (string) ($data['booking_verification_provider'] ?? ''),
        ];

        foreach ($segments as $segment) {
            $liveValues['booking_status_customer_' . $segment] = (string) ($data['booking_status_customer_' . $segment] ?? '');
            $liveValues['booking_status_provider_' . $segment] = (string) ($data['booking_status_provider_' . $segment] ?? '');
            $liveValues['booking_status_invoice_customer_' . $segment] = $request->boolean('booking_status_invoice_customer_' . $segment);
            $liveValues['booking_status_invoice_provider_' . $segment] = $request->boolean('booking_status_invoice_provider_' . $segment);
        }

        BusinessSettings::updateOrCreate(
            [
                'key_name' => BookingWhatsAppNotificationService::SETTINGS_KEY,
                'settings_type' => BookingWhatsAppNotificationService::SETTINGS_TYPE,
            ],
            [
                'live_values' => $liveValues,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.booking-templates.edit');
    }

    public function toggleEnabled(Request $request): RedirectResponse
    {
        $this->authorize('whatsapp_message_template_update');

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $service = app(BookingWhatsAppNotificationService::class);
        $liveValues = $service->getConfig();
        $liveValues['enabled'] = $request->boolean('enabled');
        $liveValues['default_phone_prefix'] = '91';
        $liveValues['apply_default_phone_prefix'] = true;

        BusinessSettings::updateOrCreate(
            [
                'key_name' => BookingWhatsAppNotificationService::SETTINGS_KEY,
                'settings_type' => BookingWhatsAppNotificationService::SETTINGS_TYPE,
            ],
            [
                'live_values' => $liveValues,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.booking-templates.edit');
    }
}
