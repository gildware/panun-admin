@php
    $impersonatableEmployees = $impersonatableEmployees ?? collect();
@endphp

@if(can_impersonate_employees() && $impersonatableEmployees->isNotEmpty())
    <div class="dropdown top-utility-item">
        <button type="button"
                class="top-utility-action-btn dropdown-toggle border-0"
                data-bs-toggle="dropdown"
                data-bs-offset="0,6"
                data-bs-popper-config='{"strategy":"fixed"}'
                aria-expanded="false"
                title="{{ translate('View_dashboard_as') }}">
            <span class="material-symbols-outlined">visibility</span>
            <span class="d-none d-lg-inline">{{ translate('View_as') }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end view-as-employee-menu py-2">
            <div class="px-3 pb-2 mb-1 border-bottom">
                <span class="small text-muted d-block mb-2">{{ translate('View_dashboard_as') }}</span>
                <input type="search"
                       class="form-control form-control-sm js-view-as-employee-search"
                       placeholder="{{ translate('search_here') }}"
                       autocomplete="off">
            </div>
            <div class="view-as-employee-list">
                @foreach($impersonatableEmployees as $employee)
                    @php
                        $employeeName = trim($employee->first_name.' '.$employee->last_name);
                        $employeeRole = $employee->roles->first()?->role_name ?? '';
                        $searchText = strtolower($employeeName.' '.$employee->email.' '.$employeeRole);
                    @endphp
                    <a href="{{ route('admin.employee.impersonate', $employee->id) }}"
                       class="dropdown-item d-flex flex-column align-items-start gap-0 py-2 js-view-as-employee-item"
                       data-search="{{ $searchText }}"
                       data-turbo="false">
                        <span class="fw-semibold">{{ $employeeName }}</span>
                        @if($employeeRole !== '')
                            <span class="small text-muted">{{ $employeeRole }}</span>
                        @endif
                    </a>
                @endforeach
                <div class="js-view-as-employee-empty px-3 py-2 small text-muted d-none">
                    {{ translate('no_data_available') }}
                </div>
            </div>
        </div>
    </div>

    @once
        @push('css_or_js')
            <style>
                .view-as-employee-menu {
                    min-width: 16rem;
                    max-width: min(22rem, 92vw);
                }

                .view-as-employee-list {
                    max-height: min(20rem, 50vh);
                    overflow-y: auto;
                }
            </style>
        @endpush

        @push('script')
            <script>
                (function () {
                    document.addEventListener('input', function (event) {
                        const input = event.target.closest('.js-view-as-employee-search');
                        if (!input) {
                            return;
                        }

                        const menu = input.closest('.view-as-employee-menu');
                        if (!menu) {
                            return;
                        }

                        const query = (input.value || '').trim().toLowerCase();
                        let visibleCount = 0;

                        menu.querySelectorAll('.js-view-as-employee-item').forEach(function (item) {
                            const haystack = item.dataset.search || '';
                            const visible = query === '' || haystack.indexOf(query) !== -1;
                            item.classList.toggle('d-none', !visible);
                            if (visible) {
                                visibleCount++;
                            }
                        });

                        const emptyState = menu.querySelector('.js-view-as-employee-empty');
                        if (emptyState) {
                            emptyState.classList.toggle('d-none', visibleCount > 0);
                        }
                    });

                    document.addEventListener('hidden.bs.dropdown', function (event) {
                        const menu = event.target.querySelector('.view-as-employee-menu');
                        if (!menu) {
                            return;
                        }

                        const input = menu.querySelector('.js-view-as-employee-search');
                        if (input) {
                            input.value = '';
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                })();
            </script>
        @endpush
    @endonce
@endif
