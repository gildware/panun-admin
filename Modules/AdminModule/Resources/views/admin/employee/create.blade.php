@extends('adminmodule::layouts.master')

@section('title', 'Add employee')

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">Add employee</h2>
            </div>
            <div class="card">
                <div class="card-body py-4">
                    <form action="{{ route('admin.employee.store') }}" method="post">
                        @csrf
                        <p class="mb-4">Name, email, password, and roles. The rest of the file is filled on the profile.</p>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">First name</label>
                                <input id="first_name" class="form-control" name="first_name" value="{{ old('first_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Last name</label>
                                <input id="last_name" class="form-control" name="last_name" value="{{ old('last_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password</label>
                                <input id="password" class="form-control" type="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm password</label>
                                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="work_schedule">Full time or part time</label>
                                <select id="work_schedule" class="form-control" name="work_schedule" required>
                                    <option value="full_time" @selected(old('work_schedule', 'full_time') === 'full_time')>Full time</option>
                                    <option value="part_time" @selected(old('work_schedule') === 'part_time')>Part time</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_type">Billing</label>
                                <select id="billing_type" class="form-control" name="billing_type" required>
                                    @foreach(\Modules\AdminModule\Entities\PeopleProfile::BILLING_TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('billing_type', 'billable') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="requires_timesheet">Timesheet</label>
                                <select id="requires_timesheet" class="form-control" name="requires_timesheet" required>
                                    <option value="1" @selected((string) old('requires_timesheet', '1') === '1')>Required</option>
                                    <option value="0" @selected((string) old('requires_timesheet', '1') === '0')>Can skip</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="work_location">Work location</label>
                                <select id="work_location" class="form-control" name="work_location">
                                    <option value="">Not set</option>
                                    @foreach(\Modules\AdminModule\Entities\PeopleProfile::WORK_LOCATIONS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('work_location') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Roles</label>
                                <div class="d-flex flex-column gap-2">
                                    @forelse($roles as $role)
                                        <label class="d-flex align-items-center gap-2 mb-0">
                                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', []), true))>
                                            <span>{{ $role->role_name }}</span>
                                        </label>
                                    @empty
                                        <p class="mb-0">No roles yet. Add one under Roles and Permission.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mt-4">
                            <a class="btn btn--secondary" href="{{ route('admin.employee.index') }}">Cancel</a>
                            <button class="btn btn--primary" type="submit">Save and open profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
