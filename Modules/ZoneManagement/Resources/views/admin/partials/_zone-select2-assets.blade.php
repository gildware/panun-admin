@once
    @push('css_or_js')
        <style>
            .zone-select2-option .zone-select2-name {
                font-weight: 500;
                line-height: 1.35;
            }
            .zone-select2-option .zone-select2-desc {
                font-size: 0.8125rem;
                line-height: 1.35;
                margin-top: 2px;
                color: #6c757d;
                white-space: normal;
            }
            .select2-results__option .zone-select2-option {
                display: block;
            }
            .zone-select2-highlight {
                background-color: #fff3cd;
                color: inherit;
                padding: 0 1px;
                border-radius: 2px;
                font-weight: 600;
            }
            .select2-results__option--highlighted .zone-select2-highlight {
                background-color: #ffe69c;
            }
        </style>
    @endpush
    @push('script')
        <script>
            "use strict";
            (function ($) {
                function zoneDataFromElement(el) {
                    var $el = $(el);
                    return {
                        prefix: String($el.data('zonePrefix') || ''),
                        name: String($el.data('zoneName') || ''),
                        description: String($el.data('zoneDescription') || '')
                    };
                }

                function zoneSearchHaystack(z) {
                    return ((z.prefix || '') + (z.name || '') + ' ' + (z.description || '')).toLowerCase();
                }

                function escapeHtml(text) {
                    return String(text || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function escapeRegExp(text) {
                    return String(text || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }

                function highlightZoneText(text, term) {
                    var safe = escapeHtml(text);
                    var q = String(term || '').trim();
                    if (!q) {
                        return safe;
                    }
                    try {
                        var re = new RegExp('(' + escapeRegExp(q) + ')', 'gi');
                        return safe.replace(re, '<mark class="zone-select2-highlight">$1</mark>');
                    } catch (e) {
                        return safe;
                    }
                }

                function zoneSelect2Matcher(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }
                    if (typeof data.element === 'undefined') {
                        return null;
                    }
                    var z = zoneDataFromElement(data.element);
                    var term = String(params.term || '').toLowerCase();
                    if (zoneSearchHaystack(z).indexOf(term) === -1) {
                        return null;
                    }
                    data._zoneSearchTerm = params.term;
                    return data;
                }

                function zoneSelect2FormatResult(data) {
                    if (!data.id) {
                        return data.text;
                    }
                    var z = zoneDataFromElement(data.element);
                    var term = data._zoneSearchTerm || '';
                    var nameLine = (z.prefix || '') + (z.name || data.text || '');
                    if (!z.description) {
                        return $('<div class="zone-select2-name"></div>').html(highlightZoneText(nameLine, term));
                    }
                    var $wrap = $('<div class="zone-select2-option"></div>');
                    $wrap.append($('<div class="zone-select2-name"></div>').html(highlightZoneText(nameLine, term)));
                    $wrap.append($('<div class="zone-select2-desc"></div>').html(highlightZoneText(z.description, term)));
                    return $wrap;
                }

                function zoneSelect2FormatSelection(data) {
                    if (!data.id) {
                        return data.text;
                    }
                    var z = zoneDataFromElement(data.element);
                    return (z.prefix || '') + (z.name || data.text || '');
                }

                window.zoneSelect2Config = function (extra) {
                    var base = {
                        templateResult: zoneSelect2FormatResult,
                        templateSelection: zoneSelect2FormatSelection,
                        matcher: zoneSelect2Matcher,
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    };
                    return $.extend(true, {}, base, extra || {});
                };

                window.initZoneTreeSelect2 = function ($select, extra) {
                    if (!$select || !$select.length) {
                        return;
                    }
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.select2(window.zoneSelect2Config($.extend({width: '100%'}, extra || {})));
                };
            })(jQuery);
        </script>
    @endpush
@endonce
