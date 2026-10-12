@extends('adminmodule::layouts.master')

@section('title', 'Export bookings')

@push('css_or_js')
    <style>
        .booking-export-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .booking-export-group {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 14px 14px 8px;
            background: #fff;
        }
        .booking-export-group h3 {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .booking-export-option {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            cursor: pointer;
        }
        .booking-export-preview {
            max-height: 420px;
        }
        .booking-export-preview th,
        .booking-export-preview td {
            white-space: nowrap;
            font-size: 13px;
        }
        .booking-export-preview td {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                <div>
                    <h2 class="page-title mb-1">Export bookings</h2>
                    <p class="mb-0 text-muted">
                        {{ ucwords(str_replace('_', ' ', $bookingStatus)) }} list · {{ $total }} {{ $total === 1 ? 'booking' : 'bookings' }}
                    </p>
                </div>
                <a href="{{ $backUrl }}" class="btn btn--secondary btn-sm h-45" data-turbo="false">Back to list</a>
            </div>

            <form method="POST" action="{{ route('admin.booking.download') }}" data-turbo="false" id="booking-export-form">
                @csrf
                <input type="hidden" name="column_selection" value="1">
                @foreach($hiddenFilters as $name => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                        @endforeach
                    @elseif($value !== null && $value !== '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h3 class="mb-1">Columns</h3>
                                <p class="mb-0 text-muted">All booking and lead fields are selected. Uncheck any you do not want in the file.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn--secondary btn-sm" id="booking-export-all">Select all</button>
                                <button type="button" class="btn btn--secondary btn-sm" id="booking-export-none">Clear</button>
                            </div>
                        </div>

                        <div class="booking-export-columns">
                            @foreach($groups as $groupName => $groupColumns)
                                <section class="booking-export-group">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h3 class="mb-0">{{ $groupName }}</h3>
                                        <button type="button" class="btn btn-link btn-sm p-0 booking-export-group-toggle" data-group="{{ $groupName }}">All</button>
                                    </div>
                                    <div class="mt-2">
                                        @foreach($groupColumns as $key => $label)
                                            <label class="booking-export-option">
                                                <input type="checkbox" name="columns[]" value="{{ $key }}" checked data-export-column="{{ $key }}" data-export-group="{{ $groupName }}">
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h3 class="mb-1">Preview</h3>
                                <p class="mb-0 text-muted">
                                    @if($total === 0)
                                        No bookings match this list.
                                    @else
                                        First {{ count($previewRows) }} of {{ $total }}. The download includes the full list.
                                    @endif
                                </p>
                            </div>
                            <button type="submit" class="btn btn--primary" id="booking-export-submit" @disabled($total === 0)>
                                Download Excel
                            </button>
                        </div>

                        <div class="table-responsive booking-export-preview">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        @foreach($columns as $key => $label)
                                            <th data-column="{{ $key }}">{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($previewRows as $row)
                                        <tr>
                                            @foreach($columns as $key => $label)
                                                <td data-column="{{ $key }}" title="{{ $row[$label] ?? '' }}">{{ $row[$label] ?? '-' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($columns) }}" class="text-center text-muted">Nothing to preview.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('booking-export-form');
            if (!form) {
                return;
            }

            var boxes = Array.prototype.slice.call(form.querySelectorAll('[data-export-column]'));
            var submit = document.getElementById('booking-export-submit');

            function sync() {
                var selected = 0;
                boxes.forEach(function (box) {
                    var show = box.checked;
                    if (show) {
                        selected += 1;
                    }
                    form.querySelectorAll('[data-column="' + box.dataset.exportColumn + '"]').forEach(function (cell) {
                        cell.hidden = !show;
                    });
                });
                if (submit) {
                    submit.disabled = selected === 0 || {{ (int) $total }} === 0;
                }
            }

            boxes.forEach(function (box) {
                box.addEventListener('change', sync);
            });

            document.getElementById('booking-export-all').addEventListener('click', function () {
                boxes.forEach(function (box) { box.checked = true; });
                sync();
            });

            document.getElementById('booking-export-none').addEventListener('click', function () {
                boxes.forEach(function (box) { box.checked = false; });
                sync();
            });

            form.querySelectorAll('.booking-export-group-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    boxes.filter(function (box) {
                        return box.dataset.exportGroup === button.dataset.group;
                    }).forEach(function (box) {
                        box.checked = true;
                    });
                    sync();
                });
            });

            sync();
        })();
    </script>
@endpush
