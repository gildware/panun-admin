@extends('adminmodule::layouts.new-master')

@section('title', translate('Daily_Employee_Day_Detail'))

@push('css_or_js')
    <style>
        .day-detail-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .day-detail-date-wrap {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .day-detail-date-wrap label {
            margin: 0;
            font-size: 13px;
            color: #6c757d;
            white-space: nowrap;
        }

        .day-detail-date-wrap input[type="date"] {
            width: auto;
            min-width: 150px;
        }

        .day-detail-avatar-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0;
        }

        .day-detail-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid #fff;
            margin-left: -10px;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 0 0 1px #dee2e6;
            position: relative;
            flex-shrink: 0;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            cursor: pointer;
            padding: 0;
        }

        .day-detail-avatar:first-child {
            margin-left: 0;
        }

        .day-detail-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .day-detail-avatar:hover {
            transform: translateY(-2px);
            z-index: 2;
            text-decoration: none;
            color: #fff;
        }

        .day-detail-avatar.is-active {
            box-shadow: 0 0 0 2px var(--bs-primary, #0d6efd);
            z-index: 3;
        }

        .day-detail-avatar.is-all {
            background: #212529;
            color: #fff;
            font-size: 11px;
        }

        .day-detail-avatar.is-more {
            background: #e9ecef;
            color: #212529;
            border: none;
        }

        .day-detail-mini-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dee2e6;
            font-size: 11px;
            font-weight: 600;
            color: #495057;
        }

        .day-detail-avatar-letter-a,
        .day-detail-mini-avatar.day-detail-avatar-letter-a { background: #e53935; color: #fff; }
        .day-detail-avatar-letter-b,
        .day-detail-mini-avatar.day-detail-avatar-letter-b { background: #d81b60; color: #fff; }
        .day-detail-avatar-letter-c,
        .day-detail-mini-avatar.day-detail-avatar-letter-c { background: #8e24aa; color: #fff; }
        .day-detail-avatar-letter-d,
        .day-detail-mini-avatar.day-detail-avatar-letter-d { background: #5e35b1; color: #fff; }
        .day-detail-avatar-letter-e,
        .day-detail-mini-avatar.day-detail-avatar-letter-e { background: #3949ab; color: #fff; }
        .day-detail-avatar-letter-f,
        .day-detail-mini-avatar.day-detail-avatar-letter-f { background: #1e88e5; color: #fff; }
        .day-detail-avatar-letter-g,
        .day-detail-mini-avatar.day-detail-avatar-letter-g { background: #039be5; color: #fff; }
        .day-detail-avatar-letter-h,
        .day-detail-mini-avatar.day-detail-avatar-letter-h { background: #00acc1; color: #fff; }
        .day-detail-avatar-letter-i,
        .day-detail-mini-avatar.day-detail-avatar-letter-i { background: #00897b; color: #fff; }
        .day-detail-avatar-letter-j,
        .day-detail-mini-avatar.day-detail-avatar-letter-j { background: #43a047; color: #fff; }
        .day-detail-avatar-letter-k,
        .day-detail-mini-avatar.day-detail-avatar-letter-k { background: #7cb342; color: #fff; }
        .day-detail-avatar-letter-l,
        .day-detail-mini-avatar.day-detail-avatar-letter-l { background: #c0ca33; color: #212529; }
        .day-detail-avatar-letter-m,
        .day-detail-mini-avatar.day-detail-avatar-letter-m { background: #fdd835; color: #212529; }
        .day-detail-avatar-letter-n,
        .day-detail-mini-avatar.day-detail-avatar-letter-n { background: #ffb300; color: #212529; }
        .day-detail-avatar-letter-o,
        .day-detail-mini-avatar.day-detail-avatar-letter-o { background: #fb8c00; color: #fff; }
        .day-detail-avatar-letter-p,
        .day-detail-mini-avatar.day-detail-avatar-letter-p { background: #f4511e; color: #fff; }
        .day-detail-avatar-letter-q,
        .day-detail-mini-avatar.day-detail-avatar-letter-q { background: #6d4c41; color: #fff; }
        .day-detail-avatar-letter-r,
        .day-detail-mini-avatar.day-detail-avatar-letter-r { background: #546e7a; color: #fff; }
        .day-detail-avatar-letter-s,
        .day-detail-mini-avatar.day-detail-avatar-letter-s { background: #00838f; color: #fff; }
        .day-detail-avatar-letter-t,
        .day-detail-mini-avatar.day-detail-avatar-letter-t { background: #2e7d32; color: #fff; }
        .day-detail-avatar-letter-u,
        .day-detail-mini-avatar.day-detail-avatar-letter-u { background: #1565c0; color: #fff; }
        .day-detail-avatar-letter-v,
        .day-detail-mini-avatar.day-detail-avatar-letter-v { background: #6a1b9a; color: #fff; }
        .day-detail-avatar-letter-w,
        .day-detail-mini-avatar.day-detail-avatar-letter-w { background: #ad1457; color: #fff; }
        .day-detail-avatar-letter-x,
        .day-detail-mini-avatar.day-detail-avatar-letter-x { background: #c62828; color: #fff; }
        .day-detail-avatar-letter-y,
        .day-detail-mini-avatar.day-detail-avatar-letter-y { background: #ef6c00; color: #fff; }
        .day-detail-avatar-letter-z,
        .day-detail-mini-avatar.day-detail-avatar-letter-z { background: #455a64; color: #fff; }

        .day-detail-more-menu {
            min-width: 220px;
            max-height: 280px;
            overflow: auto;
        }

        .day-detail-more-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.45rem 0.85rem;
            cursor: pointer;
        }

        .day-detail-more-menu .dropdown-item img,
        .day-detail-more-menu .day-detail-mini-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .day-detail-section {
            height: 100%;
        }

        .day-detail-section .card-header {
            background: #f8f9fa;
        }

        .day-detail-section .card-body {
            max-height: 360px;
            overflow: auto;
        }

        .day-detail-table {
            margin-bottom: 0;
            font-size: 13px;
        }

        .day-detail-table th {
            white-space: nowrap;
            font-size: 12px;
            font-weight: 600;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }

        .day-detail-empty {
            color: #adb5bd;
            font-size: 13px;
            padding: 1rem;
            text-align: center;
        }

        .day-detail-avatar-group.is-loading {
            opacity: 0.85;
            pointer-events: none;
        }

        #day-detail-metrics.is-updating,
        #day-detail-sections.is-updating {
            opacity: 0.55;
            transition: opacity 0.15s ease;
        }
    </style>
@endpush

@section('content')
    @php
        $avatarLimit = 4;
        $employeeList = $filterEmployees ?? collect();
        $visibleEmployees = $employeeList->take($avatarLimit);
        $overflowEmployees = $employeeList->slice($avatarLimit);
        $overflowCount = $overflowEmployees->count();
        $focusEmployeeIds = array_map('strval', $focusEmployeeIds ?? []);
        $isAllSelected = $focusEmployeeIds === [];
        $detailBaseUrl = route('admin.report.daily-employee.detail');
        $initials = function ($employee) {
            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
            if ($fullName === '') {
                $fullName = trim((string) ($employee->email ?? ''));
            }
            $words = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) >= 2) {
                $letters = '';
                foreach ($words as $word) {
                    $letters .= mb_substr($word, 0, 1);
                }

                return mb_strtoupper($letters);
            }
            if (count($words) === 1) {
                $word = $words[0];
                $take = min(2, mb_strlen($word));

                return mb_strtoupper(mb_substr($word, 0, $take));
            }

            return 'E';
        };
        $avatarLetterClass = function (string $initialsText): string {
            $letter = mb_strtolower(mb_substr(preg_replace('/[^A-Za-z]/', '', $initialsText) ?: 'e', 0, 1));
            if ($letter < 'a' || $letter > 'z') {
                $letter = 'e';
            }

            return 'day-detail-avatar-letter-' . $letter;
        };
        $employeeLabel = function ($employee) {
            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
            return $fullName !== '' ? $fullName : ($employee->email ?? (string) $employee->id);
        };
        $employeePhoto = function ($employee) {
            $stored = trim((string) ($employee->profile_image ?? ''));
            if ($stored === '') {
                return null;
            }

            $storedLower = mb_strtolower($stored);
            if (
                $storedLower === 'default.png'
                || str_contains($storedLower, 'placeholder')
                || str_contains($storedLower, 'customer.png')
                || str_contains($storedLower, 'user2x.png')
            ) {
                return null;
            }

            $path = (string) ($employee->profile_image_full_path ?? '');
            if ($path === '') {
                return null;
            }

            $pathLower = mb_strtolower($path);
            if (
                str_contains($pathLower, 'placeholder')
                || str_contains($pathLower, '/customer.png')
                || str_contains($pathLower, '/user2x.png')
                || str_contains($pathLower, '/default.png')
            ) {
                return null;
            }

            return $path;
        };
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Daily_Employee_Day_Detail') }}</h2>
                    <p class="text-muted mb-0 fs-13">{{ translate('Daily_Employee_Day_Detail_description') }}</p>
                </div>
                <a href="{{ route('admin.report.daily-employee', ['date_from' => $date, 'date_to' => $date]) }}"
                   class="btn btn--secondary">
                    {{ translate('Back_to_Daily_Report') }}
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="day-detail-toolbar">
                        <div class="day-detail-date-wrap">
                            <label for="day-detail-date">{{ translate('Date') }}</label>
                            <input type="date"
                                   id="day-detail-date"
                                   class="form-control h-45"
                                   value="{{ $date }}">
                        </div>

                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="day-detail-avatar-group"
                                 id="day-detail-avatar-group"
                                 data-base-url="{{ $detailBaseUrl }}"
                                 data-date="{{ $date }}"
                                 data-selected='@json($focusEmployeeIds)'>
                                <button type="button"
                                        class="day-detail-avatar is-all {{ $isAllSelected ? 'is-active' : '' }}"
                                        data-employee-id="all"
                                        title="{{ translate('All_Employees') }}">
                                    {{ translate('All') }}
                                </button>

                                @foreach($visibleEmployees as $employee)
                                    @php
                                        $isActive = in_array((string) $employee->id, $focusEmployeeIds, true);
                                        $label = $employeeLabel($employee);
                                        $img = $employeePhoto($employee);
                                        $initialsText = $initials($employee);
                                        $letterClass = $avatarLetterClass($initialsText);
                                    @endphp
                                    <button type="button"
                                            class="day-detail-avatar {{ $isActive ? 'is-active' : '' }} {{ $img ? '' : 'has-initials ' . $letterClass }}"
                                            data-employee-id="{{ $employee->id }}"
                                            data-letter-class="{{ $letterClass }}"
                                            title="{{ $label }}">
                                        @if($img)
                                            <img src="{{ $img }}"
                                                 alt="{{ $label }}"
                                                 class="day-detail-avatar-img"
                                                 data-initials="{{ $initialsText }}"
                                                 data-letter-class="{{ $letterClass }}"
                                                 onerror="window.dayDetailAvatarImgError && window.dayDetailAvatarImgError(this)">
                                        @else
                                            <span class="day-detail-avatar-initials">{{ $initialsText }}</span>
                                        @endif
                                    </button>
                                @endforeach

                                @if($overflowCount > 0)
                                    <div class="dropdown d-inline-flex">
                                        <button type="button"
                                                class="day-detail-avatar is-more"
                                                data-bs-toggle="dropdown"
                                                data-bs-auto-close="outside"
                                                aria-expanded="false"
                                                title="{{ translate('More') }}">
                                            +{{ $overflowCount }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end day-detail-more-menu shadow">
                                            @foreach($overflowEmployees as $employee)
                                                @php
                                                    $isActive = in_array((string) $employee->id, $focusEmployeeIds, true);
                                                    $label = $employeeLabel($employee);
                                                    $img = $employeePhoto($employee);
                                                    $initialsText = $initials($employee);
                                                    $letterClass = $avatarLetterClass($initialsText);
                                                @endphp
                                                <li>
                                                    <button type="button"
                                                            class="dropdown-item {{ $isActive ? 'active' : '' }}"
                                                            data-employee-id="{{ $employee->id }}"
                                                            data-letter-class="{{ $letterClass }}"
                                                            title="{{ $label }}">
                                                        @if($img)
                                                            <img src="{{ $img }}"
                                                                 alt="{{ $label }}"
                                                                 class="day-detail-avatar-img"
                                                                 data-initials="{{ $initialsText }}"
                                                                 data-letter-class="{{ $letterClass }}"
                                                                 onerror="window.dayDetailAvatarImgError && window.dayDetailAvatarImgError(this)">
                                                        @else
                                                            <span class="day-detail-mini-avatar day-detail-avatar-initials {{ $letterClass }}">{{ $initialsText }}</span>
                                                        @endif
                                                        <span>{{ $label }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @include('adminmodule::admin.report.partials.daily-employee-detail-metrics')
                </div>
            </div>

            @include('adminmodule::admin.report.partials.daily-employee-detail-sections')
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        window.dayDetailAvatarImgError = function (img) {
            if (!img || img.dataset.fallbackApplied === '1') {
                return;
            }
            img.dataset.fallbackApplied = '1';
            var initials = img.getAttribute('data-initials') || 'E';
            var letterClass = img.getAttribute('data-letter-class') || '';
            var parent = img.parentElement;
            if (parent && parent.classList.contains('day-detail-avatar')) {
                parent.classList.add('has-initials');
                if (letterClass) {
                    parent.classList.add(letterClass);
                }
            }
            var span = document.createElement('span');
            span.className = img.closest('.dropdown-item')
                ? ('day-detail-mini-avatar day-detail-avatar-initials ' + letterClass).trim()
                : 'day-detail-avatar-initials';
            span.textContent = initials;
            img.replaceWith(span);
        };

        (function () {
            var group = document.getElementById('day-detail-avatar-group');
            var dateInput = document.getElementById('day-detail-date');
            if (!group) {
                return;
            }

            var baseUrl = group.getAttribute('data-base-url');
            var selected = [];
            var requestToken = 0;
            try {
                selected = JSON.parse(group.getAttribute('data-selected') || '[]') || [];
            } catch (e) {
                selected = [];
            }
            selected = selected.map(String);

            function buildUrl(ids, dateValue, withAjax) {
                var params = new URLSearchParams();
                params.set('date', dateValue || group.getAttribute('data-date') || '');
                ids.forEach(function (id) {
                    params.append('employee_ids[]', id);
                });
                if (withAjax) {
                    params.set('ajax', '1');
                }
                return baseUrl + '?' + params.toString();
            }

            function syncActiveState() {
                var isAll = selected.length === 0;
                group.querySelectorAll('[data-employee-id]').forEach(function (el) {
                    var id = String(el.getAttribute('data-employee-id') || '');
                    if (id === 'all') {
                        el.classList.toggle('is-active', isAll);
                        return;
                    }
                    var active = selected.indexOf(id) >= 0;
                    el.classList.toggle('is-active', active);
                    el.classList.toggle('active', active);
                });
                group.setAttribute('data-selected', JSON.stringify(selected));
            }

            function replaceOuter(id, html) {
                var current = document.getElementById(id);
                if (!current) {
                    return;
                }
                var wrap = document.createElement('div');
                wrap.innerHTML = (html || '').trim();
                var next = wrap.firstElementChild;
                if (next) {
                    current.replaceWith(next);
                }
            }

            function loadContent(ids) {
                var dateValue = dateInput ? dateInput.value : group.getAttribute('data-date');
                var url = buildUrl(ids, dateValue, false);
                var ajaxUrl = buildUrl(ids, dateValue, true);
                var token = ++requestToken;

                group.classList.add('is-loading');
                history.replaceState({}, '', url);

                fetch(ajaxUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                    .then(function (res) {
                        if (!res.ok) {
                            throw new Error('Failed to load');
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        if (token !== requestToken) {
                            return;
                        }
                        selected = (data.employee_ids || []).map(String);
                        group.setAttribute('data-date', data.date || dateValue);
                        syncActiveState();
                        replaceOuter('day-detail-metrics', data.metrics_html);
                        replaceOuter('day-detail-sections', data.sections_html);
                    })
                    .catch(function () {
                        if (token !== requestToken) {
                            return;
                        }
                        window.location.href = url;
                    })
                    .finally(function () {
                        if (token === requestToken) {
                            group.classList.remove('is-loading');
                        }
                    });
            }

            function toggleEmployee(id) {
                id = String(id);
                if (id === 'all') {
                    selected = [];
                } else {
                    var idx = selected.indexOf(id);
                    if (idx >= 0) {
                        selected.splice(idx, 1);
                    } else {
                        selected.push(id);
                    }
                }
                syncActiveState();
                loadContent(selected);
            }

            group.addEventListener('click', function (event) {
                var btn = event.target.closest('[data-employee-id]');
                if (!btn || !group.contains(btn)) {
                    return;
                }
                // Ignore the +N overflow toggle button itself.
                if (btn.classList.contains('is-more')) {
                    return;
                }
                event.preventDefault();
                toggleEmployee(btn.getAttribute('data-employee-id'));
            });

            if (dateInput) {
                dateInput.addEventListener('change', function () {
                    loadContent(selected);
                });
            }
        })();
    </script>
@endpush
