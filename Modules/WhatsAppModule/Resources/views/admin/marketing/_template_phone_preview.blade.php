@php
    /** @var array $preview — from WhatsAppCloudService::extractTemplatePreviewState */
    $preview = $preview ?? [
        'header' => null,
        'body' => '',
        'body_display' => '',
        'footer' => '',
        'buttons' => [],
    ];
@endphp
<div class="wa-tpl-phone-preview mx-auto">
    <div class="wa-tpl-phone-frame rounded-4 overflow-hidden shadow">
        <div class="wa-tpl-phone-notch d-flex align-items-center justify-content-between px-3 py-2">
            <span class="small fw-semibold text-white-50">{{ translate('preview') }}</span>
            <span class="small text-white-50">WhatsApp</span>
        </div>
        <div class="wa-tpl-phone-body p-3">
            <div class="wa-tpl-bubble rounded-3 p-3 bg-white shadow-sm border border-light">
                @if(!empty($preview['header']))
                    @php $h = $preview['header']; $fmt = $h['format'] ?? 'TEXT'; @endphp
                    @if(in_array($fmt, ['IMAGE', 'VIDEO', 'DOCUMENT'], true))
                        <div class="wa-tpl-media rounded-2 mb-2 overflow-hidden bg-light ratio ratio-16x9 d-flex align-items-center justify-content-center">
                            @if(!empty($h['media_url']))
                                @if($fmt === 'VIDEO')
                                    <video class="w-100 h-100 object-fit-cover" src="{{ $h['media_url'] }}" muted playsinline controls></video>
                                @elseif($fmt === 'IMAGE')
                                    <img src="{{ $h['media_url'] }}" alt="" class="w-100 h-100 object-fit-cover">
                                @else
                                    <span class="text-muted small p-2 text-center">{{ $fmt }} · <a href="{{ $h['media_url'] }}" target="_blank" rel="noopener">file</a></span>
                                @endif
                            @else
                                <span class="text-muted small">{{ $fmt }}</span>
                            @endif
                        </div>
                    @elseif(($h['display_text'] ?? '') !== '')
                        <div class="fw-semibold small mb-2 text-break">{{ $h['display_text'] }}</div>
                    @endif
                @endif

                @if(($preview['body_display'] ?? '') !== '')
                    <div class="small text-break wa-tpl-body-text" style="white-space: pre-wrap;">{{ $preview['body_display'] }}</div>
                @elseif(($preview['body'] ?? '') !== '')
                    <div class="small text-break wa-tpl-body-text" style="white-space: pre-wrap;">{{ $preview['body'] }}</div>
                @endif

                @if(($preview['footer'] ?? '') !== '')
                    <div class="text-muted mt-2" style="font-size: 0.7rem;">{{ $preview['footer'] }}</div>
                @endif

                @if(!empty($preview['buttons']))
                    <div class="d-grid gap-2 mt-3">
                        @foreach($preview['buttons'] as $btn)
                            @php $t = $btn['type'] ?? ''; @endphp
                            @if($t === 'QUICK_REPLY')
                                <span class="btn btn-sm btn-outline-secondary text-start rounded-pill wa-tpl-btn-fake">{{ $btn['text'] ?? '' }}</span>
                            @elseif($t === 'URL')
                                <span class="btn btn-sm btn-outline-primary text-truncate rounded-pill wa-tpl-btn-fake">{{ $btn['text'] ?? '' }}</span>
                                @if(($btn['url'] ?? '') !== '')
                                    <span class="text-muted" style="font-size: 0.65rem; word-break: break-all;">{{ $btn['url'] }}</span>
                                @endif
                            @elseif($t === 'PHONE_NUMBER')
                                <span class="btn btn-sm btn-outline-primary rounded-pill wa-tpl-btn-fake">{{ $btn['text'] ?? '' }}</span>
                                @if(($btn['phone_number'] ?? '') !== '')
                                    <span class="text-muted" style="font-size: 0.65rem;">{{ $btn['phone_number'] }}</span>
                                @endif
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            <p class="text-center text-muted mt-2 mb-0" style="font-size: 0.65rem;">{{ translate('Template_preview_disclaimer') }}</p>
        </div>
    </div>
</div>
<style>
    .wa-tpl-phone-notch { background: rgba(0, 0, 0, 0.2); }
    .wa-tpl-phone-preview { max-width: 320px; }
    .wa-tpl-phone-frame {
        background: linear-gradient(160deg, #075e54 0%, #128c7e 45%, #25d366 100%);
        border: 1px solid rgba(0,0,0,.08);
    }
    .wa-tpl-phone-body {
        background: #e5ddd5;
        min-height: 200px;
        background-image:
            radial-gradient(circle at 20% 30%, rgba(255,255,255,.12) 0, transparent 45%),
            radial-gradient(circle at 80% 70%, rgba(0,0,0,.04) 0, transparent 40%);
    }
    .wa-tpl-btn-fake { pointer-events: none; cursor: default; }
</style>
