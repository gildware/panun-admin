@php
    $payload = $servicePreviewPayload ?? [];
    $previewCurrencySymbol = $payload['currencySymbol'] ?? '₹';
@endphp

<div class="service-detail-inline-preview" id="serviceDetailInlinePreview">
    <div class="service-mobile-preview-split service-detail-inline-preview__split">
        <aside class="service-mobile-preview-config" aria-label="{{ translate('Preview_settings') }}">
            <div class="service-mobile-preview-config-block">
                <span class="service-mobile-preview-config-label">{{ translate('Tab_view') }}</span>
                <div class="service-preview-tab-pills" id="serviceDetailPreviewTabPills" role="radiogroup">
                    <label class="service-preview-tab-pill is-active" for="serviceDetailPreviewTabOverview">
                        <input type="radio" name="serviceDetailPreviewTab" id="serviceDetailPreviewTabOverview" value="overview" checked>
                        <span>{{ translate('service_overview') }}</span>
                    </label>
                    @if(!empty($payload['hasFaqs']))
                        <label class="service-preview-tab-pill" for="serviceDetailPreviewTabFaq">
                            <input type="radio" name="serviceDetailPreviewTab" id="serviceDetailPreviewTabFaq" value="faq">
                            <span>{{ translate('faq') }}</span>
                        </label>
                    @endif
                    <label class="service-preview-tab-pill" for="serviceDetailPreviewTabReviews">
                        <input type="radio" name="serviceDetailPreviewTab" id="serviceDetailPreviewTabReviews" value="reviews">
                        <span>{{ translate('reviews') }}</span>
                    </label>
                </div>
            </div>
            <p class="service-mobile-preview-hint mb-0">{{ translate('detail_mobile_preview_saved_data_hint') }}</p>
        </aside>

        <div class="service-mobile-preview-preview-col">
            <span class="service-mobile-preview-config-label">{{ translate('Phone_view') }}</span>
            <div class="service-app-phone-stage" id="serviceDetailPreviewStage">
                <div class="service-app-phone-scale-outer" id="serviceDetailPreviewScaleOuter">
                    <div class="service-app-phone-preview"
                         id="serviceDetailPreviewRoot"
                         data-currency-symbol="{{ $previewCurrencySymbol }}"
                         data-placeholder="{{ asset('assets/admin-module/img/placeholder.png') }}"
                         data-label-overview="{{ translate('service_overview') }}"
                         data-label-faq="{{ translate('faq') }}"
                         data-label-reviews="{{ translate('reviews') }}"
                         data-label-book-now="{{ translate('book_now') }}"
                         data-label-no-faq="{{ translate('No_FAQ_added_yet') }}"
                         data-label-no-reviews="{{ translate('You don’t have any reviews yet.') }}">
                        <div class="service-app-phone-frame">
                            <div class="service-app-phone-notch"></div>
                            <div class="service-app-phone-chrome">
                                <div class="service-app-phone-statusbar">
                                    <span>9:41</span>
                                    <span class="service-app-phone-status-icons" aria-hidden="true"></span>
                                </div>
                                <div class="service-app-phone-appbar">
                                    <span class="material-icons">arrow_back</span>
                                    <span class="service-app-phone-appbar-title">{{ translate('service_details') }}</span>
                                    <span class="material-icons service-app-phone-cart">shopping_cart</span>
                                </div>
                            </div>
                            <div class="service-app-phone-viewport">
                                <div class="service-app-phone-scroll" id="serviceDetailPreviewScreen"></div>
                            </div>
                            <div class="service-app-phone-home-indicator" aria-hidden="true"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="serviceDetailPreviewPayload">@json($payload)</script>

