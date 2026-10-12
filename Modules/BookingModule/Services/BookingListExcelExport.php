<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\UserManagement\Entities\User;

class BookingListExcelExport
{
    /**
     * @return array<string, array<string, string>>
     */
    public function groups(): array
    {
        return [
            'Booking' => [
                'lead_id' => 'LeadID',
                'booking_id' => 'Booking ID',
                'category' => 'Category Name',
                'sub_category' => 'Sub category name',
                'service' => 'Service name',
                'total_price' => 'Total price',
                'status' => 'Status',
                'provider' => 'Provider',
                'provider_phone' => 'Provider phone',
                'assigned_to' => 'Assigned to',
                'serviceman' => 'Serviceman',
                'zone' => 'Zone',
                'area' => 'Area',
                'address' => 'Service address',
                'schedule' => 'Service schedule',
                'created_at' => 'Booked on',
                'booking_source' => 'Booking source',
                'service_description' => 'Service description',
                'repeat' => 'Repeat booking',
            ],
            'Customer' => [
                'customer_name' => 'Customer name',
                'customer_phone' => 'Customer phone',
                'customer_email' => 'Customer email',
            ],
            'Payment' => [
                'payment_status' => 'Payment status',
                'payment_method' => 'Payment method',
                'transaction_id' => 'Transaction ID',
                'discount' => 'Discount',
                'coupon_discount' => 'Coupon discount',
                'coupon_code' => 'Coupon code',
                'tax' => 'Tax',
                'extra_fee' => 'Extra fee',
            ],
            'Lead' => [
                'lead_name' => 'Lead name',
                'lead_phone' => 'Lead phone',
                'lead_type' => 'Lead type',
                'lead_source' => 'Lead source',
                'lead_ad_source' => 'Ad source',
                'lead_received_at' => 'Lead received at',
                'lead_handled_by' => 'Lead handled by',
                'lead_remarks' => 'Lead remarks',
                'lead_next_followup' => 'Lead next follow-up',
                'lead_created_by' => 'Lead created by',
                'lead_non_responsive' => 'Lead non-responsive',
                'lead_tags' => 'Lead tags',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function flatColumns(): array
    {
        $columns = [];
        foreach ($this->groups() as $group) {
            foreach ($group as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    public function allKeys(): array
    {
        return array_keys($this->flatColumns());
    }

    /**
     * @param  list<string>  $requested
     * @return list<string>
     */
    public function keysInOrder(array $requested, bool $defaultAll = true): array
    {
        $known = $this->allKeys();
        $requested = array_values(array_filter(array_map('strval', $requested)));
        if ($requested === []) {
            return $defaultAll ? $known : [];
        }

        return array_values(array_intersect($known, $requested));
    }

    /**
     * @return list<string>
     */
    public function allowedStatuses(): array
    {
        return array_merge(array_column(BOOKING_STATUSES, 'key'), [
            'all',
            'reopened',
            'resolved',
            'disputed_cancelled',
            'disputed_completed',
            'hold_after_visit',
            'cancelled_after_visit',
            'completed_no_or_little',
            'loss_making',
            'loss_making_pending',
            'loss_recovered',
            'loss_settled',
            'cancelled_by_provider',
            'cancelled_by_customer',
        ]);
    }

    /**
     * @return list<string>
     */
    public function relations(): array
    {
        return [
            'customer' => function ($query) {
                $query->withTrashed();
            },
            'assignee',
            'provider.owner',
            'category',
            'subCategory',
            'zone',
            'area',
            'service_address',
            'serviceman.user',
            'detail.service' => function ($query) {
                $query->withTrashed()->with(['category', 'subCategory']);
            },
            'extra_services',
            'booking_partial_payments',
            'booking_offline_payments',
            'lead.source',
            'lead.adSource',
            'lead.createdBy',
            'lead.customerLeadTags',
        ];
    }

    public function applyFilters(Builder $query, Request $request, array $assigneeIds): Builder
    {
        $bookingStatus = $request->input('booking_status') ?: 'all';
        $repeatOnly = (int) $request->input('is_repeated', 0) === 1;
        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;

        return app(AdminBookingListQueryService::class)
            ->applySharedFilters(
                $query,
                $request,
                $assigneeIds,
                $repeatOnly,
                ! in_array($bookingStatus, ['cancelled_by_provider', 'cancelled_by_customer'], true)
            )
            ->applyBookingListStatusTab($bookingStatus, ['max_booking_amount' => $maxBookingAmount])
            ->when($bookingStatus === 'cancelled_by_provider', function ($query) {
                $query->orderByDesc('provider_cancelled_at')->orderByDesc('updated_at');
            }, function ($query) {
                $query->latest();
            });
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string|float>>
     */
    public function rows(Collection $bookings, array $keys): array
    {
        $labels = $this->flatColumns();
        $handlers = $this->handlerNames($bookings);

        return $bookings->map(function (Booking $booking) use ($keys, $labels, $handlers) {
            $values = $this->values($booking, $handlers);
            $row = [];
            foreach ($keys as $key) {
                $row[$labels[$key]] = $values[$key] ?? '-';
            }

            return $row;
        })->all();
    }

    /**
     * @return array<string, string>
     */
    private function handlerNames(Collection $bookings): array
    {
        $ids = [];
        foreach ($bookings as $booking) {
            $handledBy = (string) ($booking->lead?->handled_by ?? '');
            if (Lead::assigneeIsHuman($handledBy)) {
                $ids[] = $handledBy;
            }
        }

        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', array_values(array_unique($ids)))
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [$user->id => $this->personName($user)])
            ->all();
    }

    /**
     * @param  array<string, string>  $handlers
     * @return array<string, string|float>
     */
    private function values(Booking $booking, array $handlers): array
    {
        $customerName = $this->personName($booking->customer);
        if ($customerName === '') {
            $customerName = trim((string) ($booking->service_address?->contact_person_name ?? ''));
        }
        $customerPhone = trim((string) ($booking->customer?->phone ?? ''));
        if ($customerPhone === '') {
            $customerPhone = trim((string) ($booking->service_address?->contact_person_number ?? ''));
        }
        $assigneeName = '';
        if ($booking->assignee) {
            $assigneeName = $this->personName($booking->assignee);
            $assigneeName .= ' ('.($booking->assignee->user_type === 'super-admin' ? 'Admin' : 'Employee').')';
        }

        $providerName = '';
        $providerPhone = '';
        if ($booking->provider) {
            $providerName = trim((string) ($booking->provider->company_name ?? ''));
            $providerPhone = trim((string) ($booking->provider->company_phone ?? ''));
            if ($providerName === '' && $booking->provider->owner) {
                $providerName = $this->personName($booking->provider->owner);
            }
        }

        $firstService = $booking->detail->first()?->service;
        $categoryName = trim((string) ($booking->category?->name ?? $firstService?->category?->name ?? ''));
        $subCategoryName = trim((string) ($booking->subCategory?->name ?? $firstService?->subCategory?->name ?? ''));
        $serviceNames = $booking->detail
            ->map(fn ($detail) => trim((string) ($detail->service?->name ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');
        if ($serviceNames === '') {
            $serviceNames = trim((string) ($booking->service_description ?? ''));
        }

        $address = trim((string) ($booking->service_address?->address ?? ''));
        if ($address === '' && is_object($booking->service_address_location ?? null)) {
            $address = trim((string) ($booking->service_address_location->address ?? ''));
        } elseif ($address === '' && is_string($booking->service_address_location) && $booking->service_address_location !== '') {
            $decoded = json_decode($booking->service_address_location);
            $address = trim((string) ($decoded->address ?? ''));
        }
        $landmark = trim((string) ($booking->service_address?->landmark ?? ''));
        if ($landmark !== '') {
            $address = trim($address.($address !== '' ? ', ' : '').$landmark);
        }

        $lead = $booking->lead;
        $handledBy = (string) ($lead?->handled_by ?? '');
        if ($handledBy === Lead::HANDLED_BY_AI) {
            $handledByName = 'AI';
        } elseif (Lead::assigneeIsHuman($handledBy)) {
            $handledByName = $handlers[$handledBy] ?? '';
        } else {
            $handledByName = '';
        }

        $leadType = (string) ($lead?->lead_type ?? '');
        $leadTypeLabel = Lead::leadTypes()[$leadType] ?? ($leadType !== '' ? ucwords(str_replace('_', ' ', $leadType)) : '');

        return [
            'lead_id' => $this->cell($booking->lead_id ? (string) $booking->lead_id : ''),
            'booking_id' => $this->cell((string) ($booking->readable_id ?? '')),
            'category' => $this->cell($categoryName),
            'sub_category' => $this->cell($subCategoryName),
            'service' => $this->cell($serviceNames),
            'total_price' => round(get_booking_total_amount($booking), 2),
            'status' => $this->cell($this->statusLabel($booking)),
            'provider' => $this->cell($providerName),
            'provider_phone' => $this->cell($providerPhone),
            'assigned_to' => $this->cell($assigneeName),
            'serviceman' => $this->cell($this->personName($booking->serviceman?->user)),
            'zone' => $this->cell((string) ($booking->zone?->name ?? '')),
            'area' => $this->cell((string) ($booking->area?->name ?? '')),
            'address' => $this->cell($address),
            'schedule' => $this->cell($this->dateTime($booking->service_schedule)),
            'created_at' => $this->cell($this->dateTime($booking->created_at)),
            'booking_source' => $this->cell((string) ($booking->booking_source ?? '')),
            'service_description' => $this->cell((string) ($booking->service_description ?? '')),
            'repeat' => (int) ($booking->is_repeated ?? 0) === 1 ? 'Yes' : 'No',
            'customer_name' => $this->cell($customerName),
            'customer_phone' => $this->cell($customerPhone),
            'customer_email' => $this->cell((string) ($booking->customer?->email ?? '')),
            'payment_status' => $this->cell($this->paymentStatus($booking)),
            'payment_method' => $this->cell($this->paymentMethod($booking)),
            'transaction_id' => $this->cell((string) ($booking->transaction_id ?? '')),
            'discount' => round((float) ($booking->total_discount_amount ?? 0), 2),
            'coupon_discount' => round((float) ($booking->total_coupon_discount_amount ?? 0), 2),
            'coupon_code' => $this->cell((string) ($booking->coupon_code ?? '')),
            'tax' => round((float) ($booking->total_tax_amount ?? 0), 2),
            'extra_fee' => round((float) ($booking->extra_fee ?? 0), 2),
            'lead_name' => $this->cell((string) ($lead?->name ?? '')),
            'lead_phone' => $this->cell((string) ($lead?->phone_number ?? '')),
            'lead_type' => $this->cell($leadTypeLabel),
            'lead_source' => $this->cell((string) ($lead?->source?->name ?? '')),
            'lead_ad_source' => $this->cell((string) ($lead?->adSource?->name ?? '')),
            'lead_received_at' => $this->cell($this->dateTime($lead?->date_time_of_lead_received)),
            'lead_handled_by' => $this->cell($handledByName),
            'lead_remarks' => $this->cell((string) ($lead?->remarks ?? '')),
            'lead_next_followup' => $this->cell($this->dateTime($lead?->next_followup_at)),
            'lead_created_by' => $this->cell($this->personName($lead?->createdBy)),
            'lead_non_responsive' => $lead && $lead->non_responsive_at ? 'Yes' : ($lead ? 'No' : '-'),
            'lead_tags' => $this->cell($lead ? $lead->customerLeadTags->pluck('name')->filter()->implode(', ') : ''),
        ];
    }

    private function statusLabel(Booking $booking): string
    {
        $snapshot = $booking->reopen_disputed_snapshot;
        if (is_array($snapshot) && $snapshot !== []) {
            $retained = 0.0;
            foreach (['retained_from_customer', 'final_net_to_customer'] as $key) {
                if (isset($snapshot[$key]) && is_numeric($snapshot[$key])) {
                    $retained = (float) $snapshot[$key];
                    break;
                }
            }

            return $retained > 0.009 ? 'Disputed and Completed' : 'Disputed and Cancelled';
        }

        $status = strtolower((string) ($booking->booking_status ?? ''));
        if ($status === 'cancelled') {
            $status = 'canceled';
        }

        return match ($status) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'ongoing' => 'Ongoing',
            'on_hold' => 'On hold',
            'pending_cancellation' => 'Pending cancellation',
            'completed' => 'Completed',
            'canceled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => $status === '' ? '' : ucwords(str_replace('_', ' ', $status)),
        };
    }

    private function paymentStatus(Booking $booking): string
    {
        if ((int) $booking->is_paid === 1) {
            return 'Paid';
        }

        $partialPaid = $booking->relationLoaded('booking_partial_payments')
            ? (float) $booking->booking_partial_payments->sum('paid_amount')
            : 0.0;

        return $partialPaid > 0.009 ? 'Partially paid' : 'Unpaid';
    }

    private function paymentMethod(Booking $booking): string
    {
        $method = (string) ($booking->payment_method ?? '');
        if ($method === '') {
            return '';
        }

        if ($method !== 'offline_payment') {
            $known = collect(PAYMENT_METHODS)->firstWhere('key', $method);
            if (is_array($known) && ! empty($known['value'])) {
                return (string) $known['value'];
            }
        }

        return ucwords(trim(format_booking_payment_method_for_admin_display($booking)));
    }

    private function personName(mixed $user): string
    {
        if (! is_object($user)) {
            return '';
        }

        return trim((string) (($user->first_name ?? '').' '.($user->last_name ?? '')));
    }

    private function dateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function cell(string $value): string
    {
        $value = trim($value);

        return $value === '' ? '-' : $value;
    }
}
