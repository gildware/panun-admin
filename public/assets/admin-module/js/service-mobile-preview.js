/**
 * Live mobile app preview for service create/edit (customer + provider apps).
 */
(function ($) {
    'use strict';

    var PHONE_W = 390;
    var PHONE_H = 844;

    function getActiveLang() {
        var $active = $('.lang_link.active');
        if (!$active.length) {
            return 'default';
        }
        return ($active.attr('id') || 'default-link').replace(/-link$/, '');
    }

    function descriptionEditorId(lang) {
        return lang === 'default' ? 'default_description' : lang + '_description';
    }

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function safeCssUrl(url) {
        if (!url) {
            return '';
        }
        return url.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    function getImageFromUpload(inputName) {
        var $input = $('input[name="' + inputName + '"]');
        if (!$input.length) {
            return '';
        }
        var file = $input[0].files && $input[0].files[0];
        if (file) {
            return URL.createObjectURL(file);
        }
        var $img = $input.closest('.upload-file').find('img').first();
        return $img.attr('src') || '';
    }

    function getFieldValue(lang, field) {
        if (field === 'name') {
            var $name = lang === 'default' ? $('#default_name') : $('#' + lang + '_name');
            if ($name.length) {
                return ($name.val() || '').trim();
            }
            return ($('input.default-name').first().val() || '').trim();
        }
        if (field === 'short_description') {
            var $short = lang === 'default'
                ? $('.default_short_description')
                : $('.' + lang + '_short_description');
            return ($short.val() || '').trim();
        }
        return '';
    }

    function getDescriptionHtml(lang) {
        if (window.syncServiceDescriptionEditors) {
            window.syncServiceDescriptionEditors();
        }
        var editorId = descriptionEditorId(lang);
        if (typeof tinymce !== 'undefined') {
            var editor = tinymce.get(editorId);
            if (editor) {
                return editor.getContent() || '';
            }
        }
        var $ta = $('#' + editorId);
        return $ta.length ? ($ta.val() || '') : '';
    }

    function getLowestPrice() {
        var min = null;
        $('input[name^="variant_default_price"], input[id^="default-set-"]').each(function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v) && v > 0) {
                min = min === null ? v : Math.min(min, v);
            }
        });
        if (min === null) {
            var bid = parseFloat($('input[name="min_bidding_price"]').val());
            if (!isNaN(bid) && bid > 0) {
                min = bid;
            }
        }
        return min;
    }

    function getVariantsFromForm() {
        var variants = [];
        $('input[name="variants[]"]').each(function () {
            var key = ($(this).val() || '').trim();
            if (!key) {
                return;
            }
            var $row = $(this).closest('tr');
            var label = key.replace(/-/g, ' ');
            if ($row.length) {
                var thText = $.trim($row.find('th').first().clone().children().remove().end().text());
                if (thText) {
                    label = thText;
                }
            }
            var price = null;
            var $priceInput = $row.find('input[name^="variant_default_price"]').first();
            if ($priceInput.length) {
                price = parseFloat($priceInput.val());
            }
            variants.push({
                key: key,
                name: label,
                price: !isNaN(price) && price > 0 ? price : null,
            });
        });
        return variants;
    }

    function formatPrice(amount, symbol) {
        if (amount === null || isNaN(amount)) {
            return '—';
        }
        var formatted = Number(amount).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
        return symbol + formatted;
    }

    function getActivePreviewTab() {
        return $('input[name="serviceAppPreviewTab"]:checked').val() || 'overview';
    }

    function tabIndexForValue(tabValue, appType) {
        var order = appType === 'provider'
            ? ['overview', 'price_table', 'faq', 'reviews']
            : ['overview', 'faq', 'reviews'];
        var idx = order.indexOf(tabValue);
        return idx >= 0 ? idx : 0;
    }

    function tabLabelsForApp(appType, data) {
        if (appType === 'provider') {
            return [data.labelOverview, data.labelPriceTable, data.labelFaq, data.labelReviews];
        }
        return [data.labelOverview, data.labelFaq, data.labelReviews];
    }

    function buildTabsHtml(labels, activeIndex, sticky) {
        var stickyClass = sticky ? ' service-app-tabs-sticky' : '';
        var html = '<div class="service-app-tabs' + stickyClass + '">';
        labels.forEach(function (label, i) {
            html += '<span class="service-app-tab' + (i === activeIndex ? ' active' : '') + '" title="' + escapeHtml(label) + '">'
                + escapeHtml(label) + '</span>';
        });
        html += '</div>';
        return html;
    }

    function buildOverviewPanel(descriptionHtml) {
        var inner = descriptionHtml && descriptionHtml.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        var hasText = inner && $.trim($('<div>').html(inner).text());
        if (!hasText) {
            inner = '<p class="service-app-overview-empty">' + escapeHtml('No description yet') + '</p>';
        }
        return (
            '<div class="service-app-overview-panel">' +
            '<div class="service-app-overview-html">' + inner + '</div>' +
            '</div>'
        );
    }

    function buildFaqPanel(emptyLabel) {
        return (
            '<div class="service-app-overview-panel">' +
            '<p class="service-app-overview-empty">' + escapeHtml(emptyLabel) + '</p>' +
            '</div>'
        );
    }

    function buildReviewsPanel(emptyLabel) {
        return (
            '<div class="service-app-overview-panel service-app-reviews-empty">' +
            '<span class="material-icons">rate_review</span>' +
            '<p class="mb-0">' + escapeHtml(emptyLabel) + '</p>' +
            '</div>'
        );
    }

    function buildPriceTablePanel(variants, symbol, emptyLabel) {
        if (!variants.length) {
            return (
                '<div class="service-app-overview-panel">' +
                '<p class="service-app-overview-empty">' + escapeHtml(emptyLabel) + '</p>' +
                '</div>'
            );
        }
        var rows = variants.map(function (v) {
            return (
                '<tr><td>' + escapeHtml(v.name) + '</td>' +
                '<td>' + escapeHtml(formatPrice(v.price, symbol)) + '</td></tr>'
            );
        }).join('');
        return (
            '<div class="service-app-price-table">' +
            '<table><thead><tr><th>' + escapeHtml('Variant') + '</th><th>' + escapeHtml('Price') + '</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table></div>'
        );
    }

    function buildTabContent(tabValue, data) {
        switch (tabValue) {
            case 'faq':
                return buildFaqPanel(data.labelNoFaq);
            case 'reviews':
                return buildReviewsPanel(data.labelNoReviews);
            case 'price_table':
                return buildPriceTablePanel(data.variants, data.currencySymbol, data.labelNoVariants);
            case 'overview':
            default:
                return buildOverviewPanel(data.descriptionHtml);
        }
    }

    function wrapUnifiedScrollLayout(layoutClass, scrollHtml) {
        return (
            '<div class="service-app-screen-root ' + layoutClass + '">' +
            '<div class="service-app-screen-unified-scroll">' + scrollHtml + '</div>' +
            '</div>'
        );
    }

    function renderCustomerPreview(data, activeTab) {
        var coverStyle = data.coverUrl ? "background-image:url('" + safeCssUrl(data.coverUrl) + "')" : '';
        var thumbSrc = escapeHtml(data.thumbUrl || data.placeholder);
        var name = escapeHtml(data.name || 'Service name');
        var labels = tabLabelsForApp('customer', data);
        var activeIndex = tabIndexForValue(activeTab, 'customer');

        var scrollHtml =
            '<div class="service-app-layout-customer">' +
            '<div class="service-app-customer-hero" style="' + coverStyle + '">' +
            '<div class="service-app-customer-hero-overlay"></div>' +
            '<div class="service-app-customer-hero-title"><span>' + name + '</span></div>' +
            '</div>' +
            '<div class="service-app-customer-body">' +
            '<div class="service-app-info-card">' +
            '<div class="service-app-info-thumb-wrap">' +
            '<img class="service-app-info-thumb" src="' + thumbSrc + '" alt="">' +
            '</div>' +
            '<div class="service-app-info-main">' +
            '<div class="service-app-info-top">' +
            '<div class="service-app-info-meta">' +
            '<div class="service-app-rating">★ ' + escapeHtml(data.rating) +
            ' <span class="service-app-rating-muted">(' + escapeHtml(data.ratingCount) + ')</span></div>' +
            '<div class="service-app-price">' + escapeHtml(data.price) + '</div>' +
            '</div>' +
            '<span class="service-app-book-btn">' + escapeHtml(data.labelBookNow) + '</span>' +
            '</div>' +
            '<div class="service-app-short-desc">' + escapeHtml(data.shortDescription || '') + '</div>' +
            '</div></div></div>' +
            buildTabsHtml(labels, activeIndex, true) +
            buildTabContent(activeTab, data) +
            '</div>';

        return wrapUnifiedScrollLayout('service-app-layout-customer', scrollHtml);
    }

    function renderProviderPreview(data, activeTab) {
        var coverStyle = data.coverUrl ? "background-image:url('" + safeCssUrl(data.coverUrl) + "')" : '';
        var thumbSrc = escapeHtml(data.thumbUrl || data.placeholder);
        var labels = tabLabelsForApp('provider', data);
        var activeIndex = tabIndexForValue(activeTab, 'provider');

        var scrollHtml =
            '<div class="service-app-layout-provider">' +
            '<div class="service-app-provider-banner" style="' + coverStyle + '"></div>' +
            '<div class="service-app-provider-header">' +
            '<div class="service-app-provider-row">' +
            '<img class="service-app-provider-thumb" src="' + thumbSrc + '" alt="">' +
            '<div class="service-app-provider-meta">' +
            '<div class="service-app-provider-name">' + escapeHtml(data.name || 'Service name') + '</div>' +
            '<div class="service-app-rating">★ ' + escapeHtml(data.rating) +
            ' <span class="service-app-rating-muted">(' + escapeHtml(data.ratingCount) + ')</span></div>' +
            '<div class="service-app-provider-price-row">' +
            '<span class="label">' + escapeHtml(data.labelStartFrom) + ' </span>' +
            '<span class="service-app-price">' + escapeHtml(data.price) + '</span>' +
            '</div></div></div>' +
            '<p class="service-app-provider-short">' + escapeHtml(data.shortDescription || '') + '</p>' +
            '</div>' +
            buildTabsHtml(labels, activeIndex, true) +
            buildTabContent(activeTab, data) +
            '</div>';

        return wrapUnifiedScrollLayout('service-app-layout-provider', scrollHtml);
    }

    function collectPreviewData($root) {
        var lang = getActiveLang();
        var lowest = getLowestPrice();
        var symbol = $root.data('currency-symbol') || '$';
        var rating = parseFloat($root.data('avg-rating')) || 0;
        var ratingCount = parseInt($root.data('rating-count'), 10) || 0;

        return {
            lang: lang,
            name: getFieldValue(lang, 'name'),
            shortDescription: getFieldValue(lang, 'short_description'),
            descriptionHtml: getDescriptionHtml(lang),
            coverUrl: getImageFromUpload('cover_image'),
            thumbUrl: getImageFromUpload('thumbnail'),
            placeholder: $root.data('placeholder') || '',
            price: formatPrice(lowest, symbol),
            currencySymbol: symbol,
            variants: getVariantsFromForm(),
            rating: rating.toFixed(2),
            ratingCount: String(ratingCount),
            labelOverview: $root.data('label-overview') || 'Overview',
            labelFaq: $root.data('label-faq') || 'FAQ',
            labelReviews: $root.data('label-reviews') || 'Reviews',
            labelPriceTable: $root.data('label-price-table') || 'Price table',
            labelStartFrom: $root.data('label-start-from') || 'Start from',
            labelBookNow: $root.data('label-book-now') || 'Book now',
            labelNoFaq: $root.data('label-no-faq') || 'No FAQ added yet',
            labelNoReviews: $root.data('label-no-reviews') || 'No reviews yet',
            labelNoVariants: $root.data('label-no-variants') || 'No variants added yet',
        };
    }

    function syncSegmentActiveState(selector) {
        $(selector).each(function () {
            var $opt = $(this);
            var checked = $opt.find('input[type="radio"]').prop('checked');
            $opt.toggleClass('is-active', !!checked);
        });
    }

    function syncTabPillsActiveState() {
        $('.service-preview-tab-pill').each(function () {
            var $pill = $(this);
            $pill.toggleClass('is-active', $pill.find('input[name="serviceAppPreviewTab"]').prop('checked'));
        });
    }

    function updateProviderTabVisibility(appType) {
        var isProvider = appType === 'provider';
        $('.service-preview-tab-pill--provider-only').toggleClass('d-none', !isProvider);
        if (!isProvider && $('input[name="serviceAppPreviewTab"]:checked').val() === 'price_table') {
            $('#serviceAppPreviewTabOverview').prop('checked', true);
        }
    }

    function updatePhoneScale() {
        var $stage = $('#serviceAppPreviewStage');
        var $outer = $('#serviceAppPreviewScaleOuter');
        var $preview = $('#serviceAppPreviewRoot');
        if (!$stage.length || !$outer.length || !$preview.length) {
            return;
        }

        var stageEl = $stage[0];
        if (!stageEl || stageEl.clientWidth < 1 || stageEl.clientHeight < 1) {
            return;
        }

        var pad = 4;
        var scale = Math.min(
            1,
            (stageEl.clientWidth - pad) / PHONE_W,
            (stageEl.clientHeight - pad) / PHONE_H
        );
        scale = Math.max(0.28, scale);

        $outer.css({
            width: Math.round(PHONE_W * scale) + 'px',
            height: Math.round(PHONE_H * scale) + 'px',
        });
        $preview.css('--service-preview-scale', String(scale));
    }

    function refreshPreview() {
        var $root = $('#serviceAppPreviewRoot');
        var $screen = $('#serviceAppPreviewScreen');
        if (!$root.length || !$screen.length) {
            return;
        }

        var appType = $('input[name="serviceAppPreviewType"]:checked').val() || 'customer';
        var activeTab = getActivePreviewTab();
        if (appType !== 'provider' && activeTab === 'price_table') {
            activeTab = 'overview';
        }

        var data = collectPreviewData($root);

        $('#serviceAppPreviewLangBadge').text(
            data.lang === 'default' ? 'Default' : data.lang.toUpperCase()
        );
        $('#serviceAppPreviewCartIcon').toggleClass('d-none', appType !== 'customer');
        updateProviderTabVisibility(appType);

        $screen.html(
            appType === 'provider'
                ? renderProviderPreview(data, activeTab)
                : renderCustomerPreview(data, activeTab)
        );

        $screen.scrollTop(0);
        $screen.find('.service-app-screen-unified-scroll').scrollTop(0);
        requestAnimationFrame(function () {
            updatePhoneScale();
            requestAnimationFrame(updatePhoneScale);
        });
    }

    function openPreviewModal() {
        if (window.syncServiceDescriptionEditors) {
            window.syncServiceDescriptionEditors();
        }
        refreshPreview();
        var modalEl = document.getElementById('serviceMobilePreviewModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    $(document).on('click', '.js-service-mobile-preview', function (e) {
        e.preventDefault();
        openPreviewModal();
    });

    $(document).on('click', '#serviceAppPreviewRefreshBtn', function () {
        refreshPreview();
    });

    $(document).on('change', 'input[name="serviceAppPreviewType"]', function () {
        syncSegmentActiveState('.service-preview-segment-option');
        refreshPreview();
    });

    $(document).on('click', '.service-preview-segment-option', function () {
        var $radio = $(this).find('input[type="radio"]');
        if ($radio.length && !$radio.prop('checked')) {
            $radio.prop('checked', true).trigger('change');
        }
    });

    $(document).on('change', 'input[name="serviceAppPreviewTab"]', function () {
        syncTabPillsActiveState();
        refreshPreview();
    });

    $(document).on('click', '.service-preview-tab-pill', function () {
        var $radio = $(this).find('input[name="serviceAppPreviewTab"]');
        if ($radio.length && !$radio.prop('checked')) {
            $radio.prop('checked', true).trigger('change');
        }
    });

    $('#serviceMobilePreviewModal').on('shown.bs.modal', function () {
        syncSegmentActiveState('.service-preview-segment-option');
        syncTabPillsActiveState();
        refreshPreview();
        setTimeout(updatePhoneScale, 0);
        setTimeout(updatePhoneScale, 120);
    });

    var previewResizeObserver = null;
    function bindPreviewResizeObserver() {
        var stageEl = document.getElementById('serviceAppPreviewStage');
        if (!stageEl || typeof ResizeObserver === 'undefined') {
            return;
        }
        if (previewResizeObserver) {
            previewResizeObserver.disconnect();
        }
        previewResizeObserver = new ResizeObserver(function () {
            if ($('#serviceMobilePreviewModal').hasClass('show')) {
                updatePhoneScale();
            }
        });
        previewResizeObserver.observe(stageEl);
        var colEl = stageEl.closest('.service-mobile-preview-preview-col');
        if (colEl) {
            previewResizeObserver.observe(colEl);
        }
    }

    bindPreviewResizeObserver();

    var resizeTimer;
    $(window).on('resize', function () {
        if (!$('#serviceMobilePreviewModal').hasClass('show')) {
            return;
        }
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updatePhoneScale, 80);
    });
})(jQuery);
