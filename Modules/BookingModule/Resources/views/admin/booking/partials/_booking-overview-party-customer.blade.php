@php
    $partyMeta = $followupDetailMeta['customer'] ?? null;
    $bookingOpen = ($booking ?? null)?->requiresMandatoryNextFollowup() ?? false;
    $partyCardToneClass = '';

    if ($bookingOpen && ! empty($partyMeta['has_pending'])) {
        $partyCardToneClass = ! empty($partyMeta['is_overdue'])
            ? 'party-card--followup-missed'
            : 'party-card--followup-due';
    } elseif ($bookingOpen && ($nextFollowupCustomer ?? null) && (($nextFollowupCustomer->status ?? '') === 'scheduled')) {
        $partyCardToneClass = 'party-card--followup-done';
    }
@endphp
<div class="party-card w-100 {{ $partyCardToneClass }}">
    <div class="party-card__head">
        <span class="party-card__head-title"><span class="material-icons">person</span>{{ translate('Customer_Details') }}</span>
        <div class="party-card__head-actions">
            @if(!empty($partyMeta['has_pending']))
                <span class="badge {{ !empty($partyMeta['is_overdue']) ? 'bg-danger' : 'bg-warning text-dark' }} fz-10">
                    {{ !empty($partyMeta['is_overdue']) ? translate('Missed_Follow_up') : translate('Follow_up_due') }}
                </span>
            @endif
            @can('whatsapp_chat_view')
                @if (!empty($customerPhone))
                    <button type="button"
                            class="btn btn-link p-0 border-0 d-inline-flex align-items-center wa-open-admin-chat"
                            data-phone="{{ e($customerPhone) }}"
                            data-prepare-url="{{ route('admin.whatsapp.conversations.prepare-open', ['channel' => 'whatsapp']) }}"
                            title="{{ translate('WhatsApp') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    </button>
                @endif
            @endcan
        </div>
    </div>
    <div class="party-card__body">
        @php
            $custInitials = '—';
            $custDisplayName = trim((string) ($customerName ?? ''));
            if ($custDisplayName !== '') {
                $parts = preg_split('/\s+/', $custDisplayName);
                $custInitials = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
            } elseif (!empty($customerPhone)) {
                $custInitials = mb_substr(preg_replace('/\D/', '', $customerPhone), -2);
            }
        @endphp
        @if (!$booking?->is_guest && $booking?->customer)
            <img class="party-avatar party-avatar--img" src="{{ $booking->customer->profile_image_full_path }}" alt="">
        @else
            <div class="party-avatar" aria-hidden="true">{{ $custInitials }}</div>
        @endif
        <div class="party-info">
            @if (!$booking?->is_guest && $booking?->customer)
                <a href="{{ route('admin.customer.detail', [$booking->customer->id, 'web_page' => 'overview']) }}" class="party-name">{{ Str::limit($custDisplayName ?: '—', 48) }}</a>
            @else
                <div class="party-name">{{ Str::limit($custDisplayName ?: '—', 48) }}</div>
            @endif
            @if ($customerPhone ?? null)
                <div class="party-line"><span class="material-icons">phone</span> <a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a></div>
            @endif
            @php
                $customerAddressText = trim((string) (
                    data_get($booking, 'service_address.address')
                    ?? data_get($customerAddress ?? null, 'address')
                    ?? ''
                ));
                $customerAddressMissing = ($booking->service_location ?? 'customer') !== 'provider' && $customerAddressText === '';
            @endphp
            <div class="party-line {{ $customerAddressMissing ? 'party-line--address-missing' : '' }}">
                <span class="material-icons">location_on</span>
                <span class="{{ $customerAddressMissing ? 'text-danger' : '' }}">
                    {{ Str::limit($customerAddressText !== '' ? $customerAddressText : translate('Customer Address required'), 120) }}
                </span>
            </div>
            @include('bookingmodule::admin.booking.partials._booking-overview-party-followup', [
                'followup' => $nextFollowupCustomer ?? null,
                'partyMeta' => $partyMeta,
            ])
        </div>
    </div>
</div>
