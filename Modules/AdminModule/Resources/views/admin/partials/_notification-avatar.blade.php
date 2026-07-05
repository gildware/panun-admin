@php
    $senderAvatarUrl = $notification->senderAvatarUrl();
    $avatarSize = $avatarSize ?? 40;
@endphp
@if($senderAvatarUrl)
    <img src="{{ $senderAvatarUrl }}"
         class="avatar rounded-circle object-fit-cover flex-shrink-0"
         width="{{ $avatarSize }}"
         height="{{ $avatarSize }}"
         alt="{{ translate('image') }}">
@else
    <div class="avatar title-color hover-color-c2 flex-shrink-0">
        <span class="material-symbols-outlined {{ $avatarSize >= 48 ? 'fs-2' : '' }}">{{ $notification->iconName() }}</span>
    </div>
@endif
