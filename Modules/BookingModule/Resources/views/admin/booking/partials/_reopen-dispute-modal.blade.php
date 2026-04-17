{{-- Disputed reopen: refund split + cancel/refund status + close reopen case --}}
@php
    $__rsSplit = booking_customer_paid_split_by_receiver($booking);
    $__rsPaid = round((float) get_booking_total_paid($booking), 2);
    $__rsCompanyPool = round((float) ($__rsSplit['company'] ?? 0) + (float) ($__rsSplit['unassigned'] ?? 0), 2);
    $__rsProviderPool = round((float) ($__rsSplit['provider'] ?? 0), 2);
    $__rsSvcFull = round(max(0.0, (float) get_booking_commissionable_amount($booking)), 2);
    $__rsSpareFull = round(max(0.0, (float) get_booking_spare_parts_amount($booking)), 2);
    $__rsFullTier = booking_reopen_disputed_tier_split_for_amounts($booking, $__rsSvcFull, $__rsSpareFull);
    $__rsDefaultRetained = max(0, round($__rsPaid - $__rsCompanyPool - $__rsProviderPool, 2));
    $__rsTierSetup = resolve_commission_tier_setup_for_booking($booking, $booking->provider_id);
    $__rsOnRetained = booking_reopen_disputed_commission_on_customer_retained($booking, $__rsDefaultRetained, $__rsPaid);
    $__rsFinalAdmin = (float) ($__rsOnRetained['admin_commission'] ?? 0);
    $__rsFinalPr = (float) ($__rsOnRetained['provider_earning'] ?? 0);
    $__rsFinalSvcRetained = (float) ($__rsOnRetained['services_retained'] ?? 0);
    $__rsFinalSpareRetained = (float) ($__rsOnRetained['spare_parts_retained'] ?? 0);
    $__rsFinalAdminSvc = (float) ($__rsOnRetained['services_admin_commission'] ?? 0);
    $__rsFinalAdminSpare = (float) ($__rsOnRetained['spare_parts_admin_commission'] ?? 0);
    $__rsFinalPrSvc = (float) ($__rsOnRetained['services_provider_earning'] ?? 0);
    $__rsFinalPrSpare = (float) ($__rsOnRetained['spare_parts_provider_earning'] ?? 0);
    $__rsCurPos = business_config('currency_symbol_position', 'business_information')['live_values'] ?? 'right';
    // Dispute modal must show whole numbers only (no decimals), regardless of global currency decimals.
    $__rsCurDec = 0;
