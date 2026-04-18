<?php

namespace Modules\WhatsAppModule\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\BusinessSettingsModule\Entities\AdditionalChargeType;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppBookingAutomationMessageLog;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

class BookingWhatsAppNotificationService
{
    /**
     * Cached: {@see whatsapp_booking_automation_message_logs} may exist without a `channel` column until migrations run.
     * Writing `channel` then causes a silent insert failure (caught in {@see writeAutomationLog()}).
     */
    private static ?bool $automationLogTableHasChannelColumn = null;

    /**
     * Set on failed ledger/template sends so admin UIs can show Meta's real error (e.g. #132001 wrong template language).
     */
    protected ?string $ledgerSendFailureDetail = null;

    protected function automationLogTableHasChannelColumn(): bool
    {
        if (self::$automationLogTableHasChannelColumn !== null) {
            return self::$automationLogTableHasChannelColumn;
        }

        self::$automationLogTableHasChannelColumn = Schema::hasTable('whatsapp_booking_automation_message_logs')
            && Schema::hasColumn('whatsapp_booking_automation_message_logs', 'channel');

        return self::$automationLogTableHasChannelColumn;
    }

    /**
     * For tests that recreate the automation log table with different columns.
     */
    public static function resetAutomationLogChannelSchemaCache(): void
    {
        self::$automationLogTableHasChannelColumn = null;
    }

    public const SETTINGS_KEY = 'whatsapp_booking_templates';

    public const SETTINGS_TYPE = 'whatsapp';

    public const CACHE_PREVIOUS_STATUS_PREFIX = 'wa:booking_prev_status:';

    public const CACHE_CONFIRM_LOCK_PREFIX = 'wa:lock:bcf:';

    public const CACHE_CONFIRM_SENT_PREFIX = 'wa:bcf:sent:';

    public const CACHE_STATUS_LOCK_PREFIX = 'wa:lock:bst:';

    public const CACHE_STATUS_SENT_PREFIX = 'wa:bst:sent:';

    /** @see sendBookingStatusChange() Coalesces observer + controller invocations for the same transition. */
    public const CACHE_STATUS_NOTIFY_DEDUPE_PREFIX = 'wa:bst:notify:';

    public const CACHE_PROVIDER_CHANGE_LOCK_PREFIX = 'wa:lock:bpc:';

    public const CACHE_PROVIDER_CHANGE_SENT_PREFIX = 'wa:bpc:sent:';

    public const CACHE_SCHEDULE_LOCK_PREFIX = 'wa:lock:bsc:';

    public const CACHE_SCHEDULE_SENT_PREFIX = 'wa:bsc:sent:';

    public const CACHE_PAYMENT_LOCK_PREFIX = 'wa:lock:bpy:';

    public const CACHE_PAYMENT_SENT_PREFIX = 'wa:bpy:sent:';

    public const CACHE_REOPEN_RESOLVED_LOCK_PREFIX = 'wa:lock:brv:';

    public const CACHE_REOPEN_RESOLVED_SENT_PREFIX = 'wa:brv:sent:';

    public const CACHE_DISPUTED_REOPEN_REFUND_LOCK_PREFIX = 'wa:lock:bdr:';

    public const CACHE_DISPUTED_REOPEN_REFUND_SENT_PREFIX = 'wa:bdr:sent:';

    private const WHATSAPP_DOC_CAPTION_MAX = 1024;

    /** @var array<string, string> */
    public const PLACEHOLDER_HINTS = [
        '{service_name}' => 'Service name(s)',
        '{customer_address}' => 'Customer address',
        '{customer_name}' => 'Customer name',
        '{customer_phone}' => 'Customer phone',
        '{provider_name}' => 'Provider / company name',
        '{provider_phone}' => 'Provider phone',
        '{booking_id}' => 'Booking reference (readable id)',
        '{booking_datetime}' => 'Scheduled service date & time (e.g. 4th April 2026 11:33 AM)',
        '{service_where}' => 'Where service will be provided',
        '{total_bill}' => 'Total bill',
        '{amount_paid}' => 'Amount paid so far',
        '{due_amount}' => 'Amount due',
        '{booking_status}' => 'Current booking status',
        '{previous_booking_status}' => 'Previous booking status (status updates only)',
        '{previous_provider_name}' => 'Previous provider name (provider change only)',
        '{previous_provider_phone}' => 'Previous provider phone (provider change only)',
        '{previous_service_schedule}' => 'Previous service date & time (schedule change only; same format as booking date/time)',
        '{amount_added}' => 'Amount added (Add payment modal)',
        '{payment_received_by}' => 'Received by (Company / Provider)',
        '{payment_date}' => 'Payment date',
        '{payment_method}' => 'Payment method (cash / bank / etc.)',
        '{payment_reference}' => 'Transaction / reference id (if any)',
        '{customer_payments_total}' => 'Total received from customer (all booking partial payments)',
        '{customer_payments_to_company}' => 'Total received by company from customer (partial payments marked company)',
        '{customer_payments_to_provider}' => 'Total received by provider from customer (partial payments marked provider)',
        '{customer_payments_unassigned}' => 'Customer partial payments with no received-by set',
        '{serviceman_name}' => 'Assigned serviceman name',
        '{serviceman_phone}' => 'Assigned serviceman phone',
        '{previous_serviceman_name}' => 'Previous serviceman name (assignment change only)',
        '{previous_serviceman_phone}' => 'Previous serviceman phone (assignment change only)',
        '{verification_status}' => 'Booking verification state (e.g. Approved, Denied)',
        '{previous_verification_status}' => 'Previous verification state',
        '{verification_action}' => 'Last verification action: approve, deny, or cancel',
        '{reopen_resolve_remarks}' => 'Remarks when a reopen case is marked resolved (reopen resolved template only)',
        '{booking_cancellation_reason}' => 'Cancellation reason (from the latest cancel action on this booking)',
        '{on_hold_reason}' => 'Put on hold — reason (from the latest hold action on this booking)',
        '{on_hold_reason_remarks}' => 'Put on hold — remarks/notes (from the latest hold action on this booking)',
        '{reopen_from_completed_reason}' => 'Reopen from completed — reason or notes (from reopen flow)',
        '{provider_pending_balance}' => 'Amount provider owes the company (settlement basis; payment reminders to provider)',
        '{provider_due_balance}' => 'Provider amount due (same value as provider pending balance; for Meta templates using provider_due_balance)',
        '{customer_pending_balance}' => 'Amount customer still owes (loss-making / pending debit; payment reminders to customer)',
        '{amount_received_from_provider}' => 'Amount just collected from provider (ledger IN); same value as {amount_collected_from_provider}',
        '{amount_collected_from_provider}' => 'Amount collected from provider in this step (ledger IN)',
        '{balance_after_payment_collected}' => 'Remaining settlement balance to collect from provider after this collection (0 if none)',
        '{booking_settlement_net_after_collect}' => 'Booking settlement net after this collection (signed; negative means provider still owes)',
        '{amount_sent_to_provider}' => 'Amount just sent to provider (ledger OUT)',
        '{remaining_balance_to_collect}' => 'Same as {balance_after_payment_collected} (backward compatible)',
        '{remaining_balance_to_send}' => 'Remaining amount to pay to provider after this payout',
        '{disputed_refund_total}' => 'Disputed close — total refund recorded to customer (company + provider legs)',
        '{disputed_refund_company}' => 'Disputed close — refund amount recorded against the company pool',
        '{disputed_refund_provider}' => 'Disputed close — refund amount recorded against the provider pool',
        '{disputed_customer_retained}' => 'Disputed close — customer cash retained after refunds (net kept on booking)',
        '{booking_final_amount}' => 'Total booking order amount at send time (same basis as total bill; use in reopen resolved / settlement copy)',
        '{settlement_outcome_label}' => 'Special financial settlement outcome label (e.g. loss-making / scaled, visit retained)',
        '{settlement_remarks}' => 'Admin remarks saved with special settlement configuration',
        '{scaled_loss_total}' => 'Loss-making (scaled): total economic loss amount from settlement snapshot',
        '{scaled_loss_company_share}' => 'Loss-making: company share of scaled loss',
        '{scaled_loss_provider_share}' => 'Loss-making: provider share of scaled loss',
        '{scaled_net_company_share}' => 'Loss-making: net company commission share after scaled loss',
        '{scaled_net_provider_share}' => 'Loss-making: net provider earning share after scaled loss',
        '{scaled_customer_paid_amount}' => 'Loss-making: customer paid amount (capped) from settlement snapshot',
        '{booking_customer_still_due}' => 'Amount still due from customer on this booking (admin settlement / due basis)',
        '{settlement_company_pays_provider}' => 'Net settlement: amount company must pay the provider (same basis as booking details Pay to provider)',
        '{settlement_provider_pays_company}' => 'Net settlement: amount provider must remit to the company (provider owes company)',
        '{disputed_provider_owes_company}' => 'Disputed close: refund pool amount attributed as provider owes company',
        '{disputed_company_owes_provider}' => 'Disputed close: refund pool amount attributed as company owes provider',
        '{disputed_company_cash_after_refund}' => 'Disputed close: company-collected cash after refund legs',
        '{disputed_provider_cash_after_refund}' => 'Disputed close: provider-collected cash after refund legs',
        '{disputed_final_admin_commission}' => 'Disputed close: final net company commission after dispute distribution',
        '{disputed_final_provider_earning}' => 'Disputed close: final net provider earning after dispute distribution',
        '{disputed_company_pays_provider_total}' => 'Disputed close: total company pays provider (net settlement)',
        '{disputed_provider_remittance_total}' => 'Disputed close: total provider remittance to company',
        '{dispute_reason}' => 'Disputed close — dispute reason (same as Dispute and close modal)',
        '{refund_paid_from_company_pool}' => 'Disputed close — refund paid from company pool',
        '{refund_paid_from_provider_pool}' => 'Disputed close — refund paid from provider pool',
        '{refund_company_transaction_id}' => 'Disputed close — reference / transaction ID (company leg)',
        '{refund_provider_transaction_id}' => 'Disputed close — reference / transaction ID (provider leg)',
        '{final_services_charges_retained_from_customer}' => 'Disputed close — final Services Charges retained from customer',
        '{final_spare_parts_charges_retained_from_customer}' => 'Disputed close — final Spare Parts Charges retained from customer',
        '{final_admin_commission_services_net_basis}' => 'Disputed close — final admin commission Services (net basis)',
        '{final_provider_earning_services_net_basis}' => 'Disputed close — final provider earning Services (net basis)',
        '{final_admin_commission_spare_parts_net_basis}' => 'Disputed close — final admin commission Spare Parts (net basis)',
        '{final_provider_earning_spare_parts_net_basis}' => 'Disputed close — final provider earning Spare Parts (net basis)',
        '{final_amount_retained_from_customer_after_refunds}' => 'Disputed close — final amount retained from customer after refunds',
        '{disputed_total_provider_pays_company}' => 'Disputed close — total provider pays company',
        '{disputed_total_company_pays_provider}' => 'Disputed close — total company pays provider',
        '{compensation_amount}' => 'Compensation: amount for this compensation row (when compensation message is sent)',
        '{compensation_from_party_label}' => 'Compensation: payer (Company / Provider)',
        '{compensation_to_party_label}' => 'Compensation: recipient (Customer / Provider)',
        '{compensation_direction}' => 'Compensation: human-readable direction (e.g. Company → Customer)',
        '{compensation_reference_note}' => 'Compensation: admin reference note',
        '{compensation_date}' => 'Compensation: recorded date',
        '{compensation_transaction_id}' => 'Compensation: transaction / reference id',
        '{refund_amount}' => 'Refund: amount recorded in this step (customer refund)',
        '{refund_date}' => 'Refund: recorded date',
        '{refund_transaction_id}' => 'Refund: transaction / reference id',
        '{refund_reference_note}' => 'Refund: admin reference note (if any)',
        '{refund_remaining}' => 'Refund: remaining refundable balance on the booking after this refund',
        '{customer_refund_total}' => 'Refund: total refunded to customer so far (after this step)',
        '{customer_refund_cap}' => 'Refund: maximum amount eligible for refund on this booking',
        '{customer_refund_before_this}' => 'Refund: total refunded before this refund step',
    ];

    /**
     * Long-form help for the booking-template variable picker (when to use, what is sent, which UI tab).
     * Keys must match {@see self::PLACEHOLDER_HINTS}; dynamic additional-charge keys are appended in
     * {@see self::allPlaceholderAdminGuidesForAdmin()}.
     *
     * @var array<string, string>
     */
    public const PLACEHOLDER_ADMIN_GUIDES = [
        '{service_name}' => "Modules: Booking (all booking-related message tabs).\nWhen: Any template that should name the booked service.\nContains: Human-readable service name(s) for this booking.",
        '{customer_address}' => "Modules: Booking.\nWhen: Messages to customer or provider that include where the job is.\nContains: Formatted customer/service address string from the booking.",
        '{customer_name}' => "Modules: Booking.\nWhen: Any template mentioning the end customer.\nContains: Display name of the booking customer.",
        '{customer_phone}' => "Modules: Booking.\nWhen: Provider-facing or internal templates that need to reach the customer.\nContains: Customer phone in stored/display format.",
        '{provider_name}' => "Modules: Booking.\nWhen: Customer-facing booking updates, or cross-party copy.\nContains: Assigned provider (company) name.",
        '{provider_phone}' => "Modules: Booking.\nWhen: Customer-facing templates with provider contact.\nContains: Provider phone in stored/display format.",
        '{booking_id}' => "Modules: Booking.\nWhen: Almost all booking templates (reference in copy).\nContains: Readable booking reference/id shown in the system UI.",
        '{booking_datetime}' => "Modules: Booking.\nWhen: Confirmations, reminders, schedule or status updates that mention timing.\nContains: Scheduled service date & time in the app’s display format.",
        '{service_where}' => "Modules: Booking.\nWhen: Clarifying on-site vs off-site or similar.\nContains: Short label such as where service is performed.",
        '{total_bill}' => "Modules: Booking.\nWhen: New booking, invoice, or payment-related booking messages.\nContains: Total bill for the booking, formatted with currency where applicable.",
        '{amount_paid}' => "Modules: Booking.\nWhen: Any template describing money already collected on the booking.\nContains: Amount paid so far on this booking (formatted).",
        '{due_amount}' => "Modules: Booking.\nWhen: Any template that needs the outstanding amount on the booking.\nContains: Outstanding amount still due on the booking (formatted).",
        '{booking_status}' => "Modules: Booking.\nWhen: Status-changed templates and any update that reflects current state.\nContains: Current booking status label (e.g. Pending, Accepted).",
        '{previous_booking_status}' => "Modules: Booking.\nWhen: Status-changed templates (before → after).\nContains: Previous status label before the latest transition.",
        '{previous_provider_name}' => "Modules: Booking — provider reassignment.\nWhen: Provider change tab only (old vs new assignee).\nContains: Name of the provider before reassignment.",
        '{previous_provider_phone}' => "Modules: Booking — provider reassignment.\nWhen: Provider change tab.\nContains: Phone of the provider before reassignment.",
        '{previous_service_schedule}' => "Modules: Booking — schedule change.\nWhen: Schedule change tab (old vs new time).\nContains: Previous scheduled date & time string.",
        '{amount_added}' => "Modules: Booking — payments.\nWhen: “Add payment” message templates.\nContains: The amount recorded in the Add payment modal (formatted).",
        '{payment_received_by}' => "Modules: Booking — payments.\nWhen: “Add payment” message templates.\nContains: Who received this payment (Company or Provider).",
        '{payment_date}' => "Modules: Booking — payments.\nWhen: “Add payment” message templates.\nContains: The payment date recorded in the Add payment modal.",
        '{payment_method}' => "Modules: Booking — payments.\nWhen: “Add payment” templates.\nContains: The payment method label used for ledger recording (cash/bank/etc.).",
        '{payment_reference}' => "Modules: Booking — payments.\nWhen: “Add payment” templates.\nContains: Transaction / reference id if recorded; otherwise blank/—.",
        '{customer_payments_total}' => "Modules: Booking — payments & refunds.\nWhen: Templates under Payments & refunds (add payment, refund to customer).\nContains: Sum of all customer partial payment amounts on the booking (formatted).",
        '{customer_payments_to_company}' => "Modules: Booking — payments & refunds.\nWhen: Payments & refunds templates.\nContains: Sum of partials where received-by is company (formatted).",
        '{customer_payments_to_provider}' => "Modules: Booking — payments & refunds.\nWhen: Payments & refunds templates.\nContains: Sum of partials where received-by is provider (formatted).",
        '{customer_payments_unassigned}' => "Modules: Booking — payments & refunds.\nWhen: Payments & refunds templates.\nContains: Sum of partials with missing/unknown received-by (formatted).",
        '{serviceman_name}' => "Modules: Booking — assigned technician.\nWhen: Any booking template that names the assigned serviceman.\nContains: Current serviceman display name.",
        '{serviceman_phone}' => "Modules: Booking — assigned technician.\nWhen: Templates that should show how to contact the assigned serviceman.\nContains: Serviceman phone string.",
        '{previous_serviceman_name}' => "Modules: Booking.\nWhen: Rarely used; placeholder for a prior assignee name if your copy needs it.\nContains: Otherwise typically em dash in general automations.",
        '{previous_serviceman_phone}' => "Modules: Booking.\nWhen: Rarely used; placeholder for a prior assignee phone if your copy needs it.\nContains: Otherwise typically em dash in general automations.",
        '{verification_status}' => "Modules: Booking — verification.\nWhen: Templates that mention current booking verification state.\nContains: Current booking verification state label.",
        '{previous_verification_status}' => "Modules: Booking — verification.\nWhen: Templates that compare before/after verification state.\nContains: Often em dash unless a dedicated flow sets it.",
        '{verification_action}' => "Modules: Booking — verification.\nWhen: Templates describing the last verification action.\nContains: Action label such as approve, deny, or cancel when applicable.",
        '{reopen_resolve_remarks}' => "Modules: Booking — reopen resolved.\nWhen: “Reopen resolved” status segment only.\nContains: Admin/staff remarks entered when the reopen case is marked resolved.",
        '{booking_cancellation_reason}' => "Modules: Booking.\nWhen: Cancellation or status flows that capture a reason.\nContains: Last cancellation reason text from the booking.",
        '{on_hold_reason}' => "Modules: Booking.\nWhen: On-hold / hold-related status messaging.\nContains: Reason from the latest hold action.",
        '{on_hold_reason_remarks}' => "Modules: Booking.\nWhen: On-hold / hold-related status messaging.\nContains: Remarks/notes recorded when the booking is put on hold.",
        '{reopen_from_completed_reason}' => "Modules: Booking — reopened from completed.\nWhen: Reopen flows that store a reason.\nContains: Text from the reopen-from-completed flow.",
        '{provider_pending_balance}' => "Modules: Payments / ledger (WhatsApp “Ledger payment messages” tab).\nWhen: Provider payment reminders — amount they still owe the company.\nContains: Formatted pending settlement due from provider to company.",
        '{provider_due_balance}' => "Modules: Payments / ledger.\nWhen: Same as pending provider balance; use whichever token matches your approved Meta template name.\nContains: Same numeric meaning as {provider_pending_balance} (formatted).",
        '{customer_pending_balance}' => "Modules: Payments / ledger.\nWhen: Customer-side reminders (e.g. loss / debit still owed by customer).\nContains: Formatted amount customer still owes in that context.",
        '{amount_received_from_provider}' => "Modules: Payments / ledger.\nWhen: Confirmation after recording money received from provider (collection).\nContains: Amount collected in this ledger step (formatted). Alias meaning of {amount_collected_from_provider}.",
        '{amount_collected_from_provider}' => "Modules: Payments / ledger.\nWhen: Same as {amount_received_from_provider} — pick the token your template defines.\nContains: Amount collected from provider in this transaction (formatted).",
        '{balance_after_payment_collected}' => "Modules: Payments / ledger.\nWhen: After a collection — how much provider balance remains to collect.\nContains: Remaining debt to collect after this payment (0 if none). {remaining_balance_to_collect} is equivalent.",
        '{booking_settlement_net_after_collect}' => "Modules: Payments / ledger.\nWhen: After collection — booking-level settlement snapshot.\nContains: Signed net; negative typically means provider still owes on that booking slice.",
        '{amount_sent_to_provider}' => "Modules: Payments / ledger.\nWhen: After a payout to provider (money out).\nContains: Amount paid out in this step (formatted).",
        '{remaining_balance_to_collect}' => "Modules: Payments / ledger.\nWhen: After partial collection — remaining collectable balance.\nContains: Same as {balance_after_payment_collected}.",
        '{remaining_balance_to_send}' => "Modules: Payments / ledger.\nWhen: After partial payout — how much remains to send to provider.\nContains: Remaining payout due (formatted).",
        '{disputed_refund_total}' => "Modules: Booking — disputed reopen close.\nWhen: “Disputed close” status segment only (ledger refund legs recorded).\nContains: Total refunded back to the customer for this disputed close (formatted).",
        '{disputed_refund_company}' => "Modules: Booking — disputed reopen close.\nWhen: “Disputed close” segment only.\nContains: Refund amount attributed to the company-collected pool (formatted).",
        '{disputed_refund_provider}' => "Modules: Booking — disputed reopen close.\nWhen: “Disputed close” segment only.\nContains: Refund amount attributed to the provider-collected pool (formatted).",
        '{disputed_customer_retained}' => "Modules: Booking — disputed reopen close.\nWhen: “Disputed close” segment only.\nContains: Net customer cash retained on the booking after refunds (formatted).",
        '{booking_final_amount}' => "Modules: Booking — amounts.\nWhen: Status templates after totals change, reopen resolved, loss-making completion.\nContains: Full booking total at send time (formatted), same numeric basis as {total_bill}.",
        '{settlement_outcome_label}' => "Modules: Booking — special settlement.\nWhen: Bookings with visit-fee split, scaled loss-making, or retained-cancel outcomes.\nContains: Translated settlement outcome label from admin configuration.",
        '{settlement_remarks}' => "Modules: Booking — special settlement.\nWhen: Same as settlement outcome.\nContains: Free-text remarks saved with the settlement.",
        '{scaled_loss_total}' => "Modules: Booking — loss-making (scaled).\nWhen: “Loss-making” status segment or whenever settlement snapshot includes scaled loss.\nContains: Total scaled loss amount (formatted).",
        '{scaled_loss_company_share}' => "Modules: Booking — loss-making (scaled).\nWhen: Loss-making / scaled settlement messaging.\nContains: Company’s share of the scaled loss (formatted).",
        '{scaled_loss_provider_share}' => "Modules: Booking — loss-making (scaled).\nWhen: Loss-making / scaled settlement messaging.\nContains: Provider’s share of the scaled loss (formatted).",
        '{scaled_net_company_share}' => "Modules: Booking — loss-making (scaled).\nWhen: After loss allocation in settlement snapshot.\nContains: Net company commission after scaled loss (formatted).",
        '{scaled_net_provider_share}' => "Modules: Booking — loss-making (scaled).\nWhen: After loss allocation in settlement snapshot.\nContains: Net provider earning after scaled loss (formatted).",
        '{scaled_customer_paid_amount}' => "Modules: Booking — loss-making (scaled).\nWhen: Settlement snapshot present.\nContains: Recorded customer paid amount used in scaled preview (formatted).",
        '{booking_customer_still_due}' => "Modules: Booking — collections.\nWhen: Any status; strongest meaning on pending/ongoing with amount due.\nContains: Amount still to collect from the customer for this booking (formatted).",
        '{settlement_company_pays_provider}' => "Modules: Booking — settlement legs.\nWhen: Status templates after payments (especially loss-making / scaled completion).\nContains: Amount the company must pay the provider — same as “Pay to provider” on booking details (formatted).",
        '{settlement_provider_pays_company}' => "Modules: Booking — settlement legs.\nWhen: Same as company→provider settlement token.\nContains: Amount the provider must remit to the company — same as “Provider owes you” on booking details (formatted).",
        '{disputed_provider_owes_company}' => "Modules: Booking — disputed reopen close.\nWhen: After disputed refund snapshot is stored.\nContains: Provider-side pool attributed as owed to company (formatted).",
        '{disputed_company_owes_provider}' => "Modules: Booking — disputed reopen close.\nWhen: After disputed refund snapshot.\nContains: Company-side pool attributed as owed to provider (formatted).",
        '{disputed_company_cash_after_refund}' => "Modules: Booking — disputed reopen close.\nContains: Company cash pool after refund legs (formatted).",
        '{disputed_provider_cash_after_refund}' => "Modules: Booking — disputed reopen close.\nContains: Provider cash pool after refund legs (formatted).",
        '{disputed_final_admin_commission}' => "Modules: Booking — disputed reopen close.\nContains: Final net company commission after dispute distribution (formatted).",
        '{disputed_final_provider_earning}' => "Modules: Booking — disputed reopen close.\nContains: Final net provider earning after dispute distribution (formatted).",
        '{disputed_company_pays_provider_total}' => "Modules: Booking — disputed reopen close.\nContains: Total the company pays the provider in net settlement (formatted).",
        '{disputed_provider_remittance_total}' => "Modules: Booking — disputed reopen close.\nContains: Total provider remittance due to company (formatted).",
        '{dispute_reason}' => "Modules: Booking — disputed reopen close.\nWhen: Dispute and close modal submit.\nContains: Active dispute reason name (same field as modal).",
        '{refund_paid_from_company_pool}' => "Modules: Booking — disputed reopen close.\nContains: Same as refund from company pool in the modal (formatted).",
        '{refund_paid_from_provider_pool}' => "Modules: Booking — disputed reopen close.\nContains: Same as refund from provider pool in the modal (formatted).",
        '{refund_company_transaction_id}' => "Modules: Booking — disputed reopen close.\nContains: Company-leg reference / transaction id (or em dash if none).",
        '{refund_provider_transaction_id}' => "Modules: Booking — disputed reopen close.\nContains: Provider-leg reference / transaction id (or em dash if none).",
        '{final_services_charges_retained_from_customer}' => "Modules: Booking — disputed reopen close.\nContains: Services portion of customer retained after refunds (formatted).",
        '{final_spare_parts_charges_retained_from_customer}' => "Modules: Booking — disputed reopen close.\nContains: Spare-parts portion of customer retained after refunds (formatted).",
        '{final_admin_commission_services_net_basis}' => "Modules: Booking — disputed reopen close.\nContains: Admin commission on Services net basis (formatted).",
        '{final_provider_earning_services_net_basis}' => "Modules: Booking — disputed reopen close.\nContains: Provider earning on Services net basis (formatted).",
        '{final_admin_commission_spare_parts_net_basis}' => "Modules: Booking — disputed reopen close.\nContains: Admin commission on Spare Parts net basis (formatted).",
        '{final_provider_earning_spare_parts_net_basis}' => "Modules: Booking — disputed reopen close.\nContains: Provider earning on Spare Parts net basis (formatted).",
        '{final_amount_retained_from_customer_after_refunds}' => "Modules: Booking — disputed reopen close.\nContains: Total retained from customer after refunds (formatted); same basis as modal total retained.",
        '{disputed_total_provider_pays_company}' => "Modules: Booking — disputed reopen close.\nContains: Total provider pays company (modal reconciliation total; formatted).",
        '{disputed_total_company_pays_provider}' => "Modules: Booking — disputed reopen close.\nContains: Total company pays provider (modal reconciliation total; formatted).",
        '{compensation_amount}' => "Modules: Booking — compensation.\nWhen: “Compensation” automation only.\nContains: Amount for this compensation record (formatted).",
        '{compensation_from_party_label}' => "Modules: Booking — compensation.\nContains: Payer label: Company, Provider, or Customer.",
        '{compensation_to_party_label}' => "Modules: Booking — compensation.\nContains: Recipient label.",
        '{compensation_direction}' => "Modules: Booking — compensation.\nContains: Short direction string (e.g. Company → Customer).",
        '{compensation_reference_note}' => "Modules: Booking — compensation.\nContains: Optional admin note on the compensation row.",
        '{compensation_date}' => "Modules: Booking — compensation.\nContains: Date string for the compensation.",
        '{compensation_transaction_id}' => "Modules: Booking — compensation.\nContains: Transaction / reference id entered by admin.",
        '{refund_amount}' => "Modules: Booking — customer refund.\nWhen: “Refund to customer” automation after admin records a refund on a canceled/refunded booking.\nContains: Amount refunded in this step (formatted).",
        '{refund_date}' => "Modules: Booking — customer refund.\nWhen: Refund automation.\nContains: Date string used for the ledger refund row.",
        '{refund_transaction_id}' => "Modules: Booking — customer refund.\nContains: Transaction / reference id entered when recording the refund.",
        '{refund_reference_note}' => "Modules: Booking — customer refund.\nContains: Optional admin note on the refund.",
        '{refund_remaining}' => "Modules: Booking — customer refund.\nContains: Remaining amount still refundable to the customer after this refund (formatted; 0 when fully refunded).",
        '{customer_refund_total}' => "Modules: Booking — customer refund.\nWhen: After each refund automation; also populated on canceled/refunded bookings from ledger.\nContains: Cumulative amount refunded to the customer up to send time (formatted).",
        '{customer_refund_cap}' => "Modules: Booking — customer refund.\nWhen: Canceled/refunded bookings.\nContains: Maximum total that may be refunded under current rules (formatted).",
        '{customer_refund_before_this}' => "Modules: Booking — customer refund.\nWhen: “Refund to customer” automation only.\nContains: Cumulative refunds recorded before this refund step (formatted).",
    ];

