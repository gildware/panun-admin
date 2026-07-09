@php
    $servicePreview = $service ?? null;
    $previewCurrencySymbol = $previewCurrencySymbol ?? null;
    if (empty($previewCurrencySymbol) && function_exists('with_currency_symbol')) {
        $previewCurrencySymbol = preg_replace('/[\d.,\s\x{00A0}]/u', '', with_currency_symbol(1)) ?: '$';
    }
    $previewCurrencySymbol = $previewCurrencySymbol ?: '$';
@endphp

<div class="modal fade service-mobile-preview-modal" id="serviceMobilePreviewModal" tabindex="-1" aria-labelledby="serviceMobilePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered service-mobile-preview-dialog">
        <div class="modal-content service-mobile-preview-content">
            <div class="modal-header service-mobile-preview-header">
                <div class="pe-3 min-w-0">
                    <h5 class="modal-title mb-1 text-truncate" id="serviceMobilePreviewModalLabel">{{ translate('Preview_in_mobile_app') }}</h5>
                    <p class="text-muted fs-12 mb-0">{{ translate('Preview_matches_service_details_in_customer_and_provider_apps') }}</p>
                </div>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
            </div>

            <div class="modal-body service-mobile-preview-body">
                <div class="service-mobile-preview-split">
                    <aside class="service-mobile-preview-config" aria-label="{{ translate('Preview_settings') }}">
                        <div class="service-mobile-preview-config-block">
                            <span class="service-mobile-preview-config-label">{{ translate('app_type') }}</span>
                            <div class="service-preview-segment" role="radiogroup" aria-label="{{ translate('app_type') }}">
                                <label class="service-preview-segment-option is-active" for="serviceAppPreviewCustomer">
                                    <input type="radio" name="serviceAppPreviewType" id="serviceAppPreviewCustomer" value="customer" checked>
                                    <span class="service-preview-segment-label">{{ translate('Customer_App') }}</span>
                                </label>
                                <label class="service-preview-segment-option" for="serviceAppPreviewProvider">
                                    <input type="radio" name="serviceAppPreviewType" id="serviceAppPreviewProvider" value="provider">
                                    <span class="service-preview-segment-label">{{ translate('Provider_App') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="service-mobile-preview-config-block">
                            <span class="service-mobile-preview-config-label">{{ translate('Tab_view') }}</span>
                            <div class="service-preview-tab-pills" id="serviceAppPreviewTabPills" role="radiogroup" aria-label="{{ translate('Tab_view') }}">
                                <label class="service-preview-tab-pill is-active" for="serviceAppPreviewTabOverview">
                                    <input type="radio" name="serviceAppPreviewTab" id="serviceAppPreviewTabOverview" value="overview" checked>
                                    <span>{{ translate('overview') }}</span>
                                </label>
                                <label class="service-preview-tab-pill" for="serviceAppPreviewTabFaq">
                                    <input type="radio" name="serviceAppPreviewTab" id="serviceAppPreviewTabFaq" value="faq">
                                    <span>{{ translate('faq') }}</span>
                                </label>
                                <label class="service-preview-tab-pill service-preview-tab-pill--provider-only d-none" for="serviceAppPreviewTabPrice">
                                    <input type="radio" name="serviceAppPreviewTab" id="serviceAppPreviewTabPrice" value="price_table">
                                    <span>{{ translate('price_table') }}</span>
                                </label>
                                <label class="service-preview-tab-pill" for="serviceAppPreviewTabReviews">
                                    <input type="radio" name="serviceAppPreviewTab" id="serviceAppPreviewTabReviews" value="reviews">
                                    <span>{{ translate('reviews') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="service-mobile-preview-config-meta">
                            <span class="text-muted fs-12 d-block mb-1">{{ translate('language') }}</span>
                            <span class="badge bg-light text-dark border" id="serviceAppPreviewLangBadge">{{ translate('default') }}</span>
                        </div>

                        <p class="service-mobile-preview-hint mb-0">{{ translate('Unsaved_form_changes_are_included_in_this_preview') }}</p>
                    </aside>

                    <div class="service-mobile-preview-preview-col">
                        <span class="service-mobile-preview-config-label">{{ translate('Phone_view') }}</span>
                        <div class="service-app-phone-stage" id="serviceAppPreviewStage">
                            <div class="service-app-phone-scale-outer" id="serviceAppPreviewScaleOuter">
                                <div class="service-app-phone-preview"
                                     id="serviceAppPreviewRoot"
                                     data-currency-symbol="{{ $previewCurrencySymbol }}"
                                     data-placeholder="{{ asset('assets/admin-module/img/placeholder.png') }}"
                                     data-avg-rating="{{ $servicePreview?->avg_rating ?? 0 }}"
                                     data-rating-count="{{ $servicePreview?->rating_count ?? 0 }}"
                                     data-label-overview="{{ translate('overview') }}"
                                     data-label-faq="{{ translate('faq') }}"
                                     data-label-reviews="{{ translate('reviews') }}"
                                     data-label-price-table="{{ translate('price_table') }}"
                                     data-label-start-from="{{ translate('start_form') }}"
                                     data-label-book-now="{{ translate('book_now') }}"
                                     data-label-no-faq="{{ translate('No_FAQ_added_yet') }}"
                                     data-label-no-reviews="{{ translate('You don’t have any reviews yet.') }}"
                                     data-label-no-variants="{{ translate('No_variants_added_yet') }}">
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
                                                <span class="material-icons service-app-phone-cart d-none" id="serviceAppPreviewCartIcon">shopping_cart</span>
                                            </div>
                                        </div>
                                        <div class="service-app-phone-viewport">
                                            <div class="service-app-phone-scroll" id="serviceAppPreviewScreen"></div>
                                        </div>
                                        <div class="service-app-phone-home-indicator" aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer service-mobile-preview-footer">
                <button type="button" class="btn btn--secondary btn-sm" data-bs-dismiss="modal">{{ translate('close') }}</button>
                <button type="button" class="btn btn--primary btn-sm" id="serviceAppPreviewRefreshBtn">
                    <span class="material-icons fs-16 align-middle">refresh</span>
                    {{ translate('Refresh_preview') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* iPhone 14/15 logical portrait (matches Flutter design width) */
    :root {
        --service-preview-phone-w: 390px;
        --service-preview-phone-h: 844px;
    }

    /* Modal shell — config left, preview right */
    .service-mobile-preview-modal .modal-dialog.service-mobile-preview-dialog {
        max-width: min(1080px, calc(100vw - 1.5rem));
        width: calc(100% - 1.5rem);
        height: calc(100vh - 1.5rem);
        max-height: calc(100vh - 1.5rem);
        margin: 0.75rem auto;
    }
    .service-mobile-preview-modal.show .modal-dialog.service-mobile-preview-dialog {
        display: flex;
        flex-direction: column;
    }
    .service-mobile-preview-modal .modal-dialog.service-mobile-preview-dialog > .modal-content {
        flex: 1 1 auto;
        min-height: 0;
        max-height: 100%;
    }
    .service-mobile-preview-split {
        display: flex;
        gap: 1rem;
        align-items: stretch;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    .service-mobile-preview-config {
        flex: 0 0 240px;
        max-width: 260px;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-width: 0;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        padding-right: 4px;
    }
    .service-mobile-preview-config-block {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .service-mobile-preview-config-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--bs-secondary-color);
    }
    .service-mobile-preview-config-meta {
        padding-top: 0.5rem;
        border-top: 1px solid var(--bs-border-color);
        flex-shrink: 0;
    }
    .service-mobile-preview-preview-col {
        flex: 1 1 auto;
        min-width: 0;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        background: var(--bs-light, #f8f9fa);
        border-radius: 12px;
        border: 1px solid var(--bs-border-color);
        padding: 0.75rem;
        overflow: hidden;
    }
    .service-mobile-preview-preview-col > .service-mobile-preview-config-label {
        flex-shrink: 0;
        margin-bottom: 0;
    }
    .service-mobile-preview-preview-col .service-app-phone-stage {
        flex: 1 1 auto;
    }
    .service-preview-tab-pills {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .service-preview-tab-pill {
        position: relative;
        display: flex;
        align-items: center;
        margin: 0;
        padding: 8px 12px;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        background: var(--bs-body-bg, #fff);
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: border-color 0.15s, background 0.15s;
    }
    .service-preview-tab-pill:hover {
        border-color: var(--bs-primary);
    }
    .service-preview-tab-pill.is-active {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.08);
        color: var(--bs-primary);
        font-weight: 600;
    }
    .service-preview-tab-pill input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    @media (max-width: 767.98px) {
        .service-mobile-preview-split {
            flex-direction: column;
        }
        .service-mobile-preview-config {
            flex: 0 0 auto;
            max-width: none;
            max-height: 38vh;
        }
        .service-mobile-preview-preview-col {
            flex: 1 1 auto;
            min-height: 0;
        }
    }
    .service-mobile-preview-content {
        border-radius: 12px;
        max-height: calc(100vh - 1.5rem);
        display: flex;
        flex-direction: column;
    }
    .service-mobile-preview-header {
        padding: 1rem 1.25rem 0.5rem;
        border-bottom: 0;
        flex-shrink: 0;
    }
    .service-mobile-preview-body {
        padding: 0 1.25rem 1rem;
        overflow: hidden;
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }
    .service-mobile-preview-footer {
        padding: 0.75rem 1.25rem;
        border-top: 1px solid var(--bs-border-color);
        gap: 0.5rem;
        flex-shrink: 0;
    }

    /* App type segment (customer / provider) */
    .service-preview-segment {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }
    .service-preview-segment-option {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        padding: 10px 12px;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        background: var(--bs-body-bg, #fff);
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
        min-height: 44px;
    }
    .service-preview-segment-option:hover {
        border-color: var(--bs-primary);
    }
    .service-preview-segment-option.is-active {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.08);
        box-shadow: 0 0 0 1px var(--bs-primary);
    }
    .service-preview-segment-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    .service-preview-segment-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--bs-body-color);
        text-align: center;
        line-height: 1.25;
        pointer-events: none;
    }
    .service-preview-segment-option.is-active .service-preview-segment-label {
        color: var(--bs-primary);
    }

    .service-mobile-preview-hint {
        font-size: 11px;
        color: var(--bs-secondary-color);
        line-height: 1.4;
    }

    /* Phone stage — device always scaled to fit preview column (no outer scroll) */
    .service-app-phone-stage {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    .service-app-phone-scale-outer {
        flex-shrink: 0;
        width: var(--service-preview-phone-w);
        height: var(--service-preview-phone-h);
        position: relative;
    }
    .service-app-phone-preview {
        width: var(--service-preview-phone-w);
        height: var(--service-preview-phone-h);
        position: absolute;
        top: 0;
        left: 0;
        transform-origin: top left;
        transform: scale(var(--service-preview-scale, 1));
    }

    /* Device frame — exact mobile logical size (390 × 844) */
    .service-app-phone-frame {
        position: relative;
        width: 100%;
        height: var(--service-preview-phone-h);
        min-height: var(--service-preview-phone-h);
        max-height: var(--service-preview-phone-h);
        border: 10px solid #111;
        border-radius: 44px;
        background: #f5f5f7;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-sizing: border-box;
    }
    .service-app-phone-notch {
        position: absolute;
        top: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 96px;
        height: 22px;
        background: #111;
        border-radius: 0 0 14px 14px;
        z-index: 5;
        pointer-events: none;
    }
    .service-app-phone-chrome {
        flex-shrink: 0;
        background: #fff;
        z-index: 2;
    }
    .service-app-phone-statusbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 28px;
        padding: 0 14px;
        padding-top: 4px;
        font-size: 11px;
        font-weight: 600;
        color: #111;
    }
    .service-app-phone-status-icons::after {
        content: '●●●';
        font-size: 8px;
        letter-spacing: 1px;
        color: #333;
    }
    .service-app-phone-appbar {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 44px;
        padding: 0 8px 0 4px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }
    .service-app-phone-appbar .material-icons {
        font-size: 20px;
        color: #333;
        flex-shrink: 0;
    }
    .service-app-phone-appbar-title {
        flex: 1;
        min-width: 0;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .service-app-phone-cart {
        margin-left: auto;
    }
    .service-app-phone-viewport {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background: #f5f5f7;
    }
    .service-app-phone-scroll {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    /* In-phone layout: unified scroll — header scrolls away, tabs stick at top */
    .service-app-screen-root {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }
    .service-app-screen-unified-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }
    .service-app-tabs-sticky {
        position: sticky;
        top: 0;
        z-index: 3;
    }
    .service-app-phone-home-indicator {
        flex-shrink: 0;
        height: 16px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .service-app-phone-home-indicator::after {
        content: '';
        width: 100px;
        height: 4px;
        background: #ccc;
        border-radius: 4px;
    }

    /* —— Customer app —— */
    .service-app-layout-customer {
        min-width: 0;
    }
    .service-app-customer-hero {
        position: relative;
        height: 182px;
        flex-shrink: 0;
        background: #e0e0e0 center/cover no-repeat;
    }
    .service-app-customer-hero-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
    }
    .service-app-customer-hero-bottom {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 0 14px 14px;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }
    .service-app-customer-hero-title {
        position: relative;
        inset: auto;
        display: block;
        padding: 0;
    }
    .service-app-customer-hero-title span {
        color: #fff;
        font-weight: 700;
        font-size: 17px;
        text-align: left;
        line-height: 1.2;
        word-break: break-word;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        width: 100%;
    }
    .service-app-hero-chips {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 2px;
        scrollbar-width: none;
    }
    .service-app-hero-chips::-webkit-scrollbar { display: none; }
    .service-app-hero-chip {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
    }
    .service-app-hero-chip .material-icons {
        font-size: 12px;
        color: var(--chip-color, #fff);
    }
    .service-app-customer-body {
        position: relative;
        margin-top: -78px;
        padding: 0 14px 10px;
        flex-shrink: 0;
    }
    .service-app-info-card {
        background: #fff;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        display: flex;
        gap: 10px;
        align-items: flex-start;
        min-width: 0;
    }
    .service-app-info-thumb-wrap {
        flex-shrink: 0;
        width: 85px;
        height: 85px;
        border-radius: 8px;
        overflow: hidden;
        background: #eee;
    }
    .service-app-info-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .service-app-info-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .service-app-info-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 6px;
        min-width: 0;
    }
    .service-app-info-meta {
        min-width: 0;
        flex: 1;
    }
    .service-app-rating {
        font-size: 12px;
        color: #e67e22;
        font-weight: 600;
        white-space: nowrap;
    }
    .service-app-rating-muted {
        color: #999;
        font-weight: 400;
    }
    .service-app-price {
        color: var(--bs-primary);
        font-weight: 700;
        font-size: 14px;
        white-space: nowrap;
    }
    .service-app-short-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .service-app-book-btn {
        flex-shrink: 0;
        padding: 6px 10px;
        background: var(--bs-primary);
        color: #fff;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        line-height: 1.2;
    }

    /* —— Provider app —— */
    .service-app-layout-provider {
        min-width: 0;
    }
    .service-app-provider-banner {
        height: 146px;
        flex-shrink: 0;
        background: #e0e0e0 center/cover no-repeat;
    }
    .service-app-provider-header {
        background: #fff;
        padding: 16px 14px 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        flex-shrink: 0;
    }
    .service-app-provider-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        min-width: 0;
    }
    .service-app-provider-thumb {
        width: 97px;
        height: 97px;
        border-radius: 8px;
        object-fit: cover;
        background: #eee;
        flex-shrink: 0;
    }
    .service-app-provider-meta {
        flex: 1;
        min-width: 0;
    }
    .service-app-provider-name {
        font-weight: 700;
        font-size: 15px;
        line-height: 1.25;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .service-app-provider-price-row {
        font-size: 12px;
        margin-top: 2px;
    }
    .service-app-provider-price-row .label {
        color: #888;
    }
    .service-app-provider-short {
        font-size: 12px;
        color: #888;
        line-height: 1.35;
        margin: 8px 0 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    /* Tabs */
    .service-app-tabs {
        display: flex;
        background: #fff;
        border-bottom: 1px solid rgba(var(--bs-primary-rgb), 0.2);
        flex-shrink: 0;
        overflow: hidden;
        width: 100%;
    }
    .service-app-tab {
        flex: 1;
        min-width: 0;
        padding: 10px 4px;
        font-size: 11px;
        color: #999;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
    }
    .service-app-tab.active {
        color: var(--bs-primary);
        font-weight: 700;
        border-bottom-color: var(--bs-primary);
    }

    /* Overview (HTML) */
    .service-app-overview-panel {
        background: #fff;
        padding: 14px;
        min-width: 0;
        overflow: hidden;
    }
    .service-app-overview-html {
        font-size: 13px;
        line-height: 1.55;
        color: rgba(0, 0, 0, 0.8);
        word-wrap: break-word;
        overflow-wrap: anywhere;
        max-width: 100%;
    }
    .service-app-overview-html,
    .service-app-overview-html * {
        max-width: 100% !important;
        box-sizing: border-box;
    }
    .service-app-overview-html img,
    .service-app-overview-html video,
    .service-app-overview-html iframe {
        height: auto !important;
        display: block;
    }
    .service-app-overview-html table {
        width: 100% !important;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 12px;
    }
    .service-app-overview-html table td,
    .service-app-overview-html table th {
        border: 1px solid #e5e5e5;
        padding: 4px 6px;
        word-break: break-word;
    }
    .service-app-overview-html pre {
        white-space: pre-wrap;
        overflow-x: auto;
    }
    .service-app-overview-html ul,
    .service-app-overview-html ol {
        padding-left: 1.2rem;
        margin: 0.5rem 0;
    }
    .service-app-overview-html p {
        margin: 0 0 0.5rem;
    }
    .service-app-overview-empty {
        color: #aaa;
        font-style: italic;
        font-size: 12px;
        margin: 0;
        text-align: center;
        padding: 24px 12px;
    }
    .service-app-overview-html .sov-title { font-size: 13px; font-weight: 700; margin: 12px 0 6px; }
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
    .service-app-overview-html .sov-why-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
    .service-app-overview-html .sov-why-card {
        border-radius: 10px; padding: 8px; font-size: 10px;
        background: color-mix(in srgb, var(--sov-accent) 10%, white);
        border: 1px solid color-mix(in srgb, var(--sov-accent) 18%, white);
    }
    .service-app-overview-html .sov-why-card .material-icons { color: var(--sov-accent); font-size: 18px; }
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
    .service-app-price-table {
        background: #fff;
        padding: 12px 14px;
    }
    .service-app-price-table table {
        width: 100%;
        font-size: 12px;
        border-collapse: collapse;
    }
    .service-app-price-table th,
    .service-app-price-table td {
        padding: 8px 6px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    .service-app-price-table th {
        font-weight: 600;
        color: #666;
        font-size: 11px;
    }
    .service-app-price-table td:last-child {
        text-align: right;
        font-weight: 600;
        color: var(--bs-primary);
        white-space: nowrap;
    }
    .service-app-reviews-empty {
        text-align: center;
        padding: 32px 16px;
        color: #aaa;
        font-size: 12px;
    }
    .service-app-reviews-empty .material-icons {
        font-size: 40px;
        display: block;
        margin: 0 auto 8px;
        opacity: 0.35;
    }
</style>
