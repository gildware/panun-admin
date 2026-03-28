@extends('adminmodule::layouts.master')

@section('title', translate('employee_add'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}"/>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Add_New_employee')}}</h2>
                    </div>

                    <div class="card">
                        <div class="card-body py-4">
                            <form id="add-new-employee-form" action="{{route('admin.employee.store')}}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div>
                                    <div>
                                        <div class="d-flex gap-1 flex-column">
                                            <h4>{{translate('Employee Info')}}</h4>
                                            <p class="fs-12">{{translate('Give employee’s basic and account info')}}</p>
                                        </div>
                                    </div>
                                    <section>
                                            <div class="d-flex flex-column gap-1 mb-20">
                                                <h3>{{translate('General_Information')}}</h3>
                                                <p class="fs-12">{{translate('Fill an employee’s general info such as name, address number and set role')}}</p>
                                            </div>

                                            <div class="row mb-30 g-4">
                                                <div class="col-lg-8">
                                                    <div class="bg-light rounded p-xxl-4 p-4 h-100">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-30">
                                                                <div class="input-wrap form-floating form-floating__icon">
                                                                    <input type="text" class="form-control" name="first_name"
                                                                            placeholder="{{translate('First_name')}}"
                                                                            value="{{old('first_name')}}" required>
                                                                    <label>{{translate('First_name')}}</label>
                                                                    <span class="material-icons">account_circle</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-30">
                                                                <div class="input-wrap form-floating form-floating__icon">
                                                                    <input type="text" class="form-control" name="last_name"
                                                                            placeholder="{{translate('Last_name')}}"
                                                                            value="{{old('last_name')}}" required>
                                                                    <label>{{translate('Last_name')}}</label>
                                                                    <span class="material-icons">account_circle</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-30">
                                                                <div class="input-wrap form-floating form-floting-fix">
                                                                    <label for="phone">{{translate('Phone_number')}}</label>
                                                                    <input type="tel"
                                                                           class="form-control"
                                                                           name="phone"
                                                                            placeholder="{{translate('Phone_number')}}"
                                                                            value="{{old('phone')}}" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-30">
                                                                <div class="input-wrap form-floating form-floating__icon">
                                                                    <input type="text" class="form-control" id="address" name="address"
                                                                            placeholder="{{translate('address')}}"
                                                                            value="{{old('address')}}" required>
                                                                    <label>{{translate('Address')}}</label>
                                                                    <span class="material-icons">home</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-30">
                                                                <div class="input-wrap">
                                                                    <select class="select-identity theme-input-style role-btn" name="role_id" required>
                                                                        <option selected disabled>{{translate('Select_role')}}</option>
                                                                        @foreach($roles as $role)
                                                                            <option value="{{$role->id}}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{$role->role_name}}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 mb-30">
                                                                <div class="input-wrap">
                                                                    <select class="zone-select theme-input-style"
                                                                            name="zone_ids[]" id="zone_selector__select" multiple required>
                                                                        <option value="all">{{translate('Select All')}}</option>
                                                                        @foreach($zones as $zone)
                                                                            <option value="{{$zone->id}}" {{ in_array($zone->id, old('zone_ids', [])) ? 'selected' : '' }}>{{$zone->name}}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="bg-light rounded p-xxl-4 p-3 h-100">
                                                        <div class="d-flex flex-column gap-1 align-items-center">
                                                            <div class="input-wrap">
                                                                <div class="d-flex flex-column align-items-center gap-3">
                                                                    <div class="text-center">
                                                                        <div class="text-dark fs-16 mb-1">{{translate('Image')}} <span class="text-muted fs-12">({{translate('Optional')}})</span></div>
                                                                        <div class="text-muted fs-12">{{translate('Upload your cover Image')}}</div>
                                                                    </div>
                                                                    <div class="d-flex flex-column align-items-center">
                                                                        <div class="upload-file">
                                                                            <span class="upload-file__edit">
                                                                                <span class="material-icons">edit</span>
                                                                            </span>
                                                                            <input type="file" id="uploadImage" class="upload-file__input"
                                                                                   name="profile_image"
                                                                                   accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                                                   data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                                            <div class="upload-file__img border-dashed-1-gray rounded">
                                                                                <img
                                                                                    src="{{asset('assets/admin-module')}}/img/img-upload-new-small.png"
                                                                                    alt="">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <p class="opacity-75 mx-auto text-center fs-12">
                                                                        {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                                        {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                                        <strong class="text-dark">1:1</strong>
                                                                    </p>
                                                                </div>
                                                                <div class="file_error">
    
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-column gap-1 mb-20">
                                                <h3>{{translate('Account_Information')}}</h3>
                                                <p class="fs-12">{{translate('This info will need for employee’s future login')}}</p>
                                            </div>
                                            <div class="bg-light rounded p-xxl-4 p-3">
                                                <div class="row g-4">
                                                    <div class="col-lg-4">
                                                        <div class="input-wrap m-0 form-floating form-floating__icon">
                                                            <input type="email" class="form-control" name="email"
                                                                    placeholder="{{translate('Email_*')}}"
                                                                    value="{{old('email')}}" required>
                                                            <label>{{translate('Email_*')}}</label>
                                                            <span class="material-icons">mail</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="input-wrap m-0 form-floating form-floating__icon">
                                                            <input type="password" class="form-control" name="password" value="password"
                                                                    placeholder="{{translate('Password')}}" id="pass" required>
                                                            <label>{{translate('Password')}}</label>
                                                            <span class="material-icons togglePassword">visibility_off</span>
                                                            <span class="material-icons">lock</span>
                                                        </div>
                                                        @error('password')
                                                            <small class="text-danger d-flex mt-1">{{ $message }}</small>
                                                        @else
                                                            <small class="text-muted d-flex mt-1">{{translate('Password_Must_be_at_Least_8_Digits')}}</small>
                                                        @enderror
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="input-wrap m-0 form-floating form-floating__icon">
                                                            <input type="password" class="form-control" name="confirm_password"
                                                                    value="password"
                                                                    placeholder="{{translate('Confirm_Password')}}" id="confirm_password"
                                                                    required>
                                                            <label>{{translate('Confirm_Password')}}</label>
                                                            <span class="material-icons togglePassword">visibility_off</span>
                                                            <span class="material-icons">lock</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </section>
                                    <div>
                                        <div class="d-flex gap-1 flex-column">
                                            <h4>{{translate('Set Permissions')}}</h4>
                                            <p class="fs-12">{{translate('Set what individuals on this role can do')}}</p>
                                        </div>
                                    </div>
                                    <section>
                                        <div class="d-flex flex-column gap-1 mb-20">
                                            <h4>{{translate('Set_Permission')}}</h4>
                                            <p>{{translate('Modify what individuals on this role can do')}}</p>
                                        </div>
                                        <div class="role-access-permission">
                                        </div>
                                    </section>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/provider-module')}}/plugins/jquery-steps/jquery.steps.min.js"></script>
    <script src="{{asset('assets/provider-module')}}/plugins/jquery-validation/jquery.validate.min.js"></script>
    <script>
        "use strict";

        $(document).ready(function() {
            $('.role-btn').change(function() {
                var roleId = $(this).val();

                $.ajax({
                    url: '{{route('admin.employee.ajax.role.access')}}',
                    method: 'GET',
                    data: { role_id: roleId },
                    success: function(response) {
                        $('.role-access-permission').html(response.html);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });
        });

        var form = $("#add-new-employee-form");
        form.validate({
            errorPlacement: function errorPlacement(error, element) { element.parents('.input-wrap').after(error); },
            rules: {
                password: {
                    minlength: 8,
                },
                confirm_password: {
                    minlength: 8,
                    equalTo: "#pass"
                }
            }
        });
        form.children("div").steps({
            headerTag: "div",
            bodyTag: "section",
            transitionEffect: "slideLeft",
            onStepChanging: function (event, currentIndex, newIndex) {
                form.validate().settings.ignore = ":disabled,:hidden";
                const oFile = document.getElementById("uploadImage").files[0];
                if (oFile && oFile.size > 2097152) {
                    return false;
                }
                return form.valid();
            },
            onFinishing: function (event, currentIndex) {
                form.submit();
            },
            onFinished: function (event, currentIndex) {
                form.submit();
            }
        });
    </script>
    <script>
        "use strict";

        $('#zone_selector__select').on('change', function() {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

    </script>

    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script src="{{asset('assets/admin-module')}}/js/section/employee/custom.js"></script>


@endpush
