@extends('adminmodule::layouts.master')

@section('title',translate('add_provider'))

@push('css_or_js')

    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/swiper/swiper-bundle.min.css')}}">

@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">

            @php($created = session('provider_created'))
            @if(is_array($created) && !empty($created['id']))
                <div class="modal fade" id="providerCreatedSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title">{{ translate('Provider_created_successfully') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                            </div>
                            <div class="modal-body pt-0">
                                <p class="mb-4 text-muted">{{ translate('The_provider_has_been_added_you_can_manage_subscriptions_anytime') }}</p>
                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                    <a href="{{ route('admin.provider.create') }}" class="btn btn--secondary order-2 order-sm-1">
                                        {{ translate('Create_another_provider') }}
                                    </a>
                                    <a href="{{ route('admin.provider.details', [$created['id'], 'web_page' => 'overview']) }}" class="btn btn--primary order-1 order-sm-2">
                                        {{ translate('View_provider_details') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div id="provider-create-wizard-config" class="d-none"
                 data-subcategories-url="{{ route('admin.provider.create.subcategories-for-zone') }}"
                 data-csrf-token="{{ csrf_token() }}"></div>

            <form action="{{route('admin.provider.store')}}" method="POST" enctype="multipart/form-data" id="create-provider-form" novalidate>
                @csrf
                <input type="hidden" name="plan_type" value="commission_based">
                <h3>{{translate('Step 1')}}</h3>
                <section>
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Add_New_Provider')}}</h2>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-4 create-provider-item mb-4">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="material-symbols-outlined icon-1">check</span>
                                    {{ translate('Basic info') }}
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="icon-2">2</span>
                                    {{ translate('Subscribed_Services') }}
                                </div>
                            </div>
                            @include('providermanagement::admin.provider.partials.provider-add-edit-form', ['mode' => 'add', 'zones' => $zones, 'zoneTree' => $zoneTree, 'provider' => null])

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
                                                       {{ old('provider_type', 'individual') === 'individual' ? 'checked' : '' }}>
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
                                                       {{ old('provider_type', 'individual') === 'company' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="provider_type_company">
                                                    {{ translate('Company') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-30 provider-company-fields">
                                        <div class="form-floating form-floating__icon">
                                            <input type="text" class="form-control" value="{{old('company_name')}}"
                                                    name="company_name"
                                                    placeholder="{{translate('Company_/_Individual_Name')}}" maxlength="191">
                                            <label>{{translate('Company_/_Individual_Name')}}</label>
                                            <span class="material-icons">store</span>
                                        </div>
                                    </div>
                                    <div class="mb-30 provider-company-fields">
                                        <div class="form-floating form-floting-fix">
                                            <label for="company_phone">{{translate('Phone')}}</label>
                                            <input type="tel"
                                                   class="form-control"
                                                   name="company_phone"
                                                   id="old_company_phone"
                                                   value="{{old('company_phone')}}"
                                                   placeholder="{{translate('Phone')}}">
                                        </div>
                                    </div>
                                    <div class="mb-30 provider-company-fields">
                                        <div class="form-floating form-floating__icon">
                                            <input type="email" class="form-control" id="old_company_email"
                                                    name="company_email" value="{{old('company_email')}}"
                                                    placeholder="{{translate('Email')}}">
                                            <label>{{translate('Email')}}</label>
                                            <span class="material-icons">mail</span>
                                        </div>
                                    </div>
                                    <div class="mb-30">
                                        <label class="input-label d-block mb-2">{{ translate('Service_Zones') }} <span class="text-danger">*</span></label>
                                        <p class="text-muted fz-12 mb-2">{{ translate('Hold_Ctrl_or_Cmd_to_select_multiple_zones') }}</p>
                                        <select class="select-identity theme-input-style w-100" name="zone_ids[]" multiple required size="8">
                                            @foreach($zones as $zone)
                                                <option value="{{ $zone->id }}"
                                                    {{ in_array((string) $zone->id, array_map('strval', (array) old('zone_ids', [])), true) ? 'selected' : '' }}>
                                                    {{ $zone->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <textarea class="form-control resize-none" placeholder="{{translate('Address')}}"
                                                        name="company_address"
                                                        required>{{old('company_address')}}</textarea>
                                            <label>{{translate('Address')}}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 provider-logo-fields">
                                    <div class="d-flex flex-column align-items-center gap-3">
                                        <h3 class="mb-0">{{translate('Company_Logo')}}</h3>
                                        <div class="d-flex align-items-center flex-column form-error-wrap">
                                            <div class="upload-file">
                                                <input type="file" class="upload-file__input" name="logo"
                                                       accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                       data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
                                                       >
                                                <div class="upload-file__img">
                                                    <img
                                                        src="{{onErrorImage(old('logo'),
                                                        asset('storage/app/public/provider/logo').'/' . old('logo'),
                                                        asset('assets/admin-module/img/placeholder.png') ,
                                                        'provider/logo/')}}" alt="{{translate('image')}}">
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
                                            <div class="form-error-wrap">
                                                <select class="select-identity theme-input-style w-100"
                                                        name="company_identity_type" required>
                                                    <option selected disabled>{{translate('Select_Identity_Type')}}</option>
                                                    <option value="trade_license"
                                                            {{old('company_identity_type', 'trade_license') == 'trade_license' ? 'selected' : ''}}>
                                                        {{translate('Trade_License')}}</option>
                                                    <option value="company_id"
                                                            {{old('company_identity_type') == 'company_id' ? 'selected' : ''}}>
                                                        {{translate('Company_Id')}}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-30">
                                            <div class="form-floating form-floating__icon">
                                                <input type="text" class="form-control" name="company_identity_number"
                                                       value="{{old('company_identity_number')}}"
                                                       placeholder="{{translate('Identity_Number')}}" required>
                                                <label>{{translate('Identity_Number')}}</label>
                                                <span class="material-icons">badge</span>
                                            </div>
                                        </div>

                                        <div class="upload-file w-100">
                                            <h3 class="mb-3">{{translate('Identification_Image')}}</h3>
                                            <div id="old_company_multi_image_picker"></div>
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

                                                <div class="mt-3 d-flex flex-wrap gap-2"
                                                     data-attachment-preview></div>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="c1 mb-20">{{translate('Business_Information')}}</h4>
                                    <div class="mb-30">
                                        <div class="form-error-wrap">
                                            <select class="select-identity theme-input-style w-100" name="identity_type" required>
                                                <option selected disabled>{{translate('Select_Identity_Type')}}</option>
                                                <option value="nid"
                                                        {{old('identity_type', 'nid') == 'nid' ? 'selected' : ''}}>
                                                    {{translate('Aadhar_Card')}}</option>
                                                <option value="passport"
                                                        {{old('identity_type') == 'passport' ? 'selected' : ''}}>
                                                    {{translate('Passport')}}</option>
                                                <option value="driving_license"
                                                        {{old('identity_type') == 'driving_license' ? 'selected' : ''}}>
                                                    {{translate('Driving_License')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-30">
                                        <div class="form-floating form-floating__icon">
                                            <input type="text" class="form-control" name="identity_number"
                                                    value="{{old('identity_number')}}"
                                                    placeholder="{{translate('Identity_Number')}}" required>
                                            <label>{{translate('Identity_Number')}}</label>
                                            <span class="material-icons">badge</span>
                                        </div>
                                    </div>

                                    <div class="upload-file w-100">
                                        <h3 class="mb-3">{{translate('Identification_Image')}}</h3>
                                        <div id="old_multi_image_picker"></div>
                                    </div>

                                    <div class="upload-file w-100 mt-3">
                                        <h3 class="mb-3">{{translate('Identification_PDF')}}</h3>

                                        <div class="multi-attachment-uploader" data-attachment-uploader>
                                            <input type="file"
                                                   class="d-none"
                                                   name="identity_pdf_files[]"
                                                   id="identity_pdf_files"
                                                   accept="application/pdf,.pdf"
                                                   multiple
                                                   data-maxFileSize="{{ readableUploadMaxFileSize('file') }}"
                                                   data-attachment-input>

                                            <button type="button"
                                                    class="btn btn--secondary w-100"
                                                    data-attachment-trigger>
                                                {{translate('Upload_PDF')}}
                                            </button>

                                            <div class="mt-3 d-flex flex-wrap gap-2"
                                                 data-attachment-preview></div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 order-md-1">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                                        <h4 class="c1">{{translate('Contact_Person')}}</h4>
                                    </div>
                                    <div class="mb-30">
                                        <div class="form-floating form-floating__icon">
                                            <input type="text" class="form-control" name="contact_person_name"
                                                    value="{{old('contact_person_name')}}" placeholder="name" maxlength="191" required>
                                            <label>{{translate('Name')}}</label>
                                            <span class="material-icons">account_circle</span>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-lg-6">
                                            <div class="form-floating form-floting-fix">
                                                <label for="contact_person_phone">{{translate('Phone')}}</label>
                                                <input type="tel"
                                                       class="form-control"
                                                       name="contact_person_phone"
                                                       value="{{old('contact_person_phone')}}"
                                                       id="old_contact_person_phone" placeholder="{{translate('Phone')}}" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-30">
                                                <div class="form-floating form-floating__icon">
                                                    <input type="email" class="form-control" name="contact_person_email"
                                                            value="{{old('contact_person_email')}}"
                                                            placeholder="{{translate('Email')}}" required>
                                                    <label>{{translate('Email')}}</label>
                                                    <span class="material-symbols-outlined">mail</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-30">
                                        <div class="upload-file">
                                            <input type="file"
                                                   class="upload-file__input"
                                                   name="contact_person_photo"
                                                   accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                   data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
                                                   required>
                                            <div class="upload-file__img">
                                                <img src="{{onErrorImage(old('contact_person_photo'),
                                                        asset('storage/provider/contact_person_photo').'/' . old('contact_person_photo'),
                                                        asset('assets/admin-module/img/placeholder.png') ,
                                                        'provider/contact_person_photo/')}}" alt="{{translate('image')}}">
                                            </div>
                                            <span class="upload-file__edit">
                                                <span class="material-icons">edit</span>
                                            </span>
                                        </div>
                                    </div>

                                    <h4 class="c1 mb-20">{{translate('Account_Information')}}</h4>
                                    <div class="mb-30">
                                        <div class="form-floating form-floating__icon">
                                            <input type="email" id="account_email" class="form-control"
                                                    value="{{old('account_email')}}" name="account_email"
                                                    placeholder="{{translate('Email')}}" required>
                                            <label>{{translate('Email_*')}}</label>
                                            <span class="material-icons">mail</span>
                                        </div>
                                    </div>
                                    <div class="mb-30">
                                        <div class="form-floating form-floting-fix">
                                            <input type="tel"
                                                   class="form-control"
                                                   name="account_phone"
                                                    value="{{old('account_phone')}}"
                                                   id="account_phone" placeholder="{{translate('Phone')}}"  readonly required>
                                        </div>
                                    </div>

                                    <div class="row gx-2">
                                        <div class="col-lg-6">
                                            <div class="mb-30">
                                                <div class="form-floating form-floating__icon">
                                                    <input type="password" class="form-control" name="password"
                                                            placeholder="{{translate('Password')}}" id="old_pass" required>
                                                    <label>{{translate('Password')}}</label>
                                                    <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                                    <span class="material-icons">lock</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-30">
                                                <div class="form-floating form-floating__icon">
                                                    <input type="password" class="form-control" name="confirm_password"
                                                            placeholder="{{translate('Confirm_Password')}}" id="old_confirm_password" required>
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
                                                            value="" required readonly
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{translate('Select from map')}}">
                                                    <label>{{translate('latitude')}} *</label>
                                                    <span class="material-symbols-outlined">location_on</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="mb-30">
                                                <div class="form-floating form-floating__icon">
                                                    <input type="text" class="form-control" name="longitude"
                                                            id="old_longitude"
                                                            placeholder="{{translate('longitude')}} *"
                                                            value="" required readonly
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{translate('Select from map')}}">
                                                    <label>{{translate('longitude')}} *</label>
                                                    <span class="material-symbols-outlined">location_on</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-4">
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
                    </div>
                    </fieldset>
                    @endif
                </section>
                <h3>{{translate('Step 2')}}</h3>
                <section id="provider-create-step-subscribed-services">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title mb-2">{{ translate('Add_New_Provider') }}</h2>
                        <p class="page-title-text">{{ translate('Subscribe_services_for_this_provider_zone') }}</p>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-4 create-provider-item mb-4">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="material-symbols-outlined icon-1">check</span>
                                    {{ translate('Basic info') }}
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="material-symbols-outlined icon-1">check</span>
                                    {{ translate('Subscribed_Services') }}
                                </div>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-3 gap-2">
                                <h4 class="c1 mb-0">{{ translate('Subscribed_Services') }}</h4>
                                <span class="small text-muted">{{ translate('Same_list_as_provider_details_subscribed_services') }}</span>
                            </div>

                            <div id="provider-create-subscribed-loading" class="text-muted py-4 d-none">
                                {{ translate('Loading') }}…
                            </div>
                            <div id="provider-create-subscribed-empty" class="alert alert-info d-none mb-0">
                                {{ translate('Select_zone_in_step_1_to_load_subcategories') }}
                            </div>
                            <div id="provider-create-subscribed-none" class="alert alert-warning d-none mb-0">
                                {{ translate('No_sub_categories_in_this_zone') }}
                            </div>

                            <div id="provider-create-subscribed-table-wrap" class="table-responsive d-none">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('Sub_Category_Name') }}</th>
                                        <th>{{ translate('Services') }}</th>
                                        <th class="text-nowrap">{{ translate('Subscribe') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody id="provider-create-subscribed-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </form>
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

        (function () {
            var cfg = document.getElementById("provider-create-wizard-config");
            window.loadProviderCreateSubscribedServices = function () {
                if (!cfg || typeof jQuery === "undefined") {
                    return;
                }
                var url = cfg.getAttribute("data-subcategories-url") || "";
                var token = cfg.getAttribute("data-csrf-token") || "";
                var fn = window.getAdminProviderFormZoneIds;
                var zoneIds = typeof fn === "function" ? fn() : [];
                var $loading = jQuery("#provider-create-subscribed-loading");
                var $empty = jQuery("#provider-create-subscribed-empty");
                var $none = jQuery("#provider-create-subscribed-none");
                var $wrap = jQuery("#provider-create-subscribed-table-wrap");
                var $tbody = jQuery("#provider-create-subscribed-tbody");
                $empty.addClass("d-none");
                $none.addClass("d-none");
                $wrap.addClass("d-none");
                $tbody.empty();
                if (!zoneIds.length) {
                    $empty.removeClass("d-none");
                    return;
                }
                $loading.removeClass("d-none");
                jQuery.post(url, {_token: token, zone_ids: zoneIds})
                    .done(function (res) {
                        $loading.addClass("d-none");
                        var list = (res && res.sub_categories) ? res.sub_categories : [];
                        if (!list.length) {
                            $none.removeClass("d-none");
                            return;
                        }
                        list.forEach(function (row) {
                            var tr = jQuery("<tr></tr>");
                            var nameTd = jQuery("<td></td>").text(row.name || "");
                            var cntTd = jQuery("<td></td>").text(row.services_count != null ? row.services_count : 0);
                            var sw = jQuery("<label class=\"switcher mb-0\"></label>");
                            var inp = jQuery("<input type=\"checkbox\" class=\"switcher_input\" name=\"subscribed_sub_category_ids[]\">")
                                .val(row.id);
                            sw.append(inp).append(jQuery("<span class=\"switcher_control\"></span>"));
                            tr.append(nameTd).append(cntTd).append(jQuery("<td></td>").append(sw));
                            $tbody.append(tr);
                        });
                        $wrap.removeClass("d-none");
                    })
                    .fail(function () {
                        $loading.addClass("d-none");
                        var $errRow = jQuery("<tr></tr>");
                        $errRow.append(jQuery("<td colspan=\"3\" class=\"text-danger\"></td>")
                            .text("{{ addslashes(translate('Could_not_load_subcategories')) }}"));
                        $tbody.append($errRow);
                        $wrap.removeClass("d-none");
                    });
            };
        })();

        $(document).ready(function () {
            var successModalEl = document.getElementById("providerCreatedSuccessModal");
            if (successModalEl && typeof bootstrap !== "undefined") {
                new bootstrap.Modal(successModalEl).show();
            }

            let formWizard = $("#create-provider-form");

            function isProviderCompanyType() {
                return formWizard.find('.provider-add-edit-form-root input[name="provider_type"]:checked').val() === 'company';
            }

            function providerUploadHasFile(inputName) {
                var $inp = formWizard.find('[name="' + inputName + '"]');
                var inp = $inp[0];
                if (!inp) {
                    return false;
                }
                if (inp.files && inp.files.length) {
                    return true;
                }
                var wrap = inp.closest('.provider-upload-wrapper');
                if (!wrap) {
                    return false;
                }
                var removeField = wrap.getAttribute('data-remove-field') || '';
                var hidden = removeField ? wrap.querySelector('input[type="hidden"][name="' + removeField + '"]') : null;
                if (hidden && hidden.value === '1') {
                    return false;
                }
                var img = wrap.querySelector('img[data-placeholder-src]');
                if (!img || !img.dataset.placeholderSrc) {
                    return false;
                }
                return String(img.src || '') !== String(img.dataset.placeholderSrc || '');
            }

            function clearProviderCreateBasicSummary() {
                var $s = $("#provider-create-basic-step-summary");
                if ($s.length) {
                    $s.addClass("d-none").empty();
                }
            }

            function showProviderCreateBasicSummary(messages) {
                var $s = $("#provider-create-basic-step-summary");
                if (!$s.length) {
                    return;
                }
                var seen = {};
                var list = [];
                messages.forEach(function (m) {
                    var t = (m || "").trim();
                    if (t && !seen[t]) {
                        seen[t] = true;
                        list.push(t);
                    }
                });
                if (!list.length) {
                    $s.addClass("d-none").empty();
                    return;
                }
                var esc = function (s) {
                    return String(s)
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;");
                };
                var html = '<p class="mb-2 fw-semibold">' + esc("{{ addslashes(translate('Please_review_the_following_issues')) }}") + '</p><ul class="mb-0 ps-3">';
                list.forEach(function (msg) {
                    html += "<li>" + esc(msg) + "</li>";
                });
                html += "</ul>";
                $s.removeClass("d-none").html(html);
            }

            $.validator.addMethod("providerZoneLeafPick", function () {
                var form = document.getElementById("create-provider-form");
                if (!form) {
                    return true;
                }
                return form.querySelectorAll("input.provider-zone-leaf-cb:checked").length > 0;
            }, "{{ addslashes(translate('Select_Zone')) }}");

            formWizard.validate({
                errorElement: "div",
                errorClass: "provider-jqv-error",
                ignore: function (index, element) {
                    var $el = $(element);
                    if ($el.is(":disabled")) {
                        return true;
                    }
                    if ($el.is('input[type="file"]')) {
                        return false;
                    }
                    if ($el.hasClass("provider-zone-leaf-cb")) {
                        return false;
                    }
                    if ($el.is(":hidden")) {
                        return true;
                    }
                    return false;
                },
                errorPlacement: function (error, element) {
                    error.addClass("invalid-feedback d-block text-danger small mt-1");
                    if (element.hasClass("provider-zone-leaf-cb")) {
                        var $ze = $("#provider-create-zone-error");
                        if ($ze.length) {
                            $ze.removeClass("d-none").html(error.html());
                        } else {
                            element.closest(".form-check").after(error);
                        }
                        return;
                    }
                    if (element.attr("type") === "radio" && element.attr("name") === "provider_type") {
                        element.closest(".d-flex.flex-wrap.gap-3").append(error);
                        return;
                    }
                    element.parents('.form-floating, .form-error-wrap').first().after(error);
                },
                highlight: function (element, errorClass, validClass) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function (element, errorClass, validClass) {
                    $(element).removeClass("is-invalid");
                },
                rules: {
                    provider_type: { required: true },
                    contact_person_name: { required: true, maxlength: 191 },
                    contact_person_phone: {
                        required: true,
                        minlength: 8,
                        pattern: /^([0-9\s\-\+\(\)]*)$/
                    },
                    contact_person_email: { required: true, email: true },
                    company_address: { required: true },
                    identity_type: { required: true },
                    identity_number: { required: true },
                    latitude: { required: true },
                    longitude: { required: true },
                    company_name: {
                        required: function () {
                            return isProviderCompanyType();
                        },
                        maxlength: 191
                    },
                    company_phone: {
                        required: function () {
                            return isProviderCompanyType();
                        },
                        minlength: 8,
                        pattern: /^([0-9\s\-\+\(\)]*)$/
                    },
                    company_email: {
                        required: function () {
                            return isProviderCompanyType();
                        },
                        email: true
                    },
                    company_identity_type: {
                        required: function () {
                            return isProviderCompanyType();
                        }
                    },
                    company_identity_number: {
                        required: function () {
                            return isProviderCompanyType();
                        }
                    },
                    logo: {
                        required: function () {
                            if (!isProviderCompanyType()) {
                                return false;
                            }
                            return !providerUploadHasFile("logo");
                        }
                    },
                    contact_person_photo: {
                        required: function () {
                            return !providerUploadHasFile("contact_person_photo");
                        }
                    }
                },
                messages: {
                    contact_person_phone: {
                        pattern: "{{ addslashes(translate('The phone format is invalid.')) }}"
                    },
                    company_phone: {
                        pattern: "{{ addslashes(translate('The phone format is invalid.')) }}"
                    }
                }
            });

            formWizard.find("input.provider-zone-leaf-cb").first().each(function () {
                $(this).rules("add", { providerZoneLeafPick: true });
            });

            $(document).on("change", "#create-provider-form input.provider-zone-leaf-cb", function () {
                $("#provider-create-zone-error").addClass("d-none").empty();
            });

            document.querySelectorAll('input[type="tel"]').forEach(function(input) {
                const itiInstance = window.intlTelInputGlobals.getInstance(input);
                const nextInput = input.nextElementSibling;
                if (nextInput && nextInput.tagName.toLowerCase() === 'input') {
                    const nameAttr = nextInput.getAttribute('name');
                    input.setAttribute('name', nameAttr);
                }
                if (itiInstance) itiInstance.destroy();
                input.removeAttribute('data-intl-initialized');
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
                onInit: function () {
                    var $actions = formWizard.find("ul.actions");
                    if ($actions.length && !document.getElementById("provider-create-basic-step-summary")) {
                        $('<div id="provider-create-basic-step-summary" class="alert alert-danger mt-3 d-none" role="alert"></div>').insertAfter($actions);
                    }
                    if ($actions.length && !document.getElementById("provider-create-reset-btn")) {
                        var resetUrl = "{{ route('admin.provider.create', ['reset' => 1]) }}";
                        var $nextLi = $actions.find('a[href="#next"]').closest("li");
                        var $resetLi = $("<li></li>").attr("id", "provider-create-reset-li").append(
                            $('<button type="button" id="provider-create-reset-btn" class="btn btn--secondary btn-sm"></button>')
                                .text("{{ addslashes(translate('Reset')) }}")
                                .on("click", function () {
                                    window.location.href = resetUrl;
                                })
                        );
                        if ($nextLi.length) {
                            $resetLi.insertBefore($nextLi);
                        } else {
                            $actions.prepend($resetLi);
                        }
                    }
                },
                onStepChanged: function (event, currentIndex, priorIndex) {
                    $("#provider-create-reset-li").toggle(currentIndex === 0);
                },
                onStepChanging: function (event, currentIndex, newIndex) {
                    if (newIndex < currentIndex) {
                        clearProviderCreateBasicSummary();
                        return true;
                    }

                    formWizard.validate().settings.ignore = function (index, element) {
                        var $el = $(element);
                        if ($el.is(":disabled")) {
                            return true;
                        }
                        if ($el.is('input[type="file"]')) {
                            return false;
                        }
                        if ($el.hasClass("provider-zone-leaf-cb")) {
                            return false;
                        }
                        if ($el.is(":hidden")) {
                            return true;
                        }
                        return false;
                    };
                    const providerType = $(".provider-add-edit-form-root").find("input[name='provider_type']:checked").val();
                    let identityDocsOk = true;

                    $('.identity-docs-error-msg').remove();
                    const existingContactPreviews = $('#multi_image_picker img').length + $('#multi_image_picker a').length;
                    const contactImageCount = $('#multi_image_picker .spartan_image_input[type="file"]').filter(function () {
                        return this.files && this.files.length > 0;
                    }).length || 0;
                    const identityDraftCount = parseInt($('#multi_image_picker').attr('data-identity-draft-count') || '0', 10) || 0;

                    if (contactImageCount < 1 && existingContactPreviews < 1 && identityDraftCount < 1) {
                        $('#multi_image_picker')
                            .closest('.upload-file')
                            .after('<div class="identity-docs-error-msg error text-danger mt-2 fs-12">{{ addslashes(translate('Please upload at least one contact identity image')) }}</div>');
                        identityDocsOk = false;
                    }

                    $('.company-identity-docs-error-msg').remove();
                    if (providerType === 'company') {
                        const existingCompanyPreviews = $('#company_multi_image_picker img').length + $('#company_multi_image_picker a').length;
                        const companyImageCount = $('#company_multi_image_picker .spartan_image_input[type="file"]').filter(function () {
                            return this.files && this.files.length > 0;
                        }).length || 0;
                        const companyDraftCount = parseInt($('#company_multi_image_picker').attr('data-company-identity-draft-count') || '0', 10) || 0;

                        if (companyImageCount < 1 && existingCompanyPreviews < 1 && companyDraftCount < 1) {
                            $('#company_multi_image_picker')
                                .closest('.upload-file')
                                .after('<div class="company-identity-docs-error-msg error text-danger mt-2 fs-12">{{ addslashes(translate('Please upload at least one company identity image')) }}</div>');
                            identityDocsOk = false;
                        }
                    }

                    if (!identityDocsOk) {
                        var idMsgs = [];
                        $(".identity-docs-error-msg, .company-identity-docs-error-msg").each(function () {
                            var t = ($(this).text() || "").trim();
                            if (t) {
                                idMsgs.push(t);
                            }
                        });
                        showProviderCreateBasicSummary(idMsgs);
                        var $firstIdErr = $(".identity-docs-error-msg, .company-identity-docs-error-msg").first();
                        if ($firstIdErr.length && $firstIdErr[0].scrollIntoView) {
                            $firstIdErr[0].scrollIntoView({ behavior: "smooth", block: "nearest" });
                        }
                        return false;
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
                        if (validator.settings.ignore && typeof validator.settings.ignore === "function") {
                            if (validator.settings.ignore(0, this)) {
                                return;
                            }
                        }
                        if (!validator.element(this)) {
                            stepValid = false;
                        }
                    });
                    if (currentIndex === 0 && newIndex === 1) {
                        if (!stepValid) {
                            var msgs = [];
                            if (validator.errorList && validator.errorList.length) {
                                validator.errorList.forEach(function (e) {
                                    if (e && e.message) {
                                        msgs.push(e.message);
                                    }
                                });
                            }
                            if (!msgs.length) {
                                msgs.push("{{ addslashes(translate('Please_complete_all_required_fields_before_proceeding')) }}");
                            }
                            showProviderCreateBasicSummary(msgs);
                            validator.focusInvalid();
                            return false;
                        }
                        clearProviderCreateBasicSummary();
                        if (typeof window.checkOwnerContactUniqueSync === "function") {
                            if (!window.checkOwnerContactUniqueSync()) {
                                showProviderCreateBasicSummary(["{{ addslashes(translate('Please_fix_contact_details_errors')) }}"]);
                                return false;
                            }
                        }
                        clearProviderCreateBasicSummary();
                    }
                    if (stepValid && currentIndex === 0 && newIndex === 1 && typeof window.loadProviderCreateSubscribedServices === "function") {
                        window.loadProviderCreateSubscribedServices();
                    }
                    return stepValid;
                },
                onFinished: function () {
                    formWizard.submit();
                }
            });
        });
    </script>

    <script>
        "use strict";

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
                    $formRoot.find('[name="company_phone"]').prop('required', true);
                    $formRoot.find('[name="company_phone"]').prop('disabled', false);
                    $formRoot.find('[name="company_email"]').prop('required', true);
                    $formRoot.find('[name="company_email"]').prop('disabled', false);
                    $formRoot.find('[name="company_identity_type"]').prop('required', true);
                    $formRoot.find('[name="company_identity_type"]').prop('disabled', false);
                    $formRoot.find('[name="company_identity_number"]').prop('required', true);
                    $formRoot.find('[name="company_identity_number"]').prop('disabled', false);
                }

                // Toggle identity type options based on provider type.
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

            // Account info defaults to contact person details.
            $('#account_email').val($('[name="contact_person_email"]').val());
            $('#account_phone').val($('[name="contact_person_phone"]').val());

            $('[name="contact_person_email"]').on("change keyup paste", function () {
                $('#account_email').val($(this).val());
            });

            $("#contact_person_phone").on("change keyup paste", function () {
                $('#account_phone').val($(this).val());
            });


        });

    </script>

    <script>
        "use strict";

        $(document).ready(function () {
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
                    setAcceptForAllInputs();
                    $('.identity-docs-error-msg, .company-identity-docs-error-msg').remove();
                },
                // Identity validation is handled by the wizard (images OR PDFs).
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
                        $('.identity-docs-error-msg, .company-identity-docs-error-msg').remove();
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

            // Multi-attachment uploader for PDF + additional documents.
            // Handles preview + remove while syncing selected files into the real <input type="file">.
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
                            $('.identity-docs-error-msg, .company-identity-docs-error-msg').remove();
                        });

                        item.appendChild(removeBtn);
                        previewEl.appendChild(item);
                    });
                }

                document.addEventListener('click', function (e) {
                    const trigger = e.target.closest('[data-attachment-trigger]');
                    if (trigger) {
                        const uploaderEl = trigger.closest('[data-attachment-uploader]');
                        const inputEl = uploaderEl ? uploaderEl.querySelector('[data-attachment-input]') : null;
                        if (inputEl) inputEl.click();
                    }
                });

                document.addEventListener('change', function (e) {
                    const inputEl = e.target && e.target.matches && e.target.matches('[data-attachment-input]') ? e.target : null;
                    if (!inputEl) return;

                    const uploaderEl = inputEl.closest('[data-attachment-uploader]');
                    if (!uploaderEl) return;

                    const selected = Array.from(inputEl.files || []);
                    const prev = attachmentState.get(inputEl) || [];

                    // If user cleared selection, reset.
                    if (selected.length === 0) {
                        attachmentState.set(inputEl, []);
                        syncInputFiles(inputEl, []);
                        renderPreview(uploaderEl, []);
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
                    $('.identity-docs-error-msg, .company-identity-docs-error-msg').remove();
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

                        lat: 23.811842872190343,
                        lng: 90.356331
                    };
                    const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                        center: {
                            lat: 23.811842872190343,
                            lng: 90.356331
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
