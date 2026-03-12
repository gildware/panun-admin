@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' - ' . translate('Chat'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="page-title d-flex gap-3 align-items-center">
                    <a href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chats']) }}" class="btn btn-sm btn--secondary">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <span class="material-icons">chat</span>
                    {{ translate('Chat') }}: {{ $phone }}
                </h2>
                @if($bookingLink ?? null)
                    <a href="{{ $bookingLink }}" class="btn btn--primary" target="_blank">
                        <span class="material-icons">event_note</span>
                        {{ translate('View Booking') }}
                    </a>
                @endif
            </div>

            @if($conversationState ?? null)
                <div class="card card-body mb-3">
                    <strong>{{ translate('Status') }}:</strong>
                    {{ $conversationState->active_module ?? '—' }} · {{ $conversationState->current_step ?? '—' }}
                    @if($conversationState->after_hours ?? false)
                        <span class="badge bg-warning">{{ translate('After hours') }}</span>
                    @endif
                </div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-body">
                        <div class="chat-messages overflow-auto mb-3" style="min-height: 320px; max-height: 50vh;">
                            @foreach($messages as $msg)
                                @php($isOut = strtoupper($msg->direction ?? '') === 'OUT')
                                <div class="mb-3 d-flex {{ $isOut ? 'justify-content-end' : '' }}">
                                    <div class="rounded px-3 py-2 {{ $isOut ? 'bg-primary text-white' : 'bg-light' }}"
                                         style="max-width: 85%;">
                                        <div class="fz-12 opacity-75">
                                            {{ $msg->direction ?? 'IN' }} · {{ $msg->message_type ?? 'TEXT' }}
                                            · {{ $msg->created_at?->format('M j, H:i') }}
                                        </div>
                                        <div class="mt-1">{!! nl2br(e($msg->message_text ?? $msg->body ?? '')) !!}</div>
                                    </div>
                                </div>
                            @endforeach
                            @if($messages->isEmpty())
                                <p class="text-muted text-center py-4">{{ translate('No messages yet') }}</p>
                            @endif
                        </div>

                        @can('whatsapp_chat_reply')
                            <form action="{{ route('admin.whatsapp.conversations.reply') }}" method="POST" class="border-top pt-3">
                                @csrf
                                <input type="hidden" name="phone" value="{{ $phone }}">
                                <div class="input-group">
                                    <textarea name="body" class="form-control" rows="2" required
                                              placeholder="{{ translate('Type your reply...') }}"></textarea>
                                    <button type="submit" class="btn btn--primary">
                                        <span class="material-icons">send</span>
                                        {{ translate('Send') }}
                                    </button>
                                </div>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-body">
                        <h5 class="mb-3">{{ translate('Chat info') }}</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>{{ translate('Phone') }}:</strong> {{ $phone }}</li>
                            @if($conversationState ?? null)
                                <li class="mb-2"><strong>{{ translate('Module') }}:</strong> {{ $conversationState->active_module ?? '—' }}</li>
                                <li class="mb-2"><strong>{{ translate('Step') }}:</strong> {{ $conversationState->current_step ?? '—' }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.querySelector('.chat-messages')?.scrollTo(0, 1e9);
    </script>
@endpush
