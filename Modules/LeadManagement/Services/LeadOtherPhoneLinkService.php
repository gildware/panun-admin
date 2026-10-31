<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;

class LeadOtherPhoneLinkService
{
    public const ACTION_KEEP_LEAD = 'keep_lead';

    public const ACTION_MERGE_LEAD = 'merge_lead';

    public const ACTION_ATTACH_BOOKING = 'attach_booking';

    public function __construct(
        private readonly PartyPhoneDirectory $phones,
        private readonly LeadOpenStatusService $openStatus,
    ) {}

    /**
     * @return array{redirect_lead_id: int|null, message: string}
     */
    public function link(Lead $sourceLead, array $input): array
    {
        $ownerType = (string) ($input['owner_type'] ?? '');
        $ownerId = (string) ($input['owner_id'] ?? '');
        $action = (string) ($input['action'] ?? '');
        $targetLeadId = isset($input['target_lead_id']) ? (int) $input['target_lead_id'] : null;
        $bookingId = isset($input['booking_id']) ? (string) $input['booking_id'] : null;

        if (! in_array($ownerType, [PartyAlternatePhone::OWNER_CUSTOMER, PartyAlternatePhone::OWNER_PROVIDER], true)) {
            throw new \InvalidArgumentException(translate('Select_customer_or_provider') ?: 'Select customer or provider');
        }
        if ($ownerId === '') {
            throw new \InvalidArgumentException(translate('Select_the_person') ?: 'Select the person');
        }
        if (! in_array($action, [self::ACTION_KEEP_LEAD, self::ACTION_MERGE_LEAD, self::ACTION_ATTACH_BOOKING], true)) {
            throw new \InvalidArgumentException(translate('Select_what_this_call_is_about') ?: 'Select what this contact is about');
        }

        $this->assertOwnerExists($ownerType, $ownerId);

        $phone = (string) $sourceLead->phone_number;
        $digits = PartyPhoneDirectory::normalizeDigits($phone);
        if ($digits === null) {
            throw new \InvalidArgumentException(translate('Invalid_phone_number') ?: 'Invalid phone number');
        }

        return DB::transaction(function () use (
            $sourceLead,
            $ownerType,
            $ownerId,
            $action,
            $targetLeadId,
            $bookingId,
            $phone,
            $digits
        ) {
            $this->phones->addAlternate(
                $ownerType,
                $ownerId,
                $phone,
                (int) $sourceLead->id,
                Auth::id() ? (string) Auth::id() : null
            );

            if ($action === self::ACTION_KEEP_LEAD) {
                return [
                    'redirect_lead_id' => (int) $sourceLead->id,
                    'message' => translate('Other_phone_linked_lead_kept')
                        ?: 'Other phone linked. This lead was kept as a new enquiry.',
                ];
            }

            if ($action === self::ACTION_ATTACH_BOOKING) {
                if ($bookingId === null || $bookingId === '') {
                    throw new \InvalidArgumentException(translate('Select_a_booking') ?: 'Select a booking');
                }
                $booking = Booking::query()->find($bookingId);
                if (! $booking) {
                    throw new \InvalidArgumentException(translate('Booking_was_not_found') ?: 'Booking was not found');
                }
                $this->assertBookingBelongsToOwner($booking, $ownerType, $ownerId);
                $this->reattachCallsToBooking($sourceLead, $booking, $ownerType, $ownerId);
                $this->softDeleteSourceLead(
                    $sourceLead,
                    'Merged as other phone → Booking #'.($booking->readable_id ?: $booking->id)
                );

                return [
                    'redirect_lead_id' => null,
                    'message' => translate('Other_phone_linked_attached_to_booking')
                        ?: 'Other phone linked. Call attached to booking and this lead was moved to deleted leads.',
                ];
            }

            // merge_lead
            $target = $this->resolveMergeTargetLead($ownerType, $ownerId, $targetLeadId, $digits);
            if (! $target) {
                throw new \InvalidArgumentException(
                    translate('Select_an_open_lead_to_merge_into') ?: 'Select an open lead to merge into'
                );
            }
            if ((int) $target->id === (int) $sourceLead->id) {
                throw new \InvalidArgumentException(translate('Cannot_merge_lead_into_itself') ?: 'Cannot merge a lead into itself');
            }

            $this->mergeLeadData($sourceLead, $target);
            $this->softDeleteSourceLead(
                $sourceLead,
                'Merged as other phone → Lead #'.$target->id
            );

            return [
                'redirect_lead_id' => (int) $target->id,
                'message' => translate('Other_phone_linked_merged_into_lead')
                    ?: 'Other phone linked. Data moved into the selected lead and this lead was moved to deleted leads.',
            ];
        });
    }

    /**
     * Open leads for this owner (by all known phones), for the merge picker.
     *
     * @return \Illuminate\Support\Collection<int, Lead>
     */
    public function openLeadsForOwner(string $ownerType, string $ownerId): \Illuminate\Support\Collection
    {
        $phones = $this->phones->phonesForOwner($ownerType, $ownerId);
        if ($phones === []) {
            return collect();
        }

        $leads = Lead::query()
            ->where(function ($query) use ($phones) {
                foreach ($phones as $digits) {
                    $query->orWhere(function ($inner) use ($digits) {
                        $this->wherePhoneEndsWith($inner, 'phone_number', $digits);
                    });
                }
            })
            ->orderByDesc('id')
            ->get();

        return $leads->filter(fn (Lead $lead) => $this->openStatus->isLeadOpen($lead))->values();
    }

