<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeSearchAccessFilter
{
    public function applies(): bool
    {
        return is_admin_employee();
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $grouped
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function filterGroupedResults(array $grouped): array
    {
        if (! $this->applies()) {
            return $grouped;
        }

        $filtered = [];

        foreach ($grouped as $type => $items) {
            $kept = collect($items)
                ->filter(fn (array $item) => $this->isAllowed((string) ($item['uri'] ?? '')))
                ->values()
                ->all();

            if ($kept !== []) {
                $filtered[$type] = $kept;
            }
        }

        return $filtered;
    }

    public function isAllowed(string $uri): bool
    {
        if (! $this->applies()) {
            return true;
        }

        $path = $this->normalizePath($uri);

        if ($path === '') {
            return false;
        }

        foreach ($this->rules() as $rule) {
            if (! $this->matchesAnyPattern($path, $rule['patterns'])) {
                continue;
            }

            if (! empty($rule['deny'])) {
                return false;
            }

            return $this->passesGates($rule['gates'] ?? []);
        }

        return false;
    }

    /**
     * Ordered most-specific first. First matching rule wins.
     *
     * @return list<array{patterns: list<string>, gates?: list<string>, deny?: bool}>
     */
    private function rules(): array
    {
        return [
            [
                'patterns' => [
                    'admin/employee/*',
                    'admin/role/*',
                    'admin/business-settings/*',
                    'admin/business-page-setup*',
                    'admin/social-media/*',
                    'admin/configuration/*',
                    'admin/subscription/*',
                    'admin/addon*',
                    'admin/add-on-activation/*',
                    'admin/data-transfer/*',
                    'admin/system-maintenance/*',
                    'admin/system-logs/*',
                    'admin/mobile-app-management/*',
                ],
                'deny' => true,
            ],
            [
                'patterns' => [
                    'admin/dashboard',
                    'admin/dashboard/*',
                    'admin/profile-update*',
                    'admin/my-progress*',
                    'admin/process-guides*',
                    'admin/task-board*',
                ],
                'gates' => [],
            ],
            [
                'patterns' => ['admin/lead/reports*'],
                'gates' => ['lead_report_view'],
            ],
            [
                'patterns' => ['admin/lead/configuration*'],
                'gates' => ['lead_configuration_view'],
            ],
            [
                'patterns' => ['admin/lead/outbound-enquiry*'],
                'gates' => ['lead_outbound_enquiry_view'],
            ],
            [
                'patterns' => ['admin/lead/todays-followups*'],
                'gates' => ['lead_view'],
            ],
            [
                'patterns' => ['admin/lead/create*'],
                'gates' => ['lead_add'],
            ],
            [
                'patterns' => ['admin/lead*'],
                'gates' => ['lead_view'],
            ],
            [
                'patterns' => ['admin/workflow/*'],
                'gates' => ['lead_view'],
            ],
            [
                'patterns' => [
                    'admin/booking/web-bookings*',
                    'admin/booking/web-provider-requests*',
                    'admin/booking/app-custom-requests*',
                ],
                'gates' => ['booking_view'],
            ],
            [
                'patterns' => [
                    'admin/booking/list/verification*',
                    'admin/booking/list/offline-payment*',
                    'admin/booking/list/special-scenarios*',
                    'admin/booking/list/cancelled-by-provider*',
                    'admin/booking/list/cancelled-by-customer*',
                    'admin/booking/reviews/*',
                    'admin/booking/todays-followups*',
                ],
                'gates' => ['booking_configuration_view'],
            ],
            [
                'patterns' => ['admin/booking/configuration*'],
                'gates' => ['booking_configuration_view'],
            ],
            [
                'patterns' => ['admin/booking/create*', 'admin/booking/post/create*'],
                'gates' => ['booking_add'],
            ],
            [
                'patterns' => [
                    'admin/booking/list*',
                    'admin/booking/details*',
                    'admin/booking/repeat*',
                    'admin/booking/rebooking*',
                    'admin/booking/success*',
                    'admin/booking/post*',
                ],
                'gates' => ['booking_view'],
            ],
            [
                'patterns' => ['admin/customer/create*'],
                'gates' => ['customer_add'],
            ],
            [
                'patterns' => [
                    'admin/customer/list*',
                    'admin/customer/detail*',
                    'admin/customer/edit/*',
                    'admin/customer/wallet/*',
                    'admin/customer/loyalty-point/*',
                    'admin/customer/newsletter/*',
                    'admin/customer-cart*',
                ],
                'gates' => ['customer_view'],
            ],
            [
                'patterns' => ['admin/provider/onboarding*'],
                'gates' => ['onboarding_request_view'],
            ],
            [
                'patterns' => ['admin/provider/create*'],
                'gates' => ['provider_add'],
            ],
            [
                'patterns' => [
                    'admin/provider/list*',
                    'admin/provider/details*',
                    'admin/provider/edit*',
                    'admin/provider/collect-cash*',
                ],
                'gates' => ['provider_view'],
            ],
            [
                'patterns' => ['admin/catalog/view*'],
                'gates' => ['category_view', 'service_view'],
            ],
            [
                'patterns' => ['admin/category/*', 'admin/sub-category/*'],
                'gates' => ['category_view', 'category_add'],
            ],
            [
                'patterns' => ['admin/service/*'],
                'gates' => ['service_view'],
            ],
            [
                'patterns' => ['admin/zone/*'],
                'gates' => ['zone_view'],
            ],
            [
                'patterns' => ['admin/social-inbox/*/marketing/templates*'],
                'gates' => ['whatsapp_marketing_template_view'],
            ],
            [
                'patterns' => ['admin/social-inbox/*/marketing/send*'],
                'gates' => ['whatsapp_marketing_bulk_view'],
            ],
            [
                'patterns' => ['admin/social-inbox/*/marketing/campaigns*'],
                'gates' => ['whatsapp_marketing_campaign_view'],
            ],
            [
                'patterns' => ['admin/social-inbox/*/marketing/reports*'],
                'gates' => ['whatsapp_marketing_report_view'],
            ],
            [
                'patterns' => [
                    'admin/social-inbox/*/booking-message-templates*',
                    'admin/social-inbox/*/ai-support*',
                    'admin/social-inbox/*/meta-capi-events*',
                ],
                'gates' => ['whatsapp_message_template_view', 'whatsapp_chat_view'],
            ],
            [
                'patterns' => ['admin/social-inbox/*'],
                'gates' => ['whatsapp_chat_view'],
            ],
            [
                'patterns' => ['admin/report/daily-employee*'],
                'gates' => ['report_view'],
            ],
            [
                'patterns' => ['admin/report/*'],
                'gates' => ['report_view'],
            ],
            [
                'patterns' => ['admin/analytics/*'],
                'gates' => ['analytics_view'],
            ],
            [
                'patterns' => ['admin/transaction/*', 'admin/ledger/*'],
                'gates' => ['transaction_view', 'ledger_view'],
            ],
            [
                'patterns' => ['admin/discount/*'],
                'gates' => ['discount_view'],
            ],
            [
                'patterns' => ['admin/coupon/*'],
                'gates' => ['coupon_view'],
            ],
            [
                'patterns' => ['admin/bonus/*'],
                'gates' => ['bonus_view'],
            ],
            [
                'patterns' => ['admin/campaign/*'],
                'gates' => ['campaign_view'],
            ],
            [
                'patterns' => ['admin/advertisements/*'],
                'gates' => ['advertisement_view'],
            ],
            [
                'patterns' => ['admin/banner/*'],
                'gates' => ['banner_view'],
            ],
            [
                'patterns' => ['admin/push-notification/*'],
                'gates' => ['push_notification_view'],
            ],
            [
                'patterns' => ['admin/chat/*'],
                'gates' => ['dashboard'],
            ],
            [
                'patterns' => ['admin/voice-call/*'],
                'gates' => ['lead_view', 'lead_outbound_enquiry_view'],
            ],
        ];
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesAnyPattern(string $path, array $patterns): bool
    {
        $request = Request::create('/'.$path);

        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $gates
     */
    private function passesGates(array $gates): bool
    {
        if ($gates === []) {
            return true;
        }

        foreach ($gates as $gate) {
            if (Gate::allows($gate)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $uri): string
    {
        $uri = trim($uri);

        if ($uri === '') {
            return '';
        }

        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            $uri = (string) (parse_url($uri, PHP_URL_PATH) ?? '');
        }

        if (($queryPos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $queryPos);
        }

        return ltrim($uri, '/');
    }
}