<style>
    .service-detail-inline-preview__split {
        min-height: 560px;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 0.75rem;
        background: var(--bs-body-bg);
    }
    .service-detail-inline-preview .service-mobile-preview-config {
        flex: 0 0 220px;
        max-width: 240px;
    }
    .service-detail-inline-preview .service-app-phone-stage {
        min-height: 480px;
    }
    .service-app-faq-item {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
        background: #fafafa;
    }
    .service-app-faq-item strong {
        display: block;
        font-size: 12px;
        margin-bottom: 4px;
        color: #222;
    }
    .service-app-faq-item p {
        margin: 0;
        font-size: 11px;
        color: #666;
        line-height: 1.4;
    }
    .service-app-overview-html .sov-intro { margin: 0 0 10px; font-size: 12px; color: #444; }
    .service-app-overview-html .sov-title { font-size: 13px; font-weight: 700; margin: 12px 0 6px; }
    .service-app-overview-html .sov-top-icons { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .service-app-overview-html .sov-top-icon {
        display: flex; align-items: center; gap: 6px; padding: 8px; border-radius: 10px; font-size: 10px;
        border: 1px solid color-mix(in srgb, var(--sov-accent) 20%, white);
        background: color-mix(in srgb, var(--sov-accent) 8%, white);
    }
    .service-app-overview-html .sov-top-icon .material-icons { font-size: 16px; color: var(--sov-accent); }
    .service-app-overview-html .sov-process-row { display: flex; gap: 8px; overflow-x: auto; }
    .service-app-overview-html .sov-process-step { flex: 0 0 100px; text-align: center; }
    .service-app-overview-html .sov-process-step img,
    .service-app-overview-html .sov-process-placeholder { width: 100px; height: 64px; border-radius: 8px; object-fit: cover; margin: 0 auto 4px; display: block; }
    .service-app-overview-html .sov-process-placeholder { background: #eef2ff; display: flex; align-items: center; justify-content: center; color: #3b82f6; }
    .service-app-overview-html .sov-step-no {
        display: inline-flex; width: 20px; height: 20px; border-radius: 999px; background: #25274d; color: #fff;
        font-size: 10px; font-weight: 700; align-items: center; justify-content: center;
    }
    .service-app-overview-html .sov-step-label { display: block; font-size: 10px; font-weight: 600; margin-top: 4px; }
    .service-app-overview-html .sov-step-desc { display: block; font-size: 9px; color: #64748b; margin-top: 3px; line-height: 1.35; }
    .service-app-overview-html .sov-included-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
    .service-app-overview-html .sov-included-item {
        border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 4px; text-align: center; font-size: 9px;
    }
    .service-app-overview-html .sov-included-item .material-icons { font-size: 16px; color: #64748b; }
    .service-app-overview-html .sov-chips { display: flex; flex-wrap: wrap; gap: 4px; }
    .service-app-overview-html .sov-chip {
        display: inline-flex; align-items: center; gap: 3px; padding: 4px 8px; border-radius: 999px;
        background: rgba(37, 39, 77, 0.07); font-size: 10px; font-weight: 600; color: #25274d;
    }
    .service-app-overview-html .sov-info-stack { display: flex; flex-direction: column; gap: 6px; }
    .service-app-overview-html .sov-info-card { border-radius: 10px; padding: 8px; font-size: 10px; }
    .service-app-overview-html .sov-info-card--good { background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.18); }
    .service-app-overview-html .sov-info-card--bad { background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.18); }
    .service-app-overview-html .sov-info-card--neutral { background: rgba(100, 116, 139, 0.06); border: 1px solid rgba(100, 116, 139, 0.18); }
    .service-app-overview-html .sov-info-head { display: flex; align-items: center; gap: 4px; font-weight: 700; margin-bottom: 6px; }
    .service-app-overview-html .sov-info-card--good .sov-info-head,
    .service-app-overview-html .sov-info-card--good .sov-info-line .material-icons { color: #16a34a; }
    .service-app-overview-html .sov-info-card--bad .sov-info-head,
    .service-app-overview-html .sov-info-card--bad .sov-info-line .material-icons { color: #dc2626; }
    .service-app-overview-html .sov-info-card--neutral .sov-info-head,
    .service-app-overview-html .sov-info-card--neutral .sov-info-line .sov-info-bullet { color: #64748b; }
    .service-app-overview-html .sov-info-line { display: flex; gap: 4px; margin-bottom: 4px; }
    .service-app-overview-html .sov-info-bullet {
        width: 6px; height: 6px; border-radius: 999px; background: currentColor;
        margin-top: 5px; flex: 0 0 6px;
    }
    .service-app-overview-html .sov-why-row { display: flex; gap: 6px; overflow-x: auto; }
    .service-app-overview-html .sov-why-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
    .service-app-overview-html .sov-why-card {
        flex: 0 0 130px; border-radius: 10px; padding: 8px; font-size: 10px;
        background: color-mix(in srgb, var(--sov-accent) 10%, white);
        border: 1px solid color-mix(in srgb, var(--sov-accent) 18%, white);
    }
    .service-app-overview-html .sov-why-card .material-icons { color: var(--sov-accent); font-size: 18px; }
</style>
