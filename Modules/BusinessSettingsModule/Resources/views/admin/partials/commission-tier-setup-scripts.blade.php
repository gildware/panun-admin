{{-- Requires: $previewCurrencySymbol, $previewCurrencyCode, $commissionTierBindBusinessCheckbox (bool) --}}
@php
    $commissionPreviewI18n = [
        'currencySymbol' => $previewCurrencySymbol,
        'currencyCode' => $previewCurrencyCode,
        'plain' => [
            'serviceFixed' => translate('commission_plain_service_fixed'),
            'serviceTiered' => translate('commission_plain_service_tiered'),
            'spareFixed' => translate('commission_plain_spare_fixed'),
            'spareTiered' => translate('commission_plain_spare_tiered'),
        ],
        'previewTierLabel' => translate('Preview_tier_example_label'),
        'previewExampleFixedLine' => translate('Preview_example_single_fixed_line'),
        'combinedLabel' => translate('Preview_scenario_mixed_booking'),
        'previewNoTiers' => translate('Preview_no_tiers_configured'),
        'noTier' => translate('No_matching_tier_for_amount'),
        'noRule' => translate('No_rule'),
        'unlimited' => translate('preview_unlimited_upper'),
        'percentOfLine' => translate('percent_of_line_total'),
        'fixedFromLine' => translate('Fixed_X_from_line'),
        'flatPerLine' => translate('Flat_X_per_line'),
        'ruleFixedShort' => translate('Fixed_fee_each_line'),
    ];
