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
