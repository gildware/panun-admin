@php
    $payload = $servicePreviewPayload ?? [];
    $previewCurrencySymbol = $payload['currencySymbol'] ?? '₹';
@endphp

<div class="service-detail-inline-preview service-detail-inline-preview--app-native" id="serviceDetailInlinePreview">
    <div class="service-detail-inline-preview__phone-wrap">
        <div class="service-app-phone-stage" id="serviceDetailPreviewStage">
            <div class="service-app-phone-scale-outer" id="serviceDetailPreviewScaleOuter">
                <div class="service-app-phone-preview"
                     id="serviceDetailPreviewRoot"
                     data-active-tab="overview"
                     data-currency-symbol="{{ $previewCurrencySymbol }}"
                     data-placeholder="{{ asset('assets/admin-module/img/placeholder.png') }}"
                     data-label-overview="{{ translate('overview') }}"
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

<script type="application/json" id="serviceDetailPreviewPayload">@json($payload)</script>

<style>
    .service-detail-inline-preview--app-native {
        display: flex;
        justify-content: center;
        padding: 0.5rem 0 1rem;
    }
    .service-detail-inline-preview__phone-wrap {
        width: 100%;
        max-width: 420px;
    }
    .service-detail-inline-preview--app-native .service-app-phone-stage {
        min-height: 640px;
    }
    .service-detail-inline-preview--app-native .service-app-phone-appbar {
        display: none;
    }
    .service-detail-inline-preview--app-native .service-app-phone-chrome {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 4;
        background: transparent;
        pointer-events: none;
    }
    .service-detail-inline-preview--app-native .service-app-phone-statusbar {
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
    }
    .service-detail-inline-preview--app-native .service-app-phone-status-icons::after {
        color: #fff;
    }
    .service-detail-inline-preview--app-native .service-app-customer-hero {
        height: 328px;
    }
    .service-detail-inline-preview--app-native .service-app-customer-body {
        margin-top: -78px;
    }
    .service-detail-inline-preview--app-native .service-app-customer-hero-sub {
        margin: 0;
        color: rgba(255, 255, 255, 0.9);
        font-size: 12px;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .service-detail-inline-preview--app-native .service-app-tab {
        cursor: pointer;
        user-select: none;
    }
    .service-detail-inline-preview--app-native .service-app-info-highlights {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 6px;
    }
    .service-detail-inline-preview--app-native .service-app-info-highlight {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 6px;
        border-radius: 6px;
        background: rgba(var(--bs-primary-rgb), 0.08);
        color: var(--bs-primary);
        font-size: 9px;
        font-weight: 600;
        line-height: 1.2;
    }
    .service-detail-inline-preview--app-native .service-app-info-highlight .material-icons {
        font-size: 12px;
    }
</style>
