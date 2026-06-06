@if(($enableStaffMessaging ?? false) && !empty($message))
    {!! app(\Modules\ChattingModule\Services\StaffChatMessageParser::class)->format($message) !!}
@else
    {{ $message }}
@endif
