@extends('adminmodule::layouts.master')

@section('title',translate('update_provider'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/swiper/swiper-bundle.min.css')}}">
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @php
                $updated = session('provider_updated');
            @endphp
            @if(is_array($updated) && !empty($updated['id']))
                <div class="modal fade" id="providerUpdatedSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title">{{ translate('Provider_updated_successfully') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                            </div>
                            <div class="modal-body pt-0">
                                <p class="mb-4 text-muted">{{ translate('Provider_information_updated_successfully') }}</p>
                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                    <a href="{{ route('admin.provider.edit', [$updated['id']]) }}" class="btn btn--secondary order-2 order-sm-1">
                                        {{ translate('Edit_again') }}
                                    </a>
                                    <a href="{{ route('admin.provider.details', [$updated['id'], 'web_page' => 'overview']) }}" class="btn btn--primary order-1 order-sm-2">
                                        {{ translate('View_provider_details') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mb-3" role="alert" id="provider-edit-server-validation-alert">
                    <p class="fw-semibold mb-2">{{ translate('Please_review_the_following_issues') }}</p>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{route('admin.provider.update', [$provider->id])}}" method="POST" id="create-provider-form"
                  enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="plan_type" value="{{ $packageSubscription ? 'subscription_based' : 'commission_based' }}">
                <input type="hidden" name="selected_package_id" value="{{ $packageSubscription?->subscription_package_id }}">
                <h3>{{translate('Step 1')}}</h3>
                <section>
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('update_Provider')}}</h2>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-4 create-provider-item mb-4">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="material-symbols-outlined icon-1">check</span>
                                    {{ translate('Basic info') }}
                                </div>
                            </div>
                            @include('providermanagement::admin.provider.partials.provider-add-edit-form', ['mode' => 'edit', 'zones' => $zones, 'zoneTree' => $zoneTree, 'provider' => $provider])

                            @if(false)
                            <fieldset disabled class="d-none">
                                <div class="row">
                                <div class="col-md-6" id="register-form-p-0">
                                    <h4 class="c1 mb-20">{{translate('General_Information')}}</h4>
                                    <div class="mb-30">
                                        <label class="mb-2 title-color">{{ translate('Provider_Type') }}</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="provider_type"
                                                       id="provider_type_individual"
                                                       value="individual"
                                                       {{ ($provider->provider_type ?? 'individual') === 'individual' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="provider_type_individual">
                                                    {{ translate('Individual') }}
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="provider_type"
                                                       id="provider_type_company"
                                                       value="company"
                                                       {{ ($provider->provider_type ?? 'individual') === 'company' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="provider_type_company">
                                                    {{ translate('Company') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-floating form-floating__icon mb-30 provider-company-fields">
                                        <input type="text" class="form-control"
                                               value="{{$provider->company_name}}"
                                               name="company_name" maxlength="191"
                                               placeholder="{{translate('Company_/_Individual_Name')}}">
                                        <label>{{translate('Company_/_Individual_Name')}}</label>
                                        <span class="material-icons">store</span>
                                    </div>
                                    <div class="form-floating form-floting-fix mb-30 provider-company-fields">
                                        <label for="company_phone">
                                            {{translate('Phone')}}
                                        </label>
                                        <input type="tel" class="form-control"
                                               id="old_company_phone"
                                               name="company_phone" value="{{$provider->company_phone}}"
                                               placeholder="{{translate('Phone')}}">
                                    </div>
                                    <div class="form-floating form-floating__icon mb-30 provider-company-fields">
                                        <input type="email" class="form-control"
                                               name="company_email" value="{{$provider->company_email}}"
                                               placeholder="{{translate('Email')}}">
                                        <label>{{translate('Email')}}</label>
                                        <span class="material-icons">mail</span>
                                    </div>
                                    <div class="form-floating mb-30">
                                        <select class="select-identity theme-input-style w-100" name="zone_id"
                                                required>
                                            <option disabled selected>{{translate('Select_Zone')}}</option>
                                            @foreach($zones as $zone)
                                                <option value="{{$zone->id}}"
                                                    {{$provider->zone_id == $zone->id ? 'selected': ''}}>
                                                    {{$zone->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-floating mb-30">
                                                <textarea class="form-control resize-none" placeholder="{{translate('Address')}}"
                                                          name="company_address"
                                                          required>{{$provider->company_address}}</textarea>
                                                <label>{{translate('Address')}}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 provider-logo-fields">
                                            <div class="d-flex flex-column align-items-center gap-3">
                                                <h3 class="mb-0">{{translate('Company_Logo')}}</h3>
                                                <div>
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input" name="logo"
                                                               accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                               data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                        <div class="upload-file__img">
                                                            <img src="{{ $provider->logo_full_path }}" alt="{{translate('image')}}">
                                                        </div>
                                                        <span class="upload-file__edit">
                                                            <span class="material-icons">edit</span>
                                                        </span>
                                            </div>
                                        </div>
                                                <p class="opacity-75 max-w220">
                                                    {{ translate('Image format -')}} {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                    {{ translate("Image Size") }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                    {{ translate('Image Ratio') }} - 1:1
                                                </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row gx-2 mt-2">
                        <div class="col-md-6 order-md-2">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="company-docs-fields provider-company-identity-fields">
                                        <h4 class="c1 mb-20">Company Docs & Identity</h4>

                                        <div class="mb-30">
                                            <select class="select-identity theme-input-style w-100"
                                                    name="company_identity_type" required>
                                                <option selected
                                                        disabled>{{translate('Select_Identity_Type')}}</option>
                                                <option value="trade_license"
                                                        {{($provider->company_identity_type ?? 'trade_license') == 'trade_license' ? 'selected': ''}}>
                                                    {{translate('Trade_License')}}</option>
                                                <option value="company_id"
                                                        {{$provider->company_identity_type == 'company_id' ? 'selected': ''}}>
                                                    {{translate('Company_Id')}}</option>
                                            </select>
                                        </div>

                                        <div class="form-floating form-floating__icon mb-30">
                                            <input type="text" class="form-control" name="company_identity_number"
                                                   value="{{$provider->company_identity_number}}"
                                                   placeholder="{{translate('Identity_Number')}}" required>
                                            <label>{{translate('Identity_Number')}}</label>
                                            <span class="material-icons">badge</span>
                                        </div>

                                        <div class="upload-file w-100">
                                            <h3 class="mb-3">{{translate('Identification_Image')}}</h3>
                                            <div id="old_company_multi_image_picker">
                                                @foreach($provider->company_identity_images_full_path ?? [] as $image)
                                                    @php
                                                        $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION));
                                                    @endphp
                                                    @if($ext === 'pdf')
                                                        <a class="p-1 text-decoration-none" href="{{ $image }}" target="_blank" rel="noopener">PDF</a>
                                                    @else
                                                        <img class="p-1" height="150" src="{{ $image }}" alt="{{translate('image')}}">
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="upload-file w-100 mt-3">
                                            <h3 class="mb-3">{{translate('Identification_PDF')}}</h3>
                                            <div class="multi-attachment-uploader" data-attachment-uploader>
                                                <input type="file"
                                                       class="d-none"
                                                       name="company_identity_pdf_files[]"
                                                       accept="application/pdf,.pdf"
                                                       multiple
                                                       data-maxFileSize="{{ readableUploadMaxFileSize('file') }}"
                                                       data-attachment-input>

                                                <button type="button"
                                                        class="btn btn--secondary w-100"
                                                        data-attachment-trigger>
                                                    {{translate('Upload_PDF')}}
                                                </button>
                                                <div class="mt-3 d-flex flex-wrap gap-2" data-attachment-preview></div>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="c1 mb-20">{{translate('Business Information')}}</h4>

                                    <div class="mb-30">
                                        <select class="select-identity theme-input-style w-100"
                                                name="identity_type" required>
                                            <option selected
                                                    disabled>{{translate('Select_Identity_Type')}}</option>
                                            <option value="nid"
                                                    {{$provider->owner->identification_type == 'nid' ? 'selected': ''}}>
                                                {{translate('Aadhar_Card')}}</option>
                                            <option value="passport"
                                                    {{$provider->owner->identification_type == 'passport' ? 'selected': ''}}>
                                                {{translate('Passport')}}</option>
                                            <option value="driving_license"
                                                    {{$provider->owner->identification_type == 'driving_license' ? 'selected': ''}}>
                                                {{translate('Driving_License')}}</option>
                                        </select>
                                    </div>
                                    <div class="form-floating form-floating__icon mb-30">
                                        <input type="text" class="form-control" name="identity_number"
                                               value="{{$provider->owner->identification_number}}"
                                               placeholder="{{translate('Identity_Number')}}" required>
                                        <label>{{translate('Identity_Number')}}</label>
                                        <span class="material-icons">badge</span>
                                    </div>

                                            <div class="upload-file w-100">
                                                <h3 class="mb-3">{{translate('Identification_Image')}}</h3>
                                                <div id="old_multi_image_picker">
                                                    @foreach($provider->owner->identification_image_full_path as $image)
                                                        @php
                                                            $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION));
                                                        @endphp
                                                        @if($ext === 'pdf')
                                                            <a class="p-1 text-decoration-none" href="{{ $image }}" target="_blank" rel="noopener">PDF</a>
                                                        @else
                                                            <img class="p-1" height="150" src="{{ $image }}" alt="{{translate('image')}}">
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="upload-file w-100 mt-3">
                                        <h3 class="mb-3">{{translate('Identification_PDF')}}</h3>
                                        <div class="multi-attachment-uploader" data-attachment-uploader>
                                            <input type="file"
                                                   class="d-none"
                                                   name="identity_pdf_files[]"
                                                   accept="application/pdf,.pdf"
                                                   multiple
                                                   data-maxFileSize="{{ readableUploadMaxFileSize('file') }}"
                                                   data-attachment-input>

                                            <button type="button"
                                                    class="btn btn--secondary w-100"
                                                    data-attachment-trigger>
                                                {{translate('Upload_PDF')}}
                                            </button>
                                            <div class="mt-3 d-flex flex-wrap gap-2" data-attachment-preview></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 order-md-1">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                                                <h4 class="c1">{{translate('Contact_Person')}}</h4>
                                            </div>
                                            <div class="form-floating form-floating__icon mb-30">
                                                <input type="text" class="form-control" name="contact_person_name"
                                                       value="{{$provider->contact_person_name}}" placeholder="name"
                                                       maxlength="191" required>
                                                <label>{{translate('Name')}}</label>
                                                <span class="material-icons">account_circle</span>
                                            </div>
                                            <div class="row gx-2">
                                                <div class="col-lg-6">
                                                    <div class="form-floating form-floting-fix mb-30">
                                                        <label for="contact_person_phone">{{translate('Phone')}}</label>
                                                        <input type="tel" class="form-control"
                                                               name="contact_person_phone"
                                                               id="old_contact_person_phone"
                                                               value="{{$provider->contact_person_phone}}"
                                                               placeholder="{{translate('Phone')}}"
                                                               required>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-floating form-floating__icon mb-30">
                                                        <input type="email" class="form-control"
                                                               name="contact_person_email"
                                                               value="{{$provider->contact_person_email}}"
                                                               placeholder="{{translate('Email')}}"
                                                               required>
                                                        <label>{{translate('Email')}}</label>
                                                        <span class="material-icons">mail</span>
                                                    </div>
                                                </div>
                                            </div>

                                    <div class="mb-30">
                                        <div class="upload-file">
                                            <input type="file"
                                                   class="upload-file__input"
                                                   name="contact_person_photo"
                                                   accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                   data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                            <div class="upload-file__img">
                                                <img src="{{onErrorImage($provider->contact_person_photo,
                                                        asset('storage/provider/contact_person_photo').'/' . $provider->contact_person_photo,
                                                        asset('assets/admin-module/img/placeholder.png'),
                                                        'provider/contact_person_photo/')}}"
                                                     alt="{{translate('image')}}">
                                            </div>
                                            <span class="upload-file__edit">
                                                <span class="material-icons">edit</span>
                                            </span>
                                        </div>
                                    </div>

                                    <h4 class="c1 mb-20">{{translate('Account_Information')}}</h4>
                                    <div class="form-floating form-floating__icon mb-30">
                                        <input type="email" class="form-control"
                                               id="account_email" name="account_email" value="{{$provider->owner->email}}" readonly
                                               placeholder="{{translate('Email')}}" required>
                                        <label>{{translate('Email')}}</label>
                                        <span class="material-icons">mail</span>
                                    </div>
                                    <div class="form-floating form-floting-fix mb-30">
                                        <label for="account_phone">{{translate('Phone')}}</label>
                                        <input type="tel" class="form-control"
                                                name="account_phone"
                                                id="account_phone"
                                                value="{{$provider->owner->phone}}"
                                                placeholder="{{translate('Phone')}}"
                                               readonly
                                                required >
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-lg-6">
                                            <div class="form-floating form-floating__icon mb-30">
                                                <input type="password" class="form-control" name="password"
                                                       id="pass"
                                                       placeholder="{{translate('Password')}}" autocomplete="new-password">
                                                <label>{{translate('Password')}}</label>
                                                <span class="material-icons">lock</span>
                                                <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-floating form-floating__icon mb-30">
                                                <input type="password" class="form-control"
                                                       name="confirm_password"
                                                       placeholder="{{translate('Confirm_Password')}}">
                                                <label>{{translate('Confirm_Password')}}</label>
                                                <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                                <span class="material-icons">lock</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                                    <h4 class="c1">{{translate('Additional_Documents')}}</h4>
                                </div>

                                <div id="old_additional_documents_wrapper" class="row gx-3"></div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn--secondary" id="old_additional_document_btn">
                                        {{translate('Add_Document')}}
                                    </button>
                                </div>

                                <template id="old_additional_document_template">
                                    <div class="additional-document-row col-lg-6" data-doc-index="__INDEX__">
                                        <div class="card h-100 mt-3">
                                            <div class="card-body">
                                                <div class="form-floating form-floating__icon mb-30">
                                                    <input type="text" class="form-control"
                                                           name="additional_documents[__INDEX__][name]"
                                                           placeholder="{{translate('Document_Name')}}" maxlength="191">
                                                    <label>{{translate('Document_Name')}}</label>
                                                    <span class="material-icons">description</span>
                                                </div>

                                                <div class="form-floating mb-30">
                                                    <textarea class="form-control resize-none"
                                                              name="additional_documents[__INDEX__][description]"
                                                              placeholder="{{translate('Document_Description')}}"></textarea>
                                                    <label>{{translate('Document_Description')}}</label>
                                                </div>

                                                <div class="mb-30">
                                                    <label class="mb-2 title-color">{{translate('Files')}} </label>

                                                    <div class="multi-attachment-uploader" data-attachment-uploader>
                                                        <input type="file"
                                                               class="d-none"
                                                               name="additional_documents[__INDEX__][files][]"
                                                               multiple
                                                               accept="image/*,application/pdf,.pdf"
                                                               data-maxFileSize="{{ readableUploadMaxFileSize('file') }}"
                                                               data-attachment-input>

                                                        <button type="button"
                                                                class="btn btn--secondary btn-sm w-100"
                                                                data-attachment-trigger>
                                                            {{translate('Upload')}}
                                                        </button>

                                                        <div class="mt-2 d-flex flex-wrap gap-2"
                                                             data-attachment-preview></div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn--secondary remove_additional_document_btn">
                                                        {{translate('Remove')}}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                                    <h4 class="c1">{{translate('Select Address from Map')}}</h4>
                                </div>
                                <div class="row gx-2">
                                    <div class="col-md-6 col-12">
                                        <div class="mb-30">
                                            <div class="form-floating form-floating__icon">
                                                <input type="text" class="form-control" name="latitude"
                                                       id="old_latitude"
                                                       placeholder="{{translate('latitude')}} *"
                                                       value="{{$provider->coordinates['latitude'] ?? null}}"
                                                       required readonly
                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                       title="{{translate('Select from map')}}">
                                                <label>{{translate('latitude')}} *</label>
                                                <span class="material-icons">location_on</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="mb-30">
                                            <div class="form-floating form-floating__icon">
                                                <input type="text" class="form-control" name="longitude"
                                                       id="old_longitude"
                                                       placeholder="{{translate('longitude')}} *"
                                                       value="{{$provider->coordinates['longitude'] ?? null}}"
                                                       required readonly
                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                       title="{{translate('Select from map')}}">
                                                <label>{{translate('longitude')}} *</label>
                                                <span class="material-icons">location_on</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div id="old_location_map_div" class="location_map_class">
                                            <input id="old_pac-input" class="form-control w-auto"
                                                   data-toggle="tooltip"
                                                   data-placement="right"
                                                   data-original-title="{{ translate('search_your_location_here') }}"
                                                   type="text" placeholder="{{ translate('search_here') }}"/>
                                            <div id="old_location_map_canvas"
                                                 class="overflow-hidden rounded canvas_class"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </fieldset>
                    @endif
                </section>
            </form>

            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="c1 mb-2">{{ translate('Authentication') }}</h4>
                    <p class="text-muted small mb-3">
                        {{ translate('Change_the_provider_admin_login_password_separately_from_provider_details') }}
                    </p>

                    @if ($errors->updateOwnerPassword->any())
                        <div class="alert alert-danger mb-3" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->updateOwnerPassword->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.provider.owner-password.update', [$provider->id]) }}" method="POST" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating form-floating__icon">
                                    <input
                                        type="password"
                                        class="form-control"
                                        name="password"
                                        id="provider_owner_password"
                                        minlength="8"
                                        autocomplete="new-password"
                                        required
                                        placeholder="{{ translate('Password') }}">
                                    <label for="provider_owner_password">{{ translate('Password') }}</label>
                                    <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                    <span class="material-icons">lock</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating__icon">
                                    <input
                                        type="password"
                                        class="form-control"
                                        name="password_confirmation"
                                        id="provider_owner_password_confirmation"
                                        minlength="8"
                                        autocomplete="new-password"
                                        required
                                        placeholder="{{ translate('Confirm_Password') }}">
                                    <label for="provider_owner_password_confirmation">{{ translate('Confirm_Password') }}</label>
                                    <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                    <span class="material-icons">lock</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary mt-3">
                            {{ translate('Save_changes') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

    <script src="{{asset('assets/provider-module')}}/js//tags-input.min.js"></script>
    <script src="{{asset('assets/provider-module')}}/js/spartan-multi-image-picker.js"></script>
    <script src="{{asset('assets/provider-module')}}/plugins/jquery-steps/jquery.steps.min.js"></script>
    <script src="{{asset('assets/provider-module')}}/plugins/jquery-validation/jquery.validate.min.js"></script>


    <script src="https://maps.googleapis.com/maps/api/js?key={{business_config('google_map', 'third_party')?->live_values['map_api_key_client']}}&libraries=places&v=3.45.8"></script>

    <script>
        "use strict";

        $(document).ready(function () {
            var successModalEl = document.getElementById("providerUpdatedSuccessModal");
            if (successModalEl && typeof bootstrap !== "undefined") {
                new bootstrap.Modal(successModalEl).show();
            }

            let formWizard = $("#create-provider-form");
            function providerEditIsJqvIgnoredHiddenField(el) {
                if (!el || el.nodeType !== 1) {
                    return true;
                }
                var $e = $(el);
                if (!$e.is(":hidden")) {
                    return false;
                }
                var n = el.name || "";
                if (n === "contact_person_phone" || n === "company_phone") {
                    return false;
                }
                if (n === "contact_person_photo" || n === "logo") {
                    return false;
                }
                if ($e.hasClass("provider-zone-leaf-cb")) {
                    return false;
                }
                return true;
            }

            function providerEditJqvIgnoreFilter() {
                var el = this;
                if (!el || el.nodeType !== 1 || !el.name) {
                    return true;
                }
                return providerEditIsJqvIgnoredHiddenField(el);
            }

            function providerEditShouldSkipJqvElement(el) {
                if (!el || !el.name) {
                    return true;
                }
                return providerEditIsJqvIgnoredHiddenField(el);
            }

            function isProviderCompanyTypeEdit() {
                return formWizard.find('.provider-add-edit-form-root input[name="provider_type"]:checked').val() === "company";
            }

            function providerEditPhoneHiddenMirror(telInput) {
                if (!telInput) {
                    return null;
                }
                var $t = $(telInput);
                var $h = $t.parent().find('input[type="hidden"][name="contact_person_phone"], input[type="hidden"][name="company_phone"]').first();
                if ($h.length) {
                    return $h[0];
                }
                $h = $t.closest(".form-floting-fix, .form-floating, .form-error-wrap").find('input[type="hidden"][name="contact_person_phone"], input[type="hidden"][name="company_phone"]').first();
                return $h.length ? $h[0] : null;
            }

            function providerEditSyncIntlPhoneValidation(telEl) {
                var validator = formWizard.data("validator");
                if (!validator || !telEl) {
                    return;
                }
                var hid = providerEditPhoneHiddenMirror(telEl);
                if (!hid) {
                    return;
                }
                formWizard.validate().settings.ignore = providerEditJqvIgnoreFilter;
                function run() {
                    validator.element(hid);
                }
                setTimeout(run, 0);
                setTimeout(run, 50);
            }

            formWizard.on("input change blur", "select, textarea", function () {
                var validator = formWizard.data("validator");
                if (!validator || $(this).is(":disabled") || !this.name) {
                    return;
                }
                validator.element(this);
            });

            formWizard.on("input change blur", "input:not([type=\"button\"]):not([type=\"submit\"]):not([type=\"checkbox\"]):not([type=\"radio\"])", function () {
                var validator = formWizard.data("validator");
                if (!validator || $(this).is(":disabled")) {
                    return;
                }
                var el = this;
                if ($(el).is("input[type=\"tel\"]")) {
                    providerEditSyncIntlPhoneValidation(el);
                    return;
                }
                if (el.name) {
                    validator.element(el);
                }
            });

            $(document).on("countrychange", "#create-provider-form input[type=\"tel\"]", function () {
                providerEditSyncIntlPhoneValidation(this);
            });

            formWizard.on("change", "input[type=\"checkbox\"], input[type=\"radio\"]", function () {
                var validator = formWizard.data("validator");
                if (!validator || $(this).is(":disabled") || !this.name) {
                    return;
                }
                validator.element(this);
                if (this.name === "provider_type") {
                    formWizard.find('[name="company_name"],[name="company_email"],[name="company_identity_type"],[name="company_identity_number"],[name="logo"],[name="contact_person_photo"]').each(function () {
                        if (!$(this).is(":disabled") && this.name) {
                            validator.element(this);
                        }
                    });
                    formWizard.find('input[type="hidden"][name="company_phone"], input[type="hidden"][name="contact_person_phone"]').each(function () {
                        if (!$(this).is(":disabled")) {
                            validator.element(this);
                        }
                    });
                }
                if ($(this).hasClass("provider-zone-leaf-cb")) {
                    var firstLeaf = formWizard.find("input.provider-zone-leaf-cb").first()[0];
                    if (firstLeaf) {
                        validator.element(firstLeaf);
                    }
                }
            });

            formWizard.steps({
                headerTag: "h3",
                bodyTag: "section",
                transitionEffect: "fade",
                stepsOrientation: "vertical",
                autoFocus: true,
                labels: {
                    finish: "Submit",
                    next: "Proceed",
                    previous: "Back"
                },
                onInit: function (event, currentIndex) {
                   //
                },
                onStepChanging: function (event, currentIndex, newIndex) {

                    if (newIndex < currentIndex) {
                        return true;
                    }

                    formWizard.validate().settings.ignore = providerEditJqvIgnoreFilter;
                    const $formRoot = $(".provider-add-edit-form-root");
                    const providerType = $formRoot.find("input[name='provider_type']:checked").val();
                    let identityDocsOk = true;

                    $('.spartan_item_wrapper_error_msg').remove();
                    const imageCount = $('#multi_image_picker .spartan_image_input[type="file"]').filter(function () {
                        return this.files && this.files.length > 0;
                    }).length || 0;
                    const existingPreviewDocs = $('#multi_image_picker img').length + $('#multi_image_picker a').length;
                    const identityDraftCount = parseInt($('#multi_image_picker').attr('data-identity-draft-count') || '0', 10) || 0;

                    if (imageCount < 1 && existingPreviewDocs < 1 && identityDraftCount < 1) {
                        $('#multi_image_picker')
                            .closest('.upload-file')
                            .after('<div class="spartan_item_wrapper_error_msg error text-danger mt-2 fs-12">{{ addslashes(translate('Please upload at least one contact identity image')) }}</div>');
                        identityDocsOk = false;
                    }

                    $('.company-spartan_item_wrapper_error_msg').remove();
                    if (providerType === 'company') {
                        const companyImageCount = $('#company_multi_image_picker .spartan_image_input[type="file"]').filter(function () {
                            return this.files && this.files.length > 0;
                        }).length || 0;
                        const existingPreviewCompanyDocs = $('#company_multi_image_picker img').length + $('#company_multi_image_picker a').length;
                        const companyDraftCount = parseInt($('#company_multi_image_picker').attr('data-company-identity-draft-count') || '0', 10) || 0;

                        if (companyImageCount < 1 && existingPreviewCompanyDocs < 1 && companyDraftCount < 1) {
                            $('#company_multi_image_picker')
                                .closest('.upload-file')
                                .after('<div class="company-spartan_item_wrapper_error_msg error text-danger mt-2 fs-12">{{ addslashes(translate('Please upload at least one company identity image')) }}</div>');
                            identityDocsOk = false;
                        }
                    }

                    if (!identityDocsOk) {
                        return false;
                    }

                    if (currentIndex === 0 && typeof window.checkOwnerContactUniqueSync === "function") {
                        if (!window.checkOwnerContactUniqueSync()) {
                            return false;
                        }
                    }

                    var validator = formWizard.data("validator");
                    if (!validator) {
                        return formWizard.valid();
                    }
                    var $currentSection = formWizard.find("section").eq(currentIndex);
                    var stepValid = true;
                    $currentSection.find(":input").each(function () {
                        if (!this.name) {
                            return;
                        }
                        if ($(this).is(":button") || $(this).attr("type") === "submit") {
                            return;
                        }
                        if ($(this).is(":disabled")) {
                            return;
                        }
                        if ($(this).is('input[type="file"]')) {
                            // Spartan uploads may be :hidden; still validate.
                        } else if (providerEditShouldSkipJqvElement(this)) {
                            return;
                        }
                        if (!validator.element(this)) {
                            stepValid = false;
                        }
                    });
                    return stepValid;
                },
                onFinished: function (event, currentIndex) {
                    var el = document.getElementById("create-provider-form");
                    if (!el || typeof el.submit !== "function") {
                        return;
                    }
                    if (typeof window.syncProviderWizardIntlPhoneHiddens === "function") {
                        window.syncProviderWizardIntlPhoneHiddens(el);
                    }
                    el.submit();
                }
            });

            formWizard.validate({
                ignore: providerEditJqvIgnoreFilter,
                errorPlacement: function (error, element) {
                    if (element.is('input[type="hidden"]')) {
                        var hn = element.attr("name") || "";
                        if (hn === "contact_person_phone" || hn === "company_phone") {
                            var $tel = element.parent().find('input[type="tel"]').first();
                            if ($tel.length) {
                                $tel.closest(".form-floting-fix, .form-floating, .form-error-wrap").first().after(error);
                                return;
                            }
                        }
                    }
                    element.parents('.form-floating, .form-error-wrap').after(error);
                },
                highlight: function (element) {
                    var $el = $(element);
                    $el.addClass("is-invalid");
                    if ($el.is('input[type="hidden"]')) {
                        var hn = $el.attr("name") || "";
                        if (hn === "contact_person_phone" || hn === "company_phone") {
                            $el.parent().find('input[type="tel"]').addClass("is-invalid");
                        }
                    }
                },
                unhighlight: function (element) {
                    var $el = $(element);
                    $el.removeClass("is-invalid");
                    if ($el.is('input[type="hidden"]')) {
                        var hn = $el.attr("name") || "";
                        if (hn === "contact_person_phone" || hn === "company_phone") {
                            $el.parent().find('input[type="tel"]').removeClass("is-invalid");
                        }
                    }
                },
                rules: {
                    provider_type: {
                        required: true
                    },
                    contact_person_email: {
                        email: true
                    },
                    company_email: {
                        required: function () {
                            return isProviderCompanyTypeEdit();
                        },
                        email: true
                    }
                }
            });
        });

        $(document).ready(function () {
            function toggleProviderTypeFields() {
                const $formRoot = $(".provider-add-edit-form-root");
                const providerType = $formRoot.find("input[name='provider_type']:checked").val();
                const isIndividual = providerType === "individual";

                if (isIndividual) {
                    $(".provider-company-fields").hide();
                    $(".provider-logo-fields").hide();
                    $(".provider-company-identity-fields").hide();
                    $formRoot.find('[name="company_name"]').prop('required', false);
                    $formRoot.find('[name="company_name"]').prop('disabled', true);
                    $formRoot.find('#company_phone').prop('disabled', true);
                    $formRoot.find('[name="company_phone"]').prop('required', false);
                    $formRoot.find('[name="company_phone"]').prop('disabled', true);
                    $formRoot.find('[name="company_email"]').prop('required', false);
                    $formRoot.find('[name="company_email"]').prop('disabled', true);
                    $formRoot.find('[name="company_identity_type"]').prop('required', false);
                    $formRoot.find('[name="company_identity_type"]').prop('disabled', true);
                    $formRoot.find('[name="company_identity_number"]').prop('required', false);
                    $formRoot.find('[name="company_identity_number"]').prop('disabled', true);
                } else {
                    $(".provider-company-fields").show();
                    $(".provider-logo-fields").show();
                    $(".provider-company-identity-fields").show();
                    $formRoot.find('[name="company_name"]').prop('required', true);
                    $formRoot.find('[name="company_name"]').prop('disabled', false);
                    $formRoot.find('#company_phone').prop('disabled', false);
                    $formRoot.find('[name="company_phone"]').prop('required', true);
                    $formRoot.find('[name="company_phone"]').prop('disabled', false);
                    $formRoot.find('[name="company_email"]').prop('required', true);
                    $formRoot.find('[name="company_email"]').prop('disabled', false);
                    $formRoot.find('[name="company_identity_type"]').prop('required', true);
                    $formRoot.find('[name="company_identity_type"]').prop('disabled', false);
                    $formRoot.find('[name="company_identity_number"]').prop('required', true);
                    $formRoot.find('[name="company_identity_number"]').prop('disabled', false);
                }

                const $identityType = $formRoot.find("select[name='identity_type']");
                const $options = $identityType.find("option[data-for]");
                $options.each(function () {
                    const forType = $(this).data('for');
                    const shouldEnable = isIndividual ? forType === 'individual' : forType === 'company';
                    $(this).prop('disabled', !shouldEnable);
                });

                if ($identityType.find("option:selected").prop('disabled')) {
                    $identityType.find("option:not(:disabled)").first().prop('selected', true);
                }
            }

            toggleProviderTypeFields();
            $("input[name='provider_type']").on("change", toggleProviderTypeFields);

            $('[name="contact_person_email"], [name="company_email"]').on("blur", function () {
                $(this).val($.trim($(this).val()));
            });

            // Account info defaults to contact person details.
            $('[name="contact_person_email"]').on("change keyup paste", function () {
                $('#account_email').val($(this).val());
            });

            // Set initial values (account block is legacy hidden markup; contact phone is in the shared partial).
            $('#account_email').val($('[name="contact_person_email"]').val());
        });

        $(document).ready(function () {
            let imageCount = 0;
            let maxSizeReadable = "{{ readableUploadMaxFileSize('image') }}"; // "2MB"
            let maxFileSize = 2 * 1024 * 1024; // default 2MB

            if (maxSizeReadable.toLowerCase().includes('mb')) {
                maxFileSize = parseFloat(maxSizeReadable) * 1024 * 1024;
            } else if (maxSizeReadable.toLowerCase().includes('kb')) {
                maxFileSize = parseFloat(maxSizeReadable) * 1024;
            }

            function setAcceptForAllInputs() {
                const allowedExtensions = ".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }},"

                $('#multi_image_picker input[type=file]').each(function() {
                    $(this).attr('accept', allowedExtensions);
                });
                $('#company_multi_image_picker input[type=file]').each(function() {
                    $(this).attr('accept', allowedExtensions);
                });
            }

            setAcceptForAllInputs();

            $("#multi_image_picker").spartanMultiImagePicker({
                fieldName: 'identity_images[]',
                maxCount: 2,
                allowedExt: 'png|jpg|jpeg|webp|gif',
                rowHeight: 'auto',
                groupClassName: 'item',
                maxFileSize: maxFileSize,
                dropFileLabel: "{{translate('Drop_here')}}",
                placeholderImage: {
                    image: '{{asset('assets/admin-module')}}/img/media/banner-upload-file.png',
                    width: '100%',
                },

                onRenderedPreview: function (index) {
                    toastr.success('{{translate('Image_added')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onAddRow: function (index) {
                    setAcceptForAllInputs()
                    $('.spartan_item_wrapper_error_msg, .company-spartan_item_wrapper_error_msg').remove();
                },
                onRemoveRow: function (index) {
                    // Wizard validation handles required identity docs via identity PDF input + this picker.
                },
                onExtensionErr: function (index, file) {
                    toastr.error('{{ translate("Please only input png|jpg|jpeg|gif|webp type file") }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function () {
                    toastr.error('File size must be less than ' + maxSizeReadable);
                }

            });

            if ($("#company_multi_image_picker").length) {
                $("#company_multi_image_picker").spartanMultiImagePicker({
                    fieldName: 'company_identity_images[]',
                    maxCount: 2,
                    allowedExt: 'png|jpg|jpeg|webp|gif',
                    rowHeight: 'auto',
                    groupClassName: 'item',
                    maxFileSize: maxFileSize,
                    dropFileLabel: "{{translate('Drop_here')}}",
                    placeholderImage: {
                        image: '{{asset('assets/admin-module')}}/img/media/banner-upload-file.png',
                        width: '100%',
                    },

                    onAddRow: function (index) {
                        setAcceptForAllInputs();
                    },
                    onExtensionErr: function (index, file) {
                        toastr.error('{{ translate("Please only input png|jpg|jpeg|gif|webp type file") }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    },
                    onSizeErr: function () {
                        toastr.error('File size must be less than ' + maxSizeReadable);
                    }
                });
            }

            // Multi-attachment uploader for PDF + previews (identity PDFs & company identity PDFs).
            (function () {
                const attachmentState = new Map(); // inputElement -> File[]

                function fileKey(file) {
                    return [file.name, file.size, file.lastModified].join('_');
                }

                function syncInputFiles(input, files) {
                    if (!window.DataTransfer) return;
                    const dt = new DataTransfer();
                    files.forEach((f) => {
                        if (f) dt.items.add(f);
                    });
                    input.files = dt.files;
                }

                function renderPreview(uploaderEl, files) {
                    const inputEl = uploaderEl.querySelector('[data-attachment-input]');
                    const previewEl = uploaderEl.querySelector('[data-attachment-preview]');
                    if (!previewEl || !inputEl) return;

                    previewEl.innerHTML = '';
                    if (!files || files.length === 0) return;

                    files.forEach(function (file, index) {
                        if (!file) return;
                        const isImage = (file.type || '').startsWith('image/');
                        const fileName = file.name || 'document';
                        const ext = (fileName.split('.').pop() || '').toLowerCase();
                        const isPdf = ext === 'pdf' || (file.type && file.type === 'application/pdf');

                        const item = document.createElement('div');
                        item.className = 'position-relative border rounded p-2 bg-white';
                        item.style.maxWidth = '220px';

                        if (isImage) {
                            const img = document.createElement('img');
                            img.src = URL.createObjectURL(file);
                            img.alt = fileName;
                            img.style.maxHeight = '60px';
                            img.style.maxWidth = '160px';
                            img.style.objectFit = 'contain';
                            img.className = 'd-block mx-auto';
                            item.appendChild(img);
                        } else if (isPdf) {
                            const icon = document.createElement('div');
                            icon.className = 'd-flex align-items-center gap-2 justify-content-center';
                            icon.innerHTML =
                                '<span class="material-icons text-danger">picture_as_pdf</span>' +
                                '<span class="small text-break">' + (fileName.length > 22 ? fileName.slice(0, 22) + '...' : fileName) + '</span>';
                            item.appendChild(icon);
                        } else {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-secondary';
                            badge.textContent = fileName;
                            item.appendChild(badge);
                        }

                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-sm btn-danger position-absolute top-0 end-0 translate-middle';
                        removeBtn.style.transform = 'translate(30%, -30%)';
                        removeBtn.style.width = '28px';
                        removeBtn.style.height = '28px';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.setAttribute('data-attachment-remove-btn', 'true');
                        removeBtn.dataset.removeIndex = index.toString();

                        removeBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const state = attachmentState.get(inputEl) || [];
                            const idx = parseInt(removeBtn.dataset.removeIndex || '0', 10);
                            if (Number.isNaN(idx)) return;
                            state.splice(idx, 1);
                            attachmentState.set(inputEl, state);
                            syncInputFiles(inputEl, state);
                            renderPreview(uploaderEl, state);
                            $('.spartan_item_wrapper_error_msg, .company-spartan_item_wrapper_error_msg').remove();
                        });

                        item.appendChild(removeBtn);
                        previewEl.appendChild(item);
                    });
                }

                document.addEventListener('click', function (e) {
                    const trigger = e.target.closest('[data-attachment-trigger]');
                    if (!trigger) return;
                    const uploaderEl = trigger.closest('[data-attachment-uploader]');
                    const inputEl = uploaderEl ? uploaderEl.querySelector('[data-attachment-input]') : null;
                    if (inputEl) inputEl.click();
                });

                document.addEventListener('change', function (e) {
                    const inputEl = e.target && e.target.matches && e.target.matches('[data-attachment-input]') ? e.target : null;
                    if (!inputEl) return;

                    const uploaderEl = inputEl.closest('[data-attachment-uploader]');
                    if (!uploaderEl) return;

                    const selected = Array.from(inputEl.files || []);
                    const prev = attachmentState.get(inputEl) || [];

                    if (selected.length === 0) {
                        attachmentState.set(inputEl, []);
                        syncInputFiles(inputEl, []);
                        renderPreview(uploaderEl, []);
                        $('.spartan_item_wrapper_error_msg, .company-spartan_item_wrapper_error_msg').remove();
                        return;
                    }

                    const existingKeys = new Set(prev.map(fileKey));
                    const merged = prev.slice();
                    selected.forEach(function (f) {
                        if (!f) return;
                        const k = fileKey(f);
                        if (!existingKeys.has(k)) {
                            merged.push(f);
                            existingKeys.add(k);
                        }
                    });

                    attachmentState.set(inputEl, merged);
                    syncInputFiles(inputEl, merged);
                    renderPreview(uploaderEl, merged);
                    $('.spartan_item_wrapper_error_msg, .company-spartan_item_wrapper_error_msg').remove();
                }, true);
            })();

            function readURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        $('#viewer').attr('src', e.target.result);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            $("#customFileEg1").change(function () {
                readURL(this);
            });


            $(document).ready(function () {
                function initAutocomplete() {
                    var myLatLng = {

                        lat:{{$provider->coordinates['latitude'] ?? 23.811842872190343}},
                        lng:{{$provider->coordinates['longitude'] ?? 90.356331}}
                    };
                    const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                        center: {
                            lat:{{$provider->coordinates['latitude'] ?? 23.811842872190343}},
                            lng:{{$provider->coordinates['longitude'] ?? 90.356331}}
                        },
                        zoom: 13,
                        mapTypeId: "roadmap",
                    });

                    var marker = new google.maps.Marker({
                        position: myLatLng,
                        map: map,
                    });

                    marker.setMap(map);
                    var geocoder = geocoder = new google.maps.Geocoder();
                    google.maps.event.addListener(map, 'click', function (mapsMouseEvent) {
                        var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                        var coordinates = JSON.parse(coordinates);
                        var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                        marker.setPosition(latlng);
                        map.panTo(latlng);

                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        if (typeof jQuery !== "undefined") {
                            jQuery("#latitude, #longitude").trigger("change");
                        }

                        geocoder.geocode({
                            'latLng': latlng
                        }, function (results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                if (results[1]) {
                                    document.getElementById('address').value = results[1].formatted_address;
                                }
                            }
                        });
                    });

                    const input = document.getElementById("pac-input");
                    const searchBox = new google.maps.places.SearchBox(input);
                    map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
                    map.addListener("bounds_changed", () => {
                        searchBox.setBounds(map.getBounds());
                    });
                    let markers = [];
                    searchBox.addListener("places_changed", () => {
                        const places = searchBox.getPlaces();

                        if (places.length == 0) {
                            return;
                        }
                        markers.forEach((marker) => {
                            marker.setMap(null);
                        });
                        markers = [];
                        const bounds = new google.maps.LatLngBounds();
                        places.forEach((place) => {
                            if (!place.geometry || !place.geometry.location) {
                                console.log("Returned place contains no geometry");
                                return;
                            }
                            var mrkr = new google.maps.Marker({
                                map,
                                title: place.name,
                                position: place.geometry.location,
                            });
                            google.maps.event.addListener(mrkr, "click", function (event) {
                                document.getElementById('latitude').value = this.position.lat();
                                document.getElementById('longitude').value = this.position.lng();
                                if (typeof jQuery !== "undefined") {
                                    jQuery("#latitude, #longitude").trigger("change");
                                }
                            });

                            markers.push(mrkr);

                            if (place.geometry.viewport) {
                                bounds.union(place.geometry.viewport);
                            } else {
                                bounds.extend(place.geometry.location);
                            }
                        });
                        map.fitBounds(bounds);
                    });
                };
                initAutocomplete();
            });


            $('.__right-eye').on('click', function () {
                const input = $(this).siblings('input');
                const isVisible = input.attr('type') === 'text';

                if (isVisible) {
                    input.attr('type', 'password');
                    $(this).text('visibility_off');
                } else {
                    input.attr('type', 'text');
                    $(this).text('visibility');
                }
            });
        });

    </script>

@endpush