    /**
     * Example values for the booking-template preview in admin (Meta variable mapping UI).
     *
     * @var array<string, string>
     */
    public const PLACEHOLDER_PREVIEW_SAMPLES = [
        '{service_name}' => 'AC repair, gas refill',
        '{customer_address}' => '12 MG Road, Bengaluru 560001',
        '{customer_name}' => 'Priya Sharma',
        '{customer_phone}' => '+91 98765 43210',
        '{provider_name}' => 'CoolAir Services Pvt Ltd',
        '{provider_phone}' => '+91 91234 56789',
        '{booking_id}' => 'BK-2026-10492',
        '{booking_datetime}' => '7 Apr 2026, 2:30 PM',
        '{service_where}' => 'Customer address',
        '{total_bill}' => '4,500.00',
        '{amount_paid}' => '1,000.00',
        '{due_amount}' => '3,500.00',
        '{booking_status}' => 'Accepted',
        '{previous_booking_status}' => 'Pending',
        '{previous_provider_name}' => 'Old Care HVAC',
        '{previous_provider_phone}' => '+91 90000 00001',
        '{previous_service_schedule}' => '5 Apr 2026, 10:00 AM',
        '{amount_added}' => '500.00',
        '{payment_received_by}' => 'Company',
        '{payment_date}' => '2026-04-13',
        '{payment_method}' => 'cash_after_service',
        '{payment_reference}' => 'TXN-884120',
        '{customer_payments_total}' => '3,300.00',
        '{customer_payments_to_company}' => '2,000.00',
        '{customer_payments_to_provider}' => '1,300.00',
        '{customer_payments_unassigned}' => '0.00',
        '{serviceman_name}' => 'Ravi Kumar',
        '{serviceman_phone}' => '+91 99887 76655',
        '{previous_serviceman_name}' => 'Amit Singh',
        '{previous_serviceman_phone}' => '+91 88776 65544',
        '{verification_status}' => 'Approved',
        '{previous_verification_status}' => 'Pending',
        '{verification_action}' => 'approve',
        '{reopen_resolve_remarks}' => 'Technician visit completed; case closed.',
        '{booking_cancellation_reason}' => 'Customer requested reschedule',
        '{on_hold_reason}' => 'Awaiting parts',
        '{on_hold_reason_remarks}' => 'Need supplier confirmation before resuming',
        '{reopen_from_completed_reason}' => 'Issue recurred after visit',
        '{provider_pending_balance}' => '12,500.00',
        '{provider_due_balance}' => '12,500.00',
        '{customer_pending_balance}' => '3,200.00',
        '{amount_received_from_provider}' => '5,000.00',
        '{amount_collected_from_provider}' => '5,000.00',
        '{balance_after_payment_collected}' => '7,500.00',
        '{booking_settlement_net_after_collect}' => '-7,500.00',
        '{amount_sent_to_provider}' => '8,000.00',
        '{remaining_balance_to_collect}' => '7,500.00',
        '{remaining_balance_to_send}' => '2,000.00',
        '{disputed_refund_total}' => '2,500.00',
        '{disputed_refund_company}' => '1,500.00',
        '{disputed_refund_provider}' => '1,000.00',
        '{disputed_customer_retained}' => '2,000.00',
        '{booking_final_amount}' => '4,500.00',
        '{settlement_outcome_label}' => 'Scaled / partial pay (loss-making)',
        '{settlement_remarks}' => 'Customer paid partial; loss split per policy.',
        '{scaled_loss_total}' => '1,200.00',
        '{scaled_loss_company_share}' => '480.00',
        '{scaled_loss_provider_share}' => '720.00',
        '{scaled_net_company_share}' => '620.00',
        '{scaled_net_provider_share}' => '2,180.00',
        '{scaled_customer_paid_amount}' => '3,300.00',
        '{booking_customer_still_due}' => '800.00',
        '{settlement_company_pays_provider}' => '450.00',
        '{settlement_provider_pays_company}' => '120.00',
        '{disputed_provider_owes_company}' => '400.00',
        '{disputed_company_owes_provider}' => '250.00',
        '{disputed_company_cash_after_refund}' => '900.00',
        '{disputed_provider_cash_after_refund}' => '600.00',
        '{disputed_final_admin_commission}' => '1,100.00',
        '{disputed_final_provider_earning}' => '2,400.00',
        '{disputed_company_pays_provider_total}' => '300.00',
        '{disputed_provider_remittance_total}' => '500.00',
        '{dispute_reason}' => 'Service not as described',
        '{refund_paid_from_company_pool}' => '1,500.00',
        '{refund_paid_from_provider_pool}' => '1,000.00',
        '{refund_company_transaction_id}' => 'CO-TXN-991',
        '{refund_provider_transaction_id}' => 'PR-TXN-772',
        '{final_services_charges_retained_from_customer}' => '1,800.00',
        '{final_spare_parts_charges_retained_from_customer}' => '200.00',
        '{final_admin_commission_services_net_basis}' => '540.00',
        '{final_provider_earning_services_net_basis}' => '1,260.00',
        '{final_admin_commission_spare_parts_net_basis}' => '60.00',
        '{final_provider_earning_spare_parts_net_basis}' => '140.00',
        '{final_amount_retained_from_customer_after_refunds}' => '2,000.00',
        '{disputed_total_provider_pays_company}' => '500.00',
        '{disputed_total_company_pays_provider}' => '300.00',
        '{compensation_amount}' => '150.00',
        '{compensation_from_party_label}' => 'Company',
        '{compensation_to_party_label}' => 'Customer',
        '{compensation_direction}' => 'Company → Customer',
        '{compensation_reference_note}' => 'Goodwill gesture',
        '{compensation_date}' => '2026-04-15',
        '{compensation_transaction_id}' => 'CMP-991',
        '{refund_amount}' => '1,200.00',
        '{refund_date}' => '2026-04-16',
        '{refund_transaction_id}' => 'RFN-20441',
        '{refund_reference_note}' => 'UPI reversal to customer',
        '{refund_remaining}' => '0.00',
        '{customer_refund_total}' => '3,300.00',
        '{customer_refund_cap}' => '3,300.00',
        '{customer_refund_before_this}' => '2,100.00',
    ];

    /**
     * Preview sample strings for admin UI: static tokens plus one sample per additional charge type.
     *
     * @return array<string, string>
     */
    public static function allPlaceholderPreviewSamplesForAdmin(): array
    {
        $acSamples = [];
        foreach (self::buildAdditionalChargePlaceholderData()['hints'] as $token => $_label) {
            $acSamples[$token] = '350.00';
        }

        return array_merge(self::PLACEHOLDER_PREVIEW_SAMPLES, $acSamples);
    }

    /**
     * Static booking tokens plus one token per active additional charge type (amount for that line).
     *
     * @return array<string, string>
     */
    public static function allPlaceholderHintsForAdmin(): array
    {
        $ac = self::buildAdditionalChargePlaceholderData();

        return array_merge(self::PLACEHOLDER_HINTS, $ac['hints']);
    }

