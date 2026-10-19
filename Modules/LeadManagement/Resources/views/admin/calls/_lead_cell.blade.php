@if($call->lead)
    @php
        $leadType = (string) ($call->lead->lead_type ?: 'unknown');
        $leadTypeLabel = \Modules\LeadManagement\Entities\Lead::leadTypes()[$leadType] ?? ucfirst($leadType);
    @endphp
    <a class="lead-chip lead-chip--{{ $leadType }}" href="{{ route('admin.lead.show', $call->lead_id) }}">
        <span class="lead-chip__id">#{{ $call->lead_id }}</span>
        <span class="lead-chip__type">{{ translate(str_replace(' ', '_', $leadTypeLabel)) }}</span>
    </a>
@else
    <span class="text-muted">—</span>
@endif

@once
    <style>
        .calls-lead-line { display: inline-flex; align-items: center; gap: 0.4rem; max-width: 100%; }
        .party-pill { display: inline-flex; align-items: center; padding: 0.22rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .party-pill--provider { background: #e7f0ff; color: #1d4ed8; }
        .party-pill--customer { background: #e7f6ee; color: #0f7a3a; }
        .calls-link-lead-btn { padding: 0.42rem 0.95rem; font-size: 0.8125rem; font-weight: 600; border-radius: 8px; line-height: 1.2; }
        .calls-unmatch-btn { background-color: var(--bs-danger); color: #fff; border: 1px solid var(--bs-danger); border-radius: 8px; font-weight: 600; line-height: 1.2; padding: 0.42rem 0.95rem; font-size: 0.8125rem; }
        .calls-unmatch-btn:hover,
        .calls-unmatch-btn:focus { background-color: #bb2d3b; color: #fff; border-color: #bb2d3b; }
        .lead-chip { display: inline-flex; align-items: stretch; border-radius: 999px; overflow: hidden; text-decoration: none; border: 1px solid #e5e7eb; background: #fff; line-height: 1; }
        .lead-chip__id { padding: 0.28rem 0.4rem; color: #111827; font-size: 0.72rem; font-weight: 700; }
        .lead-chip__type { padding: 0.28rem 0.45rem; font-size: 0.68rem; font-weight: 700; }
        .lead-chip--unknown .lead-chip__type { background: #f1f3f5; color: #4b5563; }
        .lead-chip--customer .lead-chip__type { background: #e7f6ee; color: #0f7a3a; }
        .lead-chip--provider .lead-chip__type { background: #e7f0ff; color: #1d4ed8; }
        .lead-chip--invalid .lead-chip__type { background: #fdecea; color: #c62828; }
        .lead-chip--future_customer .lead-chip__type { background: #fff4e5; color: #b45309; }
        .lead-chip--future_provider .lead-chip__type { background: #eef2ff; color: #4338ca; }
        .call-link-who { padding: 0.85rem 1rem; border-radius: 12px; background: #f8fafc; border: 1px solid #e5e7eb; }
        .call-link-who__kind { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
        .call-link-who__name { font-size: 1rem; font-weight: 700; color: #111827; }
        .call-link-section { margin-top: 1rem; }
        .call-link-section__title { font-size: 0.78rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem; }
        .call-link-empty { font-size: 0.78rem; color: #6b7280; margin: 0; }
        .call-link-caption { margin: 0.55rem 0 0.35rem; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #6b7280; }
        .call-link-results { max-height: 340px; overflow: auto; }
        .call-link-option { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; width: 100%; margin: 0 0 0.45rem; padding: 0.65rem 0.7rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; text-align: left; }
        .call-link-option:hover { border-color: #cbd5e1; background: #f8fafc; }
        .call-link-option__main { font-size: 0.84rem; font-weight: 700; color: #111827; }
        .call-link-option__meta { font-size: 0.75rem; color: #4b5563; }
        .call-link-option__line { display: block; line-height: 1.4; }
        .call-link-option__label { font-weight: 700; color: #374151; }
        .call-link-option__label::after { content: ': '; }
        .call-link-option__go { flex-shrink: 0; margin-top: 0.15rem; font-size: 0.75rem; font-weight: 700; color: #1d4ed8; }
        .call-link-tabs { display: flex; gap: 0.25rem; padding: 0.2rem; margin: 0.85rem 0 0.75rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; }
        .call-link-tabs button { flex: 1; border: 0; border-radius: 999px; background: transparent; padding: 0.4rem 0.75rem; font-size: 0.82rem; font-weight: 700; color: #4b5563; }
        .call-link-tabs button.is-active { background: #111827; color: #fff; }
    </style>
    <div class="modal fade" id="call-link-lead-modal" tabindex="-1" aria-hidden="true"
         data-lead-search-url="{{ route('admin.calls.lead-search') }}"
         data-booking-search-url="{{ route('admin.calls.booking-search') }}"
         data-token="{{ csrf_token() }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Link_call') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="call-link-who" class="call-link-who">
                        <div id="call-link-kind" class="call-link-who__kind"></div>
                        <div id="call-link-name" class="call-link-who__name"></div>
                        <div id="call-link-phone" class="text-muted small"></div>
                    </div>
                    <div class="call-link-tabs" role="tablist">
                        <button type="button" class="is-active" data-call-link-tab="lead">{{ translate('Lead') }}</button>
                        <button type="button" data-call-link-tab="booking">{{ translate('Booking') }}</button>
                    </div>
                    <div data-call-link-panel="lead">
                        <input id="call-link-lead-q" type="search" class="form-control" placeholder="{{ translate('Name_phone_or_lead_id') }}" autocomplete="off">
                        <p class="call-link-caption" id="call-link-lead-caption">{{ translate('Open_leads') }}</p>
                        <div id="call-link-lead-results" class="call-link-results"></div>
                    </div>
                    <div data-call-link-panel="booking" class="d-none">
                        <input id="call-link-booking-q" type="search" class="form-control" placeholder="{{ translate('Booking_number_customer_or_provider') }}" autocomplete="off">
                        <p class="call-link-caption" id="call-link-booking-caption">{{ translate('Open_bookings') }}</p>
                        <div id="call-link-booking-results" class="call-link-results"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('call-link-lead-modal');
            var leadInput = document.getElementById('call-link-lead-q');
            var leadResults = document.getElementById('call-link-lead-results');
            var bookingInput = document.getElementById('call-link-booking-q');
            var bookingResults = document.getElementById('call-link-booking-results');
            var kind = document.getElementById('call-link-kind');
            var name = document.getElementById('call-link-name');
            var phone = document.getElementById('call-link-phone');
            if (!modal || !leadInput || !leadResults || !bookingInput || !bookingResults) {
                return;
            }
            var leadAction = '';
            var bookingAction = '';
            var savedLeads = [];
            var savedBookings = [];
            var party = 'none';
            var leadCaption = document.getElementById('call-link-lead-caption');
            var bookingCaption = document.getElementById('call-link-booking-caption');
            var partyLabels = { provider: 'Provider', customer: 'Customer', lead: 'Lead' };
            var openLeadCaption = {
                provider: @json(translate('Open_leads_for_this_provider')),
                customer: @json(translate('Open_leads_for_this_customer')),
                lead: @json(translate('Open_leads_for_this_number')),
                none: @json(translate('Open_leads_for_this_number'))
            };
            var openBookingCaption = {
                provider: @json(translate('Open_bookings_for_this_provider')),
                customer: @json(translate('Open_bookings_for_this_customer')),
                lead: @json(translate('Open_bookings_for_this_number')),
                none: @json(translate('Open_bookings_for_this_number'))
            };

            function hiddenToken() {
                var token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = modal.getAttribute('data-token') || '';
                return token;
            }

            function optionForm(action, field, id, title, details) {
                var form = document.createElement('form');
                form.method = 'post';
                form.action = action;
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = field;
                hidden.value = id;
                var button = document.createElement('button');
                button.type = 'submit';
                button.className = 'call-link-option';
                var text = document.createElement('span');
                var main = document.createElement('span');
                main.className = 'call-link-option__main d-block';
                main.textContent = title;
                var sub = document.createElement('span');
                sub.className = 'call-link-option__meta d-block';
                (details || []).forEach(function (line) {
                    if (!line || !line.value) return;
                    var row = document.createElement('span');
                    row.className = 'call-link-option__line';
                    var label = document.createElement('span');
                    label.className = 'call-link-option__label';
                    label.textContent = line.label || '';
                    row.appendChild(label);
                    row.appendChild(document.createTextNode(line.value));
                    sub.appendChild(row);
                });
                text.appendChild(main);
                text.appendChild(sub);
                var go = document.createElement('span');
                go.className = 'call-link-option__go';
                go.textContent = 'Link';
                button.appendChild(text);
                button.appendChild(go);
                form.appendChild(hiddenToken());
                form.appendChild(hidden);
                form.appendChild(button);
                return form;
            }

            function emptyNote(target, message) {
                target.textContent = '';
                var note = document.createElement('p');
                note.className = 'call-link-empty';
                note.textContent = message;
                target.appendChild(note);
            }

            function showLeads(leads) {
                leadResults.textContent = '';
                if (!leads.length) {
                    emptyNote(leadResults, 'No open lead for this number.');
                    return;
                }
                leads.forEach(function (lead) {
                    leadResults.appendChild(optionForm(
                        leadAction,
                        'lead_id',
                        lead.id,
                        lead.title || ('#' + lead.id + ' ' + (lead.name || '')),
                        lead.details || []
                    ));
                });
            }

            function showBookings(bookings) {
                bookingResults.textContent = '';
                if (!bookings.length) {
                    emptyNote(bookingResults, 'No open booking for this number.');
                    return;
                }
                bookings.forEach(function (booking) {
                    bookingResults.appendChild(optionForm(
                        bookingAction,
                        'booking_id',
                        booking.id,
                        booking.title || booking.readable_id || booking.id,
                        booking.details || []
                    ));
                });
            }

            function showOpenCaptions() {
                if (leadCaption) {
                    leadCaption.textContent = openLeadCaption[party] || openLeadCaption.none;
                }
                if (bookingCaption) {
                    bookingCaption.textContent = openBookingCaption[party] || openBookingCaption.none;
                }
            }

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                leadAction = trigger ? (trigger.getAttribute('data-lead-action') || '') : '';
                bookingAction = trigger ? (trigger.getAttribute('data-booking-action') || '') : '';
                leadInput.value = '';
                bookingInput.value = '';
                savedLeads = [];
                savedBookings = [];
                emptyNote(leadResults, 'Looking up leads…');
                emptyNote(bookingResults, 'Looking up bookings…');
                modal.querySelectorAll('[data-call-link-tab]').forEach(function (tab) {
                    tab.classList.toggle('is-active', tab.getAttribute('data-call-link-tab') === 'lead');
                });
                modal.querySelectorAll('[data-call-link-panel]').forEach(function (panel) {
                    panel.classList.toggle('d-none', panel.getAttribute('data-call-link-panel') !== 'lead');
                });
                party = trigger ? (trigger.getAttribute('data-party') || 'none') : 'none';
                showOpenCaptions();
                if (kind) {
                    kind.textContent = partyLabels[party] || 'Not matched';
                }
                if (name) {
                    name.textContent = party === 'none'
                        ? 'This number is not a provider, customer, or lead.'
                        : (trigger.getAttribute('data-party-name') || '');
                }
                if (phone) {
                    phone.textContent = trigger ? (trigger.getAttribute('data-phone') || '') : '';
                }
                var optionsUrl = trigger ? (trigger.getAttribute('data-options-url') || '') : '';
                if (optionsUrl === '') {
                    return;
                }
                fetch(optionsUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        savedLeads = data.leads || [];
                        savedBookings = data.bookings || [];
                        if (leadInput.value.trim() === '') {
                            showLeads(savedLeads);
                        }
                        if (bookingInput.value.trim() === '') {
                            showBookings(savedBookings);
                        }
                    })
                    .catch(function () {
                        emptyNote(leadResults, 'Could not load existing leads.');
                        emptyNote(bookingResults, 'Could not load existing bookings.');
                    });
            });

            modal.querySelectorAll('[data-call-link-tab]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var selected = tab.getAttribute('data-call-link-tab');
                    modal.querySelectorAll('[data-call-link-tab]').forEach(function (item) {
                        item.classList.toggle('is-active', item === tab);
                    });
                    modal.querySelectorAll('[data-call-link-panel]').forEach(function (panel) {
                        panel.classList.toggle('d-none', panel.getAttribute('data-call-link-panel') !== selected);
                    });
                });
            });

            function watchSearch(input, results, urlName, listName, actionField, idField) {
                var timer = null;
                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    var query = input.value.trim();
                    var action = actionField === 'lead' ? leadAction : bookingAction;
                    if (query.length < 2) {
                        showOpenCaptions();
                        if (actionField === 'lead') {
                            showLeads(savedLeads);
                        } else {
                            showBookings(savedBookings);
                        }
                        return;
                    }
                    if (actionField === 'lead' && leadCaption) {
                        leadCaption.textContent = @json(translate('Search_results'));
                    }
                    if (actionField !== 'lead' && bookingCaption) {
                        bookingCaption.textContent = @json(translate('Search_results'));
                    }
                    if (action === '') {
                        return;
                    }
                    timer = setTimeout(function () {
                        fetch(modal.getAttribute(urlName) + '?q=' + encodeURIComponent(query), {
                            headers: { 'Accept': 'application/json' }
                        }).then(function (response) {
                            return response.json();
                        }).then(function (data) {
                            var items = data[listName] || [];
                            if (!items.length) {
                                emptyNote(results, 'No matches.');
                                return;
                            }
                            if (listName === 'leads') {
                                showLeads(items);
                            } else {
                                showBookings(items);
                            }
                        });
                    }, 250);
                });
            }

            watchSearch(leadInput, leadResults, 'data-lead-search-url', 'leads', 'lead', 'lead_id');
            watchSearch(bookingInput, bookingResults, 'data-booking-search-url', 'bookings', 'booking', 'booking_id');
        });
    </script>
@endonce