    /**
     * Bookings for this owner (open + closed), newest first.
     *
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    public function bookingsForOwner(string $ownerType, string $ownerId, int $limit = 25): \Illuminate\Support\Collection
    {
        $query = Booking::query()
            ->with(['customer:id,first_name,last_name,phone', 'provider:id,company_name,contact_person_name'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $query->where('customer_id', $ownerId);
        } else {
            $query->where('provider_id', $ownerId);
        }

        return $query->get();
    }

    private function assertOwnerExists(string $ownerType, string $ownerId): void
    {
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $exists = User::query()
                ->where('id', $ownerId)
                ->whereIn('user_type', CUSTOMER_USER_TYPES)
                ->exists();
            if (! $exists) {
                throw new \InvalidArgumentException(translate('Customer_not_found') ?: 'Customer not found');
            }

            return;
        }

        if (! Provider::query()->where('id', $ownerId)->exists()) {
            throw new \InvalidArgumentException(translate('Provider_not_found') ?: 'Provider not found');
        }
    }

    private function assertBookingBelongsToOwner(Booking $booking, string $ownerType, string $ownerId): void
    {
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER
            && (string) $booking->customer_id !== $ownerId
        ) {
            throw new \InvalidArgumentException(translate('Booking_does_not_belong_to_this_customer') ?: 'Booking does not belong to this customer');
        }
        if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER
            && (string) $booking->provider_id !== $ownerId
        ) {
            throw new \InvalidArgumentException(translate('Booking_does_not_belong_to_this_provider') ?: 'Booking does not belong to this provider');
        }
    }

    private function resolveMergeTargetLead(
        string $ownerType,
        string $ownerId,
        ?int $targetLeadId,
        string $sourceDigits
    ): ?Lead {
        $open = $this->openLeadsForOwner($ownerType, $ownerId);
        if ($targetLeadId) {
            $picked = $open->first(fn (Lead $lead) => (int) $lead->id === $targetLeadId);
            if ($picked) {
                return $picked;
            }
            // Allow explicit pick of a still-open lead even if phone list was stale.
            $lead = Lead::query()->find($targetLeadId);
            if ($lead && $this->openStatus->isLeadOpen($lead)) {
                return $lead;
            }

            return null;
        }

        if ($open->count() === 1) {
            return $open->first();
        }

        return null;
    }

    private function mergeLeadData(Lead $source, Lead $target): void
    {
        SuperfoneCall::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadComment::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadChangeLog::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        Booking::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        if (class_exists(WhatsAppBooking::class)) {
            WhatsAppBooking::query()
                ->where('lead_id', $source->id)
                ->update(['lead_id' => $target->id]);
        }

        // Remarks: append source remarks if target empty-ish.
        $sourceRemarks = trim((string) ($source->remarks ?? ''));
        $targetRemarks = trim((string) ($target->remarks ?? ''));
        if ($sourceRemarks !== '') {
            $target->remarks = $targetRemarks === ''
                ? $sourceRemarks
                : $targetRemarks."\n\n[From lead #{$source->id}]\n".$sourceRemarks;
        }

        $sourceName = trim((string) ($source->name ?? ''));
        $targetName = trim((string) ($target->name ?? ''));
        if (($targetName === '' || str_starts_with($targetName, 'WhatsApp ') || $targetName === 'Unknown')
            && $sourceName !== ''
            && ! str_starts_with($sourceName, 'WhatsApp ')
            && $sourceName !== 'Unknown'
        ) {
            $target->name = $sourceName;
        }

        if ($target->source_id === null && $source->source_id) {
            $target->source_id = $source->source_id;
        }
        if ($target->ad_source_id === null && $source->ad_source_id) {
            $target->ad_source_id = $source->ad_source_id;
        }

        $target->save();
    }

    private function reattachCallsToBooking(
        Lead $source,
        Booking $booking,
        string $ownerType,
        string $ownerId
    ): void {
        $payload = [
            'booking_id' => $booking->id,
            'lead_id' => null,
        ];
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $payload['customer_user_id'] = $ownerId;
        } else {
            $payload['provider_id'] = $ownerId;
        }

        SuperfoneCall::query()
            ->where('lead_id', $source->id)
            ->update($payload);
    }

    private function softDeleteSourceLead(Lead $lead, string $reason): void
    {
        $lead->deleted_by = Auth::id();
        $lead->deletion_reason = $reason;
        $lead->save();
        $lead->delete();
    }

    private function wherePhoneEndsWith($query, string $column, string $digits): void
    {
        $query->where(function ($inner) use ($column, $digits) {
            if (DB::connection()->getDriverName() === 'mysql') {
                $inner->whereRaw(
                    "RIGHT(REGEXP_REPLACE(COALESCE({$column}, ''), '[^0-9]', ''), ?) = ?",
                    [strlen($digits), $digits]
                );

                return;
            }

            $inner->where($column, $digits)->orWhere($column, 'like', '%'.$digits);
        });
    }
}