@endphp
@can('booking_can_manage_status')
    <div class="modal fade" id="reopenDisputeModal--{{ $booking->id }}" tabindex="-1" aria-hidden="true"
        data-rs-total-paid="{{ $__rsPaid }}"
        data-rs-svc-full="{{ $__rsSvcFull }}"
        data-rs-spare-full="{{ $__rsSpareFull }}"
        data-rs-tier-setup='@json($__rsTierSetup)'
        data-rs-cur-symbol="{{ e(currency_symbol()) }}"
        data-rs-cur-pos="{{ e($__rsCurPos) }}"
        data-rs-cur-decimals="{{ $__rsCurDec }}">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">{{ translate('Reopen_scenario_disputed_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="small text-muted mb-2">{{ translate('Reopen_scenario_disputed_intro') }}</p>
                    <ul class="small mb-3 ps-3">
                        <li>{{ translate('Customer_paid_total') }}: <strong>{{ with_currency_symbol($__rsPaid) }}</strong></li>
                        <li>{{ translate('Collected_by_company') }}: <strong>{{ with_currency_symbol($__rsCompanyPool) }}</strong>
                            <span class="text-muted">({{ translate('Includes_unassigned_partials') }})</span></li>
                        <li>{{ translate('Collected_by_provider') }}: <strong>{{ with_currency_symbol($__rsProviderPool) }}</strong></li>
                    </ul>
                    <form method="post" action="{{ route('admin.booking.reopen_scenario.disputed_refund', $booking->id) }}" class="reopen-disputed-form">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">{{ translate('Dispute_reason') }} <span class="text-danger">*</span></label>
                            <select name="booking_dispute_reason_id" class="form-select" required>
                                <option value="" disabled {{ old('booking_dispute_reason_id') ? '' : 'selected' }}>—</option>
                                @foreach(($bookingDisputeReasons ?? collect()) as $reason)
                                    <option value="{{ $reason->id }}" {{ (string) old('booking_dispute_reason_id') === (string) $reason->id ? 'selected' : '' }}>
                                        {{ $reason->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('booking_dispute_reason_id')
                                <span class="text-danger small d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <p class="small text-muted mb-2">{{ translate('Disputed_refund_pair_linked_hint') }}</p>
                        <p class="small text-muted mb-2">{{ translate('Disputed_refund_max_per_field_is_customer_paid') }}
                            <strong>{{ with_currency_symbol($__rsPaid) }}</strong>.</p>
                        <div class="small mb-2 d-flex flex-wrap gap-3 align-items-baseline">
                            <span><span class="text-muted">{{ translate('Disputed_refund_combined_total') }}:</span> <strong class="js-rs-refund-sum">{{ with_currency_symbol($__rsCompanyPool + $__rsProviderPool) }}</strong></span>
                            <span class="text-muted">/ {{ translate('Customer_paid_total') }}: {{ with_currency_symbol($__rsPaid) }}</span>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small">{{ translate('Refund_paid_from_company_pool') }} <span class="text-danger">*</span></label>
                                <input type="text" inputmode="decimal" name="refund_company_amount" class="form-control js-rs-refund-co" autocomplete="off"
                                    value="{{ old('refund_company_amount', number_format($__rsCompanyPool, $__rsCurDec, '.', '')) }}" required>
                                @error('refund_company_amount')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ translate('Refund_company_transaction_id') }}</label>
                                <input type="text" name="refund_company_transaction_id" class="form-control" maxlength="100" placeholder="{{ translate('Required_if_amount_positive') }}" value="{{ old('refund_company_transaction_id') }}">
                                @error('refund_company_transaction_id')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small">{{ translate('Refund_paid_from_provider_pool') }} <span class="text-danger">*</span></label>
                                <input type="text" inputmode="decimal" name="refund_provider_amount" class="form-control js-rs-refund-pr" autocomplete="off"
                                    value="{{ old('refund_provider_amount', number_format($__rsProviderPool, $__rsCurDec, '.', '')) }}" required>
                                @error('refund_provider_amount')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ translate('Refund_provider_transaction_id') }}</label>
                                <input type="text" name="refund_provider_transaction_id" class="form-control" maxlength="100" placeholder="{{ translate('Required_if_amount_positive') }}" value="{{ old('refund_provider_transaction_id') }}">
                                @error('refund_provider_transaction_id')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_services_charges_retained_from_customer') }}</label>
                                <input type="text" inputmode="decimal" name="final_services_retained_from_customer" class="form-control js-rs-retained-svc" autocomplete="off"
                                    value="{{ number_format($__rsFinalSvcRetained, $__rsCurDec, '.', '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_admin_commission_services_net_basis') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-final-admin-svc bg-light" readonly tabindex="-1"
                                    value="{{ number_format($__rsFinalAdminSvc, $__rsCurDec, '.', '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_provider_earning_services_net_basis') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-final-pr-svc bg-light" readonly tabindex="-1"
                                    value="{{ number_format($__rsFinalPrSvc, $__rsCurDec, '.', '') }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_spare_parts_charges_retained_from_customer') }}</label>
                                <input type="text" inputmode="decimal" name="final_spare_parts_retained_from_customer" class="form-control js-rs-retained-spare" autocomplete="off"
                                    value="{{ number_format($__rsFinalSpareRetained, $__rsCurDec, '.', '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_admin_commission_spare_parts_net_basis') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-final-admin-spare bg-light" readonly tabindex="-1"
                                    value="{{ number_format($__rsFinalAdminSpare, $__rsCurDec, '.', '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_provider_earning_spare_parts_net_basis') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-final-pr-spare bg-light" readonly tabindex="-1"
                                    value="{{ number_format($__rsFinalPrSpare, $__rsCurDec, '.', '') }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_amount_retained_from_customer_after_refunds') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-retained bg-light" readonly tabindex="-1"
                                       value="{{ $__rsDefaultRetained }}"
                                       aria-describedby="rs-retained-hint--{{ $booking->id }}">
                                <div id="rs-retained-hint--{{ $booking->id }}" class="form-text text-muted small">{{ translate('Auto_total_paid_minus_refunds') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_admin_commission_net_basis') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-final-admin bg-light" readonly tabindex="-1"
                                    value="{{ number_format($__rsFinalAdmin, $__rsCurDec, '.', '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ translate('Final_provider_earning_net_basis') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control js-rs-final-pr bg-light" readonly tabindex="-1"
                                    value="{{ number_format($__rsFinalPr, $__rsCurDec, '.', '') }}">
                            </div>
                        </div>
                        <div class="alert alert-light border small mb-2 js-rs-reconcile" data-co-pool="{{ $__rsCompanyPool }}" data-pr-pool="{{ $__rsProviderPool }}">
                            <div><span class="text-muted">{{ translate('Provider_owes_company_refund_above_pool') }}:</span> <strong class="js-rs-owes-co">0.00</strong></div>
                            <div><span class="text-muted">{{ translate('Company_owes_provider_refund_above_pool') }}:</span> <strong class="js-rs-owes-pr">0.00</strong></div>
                            <div class="border-top pt-2 mt-2"><span class="text-muted">{{ translate('Disputed_total_provider_pays_company') }} <span class="text-muted fw-normal">({{ translate('Disputed_provider_pays_company_formula_hint') }})</span>:</span> <strong class="js-rs-provider-remit-total">0.00</strong></div>
                            <div class="mt-1"><span class="text-muted">{{ translate('Disputed_total_company_pays_provider') }}:</span> <strong class="js-rs-company-pay-pr-total">0.00</strong></div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">{{ translate('Reopen_resolve_remarks') }} <span class="text-danger">*</span></label>
                            <textarea name="reopen_dispute_remarks" class="form-control" rows="3" maxlength="5000" required placeholder="{{ translate('Reopen_resolve_remarks_placeholder') }}">{{ old('reopen_dispute_remarks') }}</textarea>
                            @error('reopen_dispute_remarks')
                                <span class="text-danger small d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn--danger w-100" onclick="return confirm(@json(translate('Confirm_disputed_reopen_cancel')));">
                            {{ translate('Apply_disputed_refund_and_close') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('script')
        <script>
            (function () {
                var modal = document.getElementById('reopenDisputeModal--{{ $booking->id }}');
                if (!modal) return;
                var totalPaid = parseFloat(modal.getAttribute('data-rs-total-paid')) || 0;
                var svcFull = parseFloat(modal.getAttribute('data-rs-svc-full')) || 0;
                var spareFull = parseFloat(modal.getAttribute('data-rs-spare-full')) || 0;
                var tierSetupRaw = modal.getAttribute('data-rs-tier-setup');
                var tierSetup = null;
                try {
                    tierSetup = tierSetupRaw ? JSON.parse(tierSetupRaw) : null;
                } catch (e) {
                    tierSetup = null;
                }
                var curSymbol = modal.getAttribute('data-rs-cur-symbol') || '';
                var curPosition = (modal.getAttribute('data-rs-cur-pos') || 'right').toLowerCase();
                var curDecimals = parseInt(modal.getAttribute('data-rs-cur-decimals'), 10);
                if (isNaN(curDecimals) || curDecimals < 0) curDecimals = 2;
                var splitUserEdited = false;
                function round2(x) {
                    // Keep function name for minimal diff; rounding respects modal decimals.
                    var v = Math.max(0, parseFloat(x) || 0);
                    if (curDecimals === 0) {
                        return Math.round(v);
                    }
                    return Math.round(v * 100) / 100;
                }
                function formatMoney(n) {
                    var v = round2(n);
                    var s = (curDecimals === 0 ? String(Math.round(v)) : v.toFixed(curDecimals));
                    var parts = s.split('.');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    var num = parts.length > 1 ? parts.join('.') : parts[0];
                    return curPosition === 'left' ? (curSymbol + num) : (num + curSymbol);
                }
                function parseMoneyInput(str) {
                    if (str == null) return NaN;
                    var t = String(str).trim().replace(/,/g, '');
                    if (t === '' || t === '.') return NaN;
                    var n = parseFloat(t);
                    return isNaN(n) ? NaN : n;
                }

                function formatFieldValue(n) {
                    var v = round2(n);
                    if (curDecimals === 0) return String(Math.round(v));
                    return v.toFixed(curDecimals);
                }

                function clamp(n, min, max) {
                    n = parseFloat(n);
                    if (isNaN(n)) return NaN;
                    return Math.max(min, Math.min(max, n));
                }

                function getRetainedFromUi() {
                    var elRet = modal.querySelector('.js-rs-retained');
                    return elRet ? (parseFloat(elRet.value) || 0) : 0;
                }

                function defaultSvcSplitForRetained(retained) {
                    retained = Math.max(0, round2(retained));
                    var base = round2(Math.max(0, svcFull) + Math.max(0, spareFull));
                    if (base <= 0.0001) return { svc: retained, sp: 0 };
                    var factor = Math.min(1, retained / base);
                    var svc = round2(Math.max(0, svcFull) * factor);
                    var sp = round2(Math.max(0, retained - svc));
                    return { svc: svc, sp: sp };
                }

                function syncSvcSpInputsFromRetained(retained) {
                    var elSvc = modal.querySelector('.js-rs-retained-svc');
                    var elSp = modal.querySelector('.js-rs-retained-spare');
                    if (!elSvc || !elSp) return;
                    retained = Math.max(0, round2(retained));
                    var curSvc = parseMoneyInput(elSvc.value);
                    var curSp = parseMoneyInput(elSp.value);
                    if (!splitUserEdited || isNaN(curSvc) || isNaN(curSp)) {
                        var d = defaultSvcSplitForRetained(retained);
                        elSvc.value = formatFieldValue(d.svc);
                        elSp.value = formatFieldValue(d.sp);
                        return;
                    }
                    // Keep user's ratio but ensure sum == retained.
                    curSvc = round2(Math.max(0, curSvc));
                    if (curSvc > retained) curSvc = retained;
                    var sp = round2(Math.max(0, retained - curSvc));
                    elSvc.value = formatFieldValue(curSvc);
                    elSp.value = formatFieldValue(sp);
                }

                /** If this field alone exceeds customer paid total, clamp it (allows normal typing until value parses over max). */
                function clampFieldToCustomerPaidIfOver(inp) {
                    if (!inp || totalPaid <= 0) return;
                    var v = parseMoneyInput(inp.value);
                    if (isNaN(v)) return;
                    if (v > totalPaid + 1e-9) {
                        inp.value = formatRefundFieldValue(totalPaid);
                    }
                }

                /** Keep company + provider refunds from exceeding customer paid. Do not rewrite the field the user is typing in on every keystroke. */
                function applyPairedRefundCap(edited) {
                    var inpCo = modal.querySelector('.js-rs-refund-co');
                    var inpPr = modal.querySelector('.js-rs-refund-pr');
                    if (!inpCo || !inpPr) return;
                    if (edited === 'co') clampFieldToCustomerPaidIfOver(inpCo);
                    else if (edited === 'pr') clampFieldToCustomerPaidIfOver(inpPr);
                    else {
                        clampFieldToCustomerPaidIfOver(inpCo);
                        clampFieldToCustomerPaidIfOver(inpPr);
                    }
                    var rCo = parseMoneyInput(inpCo.value);
                    var rPr = parseMoneyInput(inpPr.value);
                    if (isNaN(rCo)) rCo = 0;
                    if (isNaN(rPr)) rPr = 0;
                    rCo = round2(Math.min(Math.max(0, rCo), totalPaid));
                    rPr = round2(Math.min(Math.max(0, rPr), totalPaid));
                    if (totalPaid <= 0) {
                        inpCo.value = '0';
                        inpPr.value = '0';
                        return;
                    }
                    if (edited === 'co' && rCo + rPr > totalPaid + 0.005) {
                        rPr = round2(Math.max(0, totalPaid - rCo));
                        inpPr.value = formatRefundFieldValue(rPr);
                    } else if (edited === 'pr' && rCo + rPr > totalPaid + 0.005) {
                        rCo = round2(Math.max(0, totalPaid - rPr));
                        inpCo.value = formatRefundFieldValue(rCo);
                    } else if (edited === null && rCo + rPr > totalPaid + 0.005) {
                        rPr = round2(Math.max(0, totalPaid - rCo));
                        if (rCo + rPr > totalPaid + 0.005) {
                            rCo = round2(Math.max(0, totalPaid - rPr));
                        }
                        inpCo.value = formatRefundFieldValue(rCo);
                        inpPr.value = formatRefundFieldValue(rPr);
                    }
                }

                function formatRefundFieldValue(n) {
                    var v = round2(n);
                    if (curDecimals === 0) return String(Math.round(v));
                    return v.toFixed(curDecimals);
                }

                function normalizeRefundField(el) {
                    if (!el) return;
                    var v = parseMoneyInput(el.value);
                    if (isNaN(v)) v = 0;
                    v = round2(Math.min(Math.max(0, v), totalPaid));
                    el.value = formatRefundFieldValue(v);
                }

                /**
                 * Mirrors PHP commission_calc_line_preview (non-additive fixed; tier match by band).
                 * Returns: { admin_commission: number, provider_earning: number }
                 */
                function commissionCalcLinePreview(lineAmount, group, additiveFixedFee) {
                    if (!group || typeof group !== 'object') {
                        return { admin_commission: 0, provider_earning: Math.max(0, round2(lineAmount)) };
                    }
                    additiveFixedFee = !!additiveFixedFee;
                    lineAmount = Math.max(0, round2(parseFloat(lineAmount) || 0));
                    if ((group.mode || '') === 'fixed') {
                        var fixed = Math.max(0, parseFloat(group.fixed_amount) || 0);
                        var adm = additiveFixedFee ? fixed : Math.min(fixed, lineAmount);
                        var prov = additiveFixedFee ? lineAmount : Math.max(0, lineAmount - adm);
                        return { admin_commission: round2(adm), provider_earning: round2(prov) };
                    }
                    var tiers = Array.isArray(group.tiers) ? group.tiers.slice() : [];
                    var normalized = [];
                    for (var i = 0; i < tiers.length; i++) {
                        var t = tiers[i];
                        if (!t || typeof t !== 'object') continue;
                        var toRaw = t.to;
                        var to = (toRaw === null || toRaw === '') ? null : parseFloat(toRaw);
                        if (to !== null && isNaN(to)) to = null;
                        normalized.push({
                            from: Math.max(0, parseFloat(t.from) || 0),
                            to: to,
                            amount_type: (t.amount_type || '') === 'fixed' ? 'fixed' : 'percentage',
                            amount: Math.max(0, parseFloat(t.amount) || 0)
                        });
                    }
                    normalized.sort(function (a, b) { return a.from - b.from; });
                    var matched = null;
                    for (var j = 0; j < normalized.length; j++) {
                        var u = normalized[j];
                        if (lineAmount < u.from) continue;
                        if (u.to !== null && lineAmount > u.to) continue;
                        matched = u;
                        break;
                    }
                    if (!matched) {
                        return { admin_commission: 0, provider_earning: round2(lineAmount) };
                    }
                    var admin = 0;
                    if (matched.amount_type === 'percentage') admin = lineAmount * (matched.amount / 100.0);
                    else {
                        var amt = matched.amount;
                        admin = additiveFixedFee ? amt : Math.min(amt, lineAmount);
                    }
                    var provider = additiveFixedFee && matched.amount_type === 'fixed'
                        ? round2(lineAmount)
                        : Math.max(0, lineAmount - admin);
                    return { admin_commission: round2(admin), provider_earning: round2(provider) };
                }

                /**
                 * Retained becomes the new effective total; compute tier split on scaled service+spare so totals sum
                 * to retained, and expose the per-line breakdown.
                 */
                function commissionTargetsForRetained(retained) {
                    var r = Math.max(0, round2(parseFloat(retained) || 0));
                    if (r <= 0.0001) {
                        return { svc: 0, sp: 0, faSvc: 0, faSp: 0, fpSvc: 0, fpSp: 0, fa: 0, fp: 0 };
                    }
                    if (!tierSetup || !tierSetup.service || !tierSetup.spare_parts) {
                        // No tier setup: treat all retained as provider earning; allocate retained to services bucket.
                        return { svc: r, sp: 0, faSvc: 0, faSp: 0, fpSvc: r, fpSp: 0, fa: 0, fp: r };
                    }
                    var base = round2(Math.max(0, svcFull) + Math.max(0, spareFull));
                    if (base <= 0.0001) {
                        return { svc: r, sp: 0, faSvc: 0, faSp: 0, fpSvc: r, fpSp: 0, fa: 0, fp: r };
                    }
                    var factor = Math.min(1, r / base);
                    var svc = round2(Math.max(0, svcFull) * factor);
                    var sp = round2(Math.max(0, r - svc));
                    var p1 = commissionCalcLinePreview(svc, tierSetup.service, false);
                    var p2 = commissionCalcLinePreview(sp, tierSetup.spare_parts, false);
                    var faSvc = round2(parseFloat(p1.admin_commission) || 0);
                    var faSp = round2(parseFloat(p2.admin_commission) || 0);
                    var fpSvc = round2(parseFloat(p1.provider_earning) || 0);
                    var fpSp = round2(parseFloat(p2.provider_earning) || 0);
                    return {
                        svc: svc,
                        sp: sp,
                        faSvc: faSvc,
                        faSp: faSp,
                        fpSvc: fpSvc,
                        fpSp: fpSp,
                        fa: round2(faSvc + faSp),
                        fp: round2(fpSvc + fpSp)
                    };
                }

                function getEffectiveRefundAmounts() {
                    var inpCo = modal.querySelector('.js-rs-refund-co');
                    var inpPr = modal.querySelector('.js-rs-refund-pr');
                    var rCo = inpCo ? parseMoneyInput(inpCo.value) : 0;
                    var rPr = inpPr ? parseMoneyInput(inpPr.value) : 0;
                    if (isNaN(rCo)) rCo = 0;
                    if (isNaN(rPr)) rPr = 0;
                    rCo = round2(Math.min(Math.max(0, rCo), totalPaid));
                    rPr = round2(Math.min(Math.max(0, rPr), totalPaid));
                    if (totalPaid <= 0) return { rCo: 0, rPr: 0 };
                    if (rCo + rPr <= totalPaid + 0.005) return { rCo: rCo, rPr: rPr };
                    var active = document.activeElement;
                    if (active === inpCo) {
                        rPr = round2(Math.max(0, totalPaid - rCo));
                    } else if (active === inpPr) {
                        rCo = round2(Math.max(0, totalPaid - rPr));
                    } else {
                        rPr = round2(Math.max(0, totalPaid - rCo));
                        if (rCo + rPr > totalPaid + 0.005) {
                            rCo = round2(Math.max(0, totalPaid - rPr));
                        }
                    }
                    return { rCo: rCo, rPr: rPr };
                }

                function recalc() {
                    var box = modal.querySelector('.js-rs-reconcile');
                    if (!box) return;
                    var coPool = parseFloat(box.getAttribute('data-co-pool')) || 0;
                    var prPool = parseFloat(box.getAttribute('data-pr-pool')) || 0;
                    var eff = getEffectiveRefundAmounts();
                    var rCo = eff.rCo;
                    var rPr = eff.rPr;
                    var sumEl = modal.querySelector('.js-rs-refund-sum');
                    if (sumEl) sumEl.textContent = formatMoney(rCo + rPr);
                    var owesCo = round2(Math.max(0, (rCo - coPool)));
                    var owesPr = round2(Math.max(0, (rPr - prPool)));
                    var elCo = modal.querySelector('.js-rs-owes-co');
                    var elPr = modal.querySelector('.js-rs-owes-pr');
                    if (elCo) elCo.textContent = owesCo.toFixed(2);
                    if (elPr) elPr.textContent = owesPr.toFixed(2);
                    var retained = round2(Math.max(0, (totalPaid - rCo - rPr)));
                    // Ensure editable service/spare retained stay in sync with overall retained.
                    syncSvcSpInputsFromRetained(retained);
                    var elSvcInput = modal.querySelector('.js-rs-retained-svc');
                    var elSpInput = modal.querySelector('.js-rs-retained-spare');
                    var svcRet = elSvcInput ? parseMoneyInput(elSvcInput.value) : 0;
                    var spRet = elSpInput ? parseMoneyInput(elSpInput.value) : 0;
                    if (isNaN(svcRet)) svcRet = 0;
                    if (isNaN(spRet)) spRet = 0;
                    svcRet = round2(Math.max(0, Math.min(retained, svcRet)));
                    spRet = round2(Math.max(0, Math.min(retained, spRet)));
                    // Keep exact sum = retained by pushing remainder to spare.
                    spRet = round2(Math.max(0, retained - svcRet));
                    var coAfter = round2(Math.max(0, (coPool - rCo)));
                    var prAfter = round2(Math.max(0, (prPool - rPr)));
                    var elRet = modal.querySelector('.js-rs-retained');
                    var elFa = modal.querySelector('.js-rs-final-admin');
                    var elFp = modal.querySelector('.js-rs-final-pr');
                    var elRetSvc = modal.querySelector('.js-rs-retained-svc');
                    var elRetSp = modal.querySelector('.js-rs-retained-spare');
                    var elFaSvc = modal.querySelector('.js-rs-final-admin-svc');
                    var elFaSp = modal.querySelector('.js-rs-final-admin-spare');
                    var elFpSvc = modal.querySelector('.js-rs-final-pr-svc');
                    var elFpSp = modal.querySelector('.js-rs-final-pr-spare');
                    if (elRet) elRet.value = retained.toFixed(2);
                    // Recompute commissions from the editable retained split.
                    var svcC = commissionCalcLinePreview(svcRet, tierSetup && tierSetup.service ? tierSetup.service : null, false);
                    var spC = commissionCalcLinePreview(spRet, tierSetup && tierSetup.spare_parts ? tierSetup.spare_parts : null, false);
                    var faSvc = round2(parseFloat(svcC.admin_commission) || 0);
                    var faSp = round2(parseFloat(spC.admin_commission) || 0);
                    var fpSvc = round2(parseFloat(svcC.provider_earning) || 0);
                    var fpSp = round2(parseFloat(spC.provider_earning) || 0);
                    var fa = round2(faSvc + faSp);
                    var fp = round2(fpSvc + fpSp);
                    if (elRetSvc && elSvcInput) elSvcInput.value = formatFieldValue(svcRet);
                    if (elRetSp && elSpInput) elSpInput.value = formatFieldValue(spRet);
                    if (elFaSvc) elFaSvc.value = faSvc.toFixed(2);
                    if (elFaSp) elFaSp.value = faSp.toFixed(2);
                    if (elFpSvc) elFpSvc.value = fpSvc.toFixed(2);
                    if (elFpSp) elFpSp.value = fpSp.toFixed(2);
                    // Overall totals must be the sum of both lines.
                    if (elFa) elFa.value = fa.toFixed(2);
                    if (elFp) elFp.value = fp.toFixed(2);

                    var shortAdmin = round2(Math.max(0, (fa - coAfter)));
                    var shortProvider = round2(Math.max(0, (fp - prAfter)));
                    var providerRemitTotal = round2(owesCo + shortAdmin);
                    var companyPayTotal = round2(owesPr + shortProvider);
                    var elRemitTot = modal.querySelector('.js-rs-provider-remit-total');
                    var elCoPayTot = modal.querySelector('.js-rs-company-pay-pr-total');
                    if (elRemitTot) elRemitTot.textContent = formatMoney(providerRemitTotal);
                    if (elCoPayTot) elCoPayTot.textContent = formatMoney(companyPayTotal);
                }

                function onRetainedSplitInput(e) {
                    if (!e.target) return;
                    if (!e.target.classList.contains('js-rs-retained-svc') && !e.target.classList.contains('js-rs-retained-spare')) {
                        return;
                    }
                    splitUserEdited = true;
                    var retained = getRetainedFromUi();
                    retained = Math.max(0, round2(retained));
                    var elSvc = modal.querySelector('.js-rs-retained-svc');
                    var elSp = modal.querySelector('.js-rs-retained-spare');
                    if (!elSvc || !elSp) return;
                    var svc = parseMoneyInput(elSvc.value);
                    var sp = parseMoneyInput(elSp.value);
                    if (isNaN(svc)) svc = 0;
                    if (isNaN(sp)) sp = 0;
                    if (e.target.classList.contains('js-rs-retained-svc')) {
                        svc = round2(Math.max(0, Math.min(retained, svc)));
                        sp = round2(Math.max(0, retained - svc));
                        elSvc.value = formatFieldValue(svc);
                        elSp.value = formatFieldValue(sp);
                    } else {
                        sp = round2(Math.max(0, Math.min(retained, sp)));
                        svc = round2(Math.max(0, retained - sp));
                        elSvc.value = formatFieldValue(svc);
                        elSp.value = formatFieldValue(sp);
                    }
                    recalc();
                }
                function onRefundInput(e) {
                    if (!e.target) return;
                    if (e.target.classList.contains('js-rs-refund-co')) {
                        applyPairedRefundCap('co');
                        recalc();
                    } else if (e.target.classList.contains('js-rs-refund-pr')) {
                        applyPairedRefundCap('pr');
                        recalc();
                    }
                }
                function onRefundBlur(e) {
                    if (!e.target) return;
                    if (!e.target.classList.contains('js-rs-refund-co') && !e.target.classList.contains('js-rs-refund-pr')) return;
                    normalizeRefundField(e.target);
                    applyPairedRefundCap(e.target.classList.contains('js-rs-refund-co') ? 'co' : 'pr');
                    recalc();
                }
                function onRefundFormSubmit() {
                    var inpCo = modal.querySelector('.js-rs-refund-co');
                    var inpPr = modal.querySelector('.js-rs-refund-pr');
                    normalizeRefundField(inpCo);
                    normalizeRefundField(inpPr);
                    applyPairedRefundCap(null);
                    // Normalize retained split too so backend receives clean numeric values.
                    var retained = getRetainedFromUi();
                    retained = Math.max(0, round2(retained));
                    syncSvcSpInputsFromRetained(retained);
                    recalc();
                }
                var refundForm = modal.querySelector('.reopen-disputed-form');
                modal.addEventListener('input', onRefundInput);
                modal.addEventListener('change', onRefundInput);
                modal.addEventListener('input', onRetainedSplitInput);
                modal.addEventListener('change', onRetainedSplitInput);
                modal.addEventListener('blur', onRefundBlur, true);
                if (refundForm) {
                    refundForm.addEventListener('submit', onRefundFormSubmit);
                }
                modal.addEventListener('shown.bs.modal', function () {
                    var inpCo = modal.querySelector('.js-rs-refund-co');
                    var inpPr = modal.querySelector('.js-rs-refund-pr');
                    normalizeRefundField(inpCo);
                    normalizeRefundField(inpPr);
                    applyPairedRefundCap(null);
                    splitUserEdited = false;
                    recalc();
                });
            })();
        </script>
    @endpush
@endcan