    /**
     * Translation keys for short “module” labels in the variable dropdown (resolved in the controller/view).
     *
     * @return array<string, string>
     */
    public static function allPlaceholderDropdownModuleLangKeysForAdmin(): array
    {
        $out = [];
        foreach (array_keys(self::allPlaceholderHintsForAdmin()) as $token) {
            $out[$token] = self::dropdownModuleLangKeyForToken($token);
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function additionalChargePlaceholderKeysOrdered(): array
    {
        $adminKeys = array_keys(self::allPlaceholderHintsForAdmin());

        return array_values(array_filter($adminKeys, static fn (string $k) => str_starts_with($k, '{additional_charge_')));
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function orderPlaceholderKeysLikeAdmin(array $tokens): array
    {
        $unique = array_values(array_unique(array_values($tokens)));
        if ($unique === []) {
            return [];
        }
        $flip = array_flip($unique);
        $out = [];
        foreach (array_keys(self::allPlaceholderHintsForAdmin()) as $k) {
            if (isset($flip[$k])) {
                $out[] = $k;
            }
        }

        return $out;
    }

    /**
     * Core booking row tokens populated by {@see self::buildReplacements()} for normal booking automations
     * (excludes “previous …” transition tokens and ledger-only keys).
     *
     * @return list<string>
     */
    private static function bookingAutomationCoreTokenKeys(): array
    {
        return [
            '{service_name}',
            '{customer_address}',
            '{customer_name}',
            '{customer_phone}',
            '{provider_name}',
            '{provider_phone}',
            '{booking_id}',
            '{booking_datetime}',
            '{service_where}',
            '{total_bill}',
            '{amount_paid}',
            '{due_amount}',
            '{booking_status}',
            '{serviceman_name}',
            '{serviceman_phone}',
        ];
    }

    /**
     * Reason / lifecycle fields (cancellation, hold, reopen) plus settlement & loss-making amounts
     * offered on every booking-status tab so admins can reference them when relevant.
     *
     * @return list<string>
     */
    private static function bookingStatusLifecycleAndFinancialTokenKeys(): array
    {
        return [
            '{booking_cancellation_reason}',
            '{on_hold_reason}',
            '{on_hold_reason_remarks}',
            '{reopen_from_completed_reason}',
            '{reopen_resolve_remarks}',
            '{booking_final_amount}',
            '{settlement_outcome_label}',
            '{settlement_remarks}',
            '{scaled_loss_total}',
            '{scaled_loss_company_share}',
            '{scaled_loss_provider_share}',
            '{scaled_net_company_share}',
            '{scaled_net_provider_share}',
            '{scaled_customer_paid_amount}',
            '{booking_customer_still_due}',
            '{settlement_company_pays_provider}',
            '{settlement_provider_pays_company}',
        ];
    }

    /**
     * @return list<string>
     */
    private static function bookingStatusDisputedScenarioTokenKeys(): array
    {
        return [
            '{dispute_reason}',
            '{refund_paid_from_company_pool}',
            '{refund_company_transaction_id}',
            '{refund_paid_from_provider_pool}',
            '{refund_provider_transaction_id}',
            '{final_services_charges_retained_from_customer}',
            '{final_admin_commission_services_net_basis}',
            '{final_provider_earning_services_net_basis}',
            '{final_spare_parts_charges_retained_from_customer}',
            '{final_admin_commission_spare_parts_net_basis}',
            '{final_provider_earning_spare_parts_net_basis}',
            '{final_amount_retained_from_customer_after_refunds}',
            '{disputed_total_provider_pays_company}',
            '{disputed_total_company_pays_provider}',
            '{disputed_refund_total}',
            '{disputed_refund_company}',
            '{disputed_refund_provider}',
            '{disputed_customer_retained}',
            '{disputed_provider_owes_company}',
            '{disputed_company_owes_provider}',
            '{disputed_company_cash_after_refund}',
            '{disputed_provider_cash_after_refund}',
            '{disputed_final_admin_commission}',
            '{disputed_final_provider_earning}',
            '{disputed_company_pays_provider_total}',
            '{disputed_provider_remittance_total}',
        ];
    }

    /**
     * Tokens for one booking-status sub-tab (same set for customer vs provider column).
     *
     * @return list<string>
     */
    private static function bookingStatusSlotTokenKeysForSegment(string $segment): array
    {
        $base = array_merge(self::bookingAutomationCoreTokenKeys(), ['{previous_booking_status}']);
        $flow = self::bookingStatusLifecycleAndFinancialTokenKeys();
        if ($segment === 'disputed_close') {
            $flow = array_merge($flow, self::bookingStatusDisputedScenarioTokenKeys());
        }

        return array_merge($base, $flow);
    }

    /**
     * Union of all per-segment status tokens for the general “booking status changed” fallback rows.
     *
     * @return list<string>
     */
    private static function bookingStatusFallbackUnionTokenKeys(): array
    {
        $u = [];
        foreach (self::statusTemplateSegmentKeys() as $segment) {
            $u = array_merge($u, self::bookingStatusSlotTokenKeysForSegment($segment));
        }

        return array_values(array_unique($u));
    }

    /**
     * Ordered token keys for the admin “Available variable” dropdown for one Meta template slot.
     * Matches what the corresponding automation populates (plus additional-charge lines on booking rows).
     *
     * @return list<string>
     */
    public static function placeholderTokenKeysForMessageSlot(string $fieldKey): array
    {
        $ac = self::additionalChargePlaceholderKeysOrdered();
        $core = self::bookingAutomationCoreTokenKeys();
        $allOrdered = array_keys(self::allPlaceholderHintsForAdmin());

        if ($fieldKey === 'booking_confirmation_customer' || $fieldKey === 'booking_confirmation_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac));
        }

        if ($fieldKey === 'booking_status_customer' || $fieldKey === 'booking_status_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge(self::bookingStatusFallbackUnionTokenKeys(), $ac));
        }

        if (preg_match('/^booking_status_(customer|provider)_(.+)$/', $fieldKey, $m)) {
            $segment = $m[2];
            if (in_array($segment, self::statusTemplateSegmentKeys(), true)) {
                return self::orderPlaceholderKeysLikeAdmin(array_merge(
                    self::bookingStatusSlotTokenKeysForSegment($segment),
                    $ac
                ));
            }
        }

        if ($fieldKey === 'provider_change_customer' || $fieldKey === 'provider_change_new_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, [
                '{previous_provider_name}',
                '{previous_provider_phone}',
            ]));
        }

        if ($fieldKey === 'provider_change_previous_provider') {
            return self::orderPlaceholderKeysLikeAdmin([
                '{booking_id}',
                '{customer_name}',
                '{customer_phone}',
                '{service_name}',
                '{booking_datetime}',
                '{provider_name}',
                '{provider_phone}',
            ]);
        }

        if ($fieldKey === 'booking_schedule_customer' || $fieldKey === 'booking_schedule_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, ['{previous_service_schedule}']));
        }

        if ($fieldKey === 'booking_payment_added_customer' || $fieldKey === 'booking_payment_added_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, [
                '{amount_added}',
                '{payment_received_by}',
                '{payment_date}',
                '{payment_method}',
                '{payment_reference}',
                '{customer_payments_total}',
                '{customer_payments_to_company}',
                '{customer_payments_to_provider}',
                '{customer_payments_unassigned}',
            ]));
        }

        if ($fieldKey === 'booking_refund_to_customer') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, [
                '{refund_amount}',
                '{refund_date}',
                '{refund_transaction_id}',
                '{refund_reference_note}',
                '{refund_remaining}',
                '{customer_refund_total}',
                '{customer_refund_cap}',
                '{customer_refund_before_this}',
                '{customer_payments_total}',
                '{customer_payments_to_company}',
                '{customer_payments_to_provider}',
                '{customer_payments_unassigned}',
            ]));
        }

        if ($fieldKey === 'booking_compensation_customer' || $fieldKey === 'booking_compensation_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, [
                '{compensation_amount}',
                '{compensation_from_party_label}',
                '{compensation_to_party_label}',
                '{compensation_direction}',
                '{compensation_reference_note}',
                '{compensation_date}',
                '{compensation_transaction_id}',
            ]));
        }

        if ($fieldKey === 'ledger_provider_payment_reminder') {
            return self::orderPlaceholderKeysLikeAdmin([
                '{provider_name}',
                '{provider_phone}',
                '{provider_pending_balance}',
                '{provider_due_balance}',
            ]);
        }

        if ($fieldKey === 'ledger_customer_payment_reminder') {
            return self::orderPlaceholderKeysLikeAdmin([
                '{customer_name}',
                '{customer_phone}',
                '{customer_pending_balance}',
            ]);
        }

        if ($fieldKey === 'ledger_payment_received_from_provider') {
            return self::orderPlaceholderKeysLikeAdmin([
                '{provider_name}',
                '{provider_phone}',
                '{provider_pending_balance}',
                '{provider_due_balance}',
                '{amount_received_from_provider}',
                '{amount_collected_from_provider}',
                '{balance_after_payment_collected}',
                '{booking_settlement_net_after_collect}',
                '{remaining_balance_to_collect}',
            ]);
        }

        if ($fieldKey === 'ledger_payment_sent_to_provider') {
            return self::orderPlaceholderKeysLikeAdmin([
                '{provider_name}',
                '{provider_phone}',
                '{amount_sent_to_provider}',
                '{remaining_balance_to_send}',
            ]);
        }

        if ($fieldKey === 'booking_serviceman_customer' || $fieldKey === 'booking_serviceman_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, [
                '{previous_serviceman_name}',
                '{previous_serviceman_phone}',
            ]));
        }

        if ($fieldKey === 'booking_verification_customer' || $fieldKey === 'booking_verification_provider') {
            return self::orderPlaceholderKeysLikeAdmin(array_merge($core, $ac, [
                '{verification_status}',
                '{previous_verification_status}',
                '{verification_action}',
            ]));
        }

        return $allOrdered;
    }

    /**
     * Map each booking WhatsApp template slot key → ordered variable tokens for the admin UI.
     *
     * @return array<string, list<string>>
     */
    public static function placeholderKeysByAllBookingMessageSlots(): array
    {
        $out = [];
        $fixed = [
            'booking_confirmation_customer',
            'booking_confirmation_provider',
            'booking_status_customer',
            'booking_status_provider',
            'provider_change_customer',
            'provider_change_previous_provider',
            'provider_change_new_provider',
            'booking_schedule_customer',
            'booking_schedule_provider',
            'booking_payment_added_customer',
            'booking_payment_added_provider',
            'booking_refund_to_customer',
            'booking_compensation_customer',
            'booking_compensation_provider',
            'ledger_provider_payment_reminder',
            'ledger_customer_payment_reminder',
            'ledger_payment_received_from_provider',
            'ledger_payment_sent_to_provider',
            'booking_serviceman_customer',
            'booking_serviceman_provider',
            'booking_verification_customer',
            'booking_verification_provider',
        ];
        foreach ($fixed as $k) {
            $out[$k] = self::placeholderTokenKeysForMessageSlot($k);
        }
        foreach (self::statusTemplateSegmentKeys() as $segment) {
            $out['booking_status_customer_'.$segment] = self::placeholderTokenKeysForMessageSlot('booking_status_customer_'.$segment);
            $out['booking_status_provider_'.$segment] = self::placeholderTokenKeysForMessageSlot('booking_status_provider_'.$segment);
        }

        return $out;
    }

    /**
     * Long-form guides for the admin variable dropdown (parallel to {@see self::allPlaceholderHintsForAdmin()}).
     *
     * @return array<string, string>
     */
    public static function allPlaceholderAdminGuidesForAdmin(): array
    {
        $ac = self::buildAdditionalChargePlaceholderData();
        $acGuides = [];
        foreach ($ac['hints'] as $token => $label) {
            $acGuides[$token] = "Modules: Booking (service fees on the order).\nWhen: Map this when your Meta template has a separate placeholder for this fee line.\nContains: Formatted amount for this additional charge on the current booking ({$label}).\nNot for: Ledger-only payout messages or booking payment status labels — use booking amount/payment tokens or ledger tokens instead.";
        }

        $merged = array_merge(self::PLACEHOLDER_ADMIN_GUIDES, $acGuides);
        $out = [];
        foreach ($merged as $token => $guide) {
            $out[$token] = $guide."\n".self::placeholderGuideAudienceLine($token);
        }

        return $out;
    }

    /**
     * Who should receive messages that use this token (customer vs provider template column).
     */
    private static function placeholderGuideAudienceLine(string $token): string
    {
        $providerLedgerTokens = [
            '{provider_pending_balance}',
            '{provider_due_balance}',
            '{amount_received_from_provider}',
            '{amount_collected_from_provider}',
            '{balance_after_payment_collected}',
            '{booking_settlement_net_after_collect}',
            '{remaining_balance_to_collect}',
        ];
        $payoutLedgerTokens = [
            '{amount_sent_to_provider}',
            '{remaining_balance_to_send}',
        ];

        if ($token === '{customer_pending_balance}') {
            return 'Audience: Customer-focused ledger/reminder templates (amount the customer still owes in that settlement context). Use the Customer template column on the Ledger reminders tab.';
        }
        if (in_array($token, $providerLedgerTokens, true)) {
            return 'Audience: Provider-focused templates for settlement collection — map on Payments & refunds → Collect from provider (automatic when staff records collection), or use the same tokens in manual copy.';
        }
        if (in_array($token, $payoutLedgerTokens, true)) {
            return 'Audience: Provider-focused payout confirmation — map on Payments & refunds → Pay provider (automatic when staff records a payout).';
        }
        if (str_starts_with($token, '{additional_charge_')) {
            return 'Audience: Booking templates (Customer or Provider columns) whenever that recipient should see this extra fee line. Not for pure ledger settlement messages — use ledger tokens there.';
        }

        $customerIdentity = ['{customer_name}', '{customer_phone}', '{customer_address}'];
        if (in_array($token, $customerIdentity, true)) {
            return 'Audience: Mainly customer-facing messages; also provider-facing when you inform the provider about the customer’s contact or address.';
        }

        $providerIdentity = ['{provider_name}', '{provider_phone}'];
        if (in_array($token, $providerIdentity, true)) {
            return 'Audience: Customer templates (who will serve them) and provider templates (their own business name/phone).';
        }

        $bookingPaymentTokens = [
            '{amount_added}',
            '{payment_received_by}',
            '{payment_date}',
            '{payment_method}',
            '{payment_reference}',
            '{customer_payments_total}',
            '{customer_payments_to_company}',
            '{customer_payments_to_provider}',
            '{customer_payments_unassigned}',
            '{amount_paid}',
            '{due_amount}',
            '{total_bill}',
        ];
        if (in_array($token, $bookingPaymentTokens, true)) {
            return 'Audience: Payments & refunds → Add payment on booking (customer vs provider columns). Customer-payment totals here are also available on Refund to customer when you need paid-to-date splits in the same copy.';
        }

        $refundTokens = [
            '{refund_amount}',
            '{refund_date}',
            '{refund_transaction_id}',
            '{refund_reference_note}',
            '{refund_remaining}',
            '{customer_refund_total}',
            '{customer_refund_cap}',
            '{customer_refund_before_this}',
        ];
        if (in_array($token, $refundTokens, true)) {
            return 'Audience: Customer template on WhatsApp booking templates → Payments & refunds → Refund to customer.';
        }

        $compensationOnlyTokens = [
            '{compensation_amount}',
            '{compensation_from_party_label}',
            '{compensation_to_party_label}',
            '{compensation_direction}',
            '{compensation_reference_note}',
            '{compensation_date}',
            '{compensation_transaction_id}',
        ];
        if (in_array($token, $compensationOnlyTokens, true)) {
            return 'Audience: Payments & refunds → Compensation — map on the Customer slot when the recipient is the customer, and on the Provider slot when the recipient is the provider.';
        }

        return 'Audience: Choose Customer template to message the customer, or Provider template to message the provider. The token value comes from the same booking (or ledger event for ledger tokens); only the recipient changes.';
    }

    private static function dropdownModuleLangKeyForToken(string $token): string
    {
        if (str_starts_with($token, '{additional_charge_')) {
            return 'WhatsApp_booking_var_module_booking_fee';
        }

        $ledgerTokens = [
            '{provider_pending_balance}',
            '{provider_due_balance}',
            '{customer_pending_balance}',
            '{amount_received_from_provider}',
            '{amount_collected_from_provider}',
            '{balance_after_payment_collected}',
            '{booking_settlement_net_after_collect}',
            '{amount_sent_to_provider}',
            '{remaining_balance_to_collect}',
            '{remaining_balance_to_send}',
        ];
        if (in_array($token, $ledgerTokens, true)) {
            return 'WhatsApp_booking_var_module_ledger';
        }

        $paymentTokens = [
            '{payment_status}',
            '{previous_payment_status}',
            '{amount_paid}',
            '{due_amount}',
            '{total_bill}',
            '{amount_added}',
            '{payment_received_by}',
            '{payment_date}',
            '{payment_method}',
            '{payment_reference}',
            '{customer_payments_total}',
            '{customer_payments_to_company}',
            '{customer_payments_to_provider}',
            '{customer_payments_unassigned}',
            '{refund_amount}',
            '{refund_date}',
            '{refund_transaction_id}',
            '{refund_reference_note}',
            '{refund_remaining}',
            '{customer_refund_total}',
            '{customer_refund_cap}',
            '{customer_refund_before_this}',
        ];
        if (in_array($token, $paymentTokens, true)) {
            return 'WhatsApp_booking_var_module_booking_payment';
        }

        return 'WhatsApp_booking_var_module_booking';
    }

    /**
     * @return array{hints: array<string, string>, id_to_key: array<string, string>}
     */
    private static function buildAdditionalChargePlaceholderData(): array
    {
        $hints = [];
        $idToKey = [];
        $types = AdditionalChargeType::query()->active()->ordered()->get();
        $seenBaseSlugs = [];
        foreach ($types as $t) {
            $id = (string) $t->id;
            $base = Str::slug((string) $t->name, '_');
            if ($base === '') {
                $base = 'charge';
            }
            $slug = $base;
            if (isset($seenBaseSlugs[$base])) {
                $slug = $base . '_' . substr(str_replace('-', '', $id), 0, 8);
            } else {
                $seenBaseSlugs[$base] = true;
            }
            $key = '{additional_charge_' . $slug . '}';
            $idToKey[$id] = $key;
            $hints[$key] = 'Additional charge amount: ' . $t->name;
        }

        return ['hints' => $hints, 'id_to_key' => $idToKey];
    }

    private static function orphanAdditionalChargeTokenKey(string $typeId, string $name): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'charge';
        }

        return '{additional_charge_' . $base . '_' . substr(str_replace('-', '', $typeId), 0, 8) . '}';
    }

    /**
     * Sub-keys for per-status WhatsApp templates (booking_status_customer_{segment}, etc.).
     * One admin tab per target status; order matches the booking-template UI.
     *
     * @return list<string>
     */
    public static function statusTemplateSegmentKeys(): array
    {
        return [
            'pending',
            'accepted',
            'ongoing',
            'on_hold',
            'completed',
            'canceled',
            'refunded',
            'reopened',
            'reopen_resolved',
            'disputed_close',
            'loss_making',
        ];
    }

    /**
     * Sub-tabs under the admin “Payments & refunds” booking WhatsApp pane (URL query payment_sub=).
     *
     * @return list<string>
     */
    public static function paymentTemplateSubTabKeys(): array
    {
        return [
            'add-payment',
            'collect-provider',
            'pay-provider',
            'refund-customer',
            'compensation',
        ];
    }

    /**
     * Keys for free-text template bodies and optional Meta template bindings (same key + _wa_tpl_id / _wa_body_params).
     *
     * @return list<string>
     */
    public static function configurableMessageKeys(): array
    {
        $keys = [
            'booking_confirmation_customer',
            'booking_confirmation_provider',
            'booking_status_customer',
            'booking_status_provider',
            'provider_change_customer',
            'provider_change_previous_provider',
            'provider_change_new_provider',
            'booking_schedule_customer',
            'booking_schedule_provider',
            'booking_payment_added_customer',
            'booking_payment_added_provider',
            'booking_refund_to_customer',
            'ledger_provider_payment_reminder',
            'ledger_customer_payment_reminder',
            'ledger_payment_received_from_provider',
            'ledger_payment_sent_to_provider',
            'booking_serviceman_customer',
            'booking_serviceman_provider',
            'booking_verification_customer',
            'booking_verification_provider',
            'booking_compensation_customer',
            'booking_compensation_provider',
        ];
        foreach (self::statusTemplateSegmentKeys() as $segment) {
            $keys[] = 'booking_status_customer_' . $segment;
            $keys[] = 'booking_status_provider_' . $segment;
        }

        return $keys;
    }

    /**
     * Short admin hint for the Message template info column: template slot · section [→ detail].
     * Omits the long “Social Inbox → … → Booking message templates” prefix.
     */
    public static function messageTemplateInfoForAdmin(string $messageKey): string
    {
        $dot = ' · ';
        $arrow = ' → ';
        $tNew = translate('WhatsApp_tab_booking_created_by_admin');
        $tSt = translate('WhatsApp_tab_booking_status_changed');
        $tFb = translate('WhatsApp_status_fallback_templates');
        $tPc = translate('WhatsApp_tab_provider_change');
        $tSc = translate('WhatsApp_tab_schedule_change');
        $tPay = translate('WhatsApp_tab_payment_change');
        $tLed = translate('WhatsApp_tab_ledger_payment_messages');

        if (preg_match('/^booking_status_(customer|provider)_(.+)$/', $messageKey, $m)) {
            $party = $m[1];
            $seg = $m[2];
            if (in_array($seg, self::statusTemplateSegmentKeys(), true)) {
                $slot = $party === 'customer' ? translate('Customer_template') : translate('Provider_template');
                $segLab = translate('Booking_status_tpl_' . $seg);

                return $slot . $dot . $tSt . $arrow . $segLab;
            }
        }

        if (in_array($messageKey, [
            'booking_serviceman_customer',
            'booking_serviceman_provider',
            'booking_verification_customer',
            'booking_verification_provider',
        ], true)) {
            return translate('WhatsApp_booking_template_info_reserved_slot', ['key' => $messageKey]);
        }

        return match ($messageKey) {
            'booking_confirmation_customer' => translate('Customer_template') . $dot . $tNew,
            'booking_confirmation_provider' => translate('Provider_template') . $dot . $tNew,
            'booking_status_customer' => translate('Customer_template') . $dot . $tSt . $arrow . $tFb,
            'booking_status_provider' => translate('Provider_template') . $dot . $tSt . $arrow . $tFb,
            'provider_change_customer' => translate('Customer_template') . $dot . $tPc,
            'provider_change_previous_provider' => translate('Previous_provider_template') . $dot . $tPc,
            'provider_change_new_provider' => translate('New_assigned_provider_template') . $dot . $tPc,
            'booking_schedule_customer' => translate('Customer_template') . $dot . $tSc,
            'booking_schedule_provider' => translate('Provider_template') . $dot . $tSc,
            'booking_payment_added_customer' => translate('Customer_template') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_add_booking_payment'),
            'booking_payment_added_provider' => translate('Provider_template') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_add_booking_payment'),
            'ledger_payment_received_from_provider' => translate('WhatsApp_ledger_tpl_payment_received_from_provider') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_collect_from_provider'),
            'ledger_payment_sent_to_provider' => translate('WhatsApp_ledger_tpl_payment_sent_to_provider') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_pay_to_provider'),
            'booking_refund_to_customer' => translate('Customer_template') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_refund_customer'),
            'booking_compensation_customer' => translate('Customer_template') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_compensation'),
            'booking_compensation_provider' => translate('Provider_template') . $dot . $tPay . $arrow . translate('WhatsApp_payment_sub_compensation'),
            'ledger_provider_payment_reminder' => translate('WhatsApp_ledger_tpl_provider_payment_reminder') . $dot . $tLed,
            'ledger_customer_payment_reminder' => translate('WhatsApp_ledger_tpl_customer_payment_reminder') . $dot . $tLed,
            default => translate('WhatsApp_booking_template_info_unknown', ['key' => $messageKey]),
        };
    }

    /**
     * Default bodies for new installs / migrations (customer: service + provider; provider: customer + service).
     *
     * @return array<string, string>
     */
    public static function defaultTemplateBodies(): array
    {
        $statusCustomerDefault = "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\nIf anything looks wrong, contact us.";
        $statusProviderDefault = "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\nCheck your app for full details.";
        $reopenedCustomer = "Booking reopened\n\nBooking *{booking_id}* was completed and has been *reopened*.\nNew status: *{booking_status}*\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\nWe will follow up on your case.";
        $reopenedProvider = "Booking reopened\n\nBooking *{booking_id}* status is now *{booking_status}* (reopened from completed).\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $resolvedCustomer = "Reopen case resolved\n\nBooking *{booking_id}* — your reopen request is marked *resolved*.\n\nRemarks: {reopen_resolve_remarks}\n\nFinal order total: {booking_final_amount} (paid {amount_paid}, due {due_amount})\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $resolvedProvider = "Reopen case resolved\n\nBooking *{booking_id}* reopen case marked resolved.\n\nRemarks: {reopen_resolve_remarks}\n\nFinal order total: {booking_final_amount} (paid {amount_paid}, due {due_amount})\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}";
        $lossMakingCustomer = "Booking completed — loss-making settlement\n\nBooking *{booking_id}* is *completed* with a scaled / partial settlement.\n\nOrder total: {booking_final_amount}\nPaid: {amount_paid}\nCustomer still due: {booking_customer_still_due}\n\nLoss total: {scaled_loss_total} (company {scaled_loss_company_share}, provider {scaled_loss_provider_share})\nNet after loss — company: {scaled_net_company_share}, provider: {scaled_net_provider_share}\n\nSettlement: company pays provider {settlement_company_pays_provider}; provider pays company {settlement_provider_pays_company}\n\nOutcome: {settlement_outcome_label}\nNotes: {settlement_remarks}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $lossMakingProvider = "Booking completed — loss-making settlement\n\nBooking *{booking_id}* completed with scaled settlement.\n\nOrder total: {booking_final_amount}\nPaid: {amount_paid}\nCustomer still due: {booking_customer_still_due}\n\nLoss total: {scaled_loss_total} (company {scaled_loss_company_share}, provider {scaled_loss_provider_share})\nNet after loss — company: {scaled_net_company_share}, provider: {scaled_net_provider_share}\n\nSettlement: company pays provider {settlement_company_pays_provider}; provider pays company {settlement_provider_pays_company}\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $disputedCloseCustomer = "Disputed booking closed\n\nBooking *{booking_id}* — refunds are recorded and the case is closed.\n\nStatus: *{booking_status}* (was: {previous_booking_status})\n\nDispute reason: {dispute_reason}\nRefund paid from company pool: {refund_paid_from_company_pool} (ref: {refund_company_transaction_id})\nRefund paid from provider pool: {refund_paid_from_provider_pool} (ref: {refund_provider_transaction_id})\n\nServices retained: {final_services_charges_retained_from_customer} — admin {final_admin_commission_services_net_basis}, provider {final_provider_earning_services_net_basis}\nSpare retained: {final_spare_parts_charges_retained_from_customer} — admin {final_admin_commission_spare_parts_net_basis}, provider {final_provider_earning_spare_parts_net_basis}\nFinal amount retained from customer: {final_amount_retained_from_customer_after_refunds}\n\nTotals — company commission: {disputed_final_admin_commission}, provider earning: {disputed_final_provider_earning}\nTotal provider pays company: {disputed_total_provider_pays_company}\nTotal company pays provider: {disputed_total_company_pays_provider}\n\nRemarks: {reopen_resolve_remarks}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $disputedCloseProvider = "Disputed booking closed\n\nBooking *{booking_id}* — refund legs recorded; case closed.\n\nStatus: *{booking_status}* (was: {previous_booking_status})\n\nDispute reason: {dispute_reason}\nRefund paid from company pool: {refund_paid_from_company_pool} (ref: {refund_company_transaction_id})\nRefund paid from provider pool: {refund_paid_from_provider_pool} (ref: {refund_provider_transaction_id})\n\nServices retained: {final_services_charges_retained_from_customer} — admin {final_admin_commission_services_net_basis}, provider {final_provider_earning_services_net_basis}\nSpare retained: {final_spare_parts_charges_retained_from_customer} — admin {final_admin_commission_spare_parts_net_basis}, provider {final_provider_earning_spare_parts_net_basis}\nFinal amount retained from customer: {final_amount_retained_from_customer_after_refunds}\n\nTotals — company: {disputed_final_admin_commission}, provider: {disputed_final_provider_earning}\nTotal provider pays company: {disputed_total_provider_pays_company}\nTotal company pays provider: {disputed_total_company_pays_provider}\n\nRemarks: {reopen_resolve_remarks}\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}";

        $perStatus = [];
        foreach (self::statusTemplateSegmentKeys() as $segment) {
            if ($segment === 'reopened') {
                $perStatus['booking_status_customer_reopened'] = $reopenedCustomer;
                $perStatus['booking_status_provider_reopened'] = $reopenedProvider;

                continue;
            }
            if ($segment === 'reopen_resolved') {
                $perStatus['booking_status_customer_reopen_resolved'] = $resolvedCustomer;
                $perStatus['booking_status_provider_reopen_resolved'] = $resolvedProvider;

                continue;
            }
            if ($segment === 'disputed_close') {
                $perStatus['booking_status_customer_disputed_close'] = $disputedCloseCustomer;
                $perStatus['booking_status_provider_disputed_close'] = $disputedCloseProvider;

                continue;
            }
            if ($segment === 'loss_making') {
                $perStatus['booking_status_customer_loss_making'] = $lossMakingCustomer;
                $perStatus['booking_status_provider_loss_making'] = $lossMakingProvider;

                continue;
            }
            $perStatus['booking_status_customer_' . $segment] = $statusCustomerDefault;
            $perStatus['booking_status_provider_' . $segment] = $statusProviderDefault;
        }

        return array_merge([
            'booking_confirmation_customer' => "Hello {customer_name},\n\nYour booking *{booking_id}* is confirmed.\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nWhere: {service_where}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nThank you for choosing us. Reply here if you have questions.",
            'booking_confirmation_provider' => "New booking *{booking_id}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\nAddress: {customer_address}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nService at: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nPlease review the booking in your app or dashboard.",
            'booking_status_customer' => $statusCustomerDefault,
            'booking_status_provider' => $statusProviderDefault,
            'provider_change_customer' => "Hello {customer_name},\n\nYour booking *{booking_id}* now has a different service provider.\n\n*New provider*\n{provider_name}\nPhone: {provider_phone}\n\n*Previous provider*\n{previous_provider_name}\nPhone: {previous_provider_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nWhere: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nPlease save the new provider's contact details for your records.",
            'provider_change_previous_provider' => "Booking reassigned\n\nBooking *{booking_id}* has been reassigned and is *no longer on your list*.\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*New provider*\n{provider_name}\nPhone: {provider_phone}\n\nThank you for your work on this booking.",
            'provider_change_new_provider' => "You have been assigned booking *{booking_id}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\nAddress: {customer_address}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nService at: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\n*Previous provider*\n{previous_provider_name}\nPhone: {previous_provider_phone}\n\nPlease accept and prepare in your app.",
            'booking_schedule_customer' => "Schedule update\n\nBooking *{booking_id}* has a new service time.\n\nBefore: {previous_service_schedule}\nNow: {booking_datetime}\n\nService: {service_name}\nProvider: {provider_name} ({provider_phone})",
            'booking_schedule_provider' => "Schedule update\n\nBooking *{booking_id}* rescheduled.\n\nBefore: {previous_service_schedule}\nNow: {booking_datetime}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
            'booking_payment_added_customer' => "Payment received\n\nBooking *{booking_id}*\nAmount added: *{amount_added}*\nReceived by: {payment_received_by}\nDate: {payment_date}\nMethod: {payment_method}\nRef: {payment_reference}\n\nFrom customer — total: {customer_payments_total} (company {customer_payments_to_company}, provider {customer_payments_to_provider})\n\nTotal: {total_bill} | Paid: {amount_paid} | Due: {due_amount}",
            'booking_payment_added_provider' => "Payment added\n\nBooking *{booking_id}*\nAmount: *{amount_added}*\nReceived by: {payment_received_by}\nDate: {payment_date}\nMethod: {payment_method}\nRef: {payment_reference}\n\nFrom customer — total: {customer_payments_total} (company {customer_payments_to_company}, provider {customer_payments_to_provider})\n\nCustomer: {customer_name} ({customer_phone})\nTotal: {total_bill} | Paid: {amount_paid} | Due: {due_amount}",
            'booking_refund_to_customer' => "Refund update\n\nBooking *{booking_id}*\nWe recorded a refund of *{refund_amount}* on {refund_date}.\nReference: {refund_transaction_id}\nNote: {refund_reference_note}\n\nThis refund: {refund_amount} | Refunded before: {customer_refund_before_this} | Total refunded: {customer_refund_total} | Refund cap: {customer_refund_cap} | Still refundable: {refund_remaining}\n\nPaid on booking — total: {customer_payments_total} (company {customer_payments_to_company}, provider {customer_payments_to_provider})\n\nService: {service_name}",
            'ledger_provider_payment_reminder' => "Payment reminder\n\nHello {provider_name},\n\nPending balance: {provider_pending_balance}\n\nPlease settle at your earliest convenience.",
            'ledger_customer_payment_reminder' => "Payment reminder\n\nHello {customer_name},\n\nOutstanding amount: {customer_pending_balance}\n\nPlease complete your payment.",
            'ledger_payment_received_from_provider' => "Payment received\n\nThank you {provider_name}. Collected: {amount_collected_from_provider}.\n\nStill to collect (settlement): {balance_after_payment_collected}\nNet after this payment: {booking_settlement_net_after_collect}",
            'ledger_payment_sent_to_provider' => "Payment sent\n\nHello {provider_name}, we sent you {amount_sent_to_provider}.\n\nRemaining to pay you: {remaining_balance_to_send}",
            'booking_serviceman_customer' => "Serviceman update\n\nBooking *{booking_id}* — your assigned serviceman changed.\n\nBefore: {previous_serviceman_name} ({previous_serviceman_phone})\nNow: {serviceman_name} ({serviceman_phone})\n\nWhen: {booking_datetime}\nProvider: {provider_name}",
            'booking_serviceman_provider' => "Serviceman update\n\nBooking *{booking_id}*\n\nServiceman: {previous_serviceman_name} → *{serviceman_name}*\nPhone: {serviceman_phone}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
            'booking_verification_customer' => "Booking verification\n\nBooking *{booking_id}* — action: *{verification_action}*\n\nStatus was: {previous_verification_status}\nNow: {verification_status}\n\nService: {service_name}\nWhen: {booking_datetime}",
            'booking_verification_provider' => "Booking verification\n\nBooking *{booking_id}* — *{verification_action}*\n\nVerification: {previous_verification_status} → {verification_status}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
            'booking_compensation_customer' => "Compensation recorded\n\nBooking *{booking_id}*\nAmount: *{compensation_amount}*\n{compensation_direction}\nDate: {compensation_date}\nRef: {compensation_transaction_id}\nNote: {compensation_reference_note}\n\nService: {service_name}",
            'booking_compensation_provider' => "Compensation recorded\n\nBooking *{booking_id}*\nAmount: *{compensation_amount}*\n{compensation_direction}\nDate: {compensation_date}\nRef: {compensation_transaction_id}\nNote: {compensation_reference_note}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
        ], $perStatus);
    }

    public function __construct(
        protected WhatsAppCloudService $cloud,
        protected WhatsAppMessagePersistenceService $messagePersistence
    ) {}

    protected function inferRecipientParty(string $logLabel): string
    {
        $l = mb_strtolower($logLabel);
        if (str_contains($l, '(customer)')) {
            return 'customer';
        }
        if (str_contains($l, '(provider)') || str_contains($l, 'previous provider') || str_contains($l, 'new provider')) {
            return 'provider';
        }

        return 'unknown';
    }

    /**
     * @param  array<string, mixed>  $logCtx
     */
    protected function filterContextForLog(array $logCtx): array
    {
        $allowed = [
            'booking_id',
            'booking_repeat_id',
            'entity_id',
            'segment',
            'party',
            'message_key',
            'skip_code',
            'config_template_id',
            'raw_phone_sample',
        ];
        $out = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $logCtx)) {
                $out[$k] = $logCtx[$k];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $logCtx
     */
    protected function writeAutomationLog(
        array $logCtx,
        string $messageKey,
        ?string $recipientPhone,
        string $logLabel,
        string $result,
        ?string $errorDetail = null,
        ?WhatsAppMarketingTemplate $template = null,
        ?string $waMessageId = null,
        ?int $localWhatsappMessageId = null,
    ): void {
        try {
            $ctxJson = $this->filterContextForLog($logCtx);
            $row = [
                'message_key' => $messageKey,
                'trigger_event' => $logLabel,
                'template_id' => $template?->id,
                'template_name' => $template
                    ? trim($template->name . ' (' . $template->language . ')')
                    : null,
                'recipient_party' => $this->inferRecipientParty($logLabel),
                'recipient_phone' => $recipientPhone,
                'booking_id' => isset($logCtx['booking_id']) ? (string) $logCtx['booking_id'] : null,
                'booking_repeat_id' => isset($logCtx['booking_repeat_id']) ? (string) $logCtx['booking_repeat_id'] : null,
                'wa_message_id' => $waMessageId,
                'local_whatsapp_message_id' => $localWhatsappMessageId,
                'result' => $result,
                'error_detail' => ($errorDetail !== null && $errorDetail !== '') ? mb_substr($errorDetail, 0, 65000) : null,
                'acting_admin_user_id' => $this->triggerAdminId(),
                'context_json' => $ctxJson !== [] ? $ctxJson : null,
            ];
            if ($this->automationLogTableHasChannelColumn()) {
                $row['channel'] = SocialInboxChannel::WHATSAPP;
            }
            WhatsAppBookingAutomationMessageLog::query()->create($row);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp booking automation log write failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log one row per template slot when the master “booking WhatsApp automation” toggle is off,
     * so the automation log is never silent while booking events still fire.
     *
     * @param  array<int, array{key: string, label: string}>  $slots
     */
    protected function logAutomationMasterDisabled(Booking $booking, ?BookingRepeat $repeat, array $slots): void
    {
        $baseCtx = [
            'booking_id' => $booking->id,
        ];
        if ($repeat !== null) {
            $baseCtx['booking_repeat_id'] = $repeat->id;
        }
        $reason = translate('WhatsApp_booking_automation_reason_master_off');
        foreach ($slots as $slot) {
            $this->writeAutomationLog(
                $baseCtx,
                $slot['key'],
                null,
                $slot['label'],
                'skipped',
                $reason
            );
        }
    }

    /**
     * When loadMissing/buildReplacements/send throws, outer callers often catch and only log to laravel.log —
     * persist rows here so the booking automation log is never empty for that event.
     *
     * @param  array<int, array{key: string, label: string}>  $slots
     */
    protected function logAutomationThrowableForSlots(
        Booking $booking,
        ?BookingRepeat $repeat,
        array $slots,
        \Throwable $e,
        string $skipCode = 'automation_exception'
    ): void {
        $detail = mb_substr($e->getMessage() !== '' ? $e->getMessage() : $e::class, 0, 65000);
        $baseCtx = [
            'booking_id' => $booking->id,
            'entity_id' => (string) $booking->id,
            'skip_code' => $skipCode,
        ];
        if ($repeat !== null) {
            $baseCtx['booking_repeat_id'] = $repeat->id;
        }
        foreach ($slots as $slot) {
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => $slot['key']]),
                $slot['key'],
                null,
                $slot['label'],
                'failed',
                $detail
            );
        }
    }

    public function sendBookingConfirmation(?Booking $booking): void
    {
        if (!$booking) {
            return;
        }
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($booking, null, [
                ['key' => 'booking_confirmation_customer', 'label' => 'booking confirm (customer)'],
                ['key' => 'booking_confirmation_provider', 'label' => 'booking confirm (provider)'],
            ]);

            return;
        }

        $lock = Cache::lock(self::CACHE_CONFIRM_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            $baseCtx = [
                'booking_id' => $booking->id,
                'entity_id' => (string) $booking->id,
                'skip_code' => 'skipped_lock_busy',
            ];
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_confirmation_customer']),
                'booking_confirmation_customer',
                null,
                'booking confirm (customer)',
                'skipped',
                'skipped_lock_busy'
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_confirmation_provider']),
                'booking_confirmation_provider',
                null,
                'booking confirm (provider)',
                'skipped',
                'skipped_lock_busy'
            );

            return;
        }
        try {
            try {
                if (Cache::has(self::CACHE_CONFIRM_SENT_PREFIX . $booking->id)) {
                    $baseCtx = [
                        'booking_id' => $booking->id,
                        'entity_id' => (string) $booking->id,
                        'skip_code' => 'skipped_dedup_booking_confirmation',
                    ];
                    $this->writeAutomationLog(
                        array_merge($baseCtx, ['message_key' => 'booking_confirmation_customer']),
                        'booking_confirmation_customer',
                        null,
                        'booking confirm (customer)',
                        'skipped',
                        'skipped_dedup_booking_confirmation'
                    );
                    $this->writeAutomationLog(
                        array_merge($baseCtx, ['message_key' => 'booking_confirmation_provider']),
                        'booking_confirmation_provider',
                        null,
                        'booking confirm (provider)',
                        'skipped',
                        'skipped_dedup_booking_confirmation'
                    );

                    return;
                }

                $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                $vars = $this->buildReplacements($booking, null);
                $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
                $customerOk = $this->trySendBookingMetaOnly(
                    $config,
                    'booking_confirmation_customer',
                    $vars,
                    $customerPhone,
                    'booking confirm (customer)',
                    ['booking_id' => $booking->id]
                );

                $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
                $providerOk = $this->trySendBookingMetaOnly(
                    $config,
                    'booking_confirmation_provider',
                    $vars,
                    $providerPhone,
                    'booking confirm (provider)',
                    ['booking_id' => $booking->id]
                );

                if ($customerOk || $providerOk) {
                    Cache::put(self::CACHE_CONFIRM_SENT_PREFIX . $booking->id, 1, now()->addYears(10));
                }
            } catch (\Throwable $e) {
                $this->logAutomationThrowableForSlots($booking, null, [
                    ['key' => 'booking_confirmation_customer', 'label' => 'booking confirm (customer)'],
                    ['key' => 'booking_confirmation_provider', 'label' => 'booking confirm (provider)'],
                ], $e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    public function sendBookingStatusChange(Booking $booking, string $previousBookingStatus): void
    {
        $newStatus = (string) $booking->booking_status;
        if ($previousBookingStatus === $newStatus) {
            $segment = $this->resolveStatusTemplateSegment($previousBookingStatus, $newStatus, $booking);
            $baseCtx = [
                'booking_id' => $booking->id,
                'entity_id' => (string) $booking->id,
                'segment' => $segment,
                'skip_code' => 'skipped_no_status_transition',
            ];
            $reason = 'skipped_no_status_transition';
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                'booking_status_customer_' . $segment,
                null,
                'booking status (customer)',
                'skipped',
                $reason
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                'booking_status_provider_' . $segment,
                null,
                'booking status (provider)',
                'skipped',
                $reason
            );

            return;
        }

        $segment = $this->resolveStatusTemplateSegment($previousBookingStatus, $newStatus, $booking);

        // Observer + explicit controller calls can both run for the same HTTP save; let the first win.
        $dedupeKey = self::CACHE_STATUS_NOTIFY_DEDUPE_PREFIX . $booking->id . ':'
            . mb_strtolower(trim($previousBookingStatus)) . ':' . mb_strtolower(trim($newStatus));
        if (! Cache::add($dedupeKey, 1, now()->addSeconds(60))) {
            $baseCtx = [
                'booking_id' => $booking->id,
                'entity_id' => (string) $booking->id,
                'segment' => $segment,
                'skip_code' => 'skipped_dedupe_same_status_transition',
            ];
            $reason = 'skipped_dedupe_same_status_transition';
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                'booking_status_customer_' . $segment,
                null,
                'booking status (duplicate notify coalesced) (customer)',
                'skipped',
                $reason
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                'booking_status_provider_' . $segment,
                null,
                'booking status (duplicate notify coalesced) (provider)',
                'skipped',
                $reason
            );

            return;
        }

        try {
            $config = $this->getConfig();
            if (empty($config['enabled'])) {
                $this->logAutomationMasterDisabled($booking, null, [
                    ['key' => 'booking_status_customer_' . $segment, 'label' => 'booking status (customer)'],
                    ['key' => 'booking_status_provider_' . $segment, 'label' => 'booking status (provider)'],
                ]);

                return;
            }

            $lock = Cache::lock(self::CACHE_STATUS_LOCK_PREFIX . $booking->id, 30);
            if (!$lock->get()) {
                // Avoid silent no-ops: if the lock can't be acquired, record a skipped row so admins can
                // see why no WhatsApp was sent for this transition.
                $baseCtx = [
                    'booking_id' => $booking->id,
                    'entity_id' => (string) $booking->id,
                    'segment' => $segment,
                ];
                $reason = 'skipped_lock_busy';
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                    'booking_status_customer_' . $segment,
                    null,
                    'booking status (customer)',
                    'skipped',
                    $reason
                );
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                    'booking_status_provider_' . $segment,
                    null,
                    'booking status (provider)',
                    'skipped',
                    $reason
                );

                return;
            }
            try {
                $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                $vars = $this->buildReplacements($booking, $previousBookingStatus);

                $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
                $this->deliverStatusTemplateMessage(
                    $config,
                    $segment,
                    'customer',
                    $vars,
                    $customerPhone,
                    $booking,
                    null,
                    'booking status',
                    (string) $booking->id
                );

                $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
                $this->deliverStatusTemplateMessage(
                    $config,
                    $segment,
                    'provider',
                    $vars,
                    $providerPhone,
                    $booking,
                    null,
                    'booking status',
                    (string) $booking->id
                );
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            // We claimed the dedupe slot before doing work. If the first caller (often the observer) throws
            // before writing any automation log, the second caller (e.g. controller) would only log
            // skipped_dedupe_same_status_transition — release so the follow-up invocation can send/log for real.
            Cache::forget($dedupeKey);
            throw $e;
        }
    }

    /**
     * When an admin marks a reopen case resolved (no booking_status change).
     */
    public function sendReopenCaseResolved(Booking $booking): void
    {
        $config = $this->getConfig();
        $segment = 'reopen_resolved';
        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($booking, null, [
                ['key' => 'booking_status_customer_' . $segment, 'label' => 'reopen resolved (customer)'],
                ['key' => 'booking_status_provider_' . $segment, 'label' => 'reopen resolved (provider)'],
            ]);

            return;
        }

        $dedupKey = self::CACHE_REOPEN_RESOLVED_SENT_PREFIX . $booking->id;
        $lock = Cache::lock(self::CACHE_REOPEN_RESOLVED_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            $baseCtx = [
                'booking_id' => $booking->id,
                'entity_id' => (string) $booking->id,
                'segment' => $segment,
                'skip_code' => 'skipped_lock_busy',
            ];
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                'booking_status_customer_' . $segment,
                null,
                'reopen resolved (customer)',
                'skipped',
                'skipped_lock_busy'
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                'booking_status_provider_' . $segment,
                null,
                'reopen resolved (provider)',
                'skipped',
                'skipped_lock_busy'
            );

            return;
        }
        try {
            if (Cache::has($dedupKey)) {
                $baseCtx = [
                    'booking_id' => $booking->id,
                    'entity_id' => (string) $booking->id,
                    'segment' => $segment,
                    'skip_code' => 'skipped_dedup_reopen_resolved',
                ];
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                    'booking_status_customer_' . $segment,
                    null,
                    'reopen resolved (customer)',
                    'skipped',
                    'skipped_dedup_reopen_resolved'
                );
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                    'booking_status_provider_' . $segment,
                    null,
                    'reopen resolved (provider)',
                    'skipped',
                    'skipped_dedup_reopen_resolved'
                );

                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, null), [
                '{reopen_resolve_remarks}' => trim((string) ($booking->reopen_resolve_remarks ?? '')) !== ''
                    ? (string) $booking->reopen_resolve_remarks
                    : '—',
            ]);

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            $cOk = $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'customer',
                $vars,
                $customerPhone,
                $booking,
                null,
                'reopen resolved',
                (string) $booking->id
            );

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            $pOk = $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'provider',
                $vars,
                $providerPhone,
                $booking,
                null,
                'reopen resolved',
                (string) $booking->id
            );

            if ($cOk || $pOk) {
                Cache::put($dedupKey, 1, now()->addSeconds(15));
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * When a disputed reopen is closed with recorded refund legs + snapshot (booking_status may also change).
     */
    public function sendDisputedReopenRefundRecorded(Booking $booking, string $previousBookingStatus): void
    {
        $snap = $booking->reopen_disputed_snapshot ?? null;
        if (! is_array($snap) || (($snap['type'] ?? null) !== 'reopen_disputed_refund')) {
            return;
        }

        $config = $this->getConfig();
        $segment = 'disputed_close';
        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($booking, null, [
                ['key' => 'booking_status_customer_' . $segment, 'label' => 'disputed close (customer)'],
                ['key' => 'booking_status_provider_' . $segment, 'label' => 'disputed close (provider)'],
            ]);

            return;
        }

        $fingerprint = (string) ($snap['submitted_at'] ?? '');
        if ($fingerprint === '') {
            $fingerprint = substr(sha1(json_encode($snap)), 0, 16);
        }
        $dedupKey = self::CACHE_DISPUTED_REOPEN_REFUND_SENT_PREFIX . $booking->id . ':' . $fingerprint;
        $lock = Cache::lock(self::CACHE_DISPUTED_REOPEN_REFUND_LOCK_PREFIX . $booking->id, 30);
        if (! $lock->get()) {
            $baseCtx = [
                'booking_id' => $booking->id,
                'entity_id' => (string) $booking->id,
                'segment' => $segment,
                'skip_code' => 'skipped_lock_busy',
            ];
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                'booking_status_customer_' . $segment,
                null,
                'disputed close (customer)',
                'skipped',
                'skipped_lock_busy'
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                'booking_status_provider_' . $segment,
                null,
                'disputed close (provider)',
                'skipped',
                'skipped_lock_busy'
            );

            return;
        }
        try {
            if (Cache::has($dedupKey)) {
                $baseCtx = [
                    'booking_id' => $booking->id,
                    'entity_id' => (string) $booking->id,
                    'segment' => $segment,
                    'skip_code' => 'skipped_dedup_disputed_close',
                ];
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                    'booking_status_customer_' . $segment,
                    null,
                    'disputed close (customer)',
                    'skipped',
                    'skipped_dedup_disputed_close'
                );
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                    'booking_status_provider_' . $segment,
                    null,
                    'disputed close (provider)',
                    'skipped',
                    'skipped_dedup_disputed_close'
                );

                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, $previousBookingStatus), [
                '{reopen_resolve_remarks}' => trim((string) ($booking->reopen_resolve_remarks ?? '')) !== ''
                    ? (string) $booking->reopen_resolve_remarks
                    : '—',
            ], $this->buildDisputedReopenRefundPlaceholderExtras($snap));

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            $cOk = $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'customer',
                $vars,
                $customerPhone,
                $booking,
                null,
                'disputed close',
                (string) $booking->id
            );

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            $pOk = $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'provider',
                $vars,
                $providerPhone,
                $booking,
                null,
                'disputed close',
                (string) $booking->id
            );

            if ($cOk || $pOk) {
                Cache::put($dedupKey, 1, now()->addSeconds(15));
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Status templates for one repeat occurrence (readable id, schedule, amounts from the repeat row).
     */
    public function sendBookingRepeatStatusChange(BookingRepeat $repeat, string $previousBookingStatus): void
    {
        $newStatus = (string) $repeat->booking_status;
        if ($previousBookingStatus === $newStatus) {
            $repeat->loadMissing(['booking']);
            $parent = $repeat->booking;
            if ($parent) {
                $segment = $this->resolveStatusTemplateSegment($previousBookingStatus, $newStatus, $parent);
                $baseCtx = [
                    'booking_id' => $parent->id,
                    'booking_repeat_id' => $repeat->id,
                    'entity_id' => (string) $repeat->id,
                    'segment' => $segment,
                    'skip_code' => 'skipped_no_status_transition',
                ];
                $reason = 'skipped_no_status_transition';
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                    'booking_status_customer_' . $segment,
                    null,
                    'repeat booking status (customer)',
                    'skipped',
                    $reason
                );
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                    'booking_status_provider_' . $segment,
                    null,
                    'repeat booking status (provider)',
                    'skipped',
                    $reason
                );
            }

            return;
        }

        $repeat->loadMissing([
            'booking.customer',
            'booking.service_address',
            'booking.detail',
            'booking.booking_partial_payments',
            'booking',
            'detail',
            'provider.owner',
        ]);
        $parent = $repeat->booking;
        if (!$parent) {
            if ($repeat->booking_id) {
                $reason = 'skipped_repeat_parent_booking_missing';
                $segFallback = in_array($newStatus, self::statusTemplateSegmentKeys(), true)
                    ? $newStatus
                    : 'pending';
                $baseCtx = [
                    'booking_id' => $repeat->booking_id,
                    'booking_repeat_id' => $repeat->id,
                    'entity_id' => (string) $repeat->id,
                    'skip_code' => $reason,
                    'segment' => $segFallback,
                ];
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segFallback]),
                    'booking_status_customer_' . $segFallback,
                    null,
                    'repeat booking status (customer)',
                    'skipped',
                    $reason
                );
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segFallback]),
                    'booking_status_provider_' . $segFallback,
                    null,
                    'repeat booking status (provider)',
                    'skipped',
                    $reason
                );
            }

            return;
        }

        $config = $this->getConfig();
        $segment = $this->resolveStatusTemplateSegment($previousBookingStatus, $newStatus, $parent);
        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($parent, $repeat, [
                ['key' => 'booking_status_customer_' . $segment, 'label' => 'repeat booking status (customer)'],
                ['key' => 'booking_status_provider_' . $segment, 'label' => 'repeat booking status (provider)'],
            ]);

            return;
        }

        $lock = Cache::lock(self::CACHE_STATUS_LOCK_PREFIX . 'repeat:' . $repeat->id, 30);
        if (!$lock->get()) {
            $baseCtx = [
                'booking_id' => $parent->id,
                'booking_repeat_id' => $repeat->id,
                'entity_id' => (string) $repeat->id,
                'segment' => $segment,
            ];
            $reason = 'skipped_lock_busy';
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'customer', 'message_key' => 'booking_status_customer_' . $segment]),
                'booking_status_customer_' . $segment,
                null,
                'repeat booking status (customer)',
                'skipped',
                $reason
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['party' => 'provider', 'message_key' => 'booking_status_provider_' . $segment]),
                'booking_status_provider_' . $segment,
                null,
                'repeat booking status (provider)',
                'skipped',
                $reason
            );

            return;
        }
        try {
            try {
                $vars = $this->buildRepeatStatusReplacements($repeat, $parent, $previousBookingStatus);

                $customerPhone = $this->normalizePhone($parent->customer?->phone, $config);
                $this->deliverStatusTemplateMessage(
                    $config,
                    $segment,
                    'customer',
                    $vars,
                    $customerPhone,
                    $parent,
                    $repeat,
                    'repeat booking status',
                    (string) $repeat->id
                );

                $provider = $repeat->provider ?? $parent->provider;
                $providerPhone = $this->resolveProviderPhone($provider, $config);
                $this->deliverStatusTemplateMessage(
                    $config,
                    $segment,
                    'provider',
                    $vars,
                    $providerPhone,
                    $parent,
                    $repeat,
                    'repeat booking status',
                    (string) $repeat->id
                );
            } catch (\Throwable $e) {
                $this->logAutomationThrowableForSlots($parent, $repeat, [
                    ['key' => 'booking_status_customer_' . $segment, 'label' => 'repeat booking status (customer)'],
                    ['key' => 'booking_status_provider_' . $segment, 'label' => 'repeat booking status (provider)'],
                ], $e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function buildRepeatRowReplacements(BookingRepeat $repeat, Booking $parent): array
    {
        $parent->loadMissing(['customer', 'service_address', 'detail', 'booking_partial_payments']);
        $repeat->loadMissing(['detail', 'provider.owner', 'serviceman.user']);

        $vars = $this->buildReplacements($parent, null);

        $newKey = (string) ($repeat->booking_status ?? '');
        $vars['{booking_status}'] = $newKey !== '' ? ucwords(str_replace('_', ' ', $newKey)) : '—';
        $vars['{previous_booking_status}'] = '—';
        $vars['{booking_id}'] = (string) ($repeat->readable_id ?? $parent->readable_id ?? $parent->id);

        $vars['{booking_datetime}'] = $this->formatServiceDateTimeForMessages(
            $repeat->service_schedule ? (string) $repeat->service_schedule : null
        );

        $provider = $repeat->provider ?? $parent->provider;
        $providerName = $provider?->company_name
            ?: ($provider?->contact_person_name ?? '—');
        $vars['{provider_name}'] = $providerName;
        $vars['{provider_phone}'] = $this->formatProviderPhoneDisplay($provider);

        $serviceNames = $repeat->detail && $repeat->detail->isNotEmpty()
            ? $repeat->detail->pluck('service_name')->filter()->unique()->implode(', ')
            : '';
        if ($serviceNames === '') {
            $serviceNames = $parent->detail->pluck('service_name')->filter()->unique()->implode(', ');
        }
        if ($serviceNames === '') {
            $serviceNames = '—';
        }
        $vars['{service_name}'] = $serviceNames;

        // For repeat rows, hold reason/remarks are recorded against booking_repeat_id (not the parent booking row).
        // Fall back to parent tokens if repeat history is missing.
        $repeatHoldHist = BookingStatusHistory::query()
            ->where('booking_repeat_id', $repeat->id)
            ->where('booking_status', 'on_hold')
            ->with('holdReopenReason')
            ->latest('created_at')
            ->latest('id')
            ->first();
        if ($repeatHoldHist) {
            $repeatHoldReason = trim((string) ($repeatHoldHist->holdReopenReason?->name ?? ''));
            $repeatHoldRemarks = trim((string) ($repeatHoldHist->status_change_remarks ?? ''));
            if ($repeatHoldReason === '' && $repeatHoldRemarks !== '') {
                $repeatHoldReason = $repeatHoldRemarks;
            }
            $vars['{on_hold_reason}'] = $repeatHoldReason !== '' ? $repeatHoldReason : '—';
            $vars['{on_hold_reason_remarks}'] = $repeatHoldRemarks !== '' ? $repeatHoldRemarks : '—';
        }

        $totalBill = get_booking_total_amount($repeat);
        $amountPaid = get_booking_total_paid($repeat);
        $due = max(0, round($totalBill - $amountPaid, 2));
        $vars['{total_bill}'] = function_exists('with_currency_symbol') ? with_currency_symbol($totalBill) : (string) $totalBill;
        $vars['{amount_paid}'] = function_exists('with_currency_symbol') ? with_currency_symbol($amountPaid) : (string) $amountPaid;
        $vars['{due_amount}'] = function_exists('with_currency_symbol') ? with_currency_symbol($due) : (string) $due;

        $customerAddress = $vars['{customer_address}'];
        $vars['{service_where}'] = $this->serviceWhereLabel($parent, $customerAddress, $provider);

        $vars['{payment_status}'] = $this->paymentPaidLabel((int) ($repeat->is_paid ?? 0));
        $sm = $this->servicemanDisplayPair($repeat->serviceman);
        $vars['{serviceman_name}'] = $sm['name'];
        $vars['{serviceman_phone}'] = $sm['phone'];
        $vars['{verification_status}'] = $this->verificationStateLabel((int) ($parent->is_verified ?? 0));
        $vars['{reopen_resolve_remarks}'] = '—';

        return $vars;
    }

    /**
     * @return array<string, string>
     */
    protected function buildRepeatStatusReplacements(BookingRepeat $repeat, Booking $parent, string $previousBookingStatus): array
    {
        $vars = $this->buildRepeatRowReplacements($repeat, $parent);
        $vars['{previous_booking_status}'] = $previousBookingStatus !== ''
            ? ucwords(str_replace('_', ' ', $previousBookingStatus))
            : '—';

        return $vars;
    }

    public function sendBookingScheduleChange(Booking $booking, ?string $previousServiceScheduleRaw): void
    {
        $config = $this->getConfig();
        $prevFormatted = $this->formatScheduleToken($previousServiceScheduleRaw);
        $newFormatted = $this->formatScheduleToken($booking->service_schedule);
        if ($prevFormatted === $newFormatted) {
            $baseCtx = [
                'booking_id' => $booking->id,
                'entity_id' => (string) $booking->id,
                'skip_code' => 'skipped_no_schedule_change',
            ];
            $reason = 'skipped_no_schedule_change';
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                'booking_schedule_customer',
                null,
                'booking schedule (customer)',
                'skipped',
                $reason
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                'booking_schedule_provider',
                null,
                'booking schedule (provider)',
                'skipped',
                $reason
            );

            return;
        }

        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($booking, null, [
                ['key' => 'booking_schedule_customer', 'label' => 'booking schedule (customer)'],
                ['key' => 'booking_schedule_provider', 'label' => 'booking schedule (provider)'],
            ]);

            return;
        }

        $dedupKey = self::CACHE_SCHEDULE_SENT_PREFIX . $booking->id . ':' . $prevFormatted . '>' . $newFormatted;
        $lock = Cache::lock(self::CACHE_SCHEDULE_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            $baseCtx = ['booking_id' => $booking->id, 'entity_id' => (string) $booking->id, 'skip_code' => 'skipped_lock_busy'];
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                'booking_schedule_customer',
                null,
                'booking schedule (customer)',
                'skipped',
                'skipped_lock_busy'
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                'booking_schedule_provider',
                null,
                'booking schedule (provider)',
                'skipped',
                'skipped_lock_busy'
            );

            return;
        }
        try {
            try {
                if (Cache::has($dedupKey)) {
                    $baseCtx = ['booking_id' => $booking->id, 'entity_id' => (string) $booking->id, 'skip_code' => 'skipped_dedup_schedule_change'];
                    $this->writeAutomationLog(
                        array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                        'booking_schedule_customer',
                        null,
                        'booking schedule (customer)',
                        'skipped',
                        'skipped_dedup_schedule_change'
                    );
                    $this->writeAutomationLog(
                        array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                        'booking_schedule_provider',
                        null,
                        'booking schedule (provider)',
                        'skipped',
                        'skipped_dedup_schedule_change'
                    );

                    return;
                }

                $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                $vars = array_merge($this->buildReplacements($booking, null), [
                    '{previous_service_schedule}' => $prevFormatted,
                ]);

                $ok = $this->sendTemplatePair($config, $vars, $booking->customer?->phone, $booking->provider, 'booking_schedule_customer', 'booking_schedule_provider', 'schedule', (string) $booking->id, $booking->id, null);
                if ($ok) {
                    Cache::put($dedupKey, 1, now()->addYears(3));
                }
            } catch (\Throwable $e) {
                $this->logAutomationThrowableForSlots($booking, null, [
                    ['key' => 'booking_schedule_customer', 'label' => 'booking schedule (customer)'],
                    ['key' => 'booking_schedule_provider', 'label' => 'booking schedule (provider)'],
                ], $e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    public function sendBookingRepeatScheduleChange(BookingRepeat $repeat, ?string $previousServiceScheduleRaw): void
    {
        $repeat->loadMissing(['booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments', 'booking', 'detail', 'provider.owner', 'serviceman.user']);
        $parent = $repeat->booking;
        if (!$parent) {
            if ($repeat->booking_id) {
                $reason = 'skipped_repeat_parent_booking_missing';
                $baseCtx = [
                    'booking_id' => $repeat->booking_id,
                    'booking_repeat_id' => $repeat->id,
                    'entity_id' => (string) $repeat->id,
                    'skip_code' => $reason,
                ];
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                    'booking_schedule_customer',
                    null,
                    'repeat booking schedule (customer)',
                    'skipped',
                    $reason
                );
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                    'booking_schedule_provider',
                    null,
                    'repeat booking schedule (provider)',
                    'skipped',
                    $reason
                );
            }

            return;
        }

        $config = $this->getConfig();
        $prevFormatted = $this->formatScheduleToken($previousServiceScheduleRaw);
        $newFormatted = $this->formatScheduleToken($repeat->service_schedule);
        if ($prevFormatted === $newFormatted) {
            $reason = 'skipped_no_schedule_change';
            $baseCtx = [
                'booking_id' => $parent->id,
                'booking_repeat_id' => $repeat->id,
                'entity_id' => (string) $repeat->id,
                'skip_code' => $reason,
            ];
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                'booking_schedule_customer',
                null,
                'repeat booking schedule (customer)',
                'skipped',
                $reason
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                'booking_schedule_provider',
                null,
                'repeat booking schedule (provider)',
                'skipped',
                $reason
            );

            return;
        }

        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($parent, $repeat, [
                ['key' => 'booking_schedule_customer', 'label' => 'booking repeat_schedule (customer)'],
                ['key' => 'booking_schedule_provider', 'label' => 'booking repeat_schedule (provider)'],
            ]);

            return;
        }

        $dedupKey = self::CACHE_SCHEDULE_SENT_PREFIX . 'repeat:' . $repeat->id . ':' . $prevFormatted . '>' . $newFormatted;
        $lock = Cache::lock(self::CACHE_SCHEDULE_LOCK_PREFIX . 'repeat:' . $repeat->id, 30);
        if (!$lock->get()) {
            $baseCtx = [
                'booking_id' => $parent->id,
                'booking_repeat_id' => $repeat->id,
                'entity_id' => (string) $repeat->id,
                'skip_code' => 'skipped_lock_busy',
            ];
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                'booking_schedule_customer',
                null,
                'repeat booking schedule (customer)',
                'skipped',
                'skipped_lock_busy'
            );
            $this->writeAutomationLog(
                array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                'booking_schedule_provider',
                null,
                'repeat booking schedule (provider)',
                'skipped',
                'skipped_lock_busy'
            );

            return;
        }
        try {
            try {
                if (Cache::has($dedupKey)) {
                    $baseCtx = [
                        'booking_id' => $parent->id,
                        'booking_repeat_id' => $repeat->id,
                        'entity_id' => (string) $repeat->id,
                        'skip_code' => 'skipped_dedup_repeat_schedule_change',
                    ];
                    $this->writeAutomationLog(
                        array_merge($baseCtx, ['message_key' => 'booking_schedule_customer']),
                        'booking_schedule_customer',
                        null,
                        'repeat booking schedule (customer)',
                        'skipped',
                        'skipped_dedup_repeat_schedule_change'
                    );
                    $this->writeAutomationLog(
                        array_merge($baseCtx, ['message_key' => 'booking_schedule_provider']),
                        'booking_schedule_provider',
                        null,
                        'repeat booking schedule (provider)',
                        'skipped',
                        'skipped_dedup_repeat_schedule_change'
                    );

                    return;
                }

                $vars = array_merge($this->buildRepeatRowReplacements($repeat, $parent), [
                    '{previous_service_schedule}' => $prevFormatted,
                ]);

                $provider = $repeat->provider ?? $parent->provider;
                $ok = $this->sendTemplatePair($config, $vars, $parent->customer?->phone, $provider, 'booking_schedule_customer', 'booking_schedule_provider', 'repeat_schedule', (string) $repeat->id, $parent->id, $repeat->id);
                if ($ok) {
                    Cache::put($dedupKey, 1, now()->addYears(3));
                }
            } catch (\Throwable $e) {
                $this->logAutomationThrowableForSlots($parent, $repeat, [
                    ['key' => 'booking_schedule_customer', 'label' => 'repeat booking schedule (customer)'],
                    ['key' => 'booking_schedule_provider', 'label' => 'repeat booking schedule (provider)'],
                ], $e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    public function sendBookingPaymentChange(Booking $booking, int $previousIsPaid): void
    {
        // Removed: “Payment status changed” automation is no longer used.
    }

    protected function compensationPartyLabel(string $party): string
    {
        return match ($party) {
            BookingCompensation::PARTY_COMPANY => translate('Company'),
            BookingCompensation::PARTY_PROVIDER => translate('Provider'),
            BookingCompensation::PARTY_CUSTOMER => translate('Customer'),
            default => $party !== '' ? ucfirst($party) : '—',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function buildCompensationPlaceholderRow(BookingCompensation $comp): array
    {
        $from = $this->compensationPartyLabel((string) $comp->from_party);
        $to = $this->compensationPartyLabel((string) $comp->to_party);
        $dateStr = $comp->date ? $comp->date->toDateString() : now()->toDateString();
        $refNote = trim((string) ($comp->reference_note ?? ''));
        $tx = trim((string) ($comp->transaction_id ?? ''));

        return [
            '{compensation_amount}' => $this->formatMoneyAmountForMessages((float) ($comp->amount ?? 0)),
            '{compensation_from_party_label}' => $from,
            '{compensation_to_party_label}' => $to,
            '{compensation_direction}' => $from.' → '.$to,
            '{compensation_reference_note}' => $refNote !== '' ? $refNote : '—',
            '{compensation_date}' => $dateStr,
            '{compensation_transaction_id}' => $tx !== '' ? $tx : '—',
        ];
    }

    /**
     * After admin records a customer refund on a canceled / eligible booking.
     *
     * @param  array{date?: string, transaction_id?: string, reference_note?: string}  $meta
     */
    public function sendBookingRefundToCustomer(Booking $booking, float $amount, array $meta = []): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($booking, null, [
                ['key' => 'booking_refund_to_customer', 'label' => 'booking refund to customer (customer)'],
            ]);

            return;
        }

        try {
            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);

            $totalsAfter = get_booking_refund_display_totals($booking);
            $remaining = round((float) ($totalsAfter['refundable_remaining'] ?? 0), 2);
            $refundedAfter = round((float) ($totalsAfter['refunded_total'] ?? 0), 2);
            $refundedBeforeThis = round(max(0.0, $refundedAfter - $amount), 2);

            $date = trim((string) ($meta['date'] ?? ''));
            if ($date === '') {
                $date = now()->toDateString();
            }
            $tx = trim((string) ($meta['transaction_id'] ?? ''));
            if ($tx === '') {
                $tx = '—';
            }
            $note = trim((string) ($meta['reference_note'] ?? ''));
            if ($note === '') {
                $note = '—';
            }

            $vars = array_merge($this->buildReplacements($booking, null), [
                '{refund_amount}' => $this->formatMoneyAmountForMessages($amount),
                '{refund_date}' => $date,
                '{refund_transaction_id}' => $tx,
                '{refund_reference_note}' => $note,
                '{refund_remaining}' => $this->formatMoneyAmountForMessages($remaining),
                '{customer_refund_before_this}' => $this->formatMoneyAmountForMessages($refundedBeforeThis),
                '{customer_refund_total}' => $this->formatMoneyAmountForMessages($refundedAfter),
                '{customer_refund_cap}' => $this->formatMoneyAmountForMessages(round((float) ($totalsAfter['max_eligible'] ?? 0), 2)),
            ]);

            $this->trySendBookingMetaOnly(
                $config,
                'booking_refund_to_customer',
                $vars,
                $booking->customer?->phone,
                'booking refund to customer (customer)',
                [
                    'booking_id' => (string) $booking->id,
                    'entity_id' => (string) $booking->id.'|'.$date.'|'.$tx,
                ]
            );
        } catch (\Throwable $e) {
            $this->logAutomationThrowableForSlots($booking, null, [
                ['key' => 'booking_refund_to_customer', 'label' => 'booking refund to customer (customer)'],
            ], $e);
            throw $e;
        }
    }

    /**
     * After {@see BookingCompensation} is created from the booking compensation modal.
     *
     * @param  'company_to_customer'|'company_to_provider'|'provider_to_customer'  $compensationType
     */
    public function sendBookingCompensationRecorded(Booking $booking, BookingCompensation $comp, string $compensationType): void
    {
        $compensationType = match ($compensationType) {
            'company_to_customer', 'company_to_provider', 'provider_to_customer' => $compensationType,
            default => '',
        };
        if ($compensationType === '') {
            return;
        }

        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            if ($compensationType === 'company_to_provider') {
                $this->logAutomationMasterDisabled($booking, null, [
                    ['key' => 'booking_compensation_provider', 'label' => 'booking compensation (provider)'],
                ]);
            } else {
                $this->logAutomationMasterDisabled($booking, null, [
                    ['key' => 'booking_compensation_customer', 'label' => 'booking compensation (customer)'],
                ]);
            }

            return;
        }

        try {
            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, null), $this->buildCompensationPlaceholderRow($comp));
            $ctx = [
                'booking_id' => (string) $booking->id,
                'entity_id' => (string) $comp->id,
            ];

            if ($compensationType === 'company_to_provider') {
                $this->trySendBookingMetaOnly(
                    $config,
                    'booking_compensation_provider',
                    $vars,
                    $this->resolveProviderPhone($booking->provider, $config),
                    'booking compensation to provider (provider)',
                    $ctx
                );

                return;
            }

            $this->trySendBookingMetaOnly(
                $config,
                'booking_compensation_customer',
                $vars,
                $booking->customer?->phone,
                'booking compensation to customer (customer)',
                $ctx
            );
        } catch (\Throwable $e) {
            if ($compensationType === 'company_to_provider') {
                $this->logAutomationThrowableForSlots($booking, null, [
                    ['key' => 'booking_compensation_provider', 'label' => 'booking compensation (provider)'],
                ], $e);
            } else {
                $this->logAutomationThrowableForSlots($booking, null, [
                    ['key' => 'booking_compensation_customer', 'label' => 'booking compensation (customer)'],
                ], $e);
            }
            throw $e;
        }
    }

    /**
     * Triggered when a payment is added from booking details (Add payment modal).
     *
     * @param  array{date?: string, payment_method?: string, reference_id?: string}  $meta
     */
    public function sendBookingPaymentAdded(Booking $booking, BookingPartialPayment $partial, array $meta = []): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            $this->logAutomationMasterDisabled($booking, null, [
                ['key' => 'booking_payment_added_customer', 'label' => 'booking payment added (customer)'],
                ['key' => 'booking_payment_added_provider', 'label' => 'booking payment added (provider)'],
            ]);

            return;
        }

        try {
            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);

            $amount = (float) ($partial->paid_amount ?? 0);
            $amountLabel = function_exists('with_currency_symbol') ? with_currency_symbol($amount) : (string) $amount;

            $receivedBy = strtolower(trim((string) ($partial->received_by ?? '')));
            $receivedByLabel = match ($receivedBy) {
                'company' => translate('Company'),
                'provider' => translate('Provider'),
                default => $receivedBy !== '' ? ucfirst($receivedBy) : '—',
            };

            $date = trim((string) ($meta['date'] ?? ''));
            if ($date === '') {
                $date = $partial->created_at ? $partial->created_at->toDateString() : now()->toDateString();
            }
            $method = trim((string) ($meta['payment_method'] ?? ''));
            if ($method === '') {
                $method = '—';
            }
            $reference = trim((string) ($meta['reference_id'] ?? ''));
            if ($reference === '') {
                $reference = (string) ($partial->transaction_id ?? '');
            }
            if ($reference === '') {
                $reference = '—';
            }

            $vars = array_merge($this->buildReplacements($booking, null), [
                '{amount_added}' => $amountLabel,
                '{payment_received_by}' => $receivedByLabel,
                '{payment_date}' => $date,
                '{payment_method}' => $method,
                '{payment_reference}' => $reference,
            ]);

            $this->sendTemplatePair(
                $config,
                $vars,
                $booking->customer?->phone,
                $booking->provider,
                'booking_payment_added_customer',
                'booking_payment_added_provider',
                'payment added',
                (string) $booking->id,
                (string) $booking->id,
                null
            );
        } catch (\Throwable $e) {
            $this->logAutomationThrowableForSlots($booking, null, [
                ['key' => 'booking_payment_added_customer', 'label' => 'booking payment added (customer)'],
                ['key' => 'booking_payment_added_provider', 'label' => 'booking payment added (provider)'],
            ], $e);
            throw $e;
        }
    }

    public function sendBookingRepeatPaymentChange(BookingRepeat $repeat, int $previousIsPaid): void
    {
        // Removed: “Payment status changed” automation is no longer used.
    }

    public function sendBookingServicemanChange(Booking $booking, ?string $previousServicemanId): void
    {
        // Serviceman-assignment WhatsApp automation removed.
    }

    public function sendBookingRepeatServicemanChange(BookingRepeat $repeat, ?string $previousServicemanId): void
    {
        // Serviceman-assignment WhatsApp automation removed.
    }

    public function sendBookingVerificationChange(Booking $booking, int $previousIsVerified, string $action): void
    {
        // Booking-verification WhatsApp automation removed.
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function sendTemplatePair(
        array $config,
        array $vars,
        ?string $customerPhoneRaw,
        ?Provider $provider,
        string $customerKey,
        string $providerKey,
        string $logContext,
        string $entityId,
        ?string $bookingIdForLog = null,
        ?string $bookingRepeatIdForLog = null,
    ): bool {
        $ctx = array_filter([
            'entity_id' => $entityId,
            'booking_id' => $bookingIdForLog,
            'booking_repeat_id' => $bookingRepeatIdForLog,
        ], static fn ($v) => $v !== null && $v !== '');

        $customerPhone = $this->normalizePhone($customerPhoneRaw, $config);
        $cOk = $this->trySendBookingMetaOnly(
            $config,
            $customerKey,
            $vars,
            $customerPhone,
            'booking ' . $logContext . ' (customer)',
            $ctx
        );

        $providerPhone = $this->resolveProviderPhone($provider, $config);
        $pOk = $this->trySendBookingMetaOnly(
            $config,
            $providerKey,
            $vars,
            $providerPhone,
            'booking ' . $logContext . ' (provider)',
            $ctx
        );

        return $cOk || $pOk;
    }

    /**
     * Human-readable service date/time for all booking WhatsApp placeholders (booking_datetime, previous_service_schedule, dedupe keys).
     */
    protected function formatServiceDateTimeForMessages(?string $raw): string
    {
        if (!$raw) {
            return '—';
        }
        try {
            $dt = \Carbon\Carbon::parse($raw)->timezone(config('app.timezone'));

            return $dt->format('jS F Y g:i A');
        } catch (\Throwable) {
            return '—';
        }
    }

    protected function formatScheduleToken(?string $raw): string
    {
        return $this->formatServiceDateTimeForMessages($raw);
    }

    protected function formatMoneyAmountForMessages(float $amount): string
    {
        $rounded = round($amount, 2);

        return function_exists('with_currency_symbol') ? with_currency_symbol($rounded) : (string) $rounded;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, string>
     */
    protected function buildDisputedReopenRefundPlaceholderExtras(array $snapshot): array
    {
        $total = round((float) ($snapshot['refund_total'] ?? 0), 2);
        $company = round((float) ($snapshot['refund_company_amount'] ?? 0), 2);
        $provider = round((float) ($snapshot['refund_provider_amount'] ?? 0), 2);
        $retained = round((float) ($snapshot['retained_from_customer'] ?? 0), 2);
        $provOwes = round((float) ($snapshot['provider_owes_company'] ?? 0), 2);
        $coOwesPr = round((float) ($snapshot['company_owes_provider'] ?? 0), 2);
        $coCash = round((float) ($snapshot['company_cash_after_refund'] ?? 0), 2);
        $prCash = round((float) ($snapshot['provider_cash_after_refund'] ?? 0), 2);
        $finCo = round((float) ($snapshot['final_admin_commission'] ?? 0), 2);
        $finPr = round((float) ($snapshot['final_provider_earning'] ?? 0), 2);
        $coPaysPr = round((float) ($snapshot['company_pays_provider_total'] ?? 0), 2);
        $prRemit = round((float) ($snapshot['provider_total_remittance_to_company'] ?? 0), 2);

        $disputeReason = trim((string) ($snapshot['booking_dispute_reason_name'] ?? ''));
        if ($disputeReason === '') {
            $disputeReason = '—';
        }
        $tidCo = trim((string) ($snapshot['refund_company_transaction_id'] ?? ''));
        $tidPr = trim((string) ($snapshot['refund_provider_transaction_id'] ?? ''));

        $fmtTx = static fn (string $s): string => $s !== '' ? $s : '—';

        return [
            '{dispute_reason}' => $disputeReason,
            '{refund_paid_from_company_pool}' => $this->formatMoneyAmountForMessages($company),
            '{refund_paid_from_provider_pool}' => $this->formatMoneyAmountForMessages($provider),
            '{refund_company_transaction_id}' => $fmtTx($tidCo),
            '{refund_provider_transaction_id}' => $fmtTx($tidPr),
            '{final_services_charges_retained_from_customer}' => $this->formatMoneyAmountForMessages(round((float) ($snapshot['final_services_retained_from_customer'] ?? 0), 2)),
            '{final_spare_parts_charges_retained_from_customer}' => $this->formatMoneyAmountForMessages(round((float) ($snapshot['final_spare_parts_retained_from_customer'] ?? 0), 2)),
            '{final_admin_commission_services_net_basis}' => $this->formatMoneyAmountForMessages(round((float) ($snapshot['final_admin_commission_services'] ?? 0), 2)),
            '{final_provider_earning_services_net_basis}' => $this->formatMoneyAmountForMessages(round((float) ($snapshot['final_provider_earning_services'] ?? 0), 2)),
            '{final_admin_commission_spare_parts_net_basis}' => $this->formatMoneyAmountForMessages(round((float) ($snapshot['final_admin_commission_spare_parts'] ?? 0), 2)),
            '{final_provider_earning_spare_parts_net_basis}' => $this->formatMoneyAmountForMessages(round((float) ($snapshot['final_provider_earning_spare_parts'] ?? 0), 2)),
            '{final_amount_retained_from_customer_after_refunds}' => $this->formatMoneyAmountForMessages($retained),
            '{disputed_total_provider_pays_company}' => $this->formatMoneyAmountForMessages($prRemit),
            '{disputed_total_company_pays_provider}' => $this->formatMoneyAmountForMessages($coPaysPr),
            '{disputed_refund_total}' => $this->formatMoneyAmountForMessages($total),
            '{disputed_refund_company}' => $this->formatMoneyAmountForMessages($company),
            '{disputed_refund_provider}' => $this->formatMoneyAmountForMessages($provider),
            '{disputed_customer_retained}' => $this->formatMoneyAmountForMessages($retained),
            '{disputed_provider_owes_company}' => $this->formatMoneyAmountForMessages($provOwes),
            '{disputed_company_owes_provider}' => $this->formatMoneyAmountForMessages($coOwesPr),
            '{disputed_company_cash_after_refund}' => $this->formatMoneyAmountForMessages($coCash),
            '{disputed_provider_cash_after_refund}' => $this->formatMoneyAmountForMessages($prCash),
            '{disputed_final_admin_commission}' => $this->formatMoneyAmountForMessages($finCo),
            '{disputed_final_provider_earning}' => $this->formatMoneyAmountForMessages($finPr),
            '{disputed_company_pays_provider_total}' => $this->formatMoneyAmountForMessages($coPaysPr),
            '{disputed_provider_remittance_total}' => $this->formatMoneyAmountForMessages($prRemit),
        ];
    }

    /**
     * Settlement snapshot, disputed snapshot, and other scenario fields merged into template vars.
     *
     * @return array<string, string>
     */
    protected function mergeBookingContextFinancialPlaceholders(Booking $booking): array
    {
        // settlement_snapshot / reopen_disputed_snapshot are model attributes (JSON casts), not relations.
        $booking->loadMissing(['booking_partial_payments']);
        $totalBill = (float) get_booking_total_amount($booking);
        $amountPaid = (float) get_booking_total_paid($booking);
        $due = max(0.0, round($totalBill - $amountPaid, 2));
        $stillDue = $due;
        if (function_exists('get_booking_admin_add_payment_remaining_amount')) {
            $stillDue = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        }

        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        $outcomeLabel = '—';
        if ($outcome !== '') {
            $opts = BookingFinancialSettlementService::outcomeOptions();
            $outcomeLabel = $opts[$outcome] ?? ucwords(str_replace('_', ' ', $outcome));
        }

        $settleRemarks = trim((string) ($booking->settlement_remarks ?? ''));
        $snap = is_array($booking->settlement_snapshot) ? $booking->settlement_snapshot : [];
        $hasScaled = (($snap['scaled_loss_mode'] ?? false) === true)
            || $outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        $dash = fn (float $v): string => abs($v) <= 0.009 ? '—' : $this->formatMoneyAmountForMessages($v);
        $num = static fn (mixed $v): float => round((float) ($v ?? 0), 2);

        $out = [
            '{booking_final_amount}' => function_exists('with_currency_symbol') ? with_currency_symbol($totalBill) : (string) $totalBill,
            '{settlement_outcome_label}' => $outcomeLabel,
            '{settlement_remarks}' => $settleRemarks !== '' ? $settleRemarks : '—',
            '{scaled_loss_total}' => $hasScaled ? $dash($num($snap['scaled_loss_amount'] ?? 0)) : '—',
            '{scaled_loss_company_share}' => $hasScaled ? $dash($num($snap['scaled_loss_company_share'] ?? 0)) : '—',
            '{scaled_loss_provider_share}' => $hasScaled ? $dash($num($snap['scaled_loss_provider_share'] ?? 0)) : '—',
            '{scaled_net_company_share}' => $hasScaled ? $dash($num($snap['scaled_net_company_share'] ?? 0)) : '—',
            '{scaled_net_provider_share}' => $hasScaled ? $dash($num($snap['scaled_net_provider_share'] ?? 0)) : '—',
            '{scaled_customer_paid_amount}' => $hasScaled ? $dash($num($snap['scaled_customer_paid_amount'] ?? $amountPaid)) : '—',
            '{booking_customer_still_due}' => $stillDue > 0.009
                ? (function_exists('with_currency_symbol') ? with_currency_symbol($stillDue) : (string) $stillDue)
                : '—',
            '{settlement_company_pays_provider}' => '—',
            '{settlement_provider_pays_company}' => '—',
        ];

        if (function_exists('get_booking_received_and_settlement')) {
            $settled = get_booking_received_and_settlement($booking);
            $payToProvider = round((float) ($settled['pay_to_provider'] ?? 0), 2);
            $providerOwesCompany = round((float) ($settled['provider_owes_company'] ?? 0), 2);
            $out['{settlement_company_pays_provider}'] = $dash($payToProvider);
            $out['{settlement_provider_pays_company}'] = $dash($providerOwesCompany);
        }

        if (function_exists('booking_customer_paid_split_by_receiver')) {
            $split = booking_customer_paid_split_by_receiver($booking);
            $out['{customer_payments_total}'] = $this->formatMoneyAmountForMessages((float) ($split['total'] ?? 0));
            $out['{customer_payments_to_company}'] = $this->formatMoneyAmountForMessages((float) ($split['company'] ?? 0));
            $out['{customer_payments_to_provider}'] = $this->formatMoneyAmountForMessages((float) ($split['provider'] ?? 0));
            $out['{customer_payments_unassigned}'] = $this->formatMoneyAmountForMessages((float) ($split['unassigned'] ?? 0));
        } else {
            $out['{customer_payments_total}'] = '—';
            $out['{customer_payments_to_company}'] = '—';
            $out['{customer_payments_to_provider}'] = '—';
            $out['{customer_payments_unassigned}'] = '—';
        }

        $status = (string) ($booking->booking_status ?? '');
        $out['{customer_refund_total}'] = '—';
        $out['{customer_refund_cap}'] = '—';
        if (function_exists('get_booking_refund_display_totals')
            && in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            $rt = get_booking_refund_display_totals($booking);
            $out['{customer_refund_total}'] = $this->formatMoneyAmountForMessages(round((float) ($rt['refunded_total'] ?? 0), 2));
            $out['{customer_refund_cap}'] = $this->formatMoneyAmountForMessages(round((float) ($rt['max_eligible'] ?? 0), 2));
        }

        $ds = $booking->reopen_disputed_snapshot;
        if (is_array($ds) && (($ds['type'] ?? '') === 'reopen_disputed_refund')) {
            $out = array_merge($out, $this->buildDisputedReopenRefundPlaceholderExtras($ds));
        }

        return $out;
    }

    protected function paymentPaidLabel(int $isPaid): string
    {
        return $isPaid ? translate('Paid') : translate('Unpaid');
    }

    /**
     * @return array{name: string, phone: string}
     */
    protected function servicemanDisplayPair(?Serviceman $serviceman): array
    {
        if (!$serviceman) {
            return ['name' => '—', 'phone' => '—'];
        }
        $serviceman->loadMissing('user');
        $user = $serviceman->user;
        $name = $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            : '';
        if ($name === '') {
            $name = '—';
        }

        return [
            'name' => $name,
            'phone' => $user?->phone ? (string) $user->phone : '—',
        ];
    }

    protected function verificationStateLabel(int $code): string
    {
        return match ($code) {
            1 => translate('Approved'),
            2 => translate('Denied'),
            3 => translate('Canceled'),
            default => translate('Pending'),
        };
    }

    protected function verificationActionLabel(string $action): string
    {
        return match ($action) {
            'approve' => translate('Approved'),
            'deny' => translate('Denied'),
            'cancel' => translate('Canceled'),
            default => ucfirst($action),
        };
    }

    /**
     * Notify customer, previous provider, and new provider after admin reassignment.
     * Skips parties when the corresponding template is empty or no phone is available.
     */
    public function sendBookingProviderChange(Booking $booking, ?Provider $previousProvider): void
    {
        $slotsForProviderChange = static function (?Provider $previousProvider): array {
            $slots = [
                ['key' => 'provider_change_customer', 'label' => 'provider change (customer)'],
                ['key' => 'provider_change_new_provider', 'label' => 'provider change (new provider)'],
            ];
            if ($previousProvider) {
                array_splice($slots, 1, 0, [['key' => 'provider_change_previous_provider', 'label' => 'provider change (previous provider)']]);
            }

            return $slots;
        };

        if (!$booking->provider_id) {
            $baseCtx = ['booking_id' => $booking->id, 'entity_id' => (string) $booking->id, 'skip_code' => 'skipped_no_provider_assigned'];
            $reason = 'skipped_no_provider_assigned';
            foreach ($slotsForProviderChange($previousProvider) as $slot) {
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['message_key' => $slot['key']]),
                    $slot['key'],
                    null,
                    $slot['label'],
                    'skipped',
                    $reason
                );
            }

            return;
        }

        $config = $this->getConfig();
        $prevId = $previousProvider?->id;
        if ($prevId !== null && (string) $prevId === (string) $booking->provider_id) {
            $baseCtx = ['booking_id' => $booking->id, 'entity_id' => (string) $booking->id, 'skip_code' => 'skipped_provider_unchanged'];
            $reason = 'skipped_provider_unchanged';
            foreach ($slotsForProviderChange($previousProvider) as $slot) {
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['message_key' => $slot['key']]),
                    $slot['key'],
                    null,
                    $slot['label'],
                    'skipped',
                    $reason
                );
            }

            return;
        }

        if (empty($config['enabled'])) {
            $slots = [
                ['key' => 'provider_change_customer', 'label' => 'provider change (customer)'],
            ];
            if ($previousProvider) {
                $slots[] = ['key' => 'provider_change_previous_provider', 'label' => 'provider change (previous provider)'];
            }
            $slots[] = ['key' => 'provider_change_new_provider', 'label' => 'provider change (new provider)'];
            $this->logAutomationMasterDisabled($booking, null, $slots);

            return;
        }

        $dedupKey = self::CACHE_PROVIDER_CHANGE_SENT_PREFIX . $booking->id . ':'
            . ($prevId ?? 'none') . '>' . $booking->provider_id;
        $lock = Cache::lock(self::CACHE_PROVIDER_CHANGE_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            $baseCtx = ['booking_id' => $booking->id, 'entity_id' => (string) $booking->id, 'skip_code' => 'skipped_lock_busy'];
            $slots = [
                ['key' => 'provider_change_customer', 'label' => 'provider change (customer)'],
                ['key' => 'provider_change_new_provider', 'label' => 'provider change (new provider)'],
            ];
            if ($previousProvider) {
                $slots[] = ['key' => 'provider_change_previous_provider', 'label' => 'provider change (previous provider)'];
            }
            foreach ($slots as $slot) {
                $this->writeAutomationLog(
                    array_merge($baseCtx, ['message_key' => $slot['key']]),
                    $slot['key'],
                    null,
                    $slot['label'],
                    'skipped',
                    'skipped_lock_busy'
                );
            }

            return;
        }
        try {
            try {
                if (Cache::has($dedupKey)) {
                    $baseCtx = ['booking_id' => $booking->id, 'entity_id' => (string) $booking->id, 'skip_code' => 'skipped_dedup_provider_change'];
                    foreach ($slotsForProviderChange($previousProvider) as $slot) {
                        $this->writeAutomationLog(
                            array_merge($baseCtx, ['message_key' => $slot['key']]),
                            $slot['key'],
                            null,
                            $slot['label'],
                            'skipped',
                            'skipped_dedup_provider_change'
                        );
                    }

                    return;
                }

                $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                $prevExtras = $this->previousProviderReplacementMap($previousProvider);
                $vars = array_merge($this->buildReplacements($booking, null), $prevExtras);

                $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
                $cOk = $this->trySendBookingMetaOnly(
                    $config,
                    'provider_change_customer',
                    $vars,
                    $customerPhone,
                    'provider change (customer)',
                    ['booking_id' => $booking->id]
                );

                $prevOk = false;
                if ($previousProvider) {
                    $oldPhone = $this->resolveProviderPhone($previousProvider, $config);
                    $prevOk = $this->trySendBookingMetaOnly(
                        $config,
                        'provider_change_previous_provider',
                        $vars,
                        $oldPhone,
                        'provider change (previous provider)',
                        ['booking_id' => $booking->id]
                    );
                }

                $newProviderPhone = $this->resolveProviderPhone($booking->provider, $config);
                $nOk = $this->trySendBookingMetaOnly(
                    $config,
                    'provider_change_new_provider',
                    $vars,
                    $newProviderPhone,
                    'provider change (new provider)',
                    ['booking_id' => $booking->id]
                );

                if ($cOk || $prevOk || $nOk) {
                    Cache::put($dedupKey, 1, now()->addSeconds(15));
                }
            } catch (\Throwable $e) {
                $this->logAutomationThrowableForSlots($booking, null, $slotsForProviderChange($previousProvider), $e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function previousProviderReplacementMap(?Provider $previousProvider): array
    {
        if (!$previousProvider) {
            return [
                '{previous_provider_name}' => '—',
                '{previous_provider_phone}' => '—',
            ];
        }

        $previousProvider->loadMissing(['owner']);
        $name = $previousProvider->company_name
            ?: ($previousProvider->contact_person_name ?? '—');

        return [
            '{previous_provider_name}' => $name,
            '{previous_provider_phone}' => $this->formatProviderPhoneDisplay($previousProvider),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $row = business_config(self::SETTINGS_KEY, self::SETTINGS_TYPE);
        $stored = is_array($row?->live_values) ? $row->live_values : [];
        $base = array_merge(
            [
                'enabled' => false,
                'default_phone_prefix' => '91',
                'apply_default_phone_prefix' => true,
            ],
            self::defaultTemplateBodies()
        );

        $merged = array_replace($base, $stored);
        // Stored JSON may use strings like "true"/"1"; normalize so automation is not stuck off.
        $merged['enabled'] = filter_var($merged['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $merged['default_phone_prefix'] = '91';
        $merged['apply_default_phone_prefix'] = true;

        foreach (self::statusTemplateSegmentKeys() as $segment) {
            $ck = 'booking_status_invoice_customer_' . $segment;
            $pk = 'booking_status_invoice_provider_' . $segment;
            if (!array_key_exists($ck, $merged)) {
                $merged[$ck] = false;
            }
            if (!array_key_exists($pk, $merged)) {
                $merged[$pk] = false;
            }
            $merged[$ck] = filter_var($merged[$ck], FILTER_VALIDATE_BOOLEAN);
            $merged[$pk] = filter_var($merged[$pk], FILTER_VALIDATE_BOOLEAN);
        }

        foreach (self::configurableMessageKeys() as $msgKey) {
            $wk = $msgKey . '_wa_tpl_id';
            $bp = $msgKey . '_wa_body_params';
            if (!array_key_exists($wk, $merged)) {
                $merged[$wk] = null;
            } elseif ($merged[$wk] !== null && $merged[$wk] !== '') {
                $merged[$wk] = (int) $merged[$wk];
            } else {
                $merged[$wk] = null;
            }
            if (!isset($merged[$bp]) || !is_array($merged[$bp])) {
                $merged[$bp] = [];
            }
            $hp = $msgKey . '_wa_header_params';
            if (!isset($merged[$hp]) || !is_array($merged[$hp])) {
                $merged[$hp] = [];
            }
            $ek = $msgKey . '_send_enabled';
            if (!array_key_exists($ek, $merged)) {
                $merged[$ek] = true;
            }
            $merged[$ek] = filter_var($merged[$ek], FILTER_VALIDATE_BOOLEAN);
        }

        return $merged;
    }

    /**
     * Per-template send switch (master {@see getConfig()['enabled']} still applies to automated booking flows).
     */
    public function isBookingTemplateMessageEnabled(array $config, string $messageKey): bool
    {
        $ek = $messageKey . '_send_enabled';

        return !array_key_exists($ek, $config) || filter_var($config[$ek], FILTER_VALIDATE_BOOLEAN);
    }

    protected function triggerAdminId(): ?int
    {
        $u = Auth::user();

        return $u ? (int) $u->getAuthIdentifier() : null;
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<int, mixed>  $paramKeys
     * @return array<int, string>
     */
    protected function buildWaBodyParameterValues(array $vars, array $paramKeys): array
    {
        $out = [];
        foreach ($paramKeys as $key) {
            $k = is_string($key) ? trim($key) : '';
            if ($k === '') {
                $out[] = '';
                continue;
            }

            // Accept both {token} (canonical) and [token] (legacy/admin-entered) mappings.
            if (isset($vars[$k])) {
                $out[] = (string) $vars[$k];
                continue;
            }

            // Some older saves (or manual edits) may store the bare token name without braces.
            // Treat "token_name" as "{token_name}" for lookup.
            if (preg_match('/^[a-z][a-z0-9_]*$/', $k)) {
                $brace = '{' . $k . '}';
                if (isset($vars[$brace])) {
                    $out[] = (string) $vars[$brace];
                    continue;
                }
                $square = '[' . $k . ']';
                if (isset($vars[$square])) {
                    $out[] = (string) $vars[$square];
                    continue;
                }
            }

            if (preg_match('/^\[(.+)\]$/', $k, $m)) {
                $alt = '{' . $m[1] . '}';
                $out[] = isset($vars[$alt]) ? (string) $vars[$alt] : '';
                continue;
            }

            if (preg_match('/^\{(.+)\}$/', $k, $m)) {
                $alt = '[' . $m[1] . ']';
                $out[] = isset($vars[$alt]) ? (string) $vars[$alt] : '';
                continue;
            }

            $out[] = '';
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $logCtx
     * @param  array<int, string>  $headerTextParameters
     * @param  array<string, mixed>|null  $bodyPlan
     * @param  array<string, mixed>|null  $headerTextPlan
     */
    protected function sendTemplateAndRecord(
        string $phone,
        WhatsAppMarketingTemplate $template,
        array $bodyParameters,
        string $logLabel,
        array $logCtx,
        string $messageStorageKey,
        array $headerTextParameters = [],
        ?array $bodyPlan = null,
        ?array $headerTextPlan = null,
        ?string $headerMediaUrl = null,
        ?string $headerMediaFormat = null,
        ?int $actingAdminUserId = null,
    ): bool {
        $err = null;
        $graphContext = null;
        $waId = $this->cloud->sendTemplateMessage(
            $phone,
            $template->name,
            $template->language,
            $bodyParameters,
            $err,
            $graphContext,
            $headerTextParameters,
            $headerMediaUrl,
            $headerMediaFormat,
            $bodyPlan,
            $headerTextPlan,
        );
        if ($err) {
            Log::warning('WhatsApp ' . $logLabel . ' failed (Meta template)', array_merge($logCtx, ['error' => $err]));
            $this->ledgerSendFailureDetail = $err;
            $detail = self::formatMetaFailureForAdmin($err) ?: $err;
            $this->writeAutomationLog(
                $logCtx,
                $messageStorageKey,
                $phone,
                $logLabel,
                'failed',
                $detail,
                $template
            );

            return false;
        }
        if (!$waId) {
            $this->ledgerSendFailureDetail = 'missing_wa_message_id';
            $this->writeAutomationLog(
                $logCtx,
                $messageStorageKey,
                $phone,
                $logLabel,
                'failed',
                'missing_wa_message_id',
                $template
            );

            return false;
        }

        $templateRow = ['components' => is_array($template->components) ? $template->components : []];
        $resolvedBodyPlan = $bodyPlan ?? WhatsAppCloudService::resolveBodyParameterPlanFromTemplate($templateRow);
        $resolvedHeaderPlan = $headerTextPlan ?? WhatsAppCloudService::resolveHeaderTextParameterPlanFromTemplate($templateRow);
        $customerPreview = WhatsAppCloudService::renderTemplateMessageAsSeenByCustomer(
            $templateRow,
            $headerTextParameters,
            $bodyParameters,
            $headerMediaUrl,
            $headerMediaFormat,
            $resolvedBodyPlan,
            $resolvedHeaderPlan
        );
        $titleLine = __('lang.whatsapp_template_conversation_title', [
            'name' => $template->name,
            'language' => $template->language,
        ]);
        if (trim($customerPreview) !== '') {
            $persistBody = $titleLine . "\n\n" . $customerPreview;
        } else {
            $persistBody = $titleLine;
            if ($bodyParameters !== []) {
                $persistBody .= "\n" . implode(' | ', $bodyParameters);
            }
        }

        $persisted = $this->tryPersistBookingOutbound($phone, $persistBody, $waId, 'TEXT', null, $actingAdminUserId);
        $this->writeAutomationLog(
            $logCtx,
            $messageStorageKey,
            $phone,
            $logLabel,
            'sent',
            null,
            $template,
            $waId,
            $persisted?->id
        );

        $this->ledgerSendFailureDetail = null;

        return true;
    }

    public function pullLedgerSendFailureDetail(): ?string
    {
        $detail = $this->ledgerSendFailureDetail;
        $this->ledgerSendFailureDetail = null;

        return $detail;
    }

    /**
     * Meta sometimes repeats "(#code)" in `message`; we add the code once ourselves.
     */
    private static function stripMetaErrorCodePrefix(string $text): string
    {
        return trim(preg_replace('/^(?:\s*\(#\d+\)\s*)+/u', '', trim($text)) ?? '');
    }

    /**
     * Turn raw Cloud API / local failure strings into text for admin toasts and JSON.
     */
    public static function formatMetaFailureForAdmin(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        $internalHints = [
            'missing_config' => 'WhatsApp_ledger_err_missing_cloud_config',
            'invalid_phone' => 'WhatsApp_ledger_err_invalid_phone_meta',
            'missing_wa_message_id' => 'WhatsApp_ledger_err_no_message_id',
        ];
        if (isset($internalHints[$raw])) {
            return __('lang.' . $internalHints[$raw]);
        }

        if (preg_match('/^status:\d+\s+body:(.+)$/s', $raw, $m)) {
            $data = json_decode(trim($m[1]), true);
            if (is_array($data) && isset($data['error']) && is_array($data['error'])) {
                $code = $data['error']['code'] ?? null;
                $msg = self::stripMetaErrorCodePrefix((string) ($data['error']['message'] ?? ''));
                $details = '';
                if (isset($data['error']['error_data']) && is_array($data['error']['error_data'])) {
                    $d = $data['error']['error_data']['details'] ?? null;
                    if ($d !== null && $d !== '') {
                        $details = is_string($d) ? $d : (is_scalar($d) ? (string) $d : json_encode($d));
                        $details = self::stripMetaErrorCodePrefix($details);
                    }
                }

                $codePart = ($code !== null && $code !== '') ? '(#' . $code . ')' : '';
                $is132001 = (int) $code === 132001
                    || str_contains(mb_strtolower($msg), 'template name does not exist')
                    || (str_contains(mb_strtolower($details), 'template name')
                        && str_contains(mb_strtolower($details), 'does not exist'));

                // Meta often puts the useful template name + locale only in `details`; concatenating
                // `message` + `details` reads as duplicated "template name … template name".
                if ($is132001 && $details !== '') {
                    $body = $details;
                } elseif ($msg !== '' && $details !== '' && $msg !== $details) {
                    $msgLow = mb_strtolower($msg);
                    $detLow = mb_strtolower($details);
                    if (str_contains($msgLow, $detLow) || str_contains($detLow, $msgLow)) {
                        $body = mb_strlen($details) >= mb_strlen($msg) ? $details : $msg;
                    } else {
                        $body = $msg . ' — ' . $details;
                    }
                } else {
                    $body = $msg !== '' ? $msg : $details;
                }

                $summary = trim($codePart . ($codePart !== '' && $body !== '' ? ' ' : '') . $body);
                if ($is132001) {
                    $summary .= ' ' . __('lang.WhatsApp_hint_template_language_or_name_mismatch');
                }

                return trim($summary);
            }
        }

        return $raw;
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<string, mixed>  $logCtx
     */
    protected function trySendWaTemplate(
        array $config,
        string $waStorageKey,
        array $vars,
        string $phone,
        string $logLabel,
        array $logCtx,
        ?string $headerMediaUrl = null,
        ?string $headerMediaFormat = null,
        ?int $actingAdminUserId = null,
    ): bool {
        $ctx = array_merge($logCtx, ['message_key' => $waStorageKey]);

        if (!$this->isBookingTemplateMessageEnabled($config, $waStorageKey)) {
            $this->ledgerSendFailureDetail = __('lang.WhatsApp_booking_message_slot_disabled');
            $this->writeAutomationLog(
                $ctx,
                $waStorageKey,
                $phone,
                $logLabel,
                'skipped',
                (string) $this->ledgerSendFailureDetail
            );

            return false;
        }
        $tplId = (int) ($config[$waStorageKey . '_wa_tpl_id'] ?? 0);
        if ($tplId <= 0) {
            $this->ledgerSendFailureDetail = __('lang.WhatsApp_ledger_err_not_configured');
            $this->writeAutomationLog(
                $ctx,
                $waStorageKey,
                $phone,
                $logLabel,
                'skipped',
                (string) $this->ledgerSendFailureDetail
            );

            return false;
        }
        $template = WhatsAppMarketingTemplate::query()->find($tplId);
        if (!$template || strtoupper((string) $template->status) !== 'APPROVED') {
            $this->ledgerSendFailureDetail = __('lang.WhatsApp_ledger_err_template_missing_or_not_approved');
            $this->writeAutomationLog(
                $ctx,
                $waStorageKey,
                $phone,
                $logLabel,
                'failed',
                (string) $this->ledgerSendFailureDetail,
                $template
            );

            return false;
        }
        $components = is_array($template->components) ? $template->components : [];
        $bodyPlan = WhatsAppCloudService::resolveBodyParameterPlanFromTemplate(['components' => $components]);
        $headerTextPlan = WhatsAppCloudService::resolveHeaderTextParameterPlanFromTemplate(['components' => $components]);

        $expectedBody = ($bodyPlan['format'] ?? '') === 'named'
            ? count($bodyPlan['named_param_names'] ?? [])
            : (int) ($bodyPlan['positional_count'] ?? 0);
        $expectedHeader = ($headerTextPlan['format'] ?? '') === 'named'
            ? count($headerTextPlan['named_param_names'] ?? [])
            : (int) ($headerTextPlan['positional_count'] ?? 0);

        $paramKeys = $config[$waStorageKey . '_wa_body_params'] ?? [];
        if (!is_array($paramKeys)) {
            $paramKeys = [];
        }
        $paramKeys = array_values($paramKeys);
        if (count($paramKeys) !== $expectedBody) {
            Log::warning('WhatsApp booking Meta template parameter count mismatch', [
                'message_key' => $waStorageKey,
                'template' => $template->name,
                'expected' => $expectedBody,
                'got' => count($paramKeys),
            ]);
            $this->ledgerSendFailureDetail = __('lang.WhatsApp_ledger_err_body_param_count', [
                'expected' => $expectedBody,
                'got' => count($paramKeys),
            ]);
            $this->writeAutomationLog(
                $ctx,
                $waStorageKey,
                $phone,
                $logLabel,
                'failed',
                (string) $this->ledgerSendFailureDetail,
                $template
            );

            return false;
        }
        $bodyParams = $this->buildWaBodyParameterValues($vars, $paramKeys);

        $headerKeys = $config[$waStorageKey . '_wa_header_params'] ?? [];
        if (!is_array($headerKeys)) {
            $headerKeys = [];
        }
        $headerKeys = array_values($headerKeys);
        // Media-only headers need no variables; old saves may still list header mappings—ignore them.
        if ($expectedHeader === 0 && count($headerKeys) > 0) {
            Log::info('WhatsApp template send: dropping unused header variable mappings (template has no text header placeholders)', [
                'message_key' => $waStorageKey,
                'template' => $template->name,
                'dropped' => count($headerKeys),
            ]);
            $headerKeys = [];
        }
        if (count($headerKeys) !== $expectedHeader) {
            Log::warning('WhatsApp booking Meta template header parameter count mismatch', [
                'message_key' => $waStorageKey,
                'template' => $template->name,
                'expected' => $expectedHeader,
                'got' => count($headerKeys),
            ]);
            $this->ledgerSendFailureDetail = __('lang.WhatsApp_ledger_err_header_param_count', [
                'expected' => $expectedHeader,
                'got' => count($headerKeys),
            ]);
            $this->writeAutomationLog(
                $ctx,
                $waStorageKey,
                $phone,
                $logLabel,
                'failed',
                (string) $this->ledgerSendFailureDetail,
                $template
            );

            return false;
        }
        $headerParams = $this->buildWaBodyParameterValues($vars, $headerKeys);

        return $this->sendTemplateAndRecord(
            $phone,
            $template,
            $bodyParams,
            $logLabel,
            $ctx,
            $waStorageKey,
            $headerParams,
            $bodyPlan,
            $headerTextPlan,
            $headerMediaUrl,
            $headerMediaFormat,
            $actingAdminUserId
        );
    }

    /**
     * Public HTTPS URL for a file on the public disk (required by Meta for template header media links).
     */
    protected function absolutePublicUrlForStoragePath(string $relativePath): string
    {
        $path = Storage::disk('public')->url($relativePath);
        $override = (string) config('whatsappmodule.public_media_base_url', '');
        if ($override !== '') {
            $urlPath = parse_url($path, PHP_URL_PATH) ?: '';
            $query = parse_url($path, PHP_URL_QUERY);
            $suffix = $urlPath . ($query !== null && $query !== '' ? '?' . $query : '');

            return $override . $suffix;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<string, mixed>  $logCtx
     */
    protected function trySendBookingMetaOnly(
        array $config,
        string $messageKey,
        array $vars,
        ?string $phone,
        string $logLabel,
        array $logCtx
    ): bool {
        if (!$phone) {
            $tid = (int) ($config[$messageKey . '_wa_tpl_id'] ?? 0);
            $this->writeAutomationLog(
                array_merge($logCtx, [
                    'message_key' => $messageKey,
                    'config_template_id' => $tid > 0 ? $tid : null,
                ]),
                $messageKey,
                null,
                $logLabel,
                'skipped',
                translate('WhatsApp_booking_automation_reason_no_phone')
            );

            return false;
        }

        return $this->trySendWaTemplate($config, $messageKey, $vars, $phone, $logLabel, $logCtx);
    }

    /**
     * Filled Meta template body for admin preview (provider/customer ledger payment messages).
     *
     * @param  array<string, string>  $vars
     * @return array{ok: bool, error: ?string, body: string}
     */
    public function previewMetaTemplateForLedgerMessage(string $messageKey, array $vars): array
    {
        $config = $this->getConfig();
        $tplId = (int) ($config[$messageKey . '_wa_tpl_id'] ?? 0);
        if ($tplId <= 0) {
            return ['ok' => false, 'error' => 'no_template', 'body' => ''];
        }
        $template = WhatsAppMarketingTemplate::query()->find($tplId);
        if (!$template || strtoupper((string) $template->status) !== 'APPROVED') {
            return ['ok' => false, 'error' => 'template_inactive', 'body' => ''];
        }
        $body = $this->synthesizeCaptionFromBookingTemplate($template, $config, $messageKey, $vars);

        return ['ok' => true, 'error' => null, 'body' => $body];
    }

    /**
     * @param  array<string, string>  $vars
     */
    public function sendConfiguredLedgerMeta(string $messageKey, array $vars, ?string $phoneRaw, ?int $actingAdminUserId = null): bool
    {
        $this->ledgerSendFailureDetail = null;
        $config = $this->getConfig();
        // Do not require the booking-automation master toggle: ledger/reminder sends are explicit admin actions
        // and use the same Meta templates + Cloud API as booking messages.
        $phone = $this->normalizePhone($phoneRaw, $config);
        if (!$phone) {
            $this->ledgerSendFailureDetail = __('lang.WhatsApp_phone_could_not_normalize', [
                'raw' => (string) ($phoneRaw ?? ''),
            ]);

            return false;
        }

        $actor = $actingAdminUserId;
        if ($actor === null && Auth::check()) {
            $actor = (int) Auth::id();
        }

        return $this->trySendWaTemplate(
            $config,
            $messageKey,
            $vars,
            $phone,
            'ledger ' . $messageKey,
            ['message_key' => $messageKey],
            null,
            null,
            $actor
        );
    }

    /**
     * @param  array<string, string>  $vars
     */
    public function sendConfiguredLedgerMetaToProvider(Provider $provider, string $messageKey, array $vars, ?int $actingAdminUserId = null): bool
    {
        $provider->loadMissing(['owner']);
        $config = $this->getConfig();
        $phone = $this->resolveProviderPhone($provider, $config);

        return $this->sendConfiguredLedgerMeta($messageKey, $vars, $phone, $actingAdminUserId);
    }

    public function templateBodyText(WhatsAppMarketingTemplate $template): string
    {
        $components = is_array($template->components) ? $template->components : [];
        foreach ($components as $c) {
            if (!is_array($c)) {
                continue;
            }
            if (strtoupper((string) ($c['type'] ?? '')) === 'BODY') {
                return (string) ($c['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param  array<int, string>  $positionalValues  Index 0 = {{1}}
     */
    protected function synthesizeFilledTemplateBody(string $bodyTemplate, array $positionalValues): string
    {
        $out = preg_replace_callback('/\{\{(\d+)\}\}/', function (array $m) use ($positionalValues) {
            $n = (int) $m[1];

            return $positionalValues[$n - 1] ?? '';
        }, $bodyTemplate);

        return is_string($out) ? $out : $bodyTemplate;
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function synthesizeCaptionFromBookingTemplate(
        WhatsAppMarketingTemplate $template,
        array $config,
        string $waStorageKey,
        array $vars
    ): string {
        $components = is_array($template->components) ? $template->components : [];
        $bodyPlan = WhatsAppCloudService::resolveBodyParameterPlanFromTemplate(['components' => $components]);
        $paramKeys = $config[$waStorageKey . '_wa_body_params'] ?? [];
        if (!is_array($paramKeys)) {
            $paramKeys = [];
        }
        $values = $this->buildWaBodyParameterValues($vars, array_values($paramKeys));
        $bodyText = $this->templateBodyText($template);

        if (($bodyPlan['format'] ?? '') === 'named' && !empty($bodyPlan['named_param_names'])) {
            foreach ($bodyPlan['named_param_names'] as $i => $name) {
                $bodyText = str_replace('{{' . $name . '}}', $values[$i] ?? '', $bodyText);
            }

            return $bodyText;
        }

        return $this->synthesizeFilledTemplateBody($bodyText, $values);
    }

    /**
     * Meta template binding for automated status messages (per-segment overrides general).
     *
     * @return array{params_storage_key: string, template_id: int}|null
     */
    protected function resolveWaBindingForStatus(array $config, string $party, string $segment): ?array
    {
        $suffix = $party === 'customer' ? 'customer' : 'provider';
        $specificKey = 'booking_status_' . $suffix . '_' . $segment;
        $generalKey = 'booking_status_' . $suffix;

        if (!empty($config[$specificKey . '_wa_tpl_id'])) {
            return [
                'params_storage_key' => $specificKey,
                'template_id' => (int) $config[$specificKey . '_wa_tpl_id'],
            ];
        }
        if (!empty($config[$generalKey . '_wa_tpl_id'])) {
            return [
                'params_storage_key' => $generalKey,
                'template_id' => (int) $config[$generalKey . '_wa_tpl_id'],
            ];
        }

        return null;
    }

    /**
     * Whether a Meta template is configured for this status segment (per-status row or general fallback).
     * Matches {@see self::resolveWaBindingForStatus()} so validation matches runtime sends.
     *
     * @param  array<string, mixed>  $config
     */
    public static function hasResolvedStatusWaTemplate(array $config, string $party, string $segment): bool
    {
        $suffix = $party === 'customer' ? 'customer' : 'provider';
        $specificKey = 'booking_status_' . $suffix . '_' . $segment;
        $generalKey = 'booking_status_' . $suffix;

        return ! empty($config[$specificKey . '_wa_tpl_id'] ?? null)
            || ! empty($config[$generalKey . '_wa_tpl_id'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $logCtx
     */
    protected function sendTextAndRecord(
        string $phone,
        string $body,
        string $logLabel,
        array $logCtx,
        ?string $messageStorageKey = null,
        ?WhatsAppMarketingTemplate $templateForLog = null,
    ): bool {
        $err = null;
        $waId = $this->cloud->sendText($phone, $body, $err);
        if ($err) {
            Log::warning('WhatsApp ' . $logLabel . ' failed', array_merge($logCtx, ['error' => $err]));
            if ($messageStorageKey !== null && $messageStorageKey !== '') {
                $this->writeAutomationLog(
                    $logCtx,
                    $messageStorageKey,
                    $phone,
                    $logLabel,
                    'failed',
                    self::formatMetaFailureForAdmin($err) ?: $err,
                    $templateForLog
                );
            }

            return false;
        }
        if (!$waId) {
            if ($messageStorageKey !== null && $messageStorageKey !== '') {
                $this->writeAutomationLog(
                    $logCtx,
                    $messageStorageKey,
                    $phone,
                    $logLabel,
                    'failed',
                    'missing_wa_message_id',
                    $templateForLog
                );
            }

            return false;
        }
        $persisted = $this->tryPersistBookingOutbound($phone, $body, $waId, 'TEXT', null, null);
        if ($messageStorageKey !== null && $messageStorageKey !== '') {
            $this->writeAutomationLog(
                $logCtx,
                $messageStorageKey,
                $phone,
                $logLabel,
                'sent',
                null,
                $templateForLog,
                $waId,
                $persisted?->id
            );
        }

        return true;
    }

    protected function tryPersistBookingOutbound(
        string $phone,
        string $body,
        string $waId,
        string $messageType = 'TEXT',
        ?string $mediaPath = null,
        ?int $actingAdminUserId = null,
    ): ?WhatsAppMessage {
        try {
            $actor = $actingAdminUserId ?? $this->triggerAdminId();

            return $this->messagePersistence->persistOutboundAutomation(
                $phone,
                $body,
                $waId,
                'Booking',
                $actor,
                $messageType,
                $mediaPath
            );
        } catch (\Throwable $e) {
            Log::warning('WhatsApp booking outbound persist failed', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function archiveInvoiceCopyForConversation(string $relativePath): ?string
    {
        if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
            return null;
        }
        $dir = 'whatsapp-conversation-media/booking-invoices';
        Storage::disk('public')->makeDirectory($dir);
        $dest = $dir . '/' . str_replace('.', '', uniqid('inv_', true)) . '.pdf';
        if (!Storage::disk('public')->copy($relativePath, $dest)) {
            return null;
        }

        return $dest;
    }

    /**
     * @param  array<string, mixed>  $config  Unused; prefix is read from the same business settings as Cloud sends.
     */
    public function normalizePhone(?string $phone, array $config): ?string
    {
        if (!$phone) {
            return null;
        }

        // Use Social Inbox → Booking templates phone rules (default prefix, etc.), not only services.whatsapp_cloud.
        return $this->cloud->normalizeRecipientPhone($phone, [
            'apply_default_phone_prefix' => (bool) ($config['apply_default_phone_prefix'] ?? true),
            'default_phone_prefix' => (string) ($config['default_phone_prefix'] ?? '91'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function buildReplacements(Booking $booking, ?string $previousBookingStatus): array
    {
        $booking->loadMissing([
            'detail',
            'serviceman.user',
            'latestParentCancellationStatusHistory.cancellationReason',
            'latestParentHoldStatusHistory.holdReopenReason',
        ]);

        $serviceNames = $booking->detail->pluck('service_name')->filter()->unique()->implode(', ');
        if ($serviceNames === '') {
            $serviceNames = '—';
        }

        $customer = $booking->customer;
        $customerName = $customer
            ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            : '';
        if ($customerName === '') {
            $customerName = '—';
        }

        $addr = $booking->service_address;
        $customerAddress = '—';
        if ($addr) {
            $parts = array_filter([
                $addr->address ?? null,
                $addr->street ?? null,
                $addr->city ?? null,
                $addr->zip_code ?? null,
                $addr->country ?? null,
            ]);
            $customerAddress = $parts !== [] ? implode(', ', $parts) : '—';
        }

        $provider = $booking->provider;
        $providerName = $provider?->company_name
            ?: ($provider?->contact_person_name ?? '—');

        $totalBill = get_booking_total_amount($booking);
        $amountPaid = get_booking_total_paid($booking);
        $due = max(0, round($totalBill - $amountPaid, 2));

        $schedule = $this->formatServiceDateTimeForMessages(
            $booking->service_schedule ? (string) $booking->service_schedule : null
        );

        $serviceWhere = $this->serviceWhereLabel($booking, $customerAddress, $provider);

        $statusKey = (string) ($booking->booking_status ?? '');
        $statusLabel = $statusKey !== '' ? ucwords(str_replace('_', ' ', $statusKey)) : '—';

        $prevKey = $previousBookingStatus ?? '';
        $prevLabel = $prevKey !== '' ? ucwords(str_replace('_', ' ', $prevKey)) : '—';

        $sm = $this->servicemanDisplayPair($booking->serviceman);

        // Like hold, cancellation can be recorded on repeat-linked history rows; mirror booking details UI.
        $cancelHist = BookingStatusHistory::query()
            ->where('booking_id', $booking->id)
            ->whereIn('booking_status', ['canceled', 'cancelled'])
            ->with('cancellationReason')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $cancellationReasonText = trim((string) ($cancelHist?->cancellationReason?->name ?? ''));
        if ($cancellationReasonText === '') {
            $cancellationReasonText = '—';
        }

        // IMPORTANT: In repeat series, "put on hold" can be recorded against a repeat-linked history row.
        // The booking details UI shows the latest hold reason across all histories; mirror that here.
        $holdHist = BookingStatusHistory::query()
            ->where('booking_id', $booking->id)
            ->where('booking_status', 'on_hold')
            ->with('holdReopenReason')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $onHoldReasonText = trim((string) ($holdHist?->holdReopenReason?->name ?? ''));
        $onHoldRemarksText = trim((string) ($holdHist?->status_change_remarks ?? ''));
        // Some flows capture remarks but not a structured reason; ensure the message still shows something meaningful.
        if ($onHoldReasonText === '' && $onHoldRemarksText !== '') {
            $onHoldReasonText = $onHoldRemarksText;
        }
        if ($onHoldReasonText === '') {
            $onHoldReasonText = '—';
        }
        if ($onHoldRemarksText === '') {
            $onHoldRemarksText = '—';
        }

        $reopenEv = $booking->reopenFromCompletedDisplayEvent();
        $reopenFromCompletedText = '—';
        if ($reopenEv) {
            $reopenEv->loadMissing('holdReopenReason');
            $reopenFromCompletedText = trim((string) ($reopenEv->holdReopenReason?->name ?? ''));
            if ($reopenFromCompletedText === '') {
                $reopenFromCompletedText = trim((string) ($reopenEv->complaint_notes ?? ''));
            }
            if ($reopenFromCompletedText === '') {
                $reopenFromCompletedText = '—';
            }
        }

        $base = [
            '{service_name}' => $serviceNames,
            '{customer_address}' => $customerAddress,
            '{customer_name}' => $customerName,
            '{customer_phone}' => $customer?->phone ? (string) $customer->phone : '—',
            '{provider_name}' => $providerName,
            '{provider_phone}' => $this->formatProviderPhoneDisplay($provider),
            '{booking_id}' => (string) ($booking->readable_id ?? $booking->id),
            '{booking_datetime}' => $schedule,
            '{service_where}' => $serviceWhere,
            '{total_bill}' => function_exists('with_currency_symbol') ? with_currency_symbol($totalBill) : (string) $totalBill,
            '{amount_paid}' => function_exists('with_currency_symbol') ? with_currency_symbol($amountPaid) : (string) $amountPaid,
            '{due_amount}' => function_exists('with_currency_symbol') ? with_currency_symbol($due) : (string) $due,
            '{booking_status}' => $statusLabel,
            '{previous_booking_status}' => $prevLabel,
            '{previous_service_schedule}' => '—',
            '{payment_status}' => $this->paymentPaidLabel((int) ($booking->is_paid ?? 0)),
            '{previous_payment_status}' => '—',
            '{amount_added}' => '—',
            '{payment_received_by}' => '—',
            '{payment_date}' => '—',
            '{payment_method}' => '—',
            '{payment_reference}' => '—',
            '{serviceman_name}' => $sm['name'],
            '{serviceman_phone}' => $sm['phone'],
            '{previous_serviceman_name}' => '—',
            '{previous_serviceman_phone}' => '—',
            '{verification_status}' => $this->verificationStateLabel((int) ($booking->is_verified ?? 0)),
            '{previous_verification_status}' => '—',
            '{verification_action}' => '—',
            '{reopen_resolve_remarks}' => (($t = trim((string) ($booking->reopen_resolve_remarks ?? ''))) !== '' ? $t : '—'),
            '{booking_cancellation_reason}' => $cancellationReasonText,
            '{on_hold_reason}' => $onHoldReasonText,
            '{on_hold_reason_remarks}' => $onHoldRemarksText,
            '{reopen_from_completed_reason}' => $reopenFromCompletedText,
            '{customer_refund_before_this}' => '—',
        ];

        $acData = self::buildAdditionalChargePlaceholderData();
        $acReplacements = [];
        foreach (enrich_booking_additional_charges_breakdown_for_display($booking) as $row) {
            $tid = (string) ($row['id'] ?? '');
            if ($tid === '') {
                continue;
            }
            $tokenKey = $acData['id_to_key'][$tid] ?? self::orphanAdditionalChargeTokenKey($tid, (string) ($row['name'] ?? ''));
            $amt = round((float) ($row['amount'] ?? 0), 2);
            $acReplacements[$tokenKey] = function_exists('with_currency_symbol') ? with_currency_symbol($amt) : (string) $amt;
        }

        return array_merge($base, $acReplacements, $this->mergeBookingContextFinancialPlaceholders($booking));
    }

    protected function resolveStatusTemplateSegment(string $previousBookingStatus, string $newStatus, ?Booking $booking = null): string
    {
        $prev = strtolower(trim($previousBookingStatus));
        $new = strtolower(trim($newStatus));
        if ($prev === 'completed' && in_array($new, ['pending', 'accepted'], true)) {
            return 'reopened';
        }

        if ($new === 'completed' && $booking !== null) {
            $booking->loadMissing([]);
            if (trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                return 'loss_making';
            }
        }

        $allowed = array_column(BOOKING_STATUSES, 'key');
        if (in_array($new, $allowed, true)) {
            return $new;
        }

        return $new !== '' ? $new : 'pending';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function statusInvoiceEnabled(array $config, string $party, string $segment): bool
    {
        $key = $party === 'customer'
            ? 'booking_status_invoice_customer_' . $segment
            : 'booking_status_invoice_provider_' . $segment;

        return !empty($config[$key]);
    }

    protected function truncateWhatsAppCaption(string $body): string
    {
        if (strlen($body) <= self::WHATSAPP_DOC_CAPTION_MAX) {
            return $body;
        }

        return substr($body, 0, self::WHATSAPP_DOC_CAPTION_MAX - 1) . '…';
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function deliverStatusTemplateMessage(
        array $config,
        string $segment,
        string $party,
        array $vars,
        ?string $phone,
        Booking $booking,
        ?BookingRepeat $repeat,
        string $logContext,
        string $entityId
    ): bool {
        $suffix = $party === 'customer' ? 'customer' : 'provider';
        $fallbackKey = 'booking_status_' . $suffix . '_' . $segment;
        $ctx = [
            'booking_id' => $booking->id,
            'booking_repeat_id' => $repeat?->id,
            'entity_id' => $entityId,
            'segment' => $segment,
            'party' => $party,
        ];
        $logLabel = $logContext . ' (' . $party . ')';

        $waBindingPreview = $this->resolveWaBindingForStatus($config, $party, $segment);
        if (!$phone) {
            $tid = $waBindingPreview ? (int) ($waBindingPreview['template_id'] ?? 0) : 0;
            $this->writeAutomationLog(
                array_merge($ctx, [
                    'message_key' => $fallbackKey,
                    'config_template_id' => $tid > 0 ? $tid : null,
                ]),
                $fallbackKey,
                null,
                $logLabel,
                'skipped',
                translate('WhatsApp_booking_automation_reason_no_phone')
            );

            return false;
        }

        $waBinding = $waBindingPreview;
        if ($waBinding === null) {
            $this->writeAutomationLog(
                array_merge($ctx, ['message_key' => $fallbackKey]),
                $fallbackKey,
                $phone,
                $logLabel,
                'skipped',
                (string) __('lang.WhatsApp_booking_skip_no_template_for_status')
            );

            return false;
        }

        $storageKey = $waBinding['params_storage_key'];
        if (!$this->isBookingTemplateMessageEnabled($config, $storageKey)) {
            $this->writeAutomationLog(
                array_merge($ctx, ['message_key' => $storageKey]),
                $storageKey,
                $phone,
                $logLabel,
                'skipped',
                (string) __('lang.WhatsApp_booking_message_slot_disabled')
            );

            return false;
        }

        $template = WhatsAppMarketingTemplate::query()->find($waBinding['template_id']);
        if (!$template) {
            $this->writeAutomationLog(
                array_merge($ctx, ['message_key' => $storageKey]),
                $storageKey,
                $phone,
                $logLabel,
                'failed',
                'template_row_missing',
                null
            );

            return false;
        }
        $attachInvoice = $this->statusInvoiceEnabled($config, $party, $segment);
        $synthesized = $this->synthesizeCaptionFromBookingTemplate($template, $config, $storageKey, $vars);

        $relativePath = null;
        if ($attachInvoice) {
            $relativePath = $this->writeBookingInvoiceToPublicDisk($booking, $repeat);
        }

        $components = is_array($template->components) ? $template->components : [];
        $headerKind = WhatsAppCloudService::headerMediaFormatFromComponents($components);

        // Meta template with DOCUMENT header: send the approved template with the invoice PDF URL in the header
        // (body variables still use your mapping). Meta must be able to HTTP(S)-fetch that URL (not localhost/private IP).
        if ($relativePath && $headerKind === 'DOCUMENT') {
            $invoiceUrl = $this->absolutePublicUrlForStoragePath($relativePath);
            $sent = false;
            if (WhatsAppCloudService::isTemplateHeaderMediaLinkLikelyFetchableByMeta($invoiceUrl)) {
                $sent = $this->trySendWaTemplate(
                    $config,
                    $storageKey,
                    $vars,
                    $phone,
                    $logLabel,
                    $ctx,
                    $invoiceUrl,
                    'DOCUMENT'
                );
                if ($sent) {
                    Storage::disk('public')->delete($relativePath);
                    $invoiceParent = dirname($relativePath);
                    if (str_starts_with(basename($invoiceParent), 'wa_')) {
                        Storage::disk('public')->deleteDirectory($invoiceParent);
                    }

                    return true;
                }

                Log::warning('WhatsApp booking template+invoice (document header) send failed; trying direct PDF send', [
                    'entity_id' => $entityId,
                    'template' => $template->name,
                ]);
            } else {
                Log::info('WhatsApp booking: skipping template DOCUMENT header for invoice (media URL not fetchable by Meta); sending PDF via direct upload', [
                    'entity_id' => $entityId,
                    'template' => $template->name,
                    'invoice_url_host' => parse_url($invoiceUrl, PHP_URL_HOST),
                ]);
            }
        }

        if ($relativePath) {
            $caption = $this->truncateWhatsAppCaption($synthesized);
            $err = null;
            $waId = $this->cloud->sendOutbound($phone, $caption, $relativePath, $err);
            $archivedPath = null;
            if ($waId) {
                $archivedPath = $this->archiveInvoiceCopyForConversation($relativePath);
            }
            Storage::disk('public')->delete($relativePath);
            $invoiceParent = dirname($relativePath);
            if (str_starts_with(basename($invoiceParent), 'wa_')) {
                Storage::disk('public')->deleteDirectory($invoiceParent);
            }
            if ($waId) {
                $persistType = $archivedPath ? 'DOCUMENT' : 'TEXT';
                $persisted = $this->tryPersistBookingOutbound(
                    $phone,
                    $synthesized,
                    $waId,
                    $persistType,
                    $archivedPath,
                    null
                );
                $this->writeAutomationLog(
                    $ctx,
                    $storageKey,
                    $phone,
                    $logLabel,
                    'sent',
                    null,
                    $template,
                    $waId,
                    $persisted?->id
                );

                return true;
            }

            Log::warning('WhatsApp ' . $logContext . ' (' . $party . ') document send failed, retrying plain text', [
                'entity_id' => $entityId,
                'error' => $err,
            ]);

            return $this->sendTextAndRecord(
                $phone,
                $synthesized,
                $logLabel,
                $ctx,
                $storageKey,
                $template
            );
        }

        if ($attachInvoice && !$relativePath) {
            Log::warning('WhatsApp ' . $logContext . ' (' . $party . '): invoice PDF not generated', ['entity_id' => $entityId]);
            $this->writeAutomationLog(
                $ctx,
                $storageKey,
                $phone,
                $logLabel,
                'failed',
                'invoice_pdf_not_generated',
                $template
            );

            return false;
        }

        return $this->trySendWaTemplate(
            $config,
            $storageKey,
            $vars,
            $phone,
            $logLabel,
            $ctx
        );
    }

    protected function writeBookingInvoiceToPublicDisk(Booking $booking, ?BookingRepeat $repeat): ?string
    {
        try {
            if (!class_exists(Pdf::class)) {
                return null;
            }

            $dir = 'whatsapp-booking-invoices';
            Storage::disk('public')->makeDirectory($dir);

            if ($repeat) {
                $row = BookingRepeat::query()
                    ->with([
                        'detail.service' => static fn ($q) => $q->withTrashed(),
                        'booking.extra_services',
                        'provider',
                        'serviceman',
                    ])
                    ->find($repeat->id);
                if (!$row || !$row->booking) {
                    return null;
                }
                $row->booking->loadMissing(['customer', 'service_address']);
                $row->booking->service_address = $row->booking->service_address_location != null
                    ? json_decode($row->booking->service_address_location)
                    : $row->booking->service_address;
                $pdf = Pdf::loadView('bookingmodule::admin.booking.fullbooking-single-invoice', ['booking' => $row]);
            } else {
                $b = Booking::query()
                    ->with([
                        'detail.service' => static fn ($q) => $q->withTrashed(),
                        'customer',
                        'provider',
                        'serviceman',
                        'status_histories.user',
                        'extra_services',
                        'booking_partial_payments',
                    ])
                    ->find($booking->id);
                if (!$b) {
                    return null;
                }
                $b->service_address = $b->service_address_location != null
                    ? json_decode($b->service_address_location)
                    : $b->service_address;
                $sub_total = $b->detail->sum(fn ($item) => $item->service_cost * $item->quantity);
                $extraServicesTotal = ($b->extra_services ?? collect())->sum('total');
                $pdf = Pdf::loadView('bookingmodule::admin.booking.invoice', [
                    'booking' => $b,
                    'sub_total' => $sub_total,
                    'extraServicesTotal' => $extraServicesTotal,
                ]);
            }

            $bookingRef = $repeat
                ? (string) ($repeat->readable_id ?? $booking->readable_id ?? $booking->id)
                : (string) ($booking->readable_id ?? $booking->id);
            $bookingRef = trim($bookingRef) !== '' ? trim($bookingRef) : (string) $booking->id;
            $safeRef = trim(preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $bookingRef), '-');
            if ($safeRef === '') {
                $safeRef = (string) $booking->id;
            }

            $uniqueDir = $dir . '/' . str_replace('.', '', uniqid('wa_', true));
            Storage::disk('public')->makeDirectory($uniqueDir);

            $basename = 'Invoice - (' . $safeRef . ').pdf';
            $name = $uniqueDir . '/' . $basename;
            Storage::disk('public')->put($name, $pdf->output());

            return $name;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp booking invoice PDF failed', [
                'booking_id' => $booking->id,
                'repeat_id' => $repeat?->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Raw + normalized digits (no +) used for provider ledger WhatsApp — same logic as {@see resolveProviderPhone()}.
     *
     * @return array{raw: string, normalized_digits: ?string}
     */
    public function providerLedgerRecipientPhoneDetail(Provider $provider): array
    {
        $provider->loadMissing(['owner']);
        $raw = $provider->owner?->phone
            ?: $provider->contact_person_phone
            ?: $provider->company_phone;
        $rawStr = $raw !== null && $raw !== '' ? trim((string) $raw) : '';
        $config = $this->getConfig();
        $normalized = $rawStr !== '' ? $this->normalizePhone($rawStr, $config) : null;

        return [
            'raw' => $rawStr,
            'normalized_digits' => ($normalized !== null && $normalized !== '') ? $normalized : null,
        ];
    }

    /**
     * Human-readable line for admin error messages (API "to" is the +normalized value when present).
     */
    public function providerLedgerRecipientLabelForErrors(Provider $provider): string
    {
        return $this->formatProviderLedgerRecipientErrorLabel($this->providerLedgerRecipientPhoneDetail($provider));
    }

    /**
     * @param  array{raw: string, normalized_digits: ?string}  $detail
     */
    protected function formatProviderLedgerRecipientErrorLabel(array $detail): string
    {
        $raw = (string) ($detail['raw'] ?? '');
        $norm = $detail['normalized_digits'] ?? null;
        if (is_string($norm) && $norm !== '') {
            $line = $norm;
            $rawDigits = preg_replace('/\D+/', '', $raw) ?? '';
            if ($raw !== '' && $rawDigits !== $norm) {
                $line .= ' (' . __('lang.WhatsApp_recipient_profile_source_note', ['raw' => $raw]) . ')';
            }

            return $line;
        }
        if ($raw !== '') {
            return __('lang.WhatsApp_phone_could_not_normalize', ['raw' => $raw]);
        }

        return __('lang.WhatsApp_no_phone_for_reminder_recipient');
    }

    /**
     * @return array{raw: string, normalized_digits: ?string}
     */
    public function customerLedgerRecipientPhoneDetail(User $customer): array
    {
        $rawStr = trim((string) ($customer->phone ?? ''));
        $config = $this->getConfig();
        $normalized = $rawStr !== '' ? $this->normalizePhone($rawStr, $config) : null;

        return [
            'raw' => $rawStr,
            'normalized_digits' => ($normalized !== null && $normalized !== '') ? $normalized : null,
        ];
    }

    public function customerLedgerRecipientLabelForErrors(User $customer): string
    {
        return $this->formatProviderLedgerRecipientErrorLabel($this->customerLedgerRecipientPhoneDetail($customer));
    }

    /**
     * Raw phone to use for WhatsApp to a provider (Cloud API "to" field).
     * Prefer provider-admin owner phone so admin preview and send target the same number as the login account;
     * company_phone is often a desk/main line and may differ or not be on WhatsApp.
     *
     * @param  array<string, mixed>  $config
     */
    protected function resolveProviderPhone(?Provider $provider, array $config): ?string
    {
        if (!$provider) {
            return null;
        }
        $provider->loadMissing(['owner']);
        $raw = $provider->owner?->phone
            ?: $provider->contact_person_phone
            ?: $provider->company_phone;

        return $this->normalizePhone($raw, $config);
    }

    protected function formatProviderPhoneDisplay(?Provider $provider): string
    {
        if (!$provider) {
            return '—';
        }
        $raw = $provider->company_phone
            ?: $provider->contact_person_phone
            ?: $provider->owner?->phone;

        return $raw ? (string) $raw : '—';
    }

    protected function serviceWhereLabel(Booking $booking, string $customerAddress, ?Provider $provider): string
    {
        $loc = $booking->service_location ?? 'customer';
        if ($loc === 'provider') {
            $a = $provider?->company_address;

            return $a ? (string) $a : translate('Service_at_provider_address');
        }

        return $customerAddress !== '—' ? $customerAddress : translate('Service_at_customer_address');
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function interpolate(string $template, array $vars): string
    {
        // Backward compatible: older/admin-entered templates sometimes use [token] instead of {token}.
        // Our canonical tokens are {token}. Support both syntaxes at render time.
        $expanded = $vars;
        foreach ($vars as $token => $value) {
            if (preg_match('/^\{(.+)\}$/', $token, $m)) {
                $expanded['[' . $m[1] . ']'] = $value;
            }
        }

        return strtr($template, $expanded);
    }
}
