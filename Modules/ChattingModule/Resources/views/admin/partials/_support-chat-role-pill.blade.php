@php
    $roleUser = $user ?? ($fromUser->user ?? null);
    $channelType = $chat->reference_type ?? null;
@endphp
@if($channelType === 'support_customer')
    <span class="badge rounded-pill bg-primary-subtle text-primary fz-11">{{ translate('customer') }}</span>
@elseif(in_array($channelType, ['support_provider', 'support_serviceman'], true))
    <span class="badge rounded-pill bg-success-subtle text-success fz-11">{{ translate('Provider') }}</span>
@elseif($roleUser)
    @if(in_array($roleUser->user_type, CUSTOMER_USER_TYPES, true))
        <span class="badge rounded-pill bg-primary-subtle text-primary fz-11">{{ translate('customer') }}</span>
    @elseif($roleUser->user_type === 'provider-admin')
        <span class="badge rounded-pill bg-success-subtle text-success fz-11">{{ translate('Provider') }}</span>
    @endif
@endif
