@php
    $pinnedMessages = $pinnedMessages ?? collect();
    $parser = app(\Modules\ChattingModule\Services\StaffChatMessageParser::class);
@endphp
<div id="chatPinnedBar" class="chat-pinned-bar">
    @if($pinnedMessages->isNotEmpty())
        <div class="border rounded bg-light px-3 py-2 mb-3 chat-pinned-accordion">
            <div class="chat-pinned-toggle d-flex align-items-center justify-content-between gap-1 text-muted fz-12 fw-semibold"
                 role="button" tabindex="0" aria-expanded="false">
                <span class="d-flex align-items-center gap-1">
                    <span class="material-symbols-outlined fs-16">push_pin</span>
                    {{ translate('Pinned_Messages') }} ({{ $pinnedMessages->count() }})
                </span>
                <span class="material-symbols-outlined fs-18 chat-pinned-chevron">expand_more</span>
            </div>
            <div class="chat-pinned-list d-flex flex-column gap-2 mt-2 d-none">
                @foreach($pinnedMessages as $pin)
                    @php
                        $pinAuthor = $pin->user
                            ? trim(($pin->user->first_name ?? '').' '.($pin->user->last_name ?? ''))
                            : translate('no_user_found');
                        $pinPreview = $parser->plainPreview($pin->message, 100);
                        if ($pinPreview === '' && $pin->conversationFiles?->isNotEmpty()) {
                            $pinPreview = translate('Attachment');
                        }
                    @endphp
                    <div class="chat-pinned-item d-flex align-items-start justify-content-between gap-2 bg-white border rounded px-2 py-2"
                         data-conversation-id="{{ $pin->id }}">
                        <div class="min-w-0 chat-pinned-jump flex-grow-1" data-target-id="{{ $pin->id }}">
                            <div class="fz-11 fw-semibold text-primary mb-1">{{ $pinAuthor }}</div>
                            <div class="fz-12 text-muted text-truncate">{{ $pinPreview }}</div>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-link text-muted p-0 flex-shrink-0 chat-unpin-btn"
                                data-conversation-id="{{ $pin->id }}"
                                title="{{ translate('Unpin') }}">
                            <span class="material-symbols-outlined fs-16">close</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
