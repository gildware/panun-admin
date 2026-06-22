@php
    $customerDisplayName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    $customerDisplayName = $customerDisplayName !== '' ? $customerDisplayName : ($customer->email ?? translate('Customer'));
    $customerStatus = (string) ($customer->manual_performance_status ?? 'active');
    $customerStatusLabel = match($customerStatus) {
        'blacklisted' => translate('Blacklisted'),
        'suspended' => translate('Suspended'),
        default => translate('Active'),
    };
    $customerStatusClass = match($customerStatus) {
        'blacklisted' => 'bg-danger',
        'suspended' => 'bg-warning text-dark',
        default => 'bg-success',
    };
@endphp

<div class="d-flex justify-content-between align-items-start gap-2">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="profile-role-pill">
                {{ translate('Customer') }}
            </span>
            <h2 class="page-title mb-0">{{ $customerDisplayName }}</h2>
            @include('reviewmodule::admin.partials._profile-role-rating', [
                'compact' => true,
                'avgRating' => $customer->received_avg_rating ?? 0,
                'ratingCount' => $customer->received_rating_count ?? 0,
            ])
        </div>
        <div class="profile-rating-meta mb-2">
            {{ (int) ($customer->received_rating_count ?? 0) }} {{ translate('customer_ratings') }}
        </div>
        <div>{{ translate('Joined_on') }} {{ date('d-M-y H:iA', strtotime($customer?->created_at)) }}</div>
    </div>
    <span class="badge {{ $customerStatusClass }}">{{ $customerStatusLabel }}</span>
</div>
