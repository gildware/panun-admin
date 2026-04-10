<?php

namespace Modules\WhatsAppModule\Services;

use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\ProviderBookingSettlementNetResolver;
use Modules\UserManagement\Entities\User;

/**
 * Variable maps for Meta templates: provider/customer ledger payment reminders and collect/payout confirmations.
 */
class LedgerPaymentWhatsAppService
{
    public function __construct(
        protected BookingWhatsAppNotificationService $notifications,
    ) {}

    public function notifications(): BookingWhatsAppNotificationService
    {
        return $this->notifications;
    }

    /**
     * Send configured Meta template for provider payment reminder (same rules as provider payment tab).
     *
     * @return array{
     *     ok: bool,
     *     nothing_due?: bool,
     *     message: string,
     *     meta_detail: ?string,
     *     chat_url: ?string,
     *     show_chat_link: bool,
     *     attempted_recipient: string,
     *     attempted_raw: string,
     *     attempted_normalized_digits: string
     * }
     */
    public function trySendProviderPaymentReminder(Provider $provider, ?int $adminUserId): array
    {
        $provider->loadMissing(['owner.account']);

        if (!$provider->owner) {
            return [
                'ok' => false,
                'message' => translate('Provider_or_account_not_found'),
                'meta_detail' => null,
                'chat_url' => null,
                'show_chat_link' => false,
                'attempted_recipient' => '',
                'attempted_raw' => '',
                'attempted_normalized_digits' => '',
            ];
        }

        $net = app(ProviderBookingSettlementNetResolver::class)
            ->resolveForProviderId((string) $provider->id)['booking_settlement_net'];

        if (max(0.0, -$net) <= 0.009 && (float) ($provider->owner->account->account_payable ?? 0) <= 0.009) {
            return [
                'ok' => false,
                'nothing_due' => true,
                'message' => translate('WhatsApp_provider_payment_reminder_nothing_due'),
                'meta_detail' => null,
                'chat_url' => null,
                'show_chat_link' => false,
                'attempted_recipient' => '',
                'attempted_raw' => '',
                'attempted_normalized_digits' => '',
            ];
        }

        $waRecipientDetail = $this->notifications->providerLedgerRecipientPhoneDetail($provider);
        $waRecipientLabel = $this->notifications->providerLedgerRecipientLabelForErrors($provider);
        $vars = $this->varsProviderPaymentReminder($provider, $net);

        $sent = $this->notifications->sendConfiguredLedgerMetaToProvider(
            $provider,
            'ledger_provider_payment_reminder',
            $vars,
            $adminUserId
        );

        $failMessage = __('lang.WhatsApp_payment_reminder_failed_with_recipient', [
            'recipient' => $waRecipientLabel,
        ]);
        $metaRaw = $this->notifications->pullLedgerSendFailureDetail();
        if (!$sent && $metaRaw) {
            $failMessage .= ' ' . __('lang.WhatsApp_meta_api_detail_suffix', [
                'detail' => BookingWhatsAppNotificationService::formatMetaFailureForAdmin($metaRaw),
            ]);
        }

        $provider->loadMissing(['owner']);
        $rawChat = (string) ($provider->owner?->phone
            ?: $provider->contact_person_phone
            ?: $provider->company_phone
            ?: '');
        $chatPhoneKey = app(WhatsAppMessagePersistenceService::class)->resolveAdminChatPhoneKey($rawChat);
        $canViewChat = auth()->user()?->can('whatsapp_chat_view') ?? false;
        $chatUrl = ($sent && $chatPhoneKey !== null && $canViewChat)
            ? route('admin.whatsapp.conversations.chat', ['phone' => $chatPhoneKey])
            : null;

        return [
            'ok' => $sent,
            'message' => $sent ? translate('WhatsApp_payment_reminder_sent') : $failMessage,
            'meta_detail' => (!$sent && $metaRaw) ? BookingWhatsAppNotificationService::formatMetaFailureForAdmin($metaRaw) : null,
            'chat_url' => $chatUrl,
            'show_chat_link' => $chatUrl !== null,
            'attempted_recipient' => $waRecipientLabel,
            'attempted_raw' => (string) ($waRecipientDetail['raw'] ?? ''),
            'attempted_normalized_digits' => (string) ($waRecipientDetail['normalized_digits'] ?? ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function varsProviderPaymentReminder(Provider $provider, float $bookingSettlementNet): array
    {
        $pending = max(0.0, round(-$bookingSettlementNet, 2));

        $due = with_currency_symbol($pending);

        return array_merge(
            $this->providerIdentityVars($provider),
            $this->blankLedgerAmountTokens(),
            [
                '{provider_pending_balance}' => $due,
                '{provider_due_balance}' => $due,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    public function varsCustomerPaymentReminder(User $customer, float $pendingBadDebtLossMaking): array
    {
        $pending = max(0.0, round($pendingBadDebtLossMaking, 2));

        return array_merge(
            $this->customerIdentityVars($customer),
            $this->blankLedgerAmountTokens(),
            [
                '{customer_pending_balance}' => with_currency_symbol($pending),
            ]
        );
    }

    /**
     * After collect IN from provider: settlement net increases by collected amount.
     *
     * @return array<string, string>
     */
    public function varsPaymentReceivedFromProvider(Provider $provider, float $amount, float $bookingSettlementNetBefore): array
    {
        $bookingSettlementNetAfter = round($bookingSettlementNetBefore + $amount, 2);
        $balanceAfterPaymentCollected = max(0.0, round(-$bookingSettlementNetAfter, 2));
        $dueBefore = with_currency_symbol(max(0.0, -$bookingSettlementNetBefore));
        $amountCollectedFormatted = with_currency_symbol($amount);
        $balanceAfterFormatted = with_currency_symbol($balanceAfterPaymentCollected);
        $netAfterFormatted = with_currency_symbol($bookingSettlementNetAfter);

        return array_merge(
            $this->providerIdentityVars($provider),
            $this->blankLedgerAmountTokens(),
            [
                '{provider_pending_balance}' => $dueBefore,
                '{provider_due_balance}' => $dueBefore,
                '{amount_received_from_provider}' => $amountCollectedFormatted,
                '{amount_collected_from_provider}' => $amountCollectedFormatted,
                '{balance_after_payment_collected}' => $balanceAfterFormatted,
                '{booking_settlement_net_after_collect}' => $netAfterFormatted,
                '{remaining_balance_to_collect}' => $balanceAfterFormatted,
            ]
        );
    }

    /**
     * After ledger OUT to provider: settlement net decreases by paid amount.
     *
     * @return array<string, string>
     */
    public function varsPaymentSentToProvider(Provider $provider, float $amount, float $bookingSettlementNetBefore): array
    {
        $newNet = round($bookingSettlementNetBefore - $amount, 2);
        $remainingSend = max(0.0, $newNet);

        return array_merge(
            $this->providerIdentityVars($provider),
            $this->blankLedgerAmountTokens(),
            [
                '{amount_sent_to_provider}' => with_currency_symbol($amount),
                '{remaining_balance_to_send}' => with_currency_symbol($remainingSend),
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    protected function providerIdentityVars(Provider $provider): array
    {
        $provider->loadMissing(['owner']);
        $name = $provider->company_name ?: ($provider->contact_person_name ?? '—');
        $phone = (string) ($provider->owner?->phone ?? '');

        return [
            '{provider_name}' => $name,
            '{provider_phone}' => $phone,
            '{customer_name}' => '',
            '{customer_phone}' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function customerIdentityVars(User $customer): array
    {
        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if ($name === '') {
            $name = '—';
        }

        return [
            '{customer_name}' => $name,
            '{customer_phone}' => (string) ($customer->phone ?? ''),
            '{provider_name}' => '',
            '{provider_phone}' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function blankLedgerAmountTokens(): array
    {
        return [
            '{provider_pending_balance}' => '',
            '{provider_due_balance}' => '',
            '{customer_pending_balance}' => '',
            '{amount_received_from_provider}' => '',
            '{amount_collected_from_provider}' => '',
            '{balance_after_payment_collected}' => '',
            '{booking_settlement_net_after_collect}' => '',
            '{amount_sent_to_provider}' => '',
            '{remaining_balance_to_collect}' => '',
            '{remaining_balance_to_send}' => '',
        ];
    }
}
