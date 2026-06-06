@php
    $displayPhone = $displayPhone ?? function ($phone) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (!$digits) {
            return '—';
        }

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    };
@endphp
@foreach($chats ?? collect() as $chat)
    @include('whatsappmodule::admin.conversations.partials._active-chat-item', [
        'chat' => $chat,
        'displayPhone' => $displayPhone,
        'humanSupportTab' => $humanSupportTab ?? false,
    ])
@endforeach
