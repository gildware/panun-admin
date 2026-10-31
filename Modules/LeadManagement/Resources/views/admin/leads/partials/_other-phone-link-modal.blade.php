@can('lead_update')
<style>
    #linkOtherPhoneModal .other-phone-results {
        max-height: 260px;
        overflow: auto;
        margin-top: 0.55rem;
    }
    #linkOtherPhoneModal .other-phone-option {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        margin: 0 0 0.5rem;
        padding: 0.7rem 0.8rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        text-align: left;
    }
    #linkOtherPhoneModal .other-phone-option:last-child {
        margin-bottom: 0;
    }
    #linkOtherPhoneModal .other-phone-option:hover,
    #linkOtherPhoneModal .other-phone-option:focus {
        border-color: #cbd5e1;
        background: #f8fafc;
        outline: none;
    }
    #linkOtherPhoneModal .other-phone-option__main {
        display: block;
        font-size: 0.875rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.35;
    }
    #linkOtherPhoneModal .other-phone-option__meta {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.78rem;
        color: #4b5563;
        line-height: 1.4;
    }
    #linkOtherPhoneModal .other-phone-option__go {
        flex-shrink: 0;
        margin-top: 0.1rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #1d4ed8;
    }
    #linkOtherPhoneModal #other-phone-selected-person.is-selected {
        display: block;
        margin-top: 0.55rem;
        padding: 0.65rem 0.75rem;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 0.84rem;
    }