@endphp
<script>
    $(function () {
        var COMMISSION_PREVIEW_I18N = @json($commissionPreviewI18n);

        function syncBusinessModelCommissionUi() {
            var on = $('#provider_commision').is(':checked');
            $('#commission-tier-settings').toggleClass('d-none', !on);
        }

        function reindexCommissionTierRows($tbody) {
            $tbody.find('.js-commission-tier-row').each(function (idx) {
                $(this).find('input[name], select[name]').each(function () {
                    var n = $(this).attr('name');
                    if (!n) return;
                    $(this).attr('name', n.replace(/\[\d+]/, '[' + idx + ']'));
                });
            });
            var cnt = $tbody.find('.js-commission-tier-row').length;
            $tbody.find('.js-remove-tier-row').prop('disabled', cnt < 2);
        }

        function moneyFmt(n) {
            var v = Number(n);
            if (isNaN(v)) v = 0;
            return COMMISSION_PREVIEW_I18N.currencySymbol + v.toFixed(2) + (COMMISSION_PREVIEW_I18N.currencyCode ? (' ' + COMMISSION_PREVIEW_I18N.currencyCode) : '');
        }

        function readCommissionGroup(kind) {
            var mode = $('input[name="commission_' + kind + '_mode"]:checked').val() || 'tiered';
            if (mode === 'fixed') {
                var fa = parseFloat($('input[name="commission_' + kind + '_fixed_amount"]').val());
                return { mode: 'fixed', fixed_amount: isNaN(fa) ? 0 : fa, tiers: [] };
            }
            var prefix = kind === 'service' ? 'commission_service_tiers' : 'commission_spare_tiers';
            var tiers = [];
            $('.js-commission-tiers-tbody[data-field-prefix="' + prefix + '"] tr.js-commission-tier-row').each(function () {
                var $tr = $(this);
                var from = parseFloat($tr.find('.js-tier-from-input').val());
                if (isNaN(from)) from = 0;
                var inf = $tr.find('.js-tier-to-infinite').prop('checked');
                var to = inf ? null : (parseFloat($tr.find('.js-tier-to-input').val()) || 0);
                var amountType = $tr.find('select[name*="[amount_type]"]').val() || 'percentage';
                var amt = parseFloat($tr.find('.js-tier-amount-input').val());
                if (isNaN(amt)) amt = 0;
                tiers.push({ from: from, to: to, amount_type: amountType, amount: amt });
            });
            return { mode: 'tiered', fixed_amount: 0, tiers: tiers };
        }

        function calcLinePreview(lineAmount, group) {
            lineAmount = Math.max(0, parseFloat(lineAmount) || 0);
            if (group.mode === 'fixed') {
                var admin = Math.min(Math.max(0, parseFloat(group.fixed_amount) || 0), lineAmount);
                var provider = Math.max(0, lineAmount - admin);
                return {
                    admin: admin,
                    provider: provider,
                    rule: COMMISSION_PREVIEW_I18N.ruleFixedShort,
                    band: COMMISSION_PREVIEW_I18N.flatPerLine.replace(':amount', admin.toFixed(2))
                };
            }
            var tiers = (group.tiers || []).slice().sort(function (a, b) { return a.from - b.from; });
            if (!tiers.length) {
                return { admin: 0, provider: lineAmount, rule: COMMISSION_PREVIEW_I18N.noRule, band: '—' };
            }
            var matched = null;
            for (var i = 0; i < tiers.length; i++) {
                var t = tiers[i];
                if (lineAmount < t.from) continue;
                if (t.to != null && lineAmount > t.to) continue;
                matched = t;
                break;
            }
            if (!matched) {
                return { admin: 0, provider: lineAmount, rule: COMMISSION_PREVIEW_I18N.noTier, band: '—' };
            }
            var admin = 0;
            if (matched.amount_type === 'fixed') {
                admin = Math.min(matched.amount || 0, lineAmount);
            } else {
                admin = lineAmount * ((matched.amount || 0) / 100);
            }
            var provider = Math.max(0, lineAmount - admin);
            var band = Number(matched.from).toFixed(2) + ' – ' + (matched.to == null ? COMMISSION_PREVIEW_I18N.unlimited : Number(matched.to).toFixed(2));
            var rule;
            if (matched.amount_type === 'fixed') {
                rule = COMMISSION_PREVIEW_I18N.fixedFromLine.replace(':amount', Number(matched.amount || 0).toFixed(2));
            } else {
                var amtStr = String(matched.amount || 0);
                if (amtStr.indexOf('.') >= 0) {
                    amtStr = parseFloat(amtStr).toFixed(2).replace(/\.?0+$/, '');
                }
                rule = amtStr + COMMISSION_PREVIEW_I18N.percentOfLine;
            }
            return { admin: admin, provider: provider, rule: rule, band: band };
        }

        function formatTierBandForPreview(t) {
            var a = Number(t.from || 0).toFixed(2);
            var b = (t.to == null || t.to === '') ? COMMISSION_PREVIEW_I18N.unlimited : Number(t.to).toFixed(2);
            return a + ' – ' + b;
        }

        function exampleAmountInTier(t) {
            var from = parseFloat(t.from);
            if (isNaN(from)) from = 0;
            var to = t.to;
            if (to == null || to === '') {
                return Math.max(from, from === 0 ? 150 : from + 1);
            }
            var hi = parseFloat(to);
            if (isNaN(hi) || hi <= from) {
                return from;
            }
            return from + (hi - from) / 2;
        }

        function buildPreviewRowsForGroup(group) {
            if (group.mode === 'fixed') {
                var amt = 250;
                return [{ label: COMMISSION_PREVIEW_I18N.previewExampleFixedLine, amount: amt }];
            }
            var tiers = (group.tiers || []).slice().sort(function (a, b) {
                return (parseFloat(a.from) || 0) - (parseFloat(b.from) || 0);
            });
            if (!tiers.length) {
                return [{ label: COMMISSION_PREVIEW_I18N.previewNoTiers, amount: 0 }];
            }
            return tiers.map(function (t, idx) {
                return {
                    label: COMMISSION_PREVIEW_I18N.previewTierLabel
                        .replace(':n', String(idx + 1))
                        .replace(':band', formatTierBandForPreview(t)),
                    amount: exampleAmountInTier(t)
                };
            });
        }

        function commissionPreviewPanelOpen() {
            return $('#commission-tier-settings .js-commission-preview-panel').toArray().some(function (el) {
                return !$(el).hasClass('d-none');
            });
        }

        function refreshCommissionPreviewsWhenOpen() {
            if (commissionPreviewPanelOpen()) {
                refreshCommissionPreviews();
            }
        }

        function refreshCommissionPreviews() {
            var gSvc = readCommissionGroup('service');
            var gSpr = readCommissionGroup('spare');
            $('.js-commission-plain-english[data-plain-target="service"]').text(
                gSvc.mode === 'fixed' ? COMMISSION_PREVIEW_I18N.plain.serviceFixed : COMMISSION_PREVIEW_I18N.plain.serviceTiered
            );
            $('.js-commission-plain-english[data-plain-target="spare"]').text(
                gSpr.mode === 'fixed' ? COMMISSION_PREVIEW_I18N.plain.spareFixed : COMMISSION_PREVIEW_I18N.plain.spareTiered
            );

            function fillTable(type, group) {
                var $tb = $('.js-commission-preview-tbody[data-preview-type="' + type + '"]');
                $tb.empty();
                var scenarios = buildPreviewRowsForGroup(group);
                scenarios.forEach(function (row) {
                    var p = calcLinePreview(row.amount, group);
                    $tb.append($('<tr>')
                        .append($('<td>').text(row.label))
                        .append($('<td class="text-end">').text(moneyFmt(row.amount)))
                        .append($('<td>').text(p.band))
                        .append($('<td>').text(p.rule))
                        .append($('<td class="text-end">').text(moneyFmt(p.admin)))
                        .append($('<td class="text-end">').text(moneyFmt(p.provider)))
                    );
                });
            }

            fillTable('service', gSvc);
            fillTable('spare', gSpr);

            var rowsSvc = buildPreviewRowsForGroup(gSvc);
            var rowsSpr = buildPreviewRowsForGroup(gSpr);
            var cSvcAmt = rowsSvc.length && rowsSvc[0].amount > 0 ? rowsSvc[0].amount : 250;
            var cSprAmt = rowsSpr.length && rowsSpr[0].amount > 0 ? rowsSpr[0].amount : 120;
            if (gSvc.mode === 'tiered' && rowsSvc.length && rowsSvc[0].label === COMMISSION_PREVIEW_I18N.previewNoTiers) {
                cSvcAmt = 250;
            }
            if (gSpr.mode === 'tiered' && rowsSpr.length && rowsSpr[0].label === COMMISSION_PREVIEW_I18N.previewNoTiers) {
                cSprAmt = 120;
            }

            var ps = calcLinePreview(cSvcAmt, gSvc);
            var pp = calcLinePreview(cSprAmt, gSpr);
            var totalCust = cSvcAmt + cSprAmt;
            var totalAd = ps.admin + pp.admin;
            var totalPr = ps.provider + pp.provider;
            var $tbc = $('.js-commission-preview-tbody[data-preview-type="combined"]');
            $tbc.empty();
            $tbc.append($('<tr>')
                .append($('<td>').text(COMMISSION_PREVIEW_I18N.combinedLabel))
                .append($('<td class="text-end">').text(moneyFmt(cSvcAmt)))
                .append($('<td class="text-end">').text(moneyFmt(cSprAmt)))
                .append($('<td class="text-end">').text(moneyFmt(totalCust)))
                .append($('<td class="text-end">').text(moneyFmt(totalAd)))
                .append($('<td class="text-end">').text(moneyFmt(totalPr)))
            );
        }

        $(document).on('click', '.js-toggle-commission-preview', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $card = $btn.closest('.card');
            var $panel = $card.find('.js-commission-preview-panel').first();
            $panel.toggleClass('d-none');
            var open = !$panel.hasClass('d-none');
            $btn.attr('aria-expanded', open ? 'true' : 'false');
            if (open) {
                refreshCommissionPreviews();
            }
        });

        $(document).on('change', '.js-tier-to-infinite', function () {
            var $inp = $(this).closest('tr').find('.js-tier-to-input');
            if ($(this).is(':checked')) {
                $inp.val('').prop('disabled', true);
            } else {
                $inp.prop('disabled', false);
            }
            refreshCommissionPreviewsWhenOpen();
        });

        $(document).on('change', '.js-commission-mode-radio', function () {
            var g = $(this).data('group');
            if (g === 'service') {
                var fixed = $('#commission_service_mode_fixed').is(':checked');
                $('.commission-service-fixed').toggleClass('d-none', !fixed);
                $('.commission-service-tiered').toggleClass('d-none', fixed);
            }
            if (g === 'spare') {
                var fixedSp = $('#commission_spare_mode_fixed').is(':checked');
                $('.commission-spare-fixed').toggleClass('d-none', !fixedSp);
                $('.commission-spare-tiered').toggleClass('d-none', fixedSp);
            }
            refreshCommissionPreviewsWhenOpen();
        });

        $(document).on('click', '.js-add-tier-row', function () {
            var prefix = $(this).data('field-prefix');
            var $tbody = $('.js-commission-tiers-tbody[data-field-prefix="' + prefix + '"]');
            var $first = $tbody.find('.js-commission-tier-row').first();
            if (!$first.length) return;
            var $clone = $first.clone();
            $clone.find('input[type="number"]').val('');
            $clone.find('.js-tier-to-infinite').prop('checked', true);
            $clone.find('.js-tier-to-input').val('').prop('disabled', true);
            $clone.find('select[name*="amount_type"]').val('percentage');
            $tbody.append($clone);
            reindexCommissionTierRows($tbody);
            refreshCommissionPreviewsWhenOpen();
        });

        $(document).on('click', '.js-remove-tier-row', function () {
            var $tbody = $(this).closest('.js-commission-tiers-tbody');
            if ($tbody.find('.js-commission-tier-row').length < 2) return;
            $(this).closest('tr').remove();
            reindexCommissionTierRows($tbody);
            refreshCommissionPreviewsWhenOpen();
        });

        $('#commission-tier-settings').on('input change', 'input, select', function () {
            refreshCommissionPreviewsWhenOpen();
        });

        @if(!empty($commissionTierBindBusinessCheckbox))
        $('#provider_commision').on('change', syncBusinessModelCommissionUi);
        syncBusinessModelCommissionUi();
        @endif
        $('.js-commission-tiers-tbody').each(function () {
            reindexCommissionTierRows($(this));
        });
    });
</script>
