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

    function getActiveDetailPreviewTab() {
        return $('input[name="serviceDetailPreviewTab"]:checked').val() || 'overview';
    }

    var OVERVIEW_ICON_MAP = {
        verified: 'verified',
        home: 'home',
        sparkle: 'auto_awesome',
        warranty: 'verified_user',
        calendar: 'calendar_month',
        location: 'location_on',
        tools: 'build',
        check: 'check_circle',
        door: 'door_front',
        building: 'apartment',
        shop: 'storefront',
        wood: 'forest',
        quality: 'workspace_premium',
        pricing: 'payments',
        support: 'support_agent',
        close: 'close',
        info: 'info',
    };

    var OVERVIEW_COLOR_MAP = {
        green: '#22C55E',
        blue: '#3B82F6',
        purple: '#8B5CF6',
        orange: '#F97316',
    };

    function overviewIconMaterial(key) {
        return OVERVIEW_ICON_MAP[key] || 'circle';
    }

    function overviewAccentColor(color) {
        return OVERVIEW_COLOR_MAP[color] || '#3B82F6';
    }

    function buildStructuredOverviewHtml(overview) {
        if (!overview || typeof overview !== 'object') {
            return '';
        }

        var html = '';

        if (overview.why_choose && overview.why_choose.items && overview.why_choose.items.length) {
            html += '<h6 class="sov-title">' + escapeHtml(overview.why_choose.title || 'Why Choose Panun Kaergar') + '</h6>';
            html += '<div class="sov-why-grid">';
            overview.why_choose.items.forEach(function (item) {
                var accent = overviewAccentColor(item.color);
                html += '<div class="sov-why-card" style="--sov-accent:' + accent + '"><span class="material-icons">'
                    + overviewIconMaterial(item.icon) + '</span><strong>'
                    + escapeHtml(item.title || '') + '</strong><span>'
                    + escapeHtml(item.description || '') + '</span></div>';
            });
            html += '</div>';
        }

        if (overview.service_process && overview.service_process.items && overview.service_process.items.length) {
            html += '<h6 class="sov-title">' + escapeHtml(overview.service_process.title || 'Service Process') + '</h6>';
            html += '<div class="sov-process-row">';
            overview.service_process.items.forEach(function (step, index) {
                html += '<div class="sov-process-step">';
                if (step.image) {
                    html += '<img src="' + escapeHtml(step.image) + '" alt="">';
                } else {
                    html += '<div class="sov-process-placeholder"><span class="material-icons">'
                        + overviewIconMaterial(step.icon) + '</span></div>';
                }
                html += '<span class="sov-step-no">' + (index + 1) + '</span>';
                html += '<span class="sov-step-label">' + escapeHtml(step.title || step.text || '') + '</span>';
                if (step.description) {
                    html += '<span class="sov-step-desc">' + escapeHtml(step.description) + '</span>';
                }
                html += '</div>';
            });
            html += '</div>';
        }

        if (overview.perfect_for && overview.perfect_for.items && overview.perfect_for.items.length) {
            html += '<h6 class="sov-title">' + escapeHtml(overview.perfect_for.title || 'Perfect For') + '</h6>';
            html += '<div class="sov-chips">';
            overview.perfect_for.items.forEach(function (chip) {
                html += '<span class="sov-chip"><span class="material-icons">'
                    + overviewIconMaterial(chip.icon) + '</span>'
                    + escapeHtml(chip.text || '') + '</span>';
            });
            html += '</div>';
        }

        if ((overview.whats_included && overview.whats_included.items && overview.whats_included.items.length)
            || (overview.whats_not_included && overview.whats_not_included.items && overview.whats_not_included.items.length)
            || (overview.good_to_know && overview.good_to_know.items && overview.good_to_know.items.length)) {
            html += '<div class="sov-info-stack">';
            if (overview.whats_included && overview.whats_included.items && overview.whats_included.items.length) {
                html += '<div class="sov-info-card sov-info-card--good"><div class="sov-info-head"><span class="material-icons">check_circle</span>'
                    + escapeHtml(overview.whats_included.title || "What's Included") + '</div>';
                overview.whats_included.items.forEach(function (item) {
                    html += '<div class="sov-info-line"><span class="material-icons">'
                        + overviewIconMaterial(item.icon || 'check') + '</span><span>'
                        + escapeHtml(item.title || item.text || '') + '</span></div>';
                });
                html += '</div>';
            }
            if (overview.whats_not_included && overview.whats_not_included.items && overview.whats_not_included.items.length) {
                html += '<div class="sov-info-card sov-info-card--bad"><div class="sov-info-head"><span class="material-icons">cancel</span>'
                    + escapeHtml(overview.whats_not_included.title || 'Not Included') + '</div>';
                overview.whats_not_included.items.forEach(function (item) {
                    html += '<div class="sov-info-line"><span class="material-icons">'
                        + overviewIconMaterial(item.icon || 'close') + '</span><span>'
                        + escapeHtml(item.title || item.text || '') + '</span></div>';
                });
                html += '</div>';
            }
            if (overview.good_to_know && overview.good_to_know.items && overview.good_to_know.items.length) {
                html += '<div class="sov-info-card sov-info-card--neutral"><div class="sov-info-head"><span class="material-icons">info</span>'
                    + escapeHtml(overview.good_to_know.title || 'Good To Know') + '</div>';
                overview.good_to_know.items.forEach(function (item) {
                    html += '<div class="sov-info-line"><span class="material-icons">'
                        + overviewIconMaterial(item.icon || 'info') + '</span><span>'
                        + escapeHtml(item.title || item.text || '') + '</span></div>';
                });
                html += '</div>';
            }
            html += '</div>';
        }

        return html;
    }

    function buildHeroInfoChipsHtml(data) {
        var chips = [];
        var rating = parseFloat(data.rating);
        if (!isNaN(rating) && rating > 0) {
            var count = parseInt(data.ratingCount, 10) || 0;
            var countLabel = count >= 100 ? count + '+' : String(count);
            chips.push({
                icon: 'star',
                color: '#F97316',
                label: rating.toFixed(1) + ' (' + countLabel + ')',
            });
        }

        var overview = data.overviewContent;
        if (overview && overview.top_icons && overview.top_icons.length) {
            overview.top_icons.forEach(function (item) {
                if (chips.length >= 4) {
                    return;
                }
                var text = (item.text || '').trim();
                if (!text) {
                    return;
                }
                chips.push({
                    icon: overviewIconMaterial(item.icon),
                    color: overviewAccentColor(item.color),
                    label: text,
                });
            });
        }

        if (!chips.length) {
            return '';
        }

        var html = '<div class="service-app-hero-chips">';
        chips.forEach(function (chip) {
            html += '<span class="service-app-hero-chip" style="--chip-color:' + chip.color + '">'
                + '<span class="material-icons">' + chip.icon + '</span>'
                + '<span>' + escapeHtml(chip.label) + '</span></span>';
        });
        html += '</div>';
        return html;
    }

    function buildOverviewPanel(descriptionHtml, overviewContent) {
        var structured = buildStructuredOverviewHtml(overviewContent);
        var inner = descriptionHtml && descriptionHtml.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        var hasLegacyText = inner && $.trim($('<div>').html(inner).text());

        if (!structured && !hasLegacyText) {
            inner = '<p class="service-app-overview-empty">' + escapeHtml('No description yet') + '</p>';
        } else if (structured) {
            inner = structured;
        } else if (hasLegacyText) {
            inner = '<div class="service-app-overview-html-legacy">' + inner + '</div>';
        }

        return (
            '<div class="service-app-overview-panel">' +
            '<div class="service-app-overview-html">' + inner + '</div>' +
            '</div>'
        );
    }

    function buildFaqPanel(emptyLabel, faqs) {
        if (!faqs || !faqs.length) {
            return (
                '<div class="service-app-overview-panel">' +
                '<p class="service-app-overview-empty">' + escapeHtml(emptyLabel) + '</p>' +
                '</div>'
            );
        }

        var items = faqs.map(function (faq) {
            return '<div class="service-app-faq-item"><strong>' + escapeHtml(faq.question || '')
                + '</strong><p>' + escapeHtml(faq.answer || '') + '</p></div>';
        }).join('');

        return '<div class="service-app-overview-panel">' + items + '</div>';
    }

    function tabIndexForValue(tabValue, appType, hasFaqs) {
        var order = appType === 'provider'
            ? ['overview', 'price_table', 'faq', 'reviews']
            : (hasFaqs ? ['overview', 'faq', 'reviews'] : ['overview', 'reviews']);
        var idx = order.indexOf(tabValue);
        return idx >= 0 ? idx : 0;
    }

    function tabLabelsForApp(appType, data) {
        if (appType === 'provider') {
            return [data.labelOverview, data.labelPriceTable, data.labelFaq, data.labelReviews];
        }
        var hasFaqs = !!(data.faqs && data.faqs.length);
        return hasFaqs
            ? [data.labelOverview, data.labelFaq, data.labelReviews]
            : [data.labelOverview, data.labelReviews];
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
                return buildFaqPanel(data.labelNoFaq, data.faqs);
            case 'reviews':
                return buildReviewsPanel(data.labelNoReviews);
            case 'price_table':
                return buildPriceTablePanel(data.variants, data.currencySymbol, data.labelNoVariants);
            case 'overview':
            default:
                return buildOverviewPanel(data.descriptionHtml, data.overviewContent);
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
        var hasFaqs = !!(data.faqs && data.faqs.length);
        var labels = tabLabelsForApp('customer', data);
        var activeIndex = tabIndexForValue(activeTab, 'customer', hasFaqs);

        var scrollHtml =
            '<div class="service-app-layout-customer">' +
            '<div class="service-app-customer-hero" style="' + coverStyle + '">' +
            '<div class="service-app-customer-hero-overlay"></div>' +
            '<div class="service-app-customer-hero-bottom">' +
            '<div class="service-app-customer-hero-title"><span>' + name + '</span></div>' +
            buildHeroInfoChipsHtml(data) +
            '</div>' +
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
        var activeIndex = tabIndexForValue(activeTab, 'provider', true);

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

    function collectPreviewDataFromDetailPayload($root) {
        var $payloadEl = $('#serviceDetailPreviewPayload');
        if (!$payloadEl.length) {
            return null;
        }

        var payload;
        try {
            payload = JSON.parse($payloadEl.text() || '{}');
        } catch (e) {
            return null;
        }

        var symbol = payload.currencySymbol || $root.data('currency-symbol') || '₹';
        var price = payload.price;

        return {
            name: payload.name || '',
            shortDescription: payload.shortDescription || '',
            descriptionHtml: payload.descriptionHtml || '',
            overviewContent: payload.overviewContent || null,
            faqs: payload.faqs || [],
            coverUrl: payload.coverUrl || '',
            thumbUrl: payload.thumbUrl || '',
            placeholder: $root.data('placeholder') || '',
            price: formatPrice(price, symbol),
            currencySymbol: symbol,
            variants: payload.variants || [],
            rating: Number(payload.rating || 0).toFixed(2),
            ratingCount: String(payload.ratingCount || 0),
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

    function syncTabPillsActiveState() {
        $('.service-preview-tab-pill').each(function () {
            var $pill = $(this);
            var checked = $pill.find('input[type="radio"]').prop('checked');
            $pill.toggleClass('is-active', !!checked);
        });
    }

    function updatePhoneScaleFor(stageId, outerId, rootId) {
        var $stage = $('#' + stageId);
        var $outer = $('#' + outerId);
        var $preview = $('#' + rootId);
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

    function updatePhoneScale() {
        updatePhoneScaleFor('serviceAppPreviewStage', 'serviceAppPreviewScaleOuter', 'serviceAppPreviewRoot');
        updatePhoneScaleFor('serviceDetailPreviewStage', 'serviceDetailPreviewScaleOuter', 'serviceDetailPreviewRoot');
    }

    function refreshDetailInlinePreview() {
        var $root = $('#serviceDetailPreviewRoot');
        var $screen = $('#serviceDetailPreviewScreen');
        if (!$root.length || !$screen.length) {
            return;
        }

        var data = collectPreviewDataFromDetailPayload($root);
        if (!data) {
            return;
        }

        var activeTab = getActiveDetailPreviewTab();
        if (activeTab === 'faq' && !(data.faqs && data.faqs.length)) {
            activeTab = 'overview';
        }
        $screen.html(renderCustomerPreview(data, activeTab));
        $screen.scrollTop(0);
        $screen.find('.service-app-screen-unified-scroll').scrollTop(0);
        requestAnimationFrame(function () {
            updatePhoneScaleFor('serviceDetailPreviewStage', 'serviceDetailPreviewScaleOuter', 'serviceDetailPreviewRoot');
            requestAnimationFrame(function () {
                updatePhoneScaleFor('serviceDetailPreviewStage', 'serviceDetailPreviewScaleOuter', 'serviceDetailPreviewRoot');
            });
        });
    }

    function initDetailInlinePreview() {
        if (!$('#serviceDetailPreviewRoot').length) {
            return;
        }
        syncTabPillsActiveState();
        refreshDetailInlinePreview();
    }

    function updateProviderTabVisibility(appType) {
        var isProvider = appType === 'provider';
        $('.service-preview-tab-pill--provider-only').toggleClass('d-none', !isProvider);
        if (!isProvider && $('input[name="serviceAppPreviewTab"]:checked').val() === 'price_table') {
            $('#serviceAppPreviewTabOverview').prop('checked', true);
        }
    }

    function updateProviderTabVisibility(appType) {
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

    $(document).on('change', 'input[name="serviceDetailPreviewTab"]', function () {
        syncTabPillsActiveState();
        refreshDetailInlinePreview();
    });

    $(document).on('click', '.service-preview-tab-pill', function () {
        var $radio = $(this).find('input[type="radio"]');
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
        if (typeof ResizeObserver === 'undefined') {
            return;
        }
        if (previewResizeObserver) {
            previewResizeObserver.disconnect();
        }
        previewResizeObserver = new ResizeObserver(function () {
            if ($('#serviceMobilePreviewModal').hasClass('show')) {
                updatePhoneScale();
            }
            if ($('#serviceDetailPreviewStage').length) {
                updatePhoneScaleFor('serviceDetailPreviewStage', 'serviceDetailPreviewScaleOuter', 'serviceDetailPreviewRoot');
            }
        });

        ['serviceAppPreviewStage', 'serviceDetailPreviewStage'].forEach(function (stageId) {
            var stageEl = document.getElementById(stageId);
            if (!stageEl) {
                return;
            }
            previewResizeObserver.observe(stageEl);
            var colEl = stageEl.closest('.service-mobile-preview-preview-col');
            if (colEl) {
                previewResizeObserver.observe(colEl);
            }
        });
    }

    bindPreviewResizeObserver();

    var resizeTimer;
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updatePhoneScale, 80);
    });

    $(function () {
        initDetailInlinePreview();
    });

    $(document).on('shown.bs.tab', 'button[data-bs-target="#mobile-preview-tab-pane"]', function () {
        initDetailInlinePreview();
    });
})(jQuery);
