@extends('adminmodule::layouts.master')

@section('title', translate('employee_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/dataTables/select.dataTables.min.css"/>
    <style>
        .employee-list-search { width: min(100%, 26rem); }
        .employee-list-search .search-form__input_group { width: 100%; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{translate('employee_list')}}</h2>
                        @can('employee_add')
                            <div>
                                <a href="{{route('admin.employee.create')}}" class="btn btn--primary">
                                    <span class="material-icons">add</span>
                                    {{translate('add_employee')}}
                                </a>
                            </div>
                        @endcan
                    </div>

                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{$status=='all'?'active':''}}"
                                   href="{{url()->current()}}?{{ http_build_query(array_filter(['status' => 'all', 'search' => $search])) }}">
                                    {{translate('all')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='active'?'active':''}}"
                                   href="{{url()->current()}}?{{ http_build_query(array_filter(['status' => 'active', 'search' => $search])) }}">
                                    {{translate('active')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='inactive'?'active':''}}"
                                   href="{{url()->current()}}?{{ http_build_query(array_filter(['status' => 'inactive', 'search' => $search])) }}">
                                    {{translate('inactive')}}
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total_Employees')}}:</span>
                            <span class="title-color">{{$employees->total()}}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <div class="search-form search-form_style-two employee-list-search">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search"
                                               id="employee-list-search"
                                               class="theme-input-style search-form__input"
                                               value="{{ $search }}"
                                               placeholder="Search name, email, or employee ID"
                                               aria-label="Search name, email, or employee ID"
                                               autocomplete="off"
                                               spellcheck="false"
                                               enterkeyhint="search"
                                               data-status="{{ $status }}">
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="dropdown">
                                        @can('employee_export')
                                            <button type="button"
                                                    class="btn btn--secondary text-capitalize dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                <span class="material-icons">file_download</span> download
                                            </button>
                                        @endcan
                                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                            <a class="dropdown-item"
                                               href="{{route('admin.employee.download')}}?search={{$search}}">
                                                {{translate('excel')}}
                                            </a>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Employee type</th>
                                        <th>Billing</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Roles</th>
                                        <th>Department</th>
                                        <th>Employee ID</th>
                                        @can('employee_manage_status')
                                            <th class="text-center">{{translate('status')}}</th>
                                        @endcan
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($employees as $key => $employee)
                                        <tr>
                                            <td>
                                                <img src="{{ $employee->profile_image_full_path }}" alt="" width="40" height="40" class="rounded-circle object-fit-cover">
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.employee.profile', $employee->id) }}">{{ $employee->first_name }} {{ $employee->last_name }}</a>
                                            </td>
                                            <td>{{ ($employee->peopleProfile->employment_stage ?: 'permanent') === 'probation' ? 'Probation' : 'Permanent' }}</td>
                                            <td>{{ \Modules\AdminModule\Entities\PeopleProfile::billingTypeLabel($employee->peopleProfile->billing_type ?: 'billable') }}</td>
                                            <td>{{ $employee->email }}</td>
                                            <td>{{ $employee->phone ?: '—' }}</td>
                                            <td>{{ $employee->roles->pluck('role_name')->join(', ') ?: '—' }}</td>
                                            <td>{{ $employee->peopleProfile->department ?: '—' }}</td>
                                            <td>{{ \Modules\AdminModule\Services\PeopleWorkspace::formatCode($employee->peopleProfile->employee_code ?? null) }}</td>

                                        @can('employee_manage_status')
                                                <td>
                                                    <label class="switcher mx-auto" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input"
                                                               type="checkbox"
                                                               {{$employee->is_active?'checked':''}} data-status="{{$employee->id}}">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>
                                            @endcan
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="dropdown dropdown__style--two">
                                                        <button type="button" class="bg-transparent border-0 title-color"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <span class="material-symbols-outlined">more_vert</span>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            @can('employee_view')
                                                                <a class="dropdown-item"
                                                                   href="{{ route('admin.employee.profile', $employee->id) }}">{{translate('View Profile')}}</a>
                                                            @endcan
                                                            @if(can_impersonate_employees() && $employee->is_active)
                                                                <a class="dropdown-item"
                                                                   href="{{ route('admin.employee.impersonate', $employee->id) }}"
                                                                   data-turbo="false">{{ translate('View_dashboard_as') }}</a>
                                                            @endif
                                                            @can('employee_delete')
                                                                <button type="button" data-delete="{{$employee->id}}"
                                                                        class="dropdown-item delete-action">{{translate('Delete Employee')}}
                                                                </button>
                                                                <form
                                                                    action="{{route('admin.employee.delete',[$employee->id])}}"
                                                                    method="post" id="delete-{{$employee->id}}"
                                                                    class="hidden">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                </form>
                                                            @endcan
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11"><p
                                                    class="text-center">{{translate('no_data_available')}}</p></td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end">
                                {!! $employees->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/js/custom.js"></script>
    <script src="{{asset('assets/admin-module')}}/plugins/dataTables/jquery.dataTables.min.js"></script>
    <script>
        "use strict";

        $('.switcher_input').on('click', function () {
            let itemId = $(this).data('status');
            let route = '{{ route('admin.employee.status-update', ['id' => ':itemId']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_update_status') }}');
        })

        $('.delete-action').on('click', function (event) {
            event.stopPropagation();
            let itemId = $(this).data('delete');
            @if(env('APP_ENV')!='demo')
            form_alert('delete-' + itemId, '{{translate('want_to_delete_this_employee')}}?')
            @endif
        })

        $('.remove').on('click', function (event) {
            event.stopPropagation();
            let itemId = $(this).data('remove');
            @if(env('APP_ENV')!='demo')
            form_alert('delete-' + itemId, '{{translate('want_to_delete_this_employee')}}?')
            @endif
        })

        ;(function () {
            var input = document.getElementById('employee-list-search');
            if (!input) return;

            var delay = 300;
            var timer = null;
            var composing = false;
            var loaded = input.value.trim();

            try {
                if (sessionStorage.getItem('employeeListSearchFocus') === '1') {
                    sessionStorage.removeItem('employeeListSearchFocus');
                    input.focus({ preventScroll: true });
                    var end = input.value.length;
                    input.setSelectionRange(end, end);
                }
            } catch (e) {}

            function visit(value) {
                if (value === loaded) return;
                var url = new URL(window.location.href);
                url.searchParams.delete('page');
                if (value) url.searchParams.set('search', value);
                else url.searchParams.delete('search');
                if (!url.searchParams.get('status')) {
                    url.searchParams.set('status', input.getAttribute('data-status') || 'all');
                }
                try { sessionStorage.setItem('employeeListSearchFocus', '1'); } catch (e) {}
                if (typeof window.adminPartialNavLoad === 'function') {
                    window.adminPartialNavLoad(url.pathname + url.search);
                    return;
                }
                window.location.assign(url.toString());
            }

            function schedule() {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    if (!composing) visit(input.value.trim());
                }, delay);
            }

            input.addEventListener('compositionstart', function () { composing = true; });
            input.addEventListener('compositionend', function () { composing = false; schedule(); });
            input.addEventListener('input', schedule);
            input.addEventListener('search', function () {
                clearTimeout(timer);
                visit(input.value.trim());
            });
            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                clearTimeout(timer);
                visit(input.value.trim());
            });
        })();
    </script>
@endpush
