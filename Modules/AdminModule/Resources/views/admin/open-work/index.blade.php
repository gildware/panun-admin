@extends('adminmodule::layouts.new-master')

@section('title', translate('Open_Work'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-status-colors.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/open-work.css') }}?v=2026111423">
@endpush

@section('content')
@php
    $itemsJson = collect($items)->map(function ($item) {
        return [
            'key' => $item['key'] ?? '',
            'type' => $item['type'] ?? '',
            'lead_type' => $item['lead_type'] ?? null,
            'booking_status' => $item['booking_status'] ?? null,
            'ref' => $item['ref'] ?? '',
            'name' => $item['name'] ?? '',
            'owner_id' => $item['owner_id'] ?? 'unassigned',
            'owner_label' => $item['owner_label'] ?? translate('Unassigned'),
            'status' => $item['status'] ?? '',
            'status_tone' => $item['status_tone'] ?? 'neutral',
            'status_color' => $item['status_color'] ?? null,
            'details' => array_values(array_filter($item['details'] ?? [], function ($chip) {
                if (is_string($chip)) {
                    return trim($chip) !== '';
                }
                return is_array($chip) && trim((string) ($chip['label'] ?? '')) !== '';
            })),
            'next_step' => $item['next_step'] ?? '',
            'next_hint' => $item['next_hint'] ?? '',
            'last_comment' => $item['last_comment'] ?? '',
            'last_handled_by' => $item['last_handled_by'] ?? '',
            'last_handled_at' => isset($item['last_handled_at']) ? (int) $item['last_handled_at'] : null,
            'age_hours' => (int) ($item['age_hours'] ?? 0),
            'followup_at' => isset($item['followup_at']) ? (int) $item['followup_at'] : null,
            'is_overdue' => ! empty($item['is_overdue']),
            'url' => $item['url'] ?? '#',
            'rank' => (int) ($item['rank'] ?? 0),
            'score' => (int) ($item['score'] ?? 0),
        ];
    })->values();
@endphp
<div class="main-content">
    <div class="container-fluid open-work" id="open-work-app"
         data-items='@json($itemsJson)'>
        <div class="ow-toolbar">
            <div class="ow-field ow-field--search">
                <label class="ow-field__label" for="ow-search">{{ translate('Search') }}</label>
                <input type="search" id="ow-search" class="ow-control" placeholder="{{ translate('Search_name_or_ID') }}" />
            </div>

            <div class="ow-field ow-field--type">
                <span class="ow-field__label">{{ translate('Type') }}</span>
                <div class="ow-type-row">
                    <div class="ow-seg" role="group" aria-label="{{ translate('Type') }}">
                        <button type="button" class="ow-seg__btn active" data-filter="all">
                            {{ translate('All') }} <span class="ow-seg__n" data-count="all">0</span>
                        </button>
                        <button type="button" class="ow-seg__btn" data-filter="lead">
                            {{ translate('Leads') }} <span class="ow-seg__n" data-count="lead">0</span>
                        </button>
                        <button type="button" class="ow-seg__btn" data-filter="booking">
                            {{ translate('Bookings') }} <span class="ow-seg__n" data-count="booking">0</span>
                        </button>
                    </div>
                    <div class="ow-type-chips" id="ow-lead-type-chips" role="group" aria-label="{{ translate('Lead_type') }}" hidden></div>
                </div>
            </div>

            <div class="ow-field ow-field--booking" id="ow-booking-status-field" hidden>
                <label class="ow-field__label" for="ow-booking-status">{{ translate('Booking_status') }}</label>
                <select id="ow-booking-status" class="ow-control" aria-label="{{ translate('Booking_status') }}" disabled>
                    <option value="" selected>{{ translate('All') }}</option>
                    <option value="pending">{{ translate('Pending') }}</option>
                    <option value="accepted">{{ translate('Accepted') }}</option>
                    <option value="ongoing">{{ translate('Ongoing') }}</option>
                    <option value="on_hold">{{ translate('On_Hold') }}</option>
                </select>
            </div>

            <div class="ow-toolbar__right">
                @unless($employeeScope)
                    <div class="ow-field ow-field--employee">
                        <label class="ow-field__label" for="ow-employee">{{ translate('Employee') }}</label>
                        <select id="ow-employee"
                                class="js-select ow-employee-select ow-control"
                                aria-label="{{ translate('Employee') }}">
                            <option value="__all__" selected>{{ translate('All_assigned_and_unassigned') }}</option>
                            <option value="__assigned__">{{ translate('All_assigned') }}</option>
                            <option value="unassigned">{{ translate('Unassigned') }}</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee['id'] }}">{{ $employee['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div class="ow-field ow-field--sort">
                    <label class="ow-field__label" for="ow-sort-by">{{ translate('Sort_by') }}</label>
                    <div class="ow-sort-pair">
                        <select id="ow-sort-by" class="ow-control" aria-label="{{ translate('Sort_by') }}">
                            <option value="followup" selected>{{ translate('Follow_up') }}</option>
                            <option value="priority">{{ translate('Priority') }}</option>
                            <option value="age">{{ translate('Open_for') }}</option>
                            <option value="name">{{ translate('Name') }}</option>
                        </select>
                        <select id="ow-sort-dir" class="ow-control" aria-label="{{ translate('Sort_order') }}">
                            <option value="asc" selected>{{ translate('Oldest_first') }}</option>
                            <option value="desc">{{ translate('Newest_first') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="ow-panel" id="ow-main"></div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    var root = document.getElementById('open-work-app');
    if (!root) return;

    var items = [];
    try {
        items = JSON.parse(root.getAttribute('data-items') || '[]');
    } catch (e) {
        items = [];
    }

    var typeLabels = {
        lead: @json(translate('Lead')),
        booking: @json(translate('Booking')),
        task: @json(translate('Task')),
        hunt: @json(translate('Hunt'))
    };

    var sortDirLabels = {
        followup: {
            asc: @json(translate('Oldest_first')),
            desc: @json(translate('Newest_first'))
        },
        priority: {
            asc: @json(translate('Lowest_first')),
            desc: @json(translate('Highest_first'))
        },
        age: {
            asc: @json(translate('Shortest_open')),
            desc: @json(translate('Longest_open'))
        },
        name: {
            asc: @json(translate('A_to_Z')),
            desc: @json(translate('Z_to_A'))
        }
    };

    var defaultSortDir = {
        followup: 'asc',
        priority: 'desc',
        age: 'desc',
        name: 'asc'
    };

    var state = {
        type: 'all',
        leadType: '',
        bookingStatus: '',
        employee: '',
        q: '',
        sortBy: 'followup',
        sortDir: 'asc'
    };

    function formatAge(h) {
        h = parseInt(h, 10) || 0;
        if (h < 24) return h + 'h';
        var d = Math.floor(h / 24);
        var rem = h % 24;
        return rem ? d + 'd ' + rem + 'h' : d + 'd';
    }

    function formatHandledAt(ts) {
        ts = parseInt(ts, 10);
        if (!ts) return '';
        var d = new Date(ts * 1000);
        if (isNaN(d.getTime())) return '';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var day = d.getDate();
        var mon = months[d.getMonth()];
        var h24 = d.getHours();
        var ampm = h24 >= 12 ? 'PM' : 'AM';
        var h12 = h24 % 12;
        if (h12 === 0) h12 = 12;
        var mm = String(d.getMinutes()).padStart(2, '0');
        return day + ' ' + mon + ', ' + h12 + ':' + mm + ' ' + ampm;
    }

    function lastHandledHtml(it) {
        var name = String(it.last_handled_by || '').trim();
        var when = formatHandledAt(it.last_handled_at);
        if (!name && !when) return '—';
        return '<div class="ow-handled">'
            + '<span class="ow-handled__name">' + escapeHtml(name || '—') + '</span>'
            + (when ? '<small>' + escapeHtml(when) + '</small>' : '')
            + '</div>';
    }

    function ageClass(item) {
        if (item.is_overdue || item.age_hours >= 48) return 'bad';
        if (item.age_hours >= 24) return 'warn';
        return '';
    }

    function prioClass(rank) {
        if (rank <= 3) return 'p1';
        if (rank <= 6) return 'p2';
        if (rank <= 9) return 'p3';
        return '';
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function detailLabel(d) {
        if (d && typeof d === 'object') return String(d.label || '');
        return String(d == null ? '' : d);
    }

    function detailsHtml(details) {
        if (!Array.isArray(details) || !details.length) return '';
        return '<div class="ow-details">' + details.map(function (d) {
            var label = detailLabel(d);
            if (!label) return '';
            var tone = (d && typeof d === 'object' && d.tone) ? String(d.tone) : 'neutral';
            var color = (d && typeof d === 'object' && d.color) ? String(d.color) : '';
            var style = color
                ? ' style="background:' + escapeHtml(color) + ';color:#fff;border-color:transparent"'
                : '';
            return '<span class="ow-detail-chip tone-' + escapeHtml(tone) + '"' + style + '>'
                + escapeHtml(label) + '</span>';
        }).join('') + '</div>';
    }

    function isLeadish(it) {
        return it.type === 'lead' || it.type === 'hunt';
    }

    function matchesType(it) {
        if (state.type === 'all') return true;
        if (state.type === 'lead') return isLeadish(it);
        return it.type === state.type;
    }

    function matchesSubFilters(it) {
        var hasLead = !!state.leadType;
        var hasBooking = !!state.bookingStatus;

        if (!hasLead && !hasBooking) return true;

        if (hasLead && !hasBooking) {
            return isLeadish(it) && it.lead_type === state.leadType;
        }
        if (!hasLead && hasBooking) {
            return it.type === 'booking' && it.booking_status === state.bookingStatus;
        }

        if (isLeadish(it)) return it.lead_type === state.leadType;
        if (it.type === 'booking') return it.booking_status === state.bookingStatus;
        return false;
    }

    function cmpFollowup(a, b) {
        var aDue = a.followup_at;
        var bDue = b.followup_at;
        var aMissing = aDue == null || aDue === '';
        var bMissing = bDue == null || bDue === '';
        if (aMissing && bMissing) return (b.score || 0) - (a.score || 0);
        if (aMissing) return 1;
        if (bMissing) return -1;
        return (aDue - bDue) || (b.score || 0) - (a.score || 0);
    }

    function cmpAsc(a, b) {
        switch (state.sortBy) {
            case 'priority':
                return (a.score || 0) - (b.score || 0) || (a.rank || 0) - (b.rank || 0);
            case 'age':
                return (a.age_hours || 0) - (b.age_hours || 0);
            case 'name':
                return String(a.name || '').localeCompare(String(b.name || ''));
            case 'followup':
            default:
                return cmpFollowup(a, b);
        }
    }

    function matchesEmployeeAndSearch(it) {
        if (state.employee === 'unassigned') {
            if (it.owner_id !== 'unassigned') return false;
        } else if (state.employee === '__assigned__') {
            if (it.owner_id === 'unassigned') return false;
        } else if (state.employee) {
            if (it.owner_id !== state.employee) return false;
        }
        if (state.q) {
            var q = state.q.toLowerCase();
            var detailText = Array.isArray(it.details)
                ? it.details.map(detailLabel).join(' ')
                : '';
            var hay = (it.name + ' ' + it.ref + ' ' + (it.status || '') + ' ' + detailText + ' ' + (it.next_step || '') + ' ' + (it.last_comment || '') + ' ' + (it.last_handled_by || '')).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        return true;
    }

    function updateCounts() {
        var leads = 0;
        var unknown = 0;
        var customer = 0;
        var provider = 0;
        var bookings = 0;
        var other = 0;

        items.forEach(function (it) {
            if (!matchesEmployeeAndSearch(it)) return;
            if (isLeadish(it)) {
                leads++;
                var lt = String(it.lead_type || 'unknown');
                if (lt === 'customer') customer++;
                else if (lt === 'provider') provider++;
                else if (lt === 'unknown') unknown++;
            } else if (it.type === 'booking') {
                bookings++;
            } else {
                other++;
            }
        });

        var all = leads + bookings + other;
        var setN = function (key, n) {
            root.querySelectorAll('.ow-seg__n[data-count="' + key + '"]').forEach(function (node) {
                node.textContent = String(n);
            });
        };
        setN('all', all);
        setN('lead', leads);
        setN('booking', bookings);

        var chips = document.getElementById('ow-lead-type-chips');
        if (chips) {
            var parts = [
                { key: 'unknown', label: @json(translate('Unknown')), n: unknown },
                { key: 'customer', label: @json(translate('Customer')), n: customer },
                { key: 'provider', label: @json(translate('Provider')), n: provider }
            ];
            chips.innerHTML = parts.map(function (p) {
                var active = state.leadType === p.key ? ' active' : '';
                return '<button type="button" class="ow-type-chip ow-type-chip--' + p.key + active + '" data-lead-type="' + p.key + '">'
                    + escapeHtml(p.label) + ' <span class="ow-type-chip__n">' + p.n + '</span>'
                    + '</button>';
            }).join('');
        }
    }

    function filtered() {
        var list = items.filter(function (it) {
            if (!matchesType(it)) return false;
            if (!matchesSubFilters(it)) return false;
            if (!matchesEmployeeAndSearch(it)) return false;
            return true;
        });

        list.sort(function (a, b) {
            if (state.sortBy === 'followup') {
                var aMissing = a.followup_at == null || a.followup_at === '';
                var bMissing = b.followup_at == null || b.followup_at === '';
                if (aMissing && bMissing) return (b.score || 0) - (a.score || 0);
                if (aMissing) return 1;
                if (bMissing) return -1;
                var dueCmp = a.followup_at - b.followup_at;
                return state.sortDir === 'desc' ? -dueCmp : dueCmp;
            }
            var cmp = cmpAsc(a, b);
            return state.sortDir === 'desc' ? -cmp : cmp;
        });

        return list;
    }

    function tableCols() {
        return '<colgroup>'
            + '<col class="ow-col-pri">'
            + '<col class="ow-col-type">'
            + '<col class="ow-col-work">'
            + '<col class="ow-col-next">'
            + '<col class="ow-col-comment">'
            + '<col class="ow-col-handled">'
            + '<col class="ow-col-age">'
            + '<col class="ow-col-owner">'
            + '</colgroup>';
    }

    function tableHead() {
        return '<thead><tr>'
            + '<th class="ow-col-pri">{{ translate('Pri') }}</th>'
            + '<th class="ow-col-type">{{ translate('Type') }}</th>'
            + '<th class="ow-col-work">{{ translate('Work') }}</th>'
            + '<th class="ow-col-next">{{ translate('Next_follow_up') }}</th>'
            + '<th class="ow-col-comment">{{ translate('Last_comment') }}</th>'
            + '<th class="ow-col-handled">{{ translate('Last_handled_by') }}</th>'
            + '<th class="ow-col-age">{{ translate('Open_for') }}</th>'
            + '<th class="ow-col-owner">{{ translate('Owner') }}</th>'
            + '</tr></thead>';
    }

    function rowHtml(it) {
        return '<tr class="ow-row ' + (it.is_overdue ? 'overdue' : '') + '" data-url="' + escapeHtml(it.url) + '">'
            + '<td class="ow-col-pri"><span class="ow-prio ' + prioClass(it.rank) + '">#' + it.rank + '</span></td>'
            + '<td class="ow-col-type"><span class="ow-type ' + escapeHtml(it.type) + '">' + escapeHtml(typeLabels[it.type] || it.type) + '</span></td>'
            + '<td class="ow-col-work"><div class="ow-what"><span class="name">' + escapeHtml(it.name) + '</span><span class="id">' + escapeHtml(it.ref) + '</span>'
            + detailsHtml(it.details)
            + '</div></td>'
            + '<td class="ow-col-next ow-next">' + escapeHtml(it.next_step || '—')
            + (it.next_hint ? '<small>' + escapeHtml(it.next_hint) + '</small>' : '')
            + '</td>'
            + '<td class="ow-col-comment ow-comment">' + escapeHtml(it.last_comment || '—') + '</td>'
            + '<td class="ow-col-handled">' + lastHandledHtml(it) + '</td>'
            + '<td class="ow-col-age ow-age ' + ageClass(it) + '">' + formatAge(it.age_hours) + (it.is_overdue ? ' · ' + @json(translate('overdue')) : '') + '</td>'
            + '<td class="ow-col-owner ow-owner ' + (it.owner_id === 'unassigned' ? 'unassigned' : '') + '">' + escapeHtml(it.owner_label) + '</td>'
            + '</tr>';
    }

    function render() {
        updateCounts();
        var list = filtered();
        var main = document.getElementById('ow-main');
        if (!list.length) {
            main.innerHTML = '<div class="ow-empty">{{ translate('Nothing_matches_these_filters') }}</div>';
            return;
        }
        main.innerHTML = '<div class="ow-table-wrap"><table>' + tableCols() + tableHead() + '<tbody>'
            + list.map(rowHtml).join('')
            + '</tbody></table></div>';
    }

    function syncSubfilterVisibility() {
        var leadChips = document.getElementById('ow-lead-type-chips');
        var bookingField = document.getElementById('ow-booking-status-field');
        var bookingSel = document.getElementById('ow-booking-status');

        var showLead = state.type === 'lead';
        var showBooking = state.type === 'booking';

        if (leadChips) {
            leadChips.hidden = !showLead;
            if (!showLead) {
                state.leadType = '';
            }
        }
        if (bookingField) {
            bookingField.hidden = !showBooking;
            if (bookingSel) bookingSel.disabled = !showBooking;
            if (!showBooking) {
                state.bookingStatus = '';
                if (bookingSel) bookingSel.value = '';
            }
        }
    }

    function updateSortDirLabels(resetDir) {
        var dirSelect = document.getElementById('ow-sort-dir');
        if (!dirSelect) return;
        var labels = sortDirLabels[state.sortBy] || sortDirLabels.followup;
        var ascOpt = dirSelect.querySelector('option[value="asc"]');
        var descOpt = dirSelect.querySelector('option[value="desc"]');
        if (ascOpt) ascOpt.textContent = labels.asc;
        if (descOpt) descOpt.textContent = labels.desc;
        if (resetDir) {
            state.sortDir = defaultSortDir[state.sortBy] || 'asc';
            dirSelect.value = state.sortDir;
        }
    }

    root.querySelectorAll('.ow-seg__btn[data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.type = btn.getAttribute('data-filter') || 'all';
            root.querySelectorAll('.ow-seg__btn[data-filter]').forEach(function (c) {
                c.classList.remove('active');
            });
            btn.classList.add('active');

            syncSubfilterVisibility();
            render();
        });
    });

    var leadTypeChips = document.getElementById('ow-lead-type-chips');
    if (leadTypeChips) {
        leadTypeChips.addEventListener('click', function (e) {
            var btn = e.target.closest('.ow-type-chip[data-lead-type]');
            if (!btn) return;
            var value = btn.getAttribute('data-lead-type') || '';
            state.leadType = state.leadType === value ? '' : value;
            render();
        });
    }

    var bookingStatusSelect = document.getElementById('ow-booking-status');
    if (bookingStatusSelect) {
        bookingStatusSelect.addEventListener('change', function () {
            state.bookingStatus = bookingStatusSelect.value || '';
            render();
        });
    }

    var sortBySelect = document.getElementById('ow-sort-by');
    var sortDirSelect = document.getElementById('ow-sort-dir');
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function () {
            state.sortBy = sortBySelect.value || 'followup';
            updateSortDirLabels(true);
            render();
        });
    }
    if (sortDirSelect) {
        sortDirSelect.addEventListener('change', function () {
            state.sortDir = sortDirSelect.value || 'asc';
            render();
        });
    }

    var empSelect = document.getElementById('ow-employee');
    if (empSelect) {
        function normalizeEmployeeValue(val) {
            val = val || '';
            if (!val || val === '__all__') return '';
            return val;
        }
        function onEmployeeChange() {
            state.employee = normalizeEmployeeValue(empSelect.value);
            render();
        }
        empSelect.addEventListener('change', onEmployeeChange);

        if (window.jQuery && jQuery.fn.select2) {
            var $emp = jQuery(empSelect);
            if ($emp.hasClass('select2-hidden-accessible')) {
                $emp.select2('destroy');
            }
            $emp.select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
            $emp.on('change', onEmployeeChange);
        }
        state.employee = normalizeEmployeeValue(empSelect.value);
    }

    document.getElementById('ow-search').addEventListener('input', function (e) {
        state.q = (e.target.value || '').trim();
        render();
    });

    document.getElementById('ow-main').addEventListener('click', function (e) {
        var row = e.target.closest('tr.ow-row');
        if (!row) return;
        var url = row.getAttribute('data-url');
        if (url && url !== '#') {
            window.location.href = url;
        }
    });

    updateSortDirLabels(false);
    syncSubfilterVisibility();
    render();
})();
</script>
@endpush
