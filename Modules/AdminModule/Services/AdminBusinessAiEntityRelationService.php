<?php

namespace Modules\AdminModule\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class AdminBusinessAiEntityRelationService
{
    public function __construct(
        protected AdminBusinessAiWhatsAppInsightService $whatsAppInsights,
    ) {}

    /**
     * Resolve cross-entity links: lead ↔ booking ↔ customer ↔ provider ↔ WhatsApp ↔ outbound enquiry.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function resolve(array $args): array
    {
        $phone = ! empty($args['phone']) ? $this->normalizePhone((string) $args['phone']) : null;
        $leadId = ! empty($args['lead_id']) ? (int) $args['lead_id'] : null;
        $bookingId = ! empty($args['booking_id']) ? (string) $args['booking_id'] : null;
        $readableId = ! empty($args['readable_id']) ? trim((string) $args['readable_id']) : null;
        $customerId = ! empty($args['customer_id']) ? (string) $args['customer_id'] : null;
        $providerId = ! empty($args['provider_id']) ? (string) $args['provider_id'] : null;

        if ($readableId && ! $bookingId) {
            $bookingId = (string) (Booking::query()->where('readable_id', $readableId)->value('id') ?? '');
            if ($bookingId === '') {
                $bookingId = null;
            }
        }

        $booking = $bookingId
            ? Booking::query()->with(['customer', 'provider'])->find($bookingId)
            : null;

        if ($booking) {
            $phone = $phone ?? $this->normalizePhone($booking->customer?->phone);
            $customerId = $customerId ?? (string) ($booking->customer_id ?? '');
            $providerId = $providerId ?? (string) ($booking->provider_id ?? '');
            $leadId = $leadId ?? (int) ($booking->lead_id ?? 0) ?: null;
        }

        $customer = $customerId
            ? User::query()->where('user_type', 'customer')->find($customerId)
            : ($phone ? $this->findCustomerByPhone($phone) : null);

        if ($customer && ! $phone) {
            $phone = $this->normalizePhone($customer->phone);
        }

        $provider = $providerId
            ? Provider::query()->find($providerId)
            : null;

        $leads = collect();
        if ($leadId) {
            $one = Lead::query()->find($leadId);
            if ($one) {
                $leads = collect([$one]);
            }
        }
        if ($phone) {
            $leads = $leads->merge(
                Lead::query()
                    ->where('phone_number', 'like', '%'.$phone.'%')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
            )->unique('id')->values();
        }

        $bookings = collect();
        if ($booking) {
            $bookings = collect([$booking]);
        }
        if ($customer) {
            $bookings = $bookings->merge(
                Booking::query()
                    ->where('customer_id', $customer->id)
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get(['id', 'readable_id', 'booking_status', 'lead_id', 'provider_id', 'total_booking_amount', 'created_at'])
            )->unique('id')->values();
        }
        if ($leads->isNotEmpty()) {
            $bookings = $bookings->merge(
                Booking::query()
                    ->whereIn('lead_id', $leads->pluck('id')->all())
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get(['id', 'readable_id', 'booking_status', 'lead_id', 'provider_id', 'total_booking_amount', 'created_at'])
            )->unique('id')->values();
        }

        $whatsapp = $phone
            ? $this->whatsAppInsights->queryConversations(['search' => $phone, 'limit' => 3])
            : ['ok' => true, 'conversations' => []];

        $waUser = $phone ? WhatsAppUser::query()->where('phone', 'like', '%'.$phone.'%')->first() : null;
        $chatHandlerLabel = null;
        if ($waUser) {
            if (Lead::assigneeIsHuman($waUser->handled_by)) {
                $handler = User::query()->find((string) $waUser->handled_by, ['first_name', 'last_name', 'email']);
                $chatHandlerLabel = $handler
                    ? (trim($handler->first_name.' '.$handler->last_name) ?: $handler->email)
                    : 'Agent';
            } else {
                $chatHandlerLabel = $waUser->handled_by === Lead::HANDLED_BY_AI ? 'AI' : 'Unassigned';
            }
        }

        $outbound = $phone
            ? LeadOutboundEnquiry::query()
                ->where('phone_number', 'like', '%'.$phone.'%')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'customer_name', 'phone_number', 'status', 'contacted_through', 'handled_by', 'contacted_at'])
            : collect();

        $providerLeads = $provider
            ? Lead::query()
                ->where('phone_number', 'like', '%'.$this->normalizePhone($provider->company_phone).'%')
                ->where('lead_type', Lead::TYPE_PROVIDER)
                ->limit(3)
                ->get(['id', 'name', 'lead_type', 'handled_by'])
            : collect();

        return [
            'ok' => true,
            'lookup' => [
                'phone_normalized' => $phone,
                'lead_id' => $leadId,
                'booking_id' => $booking?->id,
                'readable_id' => $booking?->readable_id ?? $readableId,
                'customer_id' => $customer?->id,
                'provider_id' => $provider?->id,
            ],
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => trim($customer->first_name.' '.$customer->last_name),
                'phone' => $customer->phone,
                'email' => $customer->email,
            ] : null,
            'provider' => $provider ? [
                'id' => $provider->id,
                'company' => $provider->company_name,
                'phone' => $provider->company_phone,
            ] : null,
            'crm_leads' => $leads->map(fn (Lead $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'phone' => $l->phone_number,
                'type' => $l->lead_type,
                'is_customer_lead' => $l->lead_type === Lead::TYPE_CUSTOMER,
                'lead_handler_id' => Lead::assigneeIsHuman($l->handled_by) ? (string) $l->handled_by : null,
                'lead_handler' => $l->handled_by,
                'next_followup_at' => $l->next_followup_at?->toIso8601String(),
                'has_system_booking' => Booking::query()->where('lead_id', $l->id)->exists(),
            ])->values()->all(),
            'provider_crm_leads' => $providerLeads->map(fn (Lead $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'handled_by' => $l->handled_by,
            ])->values()->all(),
            'bookings' => $bookings->map(fn (Booking $b) => [
                'id' => $b->id,
                'readable_id' => $b->readable_id,
                'status' => $b->booking_status,
                'lead_id' => $b->lead_id,
                'amount' => (float) ($b->total_booking_amount ?? 0),
                'created_at' => $b->created_at?->toIso8601String(),
            ])->values()->all(),
            'whatsapp' => $whatsapp['conversations'] ?? [],
            'whatsapp_chat_assignment' => $waUser ? [
                'phone' => $waUser->phone,
                'display_name' => $waUser->name,
                'chat_handler_id' => Lead::assigneeIsHuman($waUser->handled_by) ? (string) $waUser->handled_by : null,
                'chat_handler' => $chatHandlerLabel,
                'human_support_pending' => $waUser->human_support_requested_at !== null,
            ] : null,
            'whatsapp_chat_handler_note' => 'chat_handler = WhatsApp inbox assignee; lead_handler = CRM lead handled_by — they can differ.',
            'outbound_enquiries' => $outbound->map(fn (LeadOutboundEnquiry $e) => [
                'id' => $e->id,
                'name' => $e->customer_name,
                'phone' => $e->phone_number,
                'status' => $e->status,
                'contacted_through' => $e->contacted_through,
                'handled_by' => $e->handled_by,
                'contacted_at' => $e->contacted_at?->toIso8601String(),
            ])->values()->all(),
            'relation_map' => [
                'lead_to_booking' => 'bookings.lead_id → leads.id',
                'booking_to_customer' => 'bookings.customer_id → users.id (customer)',
                'booking_to_provider' => 'bookings.provider_id → providers.id',
                'phone_to_lead' => 'normalized last-10 digits match leads.phone_number',
                'phone_to_whatsapp' => 'WhatsAppUser phone matches normalized customer/lead phone',
                'phone_to_customer' => 'users.phone matches normalized phone',
            ],
        ];
    }

    private function findCustomerByPhone(string $phone): ?User
    {
        return User::query()
            ->where('user_type', 'customer')
            ->where('phone', 'like', '%'.$phone.'%')
            ->first();
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }
}
