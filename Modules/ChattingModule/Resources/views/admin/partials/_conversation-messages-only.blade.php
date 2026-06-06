@php($isStaffGroup = $isStaffGroup ?? false)
@include('chattingmodule::admin.partials._chat-messages-list', [
    'conversation' => $conversation,
    'enableStaffMessaging' => $enableStaffMessaging ?? false,
    'isStaffGroup' => $isStaffGroup,
])