</style>
<div class="modal fade" id="linkOtherPhoneModal" tabindex="-1" aria-labelledby="linkOtherPhoneModalLabel" aria-hidden="true"
     data-search-url="{{ route('admin.lead.other-phone.search-people') }}"
     data-options-url="{{ route('admin.lead.other-phone.options', $lead->id) }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="linkOtherPhoneModalLabel">{{ translate('Link_as_other_phone') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST" action="{{ route('admin.lead.other-phone.store', $lead->id) }}" id="linkOtherPhoneForm">
                @csrf
                @if(!empty($inModal))
                    <input type="hidden" name="in_modal" value="1">
                @endif
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-3">
                        {{ translate('Link_as_other_phone_help_short') }}
                        ({{ translate('Lead_phone') }}: {{ $lead->phone_number }})
                    </p>

                    <div class="mb-3">
                        <label class="form-label">{{ translate('This_number_belongs_to') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="radio"
                                       name="owner_type"
                                       id="other-phone-type-customer"
                                       value="customer"
                                       checked>
                                <label class="form-check-label" for="other-phone-type-customer">{{ translate('Customer') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="radio"
                                       name="owner_type"
                                       id="other-phone-type-provider"
                                       value="provider">
                                <label class="form-check-label" for="other-phone-type-provider">{{ translate('Provider') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="other-phone-person-search">{{ translate('Search_person') }}</label>
                        <input type="text"
                               class="form-control"
                               id="other-phone-person-search"
                               placeholder="{{ translate('Search_by_name_or_phone') }}"
                               autocomplete="off">
                        <input type="hidden" name="owner_id" id="other-phone-owner-id" value="">
                        <div id="other-phone-search-hint" class="small text-muted mt-1">{{ translate('Type_at_least_2_characters_to_search') }}</div>
                        <div id="other-phone-person-results" class="other-phone-results"></div>
                        <div id="other-phone-selected-person" class="d-none"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="other-phone-action">{{ translate('What_is_this_contact_about') }}</label>
                        <select name="action" id="other-phone-action" class="form-control" required>
                            <option value="">{{ translate('Select') }}</option>
                            <option value="attach_booking">{{ translate('About_a_booking') }}</option>
                            <option value="merge_lead">{{ translate('Merge_into_open_lead_short') }}</option>
                            <option value="keep_lead">{{ translate('New_enquiry_keep_lead_short') }}</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="other-phone-booking-wrap">
                        <label class="form-label" for="other-phone-booking-id">{{ translate('Booking') }}</label>
                        <select name="booking_id" id="other-phone-booking-id" class="form-control">
                            <option value="">{{ translate('Select_a_booking') }}</option>
                        </select>
                    </div>

                    <div class="mb-0 d-none" id="other-phone-lead-wrap">
                        <label class="form-label" for="other-phone-target-lead-id">{{ translate('Open_lead') }}</label>
                        <select name="target_lead_id" id="other-phone-target-lead-id" class="form-control">
                            <option value="">{{ translate('Select_an_open_lead') }}</option>
                        </select>
                        <p class="small text-muted mt-1 mb-0" id="other-phone-lead-hint"></p>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary" id="other-phone-submit-btn" disabled>
                        {{ translate('Link_other_phone') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function bindOtherPhoneModal() {
        var modal = document.getElementById('linkOtherPhoneModal');
        if (!modal || modal.dataset.bound === '1') return;
        modal.dataset.bound = '1';

        var searchUrl = modal.getAttribute('data-search-url');
        var optionsUrl = modal.getAttribute('data-options-url');
        var searchInput = document.getElementById('other-phone-person-search');
        var results = document.getElementById('other-phone-person-results');
        var searchHint = document.getElementById('other-phone-search-hint');
        var ownerIdInput = document.getElementById('other-phone-owner-id');
        var selectedEl = document.getElementById('other-phone-selected-person');
        var actionSelect = document.getElementById('other-phone-action');
        var bookingWrap = document.getElementById('other-phone-booking-wrap');
        var leadWrap = document.getElementById('other-phone-lead-wrap');
        var bookingSelect = document.getElementById('other-phone-booking-id');
        var leadSelect = document.getElementById('other-phone-target-lead-id');
        var leadHint = document.getElementById('other-phone-lead-hint');
        var submitBtn = document.getElementById('other-phone-submit-btn');
        var form = document.getElementById('linkOtherPhoneForm');
        var searchTimer = null;

        function ownerType() {
            var el = modal.querySelector('input[name="owner_type"]:checked');
            return el ? el.value : 'customer';
        }

        function refreshSubmit() {
            var ok = !!ownerIdInput.value && !!actionSelect.value;
            if (actionSelect.value === 'attach_booking') {
                ok = ok && !!bookingSelect.value;
            }
            if (actionSelect.value === 'merge_lead') {
                var leadOptions = Array.prototype.slice.call(leadSelect.options).filter(function (o) {
                    return o.value;
                });
                if (leadOptions.length === 0) {
                    ok = false;
                } else if (leadOptions.length > 1) {
                    ok = ok && !!leadSelect.value;
                }
            }
            submitBtn.disabled = !ok;
        }

        function clearPerson() {
            ownerIdInput.value = '';
            selectedEl.classList.add('d-none');
            selectedEl.classList.remove('is-selected');
            selectedEl.innerHTML = '';
            bookingSelect.innerHTML = '<option value="">{{ translate('Select_a_booking') }}</option>';
            leadSelect.innerHTML = '<option value="">{{ translate('Select_an_open_lead') }}</option>';
            leadHint.textContent = '';
            refreshSubmit();
        }

        function loadOptions() {
            if (!ownerIdInput.value) return;
            fetch(optionsUrl + '?owner_type=' + encodeURIComponent(ownerType()) + '&owner_id=' + encodeURIComponent(ownerIdInput.value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (r) {
                if (!r.ok) throw new Error('options_failed');
                return r.json();
            }).then(function (data) {
                bookingSelect.innerHTML = '<option value="">{{ translate('Select_a_booking') }}</option>';
                (data.bookings || []).forEach(function (b) {
                    var opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.label + (b.is_open ? '' : ' ({{ translate('Closed') }})');
                    bookingSelect.appendChild(opt);
                });

                leadSelect.innerHTML = '<option value="">{{ translate('Select_an_open_lead') }}</option>';
                var leads = data.open_leads || [];
                leads.forEach(function (l) {
                    var opt = document.createElement('option');
                    opt.value = String(l.id);
                    opt.textContent = l.label;
                    leadSelect.appendChild(opt);
                });
                if (leads.length === 1) {
                    leadSelect.value = String(leads[0].id);
                    leadHint.textContent = '{{ translate('One_open_lead_will_be_used') }}';
                } else if (leads.length === 0) {
                    leadHint.textContent = '{{ translate('No_open_lead_for_this_person') }}';
                } else {
                    leadHint.textContent = '{{ translate('Select_which_open_lead_to_merge_into') }}';
                }
                refreshActionUi();
                refreshSubmit();
            }).catch(function () {
                searchHint.textContent = '{{ translate('Could_not_load_options') }}';
            });
        }

        function refreshActionUi() {
            var action = actionSelect.value;
            bookingWrap.classList.toggle('d-none', action !== 'attach_booking');
            leadWrap.classList.toggle('d-none', action !== 'merge_lead');
            refreshSubmit();
        }

        modal.querySelectorAll('input[name="owner_type"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                searchInput.value = '';
                results.innerHTML = '';
                searchHint.textContent = '{{ translate('Type_at_least_2_characters_to_search') }}';
                clearPerson();
            });
        });

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = (searchInput.value || '').trim();
            results.innerHTML = '';
            if (ownerIdInput.value) {
                clearPerson();
            }
            if (q.length < 2) {
                searchHint.textContent = '{{ translate('Type_at_least_2_characters_to_search') }}';
                return;
            }
            searchHint.textContent = '{{ translate('Searching') }}…';
            searchTimer = setTimeout(function () {
                fetch(searchUrl + '?owner_type=' + encodeURIComponent(ownerType()) + '&q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).then(function (r) {
                    if (!r.ok) throw new Error('search_failed');
                    return r.json();
                }).then(function (data) {
                    results.innerHTML = '';
                    var people = data.people || [];
                    if (!people.length) {
                        searchHint.textContent = '{{ translate('No_matching_person_found') }}';
                        return;
                    }
                    searchHint.textContent = '{{ translate('Click_a_result_to_select') }}';
                    people.forEach(function (p) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'other-phone-option';

                        var text = document.createElement('span');
                        var main = document.createElement('span');
                        main.className = 'other-phone-option__main';
                        main.textContent = p.label || '—';
                        text.appendChild(main);

                        var meta = document.createElement('span');
                        meta.className = 'other-phone-option__meta';
                        meta.textContent = [p.phone || '', p.meta || ''].filter(Boolean).join(' · ');
                        if (meta.textContent) {
                            text.appendChild(meta);
                        }

                        var go = document.createElement('span');
                        go.className = 'other-phone-option__go';
                        go.textContent = '{{ translate('Select') }}';

                        btn.appendChild(text);
                        btn.appendChild(go);
                        btn.addEventListener('click', function () {
                            ownerIdInput.value = p.id;
                            selectedEl.innerHTML = '<strong>{{ translate('Selected') }}:</strong> ' +
                                (p.label || '') +
                                (p.phone ? ' · ' + p.phone : '');
                            selectedEl.classList.remove('d-none');
                            selectedEl.classList.add('is-selected');
                            results.innerHTML = '';
                            searchInput.value = p.label || '';
                            searchHint.textContent = '';
                            loadOptions();
                            refreshSubmit();
                        });
                        results.appendChild(btn);
                    });
                }).catch(function () {
                    searchHint.textContent = '{{ translate('Search_failed_try_again') }}';
                });
            }, 250);
        });

        actionSelect.addEventListener('change', refreshActionUi);
        bookingSelect.addEventListener('change', refreshSubmit);
        leadSelect.addEventListener('change', refreshSubmit);

        form.addEventListener('submit', function (e) {
            refreshSubmit();
            if (submitBtn.disabled) {
                e.preventDefault();
                if (!ownerIdInput.value) {
                    searchHint.textContent = '{{ translate('Please_select_a_person_from_search') }}';
                    searchInput.focus();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindOtherPhoneModal);
    } else {
        bindOtherPhoneModal();
    }
})();
</script>
@endcan
