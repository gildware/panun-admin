@php
    /** @var 'add'|'edit' $mode */
    $mode = $mode ?? 'add';
    $isEdit = $mode === 'edit';

    $providerFormDraft = $providerFormDraft ?? null;
    $draftDisk = \Illuminate\Support\Facades\Storage::disk('public');
    $draftLogoUrl = null;
    $draftContactPhotoUrl = null;
    if (is_array($providerFormDraft) && ! empty($providerFormDraft['files']['logo']['rel'] ?? null)) {
        $draftLogoUrl = $draftDisk->url($providerFormDraft['files']['logo']['rel']);
    }
    if (is_array($providerFormDraft) && ! empty($providerFormDraft['files']['contact_person_photo']['rel'] ?? null)) {
        $draftContactPhotoUrl = $draftDisk->url($providerFormDraft['files']['contact_person_photo']['rel']);
    }
    $identityDraftCount = 0;
    if (is_array($providerFormDraft)) {
        $idImgs = $providerFormDraft['files']['identity_images'] ?? [];
        if (is_array($idImgs)) {
            $identityDraftCount += count(array_filter($idImgs));
        }
        $idPdfs = $providerFormDraft['files']['identity_pdf_files'] ?? [];
        if (is_array($idPdfs)) {
            $identityDraftCount += count(array_filter($idPdfs));
        }
    }
    $companyIdentityDraftCount = 0;
    if (is_array($providerFormDraft)) {
        $cImgs = $providerFormDraft['files']['company_identity_images'] ?? [];
        if (is_array($cImgs)) {
            $companyIdentityDraftCount += count(array_filter($cImgs));
        }
        $cPdfs = $providerFormDraft['files']['company_identity_pdf_files'] ?? [];
        if (is_array($cPdfs)) {
            $companyIdentityDraftCount += count(array_filter($cPdfs));
        }
    }

    $draftAdditionalDocumentRows = [];
    if (is_array($providerFormDraft) && ! empty($providerFormDraft['files']['additional_documents'])) {
        $draftAdditionalDocumentRows = $providerFormDraft['files']['additional_documents'];
    }

    $providerType = old('provider_type', ($provider?->provider_type ?? 'individual'));

    // Contact person (Box 4)
    $contactName = old('contact_person_name', $provider?->contact_person_name ?? '');
    $contactPhone = old('contact_person_phone', $provider?->contact_person_phone ?? '');
    $contactEmail = old('contact_person_email', $provider?->contact_person_email ?? '');

    // Identity (Box 5)
    $identityType = old('identity_type', $provider?->owner?->identification_type ?? 'nid');
    $identityNumber = old('identity_number', $provider?->owner?->identification_number ?? '');

    // Company info (Box 2)
    $companyName = old('company_name', $provider?->company_name ?? '');
    $companyPhone = old('company_phone', $provider?->company_phone ?? '');
    $companyEmail = old('company_email', $provider?->company_email ?? '');

    // Company identity (Box 3)
    $companyIdentityType = old('company_identity_type', $provider?->company_identity_type ?? 'trade_license');
    $companyIdentityNumber = old('company_identity_number', $provider?->company_identity_number ?? '');

    // Address (Box 6) — multi-zone coverage (leaf zones after server normalization)
    $selectedZoneIds = old('zone_ids', $provider && $provider->relationLoaded('zones') && $provider->zones->isNotEmpty()
        ? $provider->zones->pluck('id')->all()
        : ($provider?->zone_id ? [(string) $provider->zone_id] : []));
    if (! is_array($selectedZoneIds)) {
        $selectedZoneIds = [];
    }
    $companyAddress = old('company_address', $provider?->company_address ?? '');

    $defaultAddProviderMapLat = 34.0573181;
    $defaultAddProviderMapLng = 74.806267;
    $latitude = old('latitude', $provider?->coordinates['latitude'] ?? (! $isEdit ? $defaultAddProviderMapLat : null));
    $longitude = old('longitude', $provider?->coordinates['longitude'] ?? (! $isEdit ? $defaultAddProviderMapLng : null));

    $zoneTree = $zoneTree ?? [];

    $contactPhotoRequired = $mode === 'add';

    $contactPersonPhotoHasFile = ! empty($draftContactPhotoUrl)
        || ($isEdit && filled($provider?->contact_person_photo ?? null))
        || (! $isEdit && filled(old('contact_person_photo')));

    $logoRemoveRequested = (string) old('logo_remove', '0') === '1';
    $companyLogoHasFile = ! empty($draftLogoUrl)
        || ($isEdit && filled($provider?->logo ?? null) && ! $logoRemoveRequested)
        || (! $isEdit && filled(old('logo')) && ! $logoRemoveRequested);
@endphp

<div class="provider-add-edit-form-root row g-3">
    {{-- Box 1 (Full Width) --}}
    <div class="col-12">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-20">
                    <h4 class="c1 mb-0">{{ translate('Provider_Type') }}</h4>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="provider_type"
                            id="provider_type_individual"
                            value="individual"
                            required
                            {{ $providerType === 'individual' ? 'checked' : '' }}>
                        <label class="form-check-label" for="provider_type_individual">
                            {{ translate('Individual') }}
                        </label>
                    </div>

                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="provider_type"
                            id="provider_type_company"
                            value="company"
                            required
                            {{ $providerType === 'company' ? 'checked' : '' }}>
                        <label class="form-check-label" for="provider_type_company">
                            {{ translate('Company') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Box 2 (Half Width - Left | Company only) --}}
    <div class="col-12 col-md-6 provider-company-fields">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Company_Information') }}</h4>
                </div>

                {{-- Logo first (requested) --}}
                <div class="mb-30 provider-logo-fields">
                    <div class="d-flex flex-column align-items-center gap-3">
                        <h3 class="mb-0">{{ translate('Company_Logo') }}</h3>

                        <div class="form-error-wrap d-flex align-items-center flex-column">
                            <div
                                class="provider-upload-wrapper"
                                data-remove-field="logo_remove"
                                data-hide-remove-until-upload="1">
                                <div class="upload-file">
                                    <input
                                        type="file"
                                        class="upload-file__input provider-upload-input"
                                        name="logo"
                                        accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                        data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                    <div class="upload-file__img">
                                        <img
                                            src="{{ $isEdit ? ($draftLogoUrl ?: $provider?->logo_full_path) : ($draftLogoUrl ?? onErrorImage(
                                                old('logo'),
                                                asset('storage/app/public/provider/logo') . '/' . old('logo'),
                                                asset('assets/admin-module/img/placeholder.png'),
                                                'provider/logo/'
                                            )) }}"
                                            data-placeholder-src="{{ asset('assets/admin-module/img/placeholder.png') }}"
                                            alt="{{ translate('image') }}">
                                    </div>
                                    <span class="upload-file__edit">
                                        <span class="material-icons">edit</span>
                                    </span>
                                </div>

                                <input type="hidden" name="logo_remove" value="{{ $logoRemoveRequested ? '1' : '0' }}">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn-sm mt-2 provider-upload-remove-btn {{ $companyLogoHasFile ? '' : 'd-none' }}">
                                    {{ translate('Remove') }}
                                </button>
                            </div>
                        </div>

                        <p class="opacity-75 max-w220">
                            {{ translate('Image format -') }} {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                            {{ translate('Image Size') }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                            {{ translate('Image Ratio') }} - 1:1
                        </p>
                    </div>
                </div>

                <div class="mb-30">
                    <div class="form-floating form-floating__icon">
                        <input
                            type="text"
                            class="form-control"
                            name="company_name"
                            value="{{ $companyName }}"
                            placeholder="{{ translate('Company_/_Individual_Name') }}"
                            maxlength="191">
                        <label>{{ translate('Company_/_Individual_Name') }}</label>
                        <span class="material-icons">store</span>
                    </div>
                </div>

                <div class="mb-30">
                    <div class="form-floating form-floting-fix">
                        <label for="company_phone">{{ translate('Phone') }}</label>
                        <input
                            type="tel"
                            class="form-control"
                            name="company_phone"
                            id="company_phone"
                            value="{{ $companyPhone }}"
                            placeholder="{{ translate('Phone') }}"
                            pattern="^([0-9\s\-\+\(\)]*)$"
                            minlength="8">
                    </div>
                </div>

                <div class="mb-30">
                    <div class="form-floating form-floating__icon">
                        <input
                            type="text"
                            class="form-control"
                            id="company_email"
                            name="company_email"
                            value="{{ $companyEmail }}"
                            placeholder="{{ translate('Email') }}"
                            autocomplete="email"
                            inputmode="email">
                        <label>{{ translate('Email') }}</label>
                        <span class="material-icons">mail</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Box 3 (Half Width - Right | Company only) --}}
    <div class="col-12 col-md-6 provider-company-identity-fields">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Company_Docs_&_Identity') }}</h4>
                </div>

                <div class="mb-30">
                    <div class="form-error-wrap">
                        <select class="select-identity theme-input-style w-100" name="company_identity_type">
                            <option selected disabled>{{ translate('Select_Identity_Type') }}</option>
                            <option value="trade_license" {{ $companyIdentityType === 'trade_license' ? 'selected' : '' }}>
                                {{ translate('Trade_License') }}
                            </option>
                            <option value="company_id" {{ $companyIdentityType === 'company_id' ? 'selected' : '' }}>
                                {{ translate('Company_Id') }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-30">
                    <div class="form-floating form-floating__icon">
                        <input
                            type="text"
                            class="form-control"
                            name="company_identity_number"
                            value="{{ $companyIdentityNumber }}"
                            placeholder="{{ translate('Identity_Number') }}">
                        <label>{{ translate('Identity_Number') }}</label>
                        <span class="material-icons">badge</span>
                    </div>
                </div>

                <div class="upload-file w-100 mb-30">
                    <h3 class="mb-3">{{ translate('Identification_Image') }}</h3>
                    <div id="company_multi_image_picker" data-company-identity-draft-count="{{ (int) $companyIdentityDraftCount }}">
                        @if($isEdit)
                            @foreach($provider?->company_identity_images_full_path ?? [] as $image)
                                @php
                                    $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION));
                                @endphp
                                @if($ext === 'pdf')
                                    <a class="p-1 text-decoration-none" href="{{ $image }}" target="_blank" rel="noopener">PDF</a>
                                @else
                                    <img class="p-1" height="150" src="{{ $image }}" alt="{{ translate('image') }}">
                                @endif
                            @endforeach
                        @elseif(is_array($providerFormDraft) && ! empty($providerFormDraft['files']['company_identity_images']))
                            @foreach($providerFormDraft['files']['company_identity_images'] as $cDraft)
                                @if(! empty($cDraft['rel']))
                                    <img class="p-1 border rounded" height="120" src="{{ $draftDisk->url($cDraft['rel']) }}" alt="">
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Box 4 (Half Width - Left) --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Contact_Person') }}</h4>
                </div>

                {{-- Contact photo: centered like company logo; Remove only when an image exists --}}
                <div class="mb-30">
                    <div class="d-flex flex-column align-items-center gap-3">
                        <h3 class="mb-0">{{ translate('Contact_Person_Photo') }}</h3>

                        <div class="form-error-wrap d-flex align-items-center flex-column">
                            <div
                                class="provider-upload-wrapper"
                                data-remove-field="contact_person_photo_remove"
                                data-hide-remove-until-upload="1">
                                <div class="upload-file">
                                    <input
                                        type="file"
                                        class="upload-file__input provider-upload-input"
                                        name="contact_person_photo"
                                        accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                        data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                    <div class="upload-file__img">
                                        <img
                                            src="{{ $isEdit ? ($draftContactPhotoUrl ? $draftContactPhotoUrl : onErrorImage(
                                                $provider?->contact_person_photo,
                                                asset('storage/provider/contact_person_photo') . '/' . $provider?->contact_person_photo,
                                                asset('assets/admin-module/img/placeholder.png'),
                                                'provider/contact_person_photo/'
                                            )) : ($draftContactPhotoUrl ?? onErrorImage(
                                                old('contact_person_photo'),
                                                asset('storage/provider/contact_person_photo') . '/' . old('contact_person_photo'),
                                                asset('assets/admin-module/img/placeholder.png'),
                                                'provider/contact_person_photo/'
                                            )) }}"
                                            data-placeholder-src="{{ asset('assets/admin-module/img/placeholder.png') }}"
                                            alt="{{ translate('image') }}">
                                    </div>

                                    <span class="upload-file__edit">
                                        <span class="material-icons">edit</span>
                                    </span>
                                </div>

                                <input type="hidden" name="contact_person_photo_remove" value="0">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn-sm mt-2 provider-upload-remove-btn {{ $contactPersonPhotoHasFile ? '' : 'd-none' }}">
                                    {{ translate('Remove') }}
                                </button>
                            </div>
                        </div>

                        <p class="opacity-75 max-w220 text-center">
                            {{ translate('Image format -') }} {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                            {{ translate('Image Size') }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                            {{ translate('Image Ratio') }} - 1:1
                        </p>
                    </div>
                </div>

                <div class="mb-30">
                    <div class="form-floating form-floating__icon">
                        <input
                            type="text"
                            class="form-control"
                            name="contact_person_name"
                            value="{{ $contactName }}"
                            placeholder="name"
                            maxlength="191"
                            required>
                        <label>{{ translate('Name') }}</label>
                        <span class="material-icons">account_circle</span>
                    </div>
                </div>

                <div class="row gx-2">
                    <div class="col-lg-6">
                        <div class="form-floating form-floting-fix mb-30">
                            <label for="contact_person_phone">{{ translate('Phone') }}</label>
                            <input
                                type="tel"
                                class="form-control"
                                name="contact_person_phone"
                                id="contact_person_phone"
                                value="{{ $contactPhone }}"
                                placeholder="{{ translate('Phone') }}"
                                required
                                pattern="^([0-9\s\-\+\(\)]*)$"
                                minlength="8">
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-30">
                            <div class="form-floating form-floating__icon">
                                <input
                                    type="text"
                                    class="form-control"
                                    name="contact_person_email"
                                    id="contact_person_email"
                                    value="{{ $contactEmail }}"
                                    placeholder="{{ translate('Email') }}"
                                    autocomplete="email"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    data-lpignore="true"
                                    data-1p-ignore
                                    @if($mode === 'add')
                                        readonly
                                        onfocus="this.removeAttribute('readonly')"
                                    @endif>
                                <label>{{ translate('Email') }}</label>
                                <span class="material-symbols-outlined">mail</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($mode === 'edit')
                    <h4 class="c1 mb-20">{{ translate('Authentication') }}</h4>
                    <div class="row gx-2">
                        <div class="col-lg-6">
                            <div class="mb-30">
                                <div class="form-floating form-floating__icon">
                                    <input
                                        type="password"
                                        class="form-control"
                                        name="password"
                                        id="pass"
                                        placeholder="{{ translate('Password') }}">
                                    <label>{{ translate('Password') }}</label>
                                    <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                    <span class="material-icons">lock</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-30">
                                <div class="form-floating form-floating__icon">
                                    <input
                                        type="password"
                                        class="form-control"
                                        name="confirm_password"
                                        id="confirm_password"
                                        placeholder="{{ translate('Confirm_Password') }}">
                                    <label>{{ translate('Confirm_Password') }}</label>
                                    <span class="material-icons togglePassword __right-eye">visibility_off</span>
                                    <span class="material-icons">lock</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-muted small mb-0">{{ translate('New_providers_use_contact_phone_as_login_password') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Box 5 (Half Width - Right) --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Contact_Person_Identity') }}</h4>
                </div>

                <div class="mb-30">
                    <div class="form-error-wrap">
                        <select class="select-identity theme-input-style w-100" name="identity_type" required>
                            <option selected disabled>{{ translate('Select_Identity_Type') }}</option>
                            <option value="nid" {{ $identityType === 'nid' ? 'selected' : '' }}>{{ translate('Aadhar_Card') }}</option>
                            <option value="passport" {{ $identityType === 'passport' ? 'selected' : '' }}>{{ translate('Passport') }}</option>
                            <option value="driving_license" {{ $identityType === 'driving_license' ? 'selected' : '' }}>{{ translate('Driving_License') }}</option>
                        </select>
                    </div>
                </div>

                <div class="mb-30">
                    <div class="form-floating form-floating__icon">
                        <input
                            type="text"
                            class="form-control"
                            name="identity_number"
                            value="{{ $identityNumber }}"
                            placeholder="{{ translate('Identity_Number') }}"
                            required>
                        <label>{{ translate('Identity_Number') }}</label>
                        <span class="material-icons">badge</span>
                    </div>
                </div>

                <div class="upload-file w-100 mb-30">
                    <h3 class="mb-3">{{ translate('Identification_Image') }}</h3>
                    <div id="multi_image_picker" data-identity-draft-count="{{ (int) $identityDraftCount }}">
                        @if($isEdit)
                            @foreach($provider?->owner?->identification_image_full_path as $image)
                                @php
                                    $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION));
                                @endphp
                                @if($ext === 'pdf')
                                    <a class="p-1 text-decoration-none" href="{{ $image }}" target="_blank" rel="noopener">PDF</a>
                                @else
                                    <img class="p-1" height="150" src="{{ $image }}" alt="{{ translate('image') }}">
                                @endif
                            @endforeach
                        @elseif(is_array($providerFormDraft) && ! empty($providerFormDraft['files']['identity_images']))
                            @foreach($providerFormDraft['files']['identity_images'] as $idDraft)
                                @if(! empty($idDraft['rel']))
                                    <img class="p-1 border rounded" height="120" src="{{ $draftDisk->url($idDraft['rel']) }}" alt="">
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Service zones (left) | Address + map (right) --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Service_Zones') }}</h4>
                </div>
                <p class="text-muted fz-12 mb-20 mx-1 mt-1" style="line-height: 1.55;">{{ translate('provider_form_zone_tree_hint') }}</p>

                @if(count($zoneTree) > 0)
                    <div class="provider-zone-tree border rounded overflow-hidden mx-1 px-2">
                        @foreach($zoneTree as $rootNode)
                            <div class="provider-zone-tree-root border-bottom border-light">
                                @include('providermanagement::admin.provider.partials.provider-zone-tree-branch', [
                                    'nodes' => [$rootNode],
                                    'level' => 0,
                                    'selectedZoneIds' => $selectedZoneIds,
                                ])
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info mb-0 mx-1">{{ translate('no_data_found') }}</div>
                @endif
                <div id="provider-create-zone-error" class="alert alert-danger py-2 px-3 mb-0 mt-3 d-none" role="alert"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Address_Information') }}</h4>
                </div>

                <div class="mb-30">
                    <div class="form-floating">
                        <textarea
                            id="address"
                            class="form-control resize-none"
                            placeholder="{{ translate('Full_Address') }}"
                            name="company_address"
                            required>{{ $companyAddress }}</textarea>
                        <label>{{ translate('Full_Address') }}</label>
                    </div>
                </div>

                <div class="border-top pt-4 mt-2">
                    <h5 class="c1 mb-20">{{ translate('Select Address from Map') }}</h5>

                    <div class="row gx-2">
                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <div class="form-floating form-floating__icon">
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="latitude"
                                        id="latitude"
                                        placeholder="{{ translate('latitude') }} *"
                                        value="{{ $latitude }}"
                                        required
                                        readonly
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="{{ translate('Select from map') }}">
                                    <label>{{ translate('latitude') }} *</label>
                                    <span class="material-symbols-outlined">location_on</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mb-30">
                                <div class="form-floating form-floating__icon">
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="longitude"
                                        id="longitude"
                                        placeholder="{{ translate('longitude') }} *"
                                        value="{{ $longitude }}"
                                        required
                                        readonly
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="{{ translate('Select from map') }}">
                                    <label>{{ translate('longitude') }} *</label>
                                    <span class="material-symbols-outlined">location_on</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div id="location_map_div" class="location_map_class">
                            <input
                                id="pac-input"
                                class="form-control w-auto mb-3"
                                data-toggle="tooltip"
                                data-placement="right"
                                data-original-title="{{ translate('search_your_location_here') }}"
                                type="text"
                                placeholder="{{ translate('search_here') }}"/>
                            <div id="location_map_canvas" class="overflow-hidden rounded canvas_class"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Box 7 (Full Width) --}}
    <div class="col-12">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-20">
                    <h4 class="c1 mb-0">{{ translate('Additional_Documents') }}</h4>
                    <button
                        type="button"
                        class="btn btn--secondary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#additionalDocumentModal">
                        {{ translate('Add_New') }}
                    </button>
                </div>
                @php
                    $oldAdditionalDocuments = old('additional_documents', []);
                    $oldAdditionalDocuments = is_array($oldAdditionalDocuments) ? $oldAdditionalDocuments : [];
                    $existingAdditionalDocuments = $existingAdditionalDocuments ?? collect();
                    $existingAdditionalDocumentFiles = $existingAdditionalDocumentFiles ?? collect();
                    if (count($oldAdditionalDocuments) === 0 && $isEdit && $existingAdditionalDocuments->isNotEmpty()) {
                        foreach ($existingAdditionalDocuments as $eIdx => $existingDoc) {
                            $oldAdditionalDocuments[$eIdx] = [
                                'name' => $existingDoc->document_name ?? '',
                                'description' => $existingDoc->document_description ?? '',
                                '__document_id' => $existingDoc->id ?? null,
                            ];
                        }
                    }
                    if (count($oldAdditionalDocuments) === 0 && count($draftAdditionalDocumentRows) > 0) {
                        foreach ($draftAdditionalDocumentRows as $dIdx => $dr) {
                            $oldAdditionalDocuments[$dIdx] = [
                                'name' => $dr['name'] ?? '',
                                'description' => $dr['description'] ?? '',
                            ];
                        }
                    }
                @endphp
                <div
                    id="provider-contact-unique-config"
                    class="d-none"
                    data-check-url="{{ route('admin.provider.check-owner-contact-unique') }}"
                    data-csrf="{{ csrf_token() }}"
                    data-exclude-user-id="{{ $isEdit ? (string) ($provider->user_id ?? '') : '' }}">
                </div>
                <div
                    id="additional_documents_rows"
                    class="d-flex flex-column gap-3"
                    data-next-index="{{ count($oldAdditionalDocuments) }}">
                    @foreach($oldAdditionalDocuments as $addIdx => $addRow)
                        @php
                            $draftAddRow = $draftAdditionalDocumentRows[$addIdx] ?? null;
                            $draftAddFiles = is_array($draftAddRow) ? ($draftAddRow['files'] ?? []) : [];
                            $existingDocId = $addRow['__document_id'] ?? null;
                            $existingAddFiles = $existingDocId ? ($existingAdditionalDocumentFiles[$existingDocId] ?? collect()) : collect();
                        @endphp
                        <div class="border rounded p-3 additional-document-row">
                            <div class="row g-2 align-items-start">
                                <div class="col-md-5">
                                    <div class="fw-semibold text-dark mb-1" data-doc-name>{{ $addRow['name'] ?? '' }}</div>
                                    <div class="text-muted fs-12" data-doc-description>{{ $addRow['description'] ?? '' }}</div>
                                    <input type="hidden" name="additional_documents[{{ $addIdx }}][name]" value="{{ $addRow['name'] ?? '' }}" data-doc-name-input>
                                    <input type="hidden" name="additional_documents[{{ $addIdx }}][description]" value="{{ $addRow['description'] ?? '' }}" data-doc-description-input>
                                    <input type="hidden" name="additional_documents[{{ $addIdx }}][existing_document_id]" value="{{ $existingDocId ?? '' }}">
                                    <input
                                        type="file"
                                        class="d-none"
                                        name="additional_documents[{{ $addIdx }}][files][]"
                                        multiple
                                        accept="image/*,application/pdf,.pdf"
                                        data-doc-row-files>
                                </div>
                                <div class="col-md-5">
                                    <div class="d-flex flex-wrap gap-2" data-doc-files-preview>
                                        @if(count($draftAddFiles) > 0)
                                            @foreach($draftAddFiles as $df)
                                                @if(! empty($df['rel']))
                                                    @php
                                                        $dn = $df['original'] ?? '';
                                                        $ext = strtolower(pathinfo($dn, PATHINFO_EXTENSION));
                                                    @endphp
                                                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true))
                                                        <div class="position-relative border rounded p-2 bg-white" style="width:110px">
                                                            <img src="{{ $draftDisk->url($df['rel']) }}" alt="" class="rounded" style="width:100%;height:60px;object-fit:cover">
                                                            <div class="small mt-1 text-truncate" title="{{ $dn }}">{{ $dn }}</div>
                                                        </div>
                                                    @else
                                                        <div class="position-relative border rounded p-2 bg-white" style="width:110px;min-height:100px">
                                                            <div class="d-flex align-items-center justify-content-center rounded" style="height:60px;background:#fff5f5">
                                                                <span class="material-icons text-danger">picture_as_pdf</span>
                                                            </div>
                                                            <div class="small mt-1 text-truncate" title="{{ $dn }}">{{ $dn }}</div>
                                                        </div>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @elseif($existingAddFiles instanceof \Illuminate\Support\Collection && $existingAddFiles->isNotEmpty())
                                            @foreach($existingAddFiles as $existingFile)
                                                @php
                                                    $storedPath = $existingFile->file_path ?? '';
                                                    $storageDisk = $existingFile->storage ?? 'public';
                                                    $fileName = basename((string) $storedPath);
                                                    if ($storedPath && ! str_contains($storedPath, '/')) {
                                                        $storedPath = 'provider/additional-documents/' . $existingDocId . '/' . $storedPath;
                                                    }
                                                    $fileUrl = $storedPath ? \Illuminate\Support\Facades\Storage::disk($storageDisk)->url($storedPath) : '';
                                                    $ext = strtolower(pathinfo((string) $storedPath, PATHINFO_EXTENSION));
                                                @endphp
                                                @if($fileUrl)
                                                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true))
                                                        <div class="position-relative border rounded p-2 bg-white" style="width:110px">
                                                            <img src="{{ $fileUrl }}" alt="" class="rounded" style="width:100%;height:60px;object-fit:cover">
                                                            <div class="small mt-1 text-truncate" title="{{ $fileName }}">{{ $fileName }}</div>
                                                        </div>
                                                    @else
                                                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="position-relative border rounded p-2 bg-white text-decoration-none" style="width:110px;min-height:100px">
                                                            <div class="d-flex align-items-center justify-content-center rounded" style="height:60px;background:#fff5f5">
                                                                <span class="material-icons text-danger">picture_as_pdf</span>
                                                            </div>
                                                            <div class="small mt-1 text-truncate text-dark" title="{{ $fileName }}">{{ $fileName }}</div>
                                                        </a>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex justify-content-md-end">
                                    <button type="button" class="btn btn--danger btn-sm additional-document-delete-btn">
                                        {{ translate('Delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template id="additional_document_row_template">
                    <div class="border rounded p-3 additional-document-row">
                        <div class="row g-2 align-items-start">
                            <div class="col-md-5">
                                <div class="fw-semibold text-dark mb-1" data-doc-name></div>
                                <div class="text-muted fs-12" data-doc-description></div>
                                <input type="hidden" name="additional_documents[__INDEX__][name]" data-doc-name-input>
                                <input type="hidden" name="additional_documents[__INDEX__][description]" data-doc-description-input>
                                <input type="hidden" name="additional_documents[__INDEX__][existing_document_id]" value="">
                                <input
                                    type="file"
                                    class="d-none"
                                    name="additional_documents[__INDEX__][files][]"
                                    multiple
                                    accept="image/*,application/pdf,.pdf"
                                    data-doc-row-files>
                            </div>

                            <div class="col-md-5">
                                <div class="d-flex flex-wrap gap-2" data-doc-files-preview></div>
                            </div>

                            <div class="col-md-2 d-flex justify-content-md-end">
                                <button type="button" class="btn btn--danger btn-sm additional-document-delete-btn">
                                    {{ translate('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="additionalDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Add_New_Document') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-floating form-floating__icon mb-3">
                    <input type="text" class="form-control" id="additional_doc_name" placeholder="{{ translate('File_Name') }}" maxlength="191">
                    <label>{{ translate('File_Name') }}</label>
                    <span class="material-icons">description</span>
                </div>
                <div class="form-floating mb-3">
                    <textarea class="form-control resize-none" id="additional_doc_description" placeholder="{{ translate('Description') }}"></textarea>
                    <label>{{ translate('Description') }}</label>
                </div>
                <div id="additional_doc_modal_error" class="text-danger small mb-2"></div>
                <div class="multi-attachment-uploader">
                    <input
                        type="file"
                        class="d-none"
                        id="additional_doc_files_input"
                        multiple
                        accept="image/*,application/pdf,.pdf">
                    <button type="button" class="btn btn--secondary btn-sm w-100 mb-2" id="additional_doc_select_files_btn">
                        {{ translate('Add_Files') }}
                    </button>
                    <div class="small text-muted mb-2" id="additional_doc_files_count"></div>
                    <div class="d-flex flex-wrap gap-2 text-dark" id="additional_doc_files_preview"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button
                    type="button"
                    class="btn btn--primary"
                    id="save_additional_document_btn"
                    onclick="return window.handleAdditionalDocumentAdd ? window.handleAdditionalDocumentAdd(event) : false;">
                    {{ translate('Add') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        "use strict";
        (function () {
            function expandProviderZoneAncestorsOfChecked() {
                document.querySelectorAll(".provider-zone-leaf-cb:checked").forEach(function (cb) {
                    var panel = cb.closest(".provider-zone-tree-children");
                    while (panel) {
                        panel.classList.remove("d-none");
                        var toggle = panel.parentElement ? panel.parentElement.querySelector(".provider-zone-tree-toggle") : null;
                        if (toggle) {
                            toggle.setAttribute("aria-expanded", "true");
                        }
                        panel = panel.parentElement && panel.parentElement.closest
                            ? panel.parentElement.closest(".provider-zone-tree-children")
                            : null;
                    }
                });
            }

            function syncProviderZoneParentsFromLeaves() {
                document.querySelectorAll("input.provider-zone-parent-cb").forEach(function (cb) {
                    var item = cb.closest(".provider-zone-tree-item");
                    var panel = item ? item.querySelector(".provider-zone-tree-children") : null;
                    var leaves = panel ? panel.querySelectorAll("input.provider-zone-leaf-cb") : [];
                    var leavesArr = Array.from(leaves);

                    if (!leavesArr.length) {
                        cb.checked = false;
                        cb.indeterminate = false;
                        return;
                    }

                    var checkedCount = leavesArr.filter(function (l) {
                        return l.checked;
                    }).length;

                    cb.checked = checkedCount === leavesArr.length;
                    cb.indeterminate = checkedCount > 0 && checkedCount < leavesArr.length;
                });
            }

            function syncProviderZoneLabelStyles() {
                // Leaf labels are "selected" (blue) only when checked
                document.querySelectorAll("input.provider-zone-leaf-cb").forEach(function (cb) {
                    var label = cb.id ? document.querySelector('label[for="' + cb.id + '"]') : null;
                    if (!label) return;
                    var isSelected = cb.checked === true;
                    label.classList.toggle("text-primary", isSelected);
                    label.classList.toggle("text-muted", !isSelected);
                });

                // Parent labels are "selected" (blue) only when fully checked (not indeterminate)
                document.querySelectorAll("input.provider-zone-parent-cb").forEach(function (cb) {
                    var label = cb.id ? document.querySelector('label[for="' + cb.id + '"]') : null;
                    if (!label) return;
                    var isSelected = cb.checked === true && cb.indeterminate === false;
                    label.classList.toggle("text-primary", isSelected);
                    label.classList.toggle("text-muted", !isSelected);
                });
            }

            function initProviderZoneTreeSelection() {
                syncProviderZoneParentsFromLeaves();
                syncProviderZoneLabelStyles();
                expandProviderZoneAncestorsOfChecked();
            }

            document.addEventListener("click", function (e) {
                var t = e.target && e.target.closest ? e.target.closest(".provider-zone-tree-toggle") : null;
                if (!t) {
                    return;
                }
                e.preventDefault();
                var item = t.closest(".provider-zone-tree-item");
                if (!item) {
                    return;
                }
                var ch = null;
                var kids = item.children;
                for (var i = 0; i < kids.length; i++) {
                    if (kids[i].classList && kids[i].classList.contains("provider-zone-tree-children")) {
                        ch = kids[i];
                        break;
                    }
                }
                if (!ch) {
                    return;
                }
                var open = ch.classList.toggle("d-none") === false;
                t.setAttribute("aria-expanded", open ? "true" : "false");
                var icon = t.querySelector(".provider-zone-chevron");
                if (icon) {
                    icon.textContent = open ? "remove" : "add";
                }
            });

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initProviderZoneTreeSelection);
            } else {
                initProviderZoneTreeSelection();
            }

            // Parent checkbox controls all descendant leaves
            document.addEventListener("change", function (e) {
                var input = e.target;
                if (!(input && input.matches && input.matches("input.provider-zone-parent-cb"))) {
                    return;
                }

                var item = input.closest(".provider-zone-tree-item");
                if (!item) {
                    return;
                }

                var leaves = item.querySelectorAll("input.provider-zone-leaf-cb");
                leaves.forEach(function (l) {
                    l.checked = input.checked;
                });

                syncProviderZoneParentsFromLeaves();
                syncProviderZoneLabelStyles();
                expandProviderZoneAncestorsOfChecked();
            });

            // Leaf checkbox controls its parents (checked/indeterminate)
            document.addEventListener("change", function (e) {
                var input = e.target;
                if (!(input && input.matches && input.matches("input.provider-zone-leaf-cb"))) {
                    return;
                }

                syncProviderZoneParentsFromLeaves();
                syncProviderZoneLabelStyles();
                expandProviderZoneAncestorsOfChecked();
            });

            document.addEventListener("click", function (e) {
                const btn = e.target.closest(".provider-upload-remove-btn");
                if (!btn) return;

                const wrapper = btn.closest(".provider-upload-wrapper");
                if (!wrapper) return;

                const input = wrapper.querySelector('input[type="file"].provider-upload-input');
                const img = wrapper.querySelector("img[data-placeholder-src]");
                const hidden = wrapper.querySelector('input[type="hidden"][name="' + (wrapper.dataset.removeField || "") + '"]');
                const placeholderSrc = img ? img.dataset.placeholderSrc : "";

                if (hidden) hidden.value = "1";
                if (input) input.value = "";
                if (img && placeholderSrc) img.src = placeholderSrc;

                if (wrapper.dataset.hideRemoveUntilUpload === "1") {
                    const rm = wrapper.querySelector(".provider-upload-remove-btn");
                    if (rm) rm.classList.add("d-none");
                }

                if (input && typeof window.jQuery !== "undefined") {
                    const $form = window.jQuery(input).closest("form");
                    const validator = $form.length ? $form.data("validator") : null;
                    if (validator && input.name) {
                        validator.element(input);
                    }
                }
                if (typeof window.refreshProviderCreateStep0ValidationSummary === "function") {
                    setTimeout(function () {
                        window.refreshProviderCreateStep0ValidationSummary();
                    }, 0);
                }
            });

            document.addEventListener("change", function (e) {
                const input = e.target;
                if (!(input && input.matches && input.matches("input.provider-upload-input[type='file']"))) return;

                const wrapper = input.closest(".provider-upload-wrapper");
                if (!wrapper) return;

                const hidden = wrapper.querySelector('input[type="hidden"][name="' + (wrapper.dataset.removeField || "") + '"]');
                if (hidden) hidden.value = "0";

                if (wrapper.dataset.hideRemoveUntilUpload === "1" && input.files && input.files.length > 0) {
                    const rm = wrapper.querySelector(".provider-upload-remove-btn");
                    if (rm) rm.classList.remove("d-none");
                }

                if (typeof window.jQuery !== "undefined") {
                    const $form = window.jQuery(input).closest("form");
                    const validator = $form.length ? $form.data("validator") : null;
                    if (validator && input.name) {
                        validator.element(input);
                    }
                }
                if (typeof window.refreshProviderCreateStep0ValidationSummary === "function") {
                    setTimeout(function () {
                        window.refreshProviderCreateStep0ValidationSummary();
                    }, 0);
                }
            });

            function getAdditionalDocRefs() {
                return {
                    rowsContainer: document.getElementById("additional_documents_rows"),
                    rowTemplate: document.getElementById("additional_document_row_template"),
                    modalEl: document.getElementById("additionalDocumentModal"),
                    modalName: document.getElementById("additional_doc_name"),
                    modalDescription: document.getElementById("additional_doc_description"),
                    modalFilesInput: document.getElementById("additional_doc_files_input"),
                    modalSelectFilesBtn: document.getElementById("additional_doc_select_files_btn"),
                    modalFilesCount: document.getElementById("additional_doc_files_count"),
                    modalFilesPreview: document.getElementById("additional_doc_files_preview"),
                    modalError: document.getElementById("additional_doc_modal_error"),
                };
            }

            let modalSelectedFiles = [];

            /** Stable id for deduping File objects across picker rounds */
            function additionalDocFileKey(file) {
                if (!file) return "";
                return [
                    file.name || "",
                    String(file.size || 0),
                    String(file.lastModified || 0),
                ].join("\u0000");
            }

            function mergePickedFilesIntoModal(picked) {
                if (!picked || !picked.length) return;
                const seen = new Set(modalSelectedFiles.map(additionalDocFileKey));
                picked.forEach(function (file) {
                    if (!file) return;
                    const key = additionalDocFileKey(file);
                    if (key && !seen.has(key)) {
                        seen.add(key);
                        modalSelectedFiles.push(file);
                    }
                });
            }

            function syncModalFileInput() {
                const refs = getAdditionalDocRefs();
                if (!refs.modalFilesInput || !window.DataTransfer) return;
                const dt = new DataTransfer();
                modalSelectedFiles.forEach(function (file) {
                    if (file) dt.items.add(file);
                });
                refs.modalFilesInput.files = dt.files;
            }

            function clearAdditionalDocumentModal() {
                const refs = getAdditionalDocRefs();
                if (refs.modalName) refs.modalName.value = "";
                if (refs.modalDescription) refs.modalDescription.value = "";
                if (refs.modalFilesInput) refs.modalFilesInput.value = "";
                if (refs.modalFilesPreview) refs.modalFilesPreview.innerHTML = "";
                if (refs.modalFilesCount) refs.modalFilesCount.textContent = "";
                if (refs.modalError) refs.modalError.textContent = "";
                modalSelectedFiles = [];
            }

            function renderModalFilePreview() {
                const refs = getAdditionalDocRefs();
                if (!refs.modalFilesPreview) return;

                refs.modalFilesPreview.innerHTML = "";
                if (refs.modalFilesCount) {
                    refs.modalFilesCount.textContent = modalSelectedFiles.length > 0
                        ? "Selected files: " + modalSelectedFiles.length
                        : "";
                }

                if (modalSelectedFiles.length < 1) {
                    refs.modalFilesPreview.textContent = "";
                    return;
                }

                modalSelectedFiles.forEach(function (file, index) {
                    const fileName = file.name || "document";
                    const fileType = (file.type || "").toLowerCase();
                    const ext = (fileName.split(".").pop() || "").toLowerCase();
                    const isImage = fileType.startsWith("image/");
                    const isPdf = fileType === "application/pdf" || ext === "pdf";

                    const card = document.createElement("div");
                    card.className = "position-relative border rounded p-2 bg-white";
                    card.style.width = "110px";
                    card.style.minHeight = "100px";

                    if (isImage) {
                        const img = document.createElement("img");
                        img.src = URL.createObjectURL(file);
                        img.alt = fileName;
                        img.style.width = "100%";
                        img.style.height = "60px";
                        img.style.objectFit = "cover";
                        img.className = "rounded";
                        card.appendChild(img);
                    } else if (isPdf) {
                        const iconWrap = document.createElement("div");
                        iconWrap.className = "d-flex align-items-center justify-content-center rounded";
                        iconWrap.style.width = "100%";
                        iconWrap.style.height = "60px";
                        iconWrap.style.background = "#fff5f5";
                        iconWrap.innerHTML = '<span class="material-icons text-danger">picture_as_pdf</span>';
                        card.appendChild(iconWrap);
                    } else {
                        const iconWrap = document.createElement("div");
                        iconWrap.className = "d-flex align-items-center justify-content-center rounded";
                        iconWrap.style.width = "100%";
                        iconWrap.style.height = "60px";
                        iconWrap.style.background = "#f3f4f6";
                        iconWrap.innerHTML = '<span class="material-icons text-secondary">attach_file</span>';
                        card.appendChild(iconWrap);
                    }

                    const name = document.createElement("div");
                    name.className = "small mt-1 text-truncate";
                    name.title = fileName;
                    name.textContent = fileName;
                    card.appendChild(name);

                    const removeBtn = document.createElement("button");
                    removeBtn.type = "button";
                    removeBtn.className = "btn btn-sm btn-danger position-absolute top-0 end-0 translate-middle";
                    removeBtn.style.transform = "translate(30%, -30%)";
                    removeBtn.style.width = "24px";
                    removeBtn.style.height = "24px";
                    removeBtn.style.padding = "0";
                    removeBtn.innerHTML = "&times;";
                    removeBtn.dataset.modalFileRemoveIndex = String(index);
                    card.appendChild(removeBtn);

                    refs.modalFilesPreview.appendChild(card);
                });
            }

            function renderRowFilesPreview(rowEl, files) {
                const preview = rowEl.querySelector("[data-doc-files-preview]");
                if (!preview) return;
                if (!files || files.length < 1) {
                    preview.innerHTML = "";
                    return;
                }
                preview.innerHTML = "";

                files.forEach(function (file) {
                    if (!file) return;

                    const fileName = file.name || "document";
                    const fileType = (file.type || "").toLowerCase();
                    const ext = (fileName.split(".").pop() || "").toLowerCase();
                    const isImage = fileType.startsWith("image/");
                    const isPdf = fileType === "application/pdf" || ext === "pdf";

                    const card = document.createElement("div");
                    card.className = "position-relative border rounded p-2 bg-white";
                    card.style.width = "110px";
                    card.style.minHeight = "100px";

                    if (isImage) {
                        const img = document.createElement("img");
                        img.src = URL.createObjectURL(file);
                        img.alt = fileName;
                        img.style.width = "100%";
                        img.style.height = "60px";
                        img.style.objectFit = "cover";
                        img.className = "rounded";
                        card.appendChild(img);
                    } else if (isPdf) {
                        const iconWrap = document.createElement("div");
                        iconWrap.className = "d-flex align-items-center justify-content-center rounded";
                        iconWrap.style.width = "100%";
                        iconWrap.style.height = "60px";
                        iconWrap.style.background = "#fff5f5";
                        iconWrap.innerHTML = '<span class="material-icons text-danger">picture_as_pdf</span>';
                        card.appendChild(iconWrap);
                    } else {
                        const iconWrap = document.createElement("div");
                        iconWrap.className = "d-flex align-items-center justify-content-center rounded";
                        iconWrap.style.width = "100%";
                        iconWrap.style.height = "60px";
                        iconWrap.style.background = "#f3f4f6";
                        iconWrap.innerHTML = '<span class="material-icons text-secondary">attach_file</span>';
                        card.appendChild(iconWrap);
                    }

                    const name = document.createElement("div");
                    name.className = "small mt-1 text-truncate";
                    name.title = fileName;
                    name.textContent = fileName;
                    card.appendChild(name);

                    preview.appendChild(card);
                });
            }

            function addAdditionalDocumentRow(name, description, files) {
                const refs = getAdditionalDocRefs();
                if (!refs.rowsContainer || !refs.rowTemplate) {
                    throw new Error("Additional document container/template not found");
                }

                const currentIndex = Number(refs.rowsContainer.dataset.nextIndex || 0);
                const html = refs.rowTemplate.innerHTML.split("__INDEX__").join(String(currentIndex));
                refs.rowsContainer.insertAdjacentHTML("beforeend", html);

                const newRow = refs.rowsContainer.lastElementChild;
                if (!newRow) {
                    throw new Error("Unable to append additional document row");
                }

                const rowName = newRow.querySelector("[data-doc-name]");
                const rowDescription = newRow.querySelector("[data-doc-description]");
                const rowNameInput = newRow.querySelector("[data-doc-name-input]");
                const rowDescriptionInput = newRow.querySelector("[data-doc-description-input]");
                const rowFilesInput = newRow.querySelector("[data-doc-row-files]");

                if (rowName) rowName.textContent = name || "";
                if (rowDescription) rowDescription.textContent = description || "";
                if (rowNameInput) rowNameInput.value = name || "";
                if (rowDescriptionInput) rowDescriptionInput.value = description || "";

                if (rowFilesInput && files && files.length > 0) {
                    try {
                        if (window.DataTransfer) {
                            const dt = new DataTransfer();
                            files.forEach(function (file) { dt.items.add(file); });
                            rowFilesInput.files = dt.files;
                        }
                    } catch (error) {
                        console.warn("Unable to assign files to row input:", error);
                    }
                }

                renderRowFilesPreview(newRow, files || []);
                refs.rowsContainer.dataset.nextIndex = String(currentIndex + 1);
                newRow.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }

            function runAdditionalDocumentAdd(e) {
                if (e && e.preventDefault) e.preventDefault();
                const refs = getAdditionalDocRefs();
                const name = (refs.modalName ? refs.modalName.value : "").trim();
                const description = (refs.modalDescription ? refs.modalDescription.value : "").trim();
                const selectedFiles = modalSelectedFiles.slice();

                if (!name) {
                    if (refs.modalError) refs.modalError.textContent = "Please enter file name";
                    return false;
                }
                if (selectedFiles.length < 1) {
                    if (refs.modalError) refs.modalError.textContent = "Please add files";
                    return false;
                }

                if (refs.modalError) refs.modalError.textContent = "";
                addAdditionalDocumentRow(name, description, selectedFiles);
                clearAdditionalDocumentModal();

                if (typeof bootstrap !== "undefined" && refs.modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(refs.modalEl);
                    if (modalInstance) modalInstance.hide();
                }
                return false;
            }

            window.handleAdditionalDocumentAdd = runAdditionalDocumentAdd;

            document.addEventListener("click", function (e) {
                const addBtn = e.target.closest("#save_additional_document_btn");
                if (!addBtn) return;
                runAdditionalDocumentAdd(e);
            });

            document.addEventListener("click", function (e) {
                const selectFilesBtn = e.target.closest("#additional_doc_select_files_btn");
                if (selectFilesBtn) {
                    e.preventDefault();
                    const refs = getAdditionalDocRefs();
                    if (refs.modalFilesInput) refs.modalFilesInput.click();
                    return;
                }

                const removeBtn = e.target.closest("[data-modal-file-remove-index]");
                if (!removeBtn) return;
                e.preventDefault();
                const index = Number(removeBtn.dataset.modalFileRemoveIndex || -1);
                if (index < 0) return;
                modalSelectedFiles.splice(index, 1);
                syncModalFileInput();
                renderModalFilePreview();
            });

            document.addEventListener("change", function (e) {
                if (e.target && e.target.id === "additional_doc_files_input") {
                    const refs = getAdditionalDocRefs();
                    const input = refs.modalFilesInput;
                    const picked = input ? Array.from(input.files || []) : [];
                    // Merge each picker round into the list (browser only gives the current dialog selection).
                    mergePickedFilesIntoModal(picked);
                    // Clear native value so the same paths can be chosen again; real list lives in modalSelectedFiles + DataTransfer.
                    if (input) input.value = "";
                    syncModalFileInput();
                    renderModalFilePreview();
                }
            });

            document.addEventListener("click", function (e) {
                const deleteBtn = e.target.closest(".additional-document-delete-btn");
                if (!deleteBtn) return;
                const row = deleteBtn.closest(".additional-document-row");
                if (row) row.remove();
            });

            const initialRefs = getAdditionalDocRefs();
            if (initialRefs.modalEl) {
                initialRefs.modalEl.addEventListener("hidden.bs.modal", function () {
                    clearAdditionalDocumentModal();
                });
            }

            @if($isEdit)
            function getProviderContactUniqueConfig() {
                const el = document.getElementById("provider-contact-unique-config");
                if (!el) {
                    return null;
                }
                return {
                    url: el.getAttribute("data-check-url") || "",
                    token: el.getAttribute("data-csrf") || "",
                    excludeUserId: el.getAttribute("data-exclude-user-id") || "",
                };
            }

            function clearProviderContactUniqueErrors() {
                document.querySelectorAll(".provider-contact-unique-error").forEach(function (n) {
                    n.remove();
                });
                ["contact_person_phone", "contact_person_email"].forEach(function (name) {
                    const el = document.querySelector('[name="' + name + '"]');
                    if (el) {
                        el.classList.remove("is-invalid");
                    }
                });
                const formU = document.getElementById("create-provider-form");
                if (formU) {
                    const telVis = formU.querySelector("#contact_person_phone");
                    if (telVis) {
                        telVis.classList.remove("is-invalid");
                    }
                }
            }

            function attachProviderContactUniqueError(inputName, message) {
                const formEl = document.getElementById("create-provider-form");
                let inp = formEl ? formEl.querySelector('[name="' + inputName + '"]') : document.querySelector('[name="' + inputName + '"]');
                if (inputName === "contact_person_phone" && formEl) {
                    const tel = formEl.querySelector("#contact_person_phone");
                    if (tel && tel.matches && tel.matches('input[type="tel"]')) {
                        inp = tel;
                    }
                }
                if (!inp || !message) {
                    return;
                }
                const holder = inp.closest(".form-floating") || inp.closest(".form-floting-fix") || inp.parentElement;
                if (!holder) {
                    return;
                }
                holder.querySelectorAll(".provider-contact-unique-error").forEach(function (n) {
                    n.remove();
                });
                inp.classList.add("is-invalid");
                const div = document.createElement("div");
                div.className = "provider-contact-unique-error text-danger small mt-1";
                div.setAttribute("role", "alert");
                div.textContent = message;
                holder.appendChild(div);
            }

            window.checkOwnerContactUniqueSync = function () {
                const cfg = getProviderContactUniqueConfig();
                if (!cfg || !cfg.url || typeof jQuery === "undefined") {
                    return true;
                }
                const formSync = document.getElementById("create-provider-form");
                if (formSync) {
                    formSync.querySelectorAll('input[type="tel"][data-intl-initialized]').forEach(function (tel) {
                        try {
                            tel.dispatchEvent(new Event("input", { bubbles: false }));
                        } catch (e) {}
                    });
                }
                const phoneEl = document.querySelector('[name="contact_person_phone"]');
                const emailEl = document.querySelector('[name="contact_person_email"]');
                let ok = true;
                clearProviderContactUniqueErrors();
                jQuery.ajax({
                    url: cfg.url,
                    method: "POST",
                    async: false,
                    data: {
                        _token: cfg.token,
                        contact_person_phone: phoneEl ? phoneEl.value : "",
                        contact_person_email: emailEl ? emailEl.value : "",
                        exclude_user_id: cfg.excludeUserId,
                    },
                    success: function (res) {
                        if (res && res.valid) {
                            return;
                        }
                        ok = false;
                        if (res && res.field_errors) {
                            Object.keys(res.field_errors).forEach(function (field) {
                                attachProviderContactUniqueError(field, res.field_errors[field]);
                            });
                        }
                    },
                    error: function (xhr) {
                        ok = false;
                        if (xhr.responseJSON && xhr.responseJSON.field_errors) {
                            Object.keys(xhr.responseJSON.field_errors).forEach(function (field) {
                                attachProviderContactUniqueError(field, xhr.responseJSON.field_errors[field]);
                            });
                            return;
                        }
                        var msg = "Unable to verify contact details.";
                        if (xhr.responseJSON && xhr.responseJSON.messages && xhr.responseJSON.messages.length) {
                            msg = xhr.responseJSON.messages[0];
                        }
                        if (phoneEl) {
                            attachProviderContactUniqueError("contact_person_phone", msg);
                        }
                        if (emailEl) {
                            attachProviderContactUniqueError("contact_person_email", msg);
                        }
                    },
                });
                return ok;
            };

            (function initOwnerContactUniqueLiveCheck() {
                const cfg = getProviderContactUniqueConfig();
                if (!cfg || !cfg.url || typeof jQuery === "undefined") {
                    return;
                }
                let debounceTimer = null;
                let liveCheckSeq = 0;
                let liveCheckXhr = null;

                function run() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        const formEl = document.getElementById("create-provider-form");
                        if (formEl) {
                            formEl.querySelectorAll('input[type="tel"][data-intl-initialized]').forEach(function (tel) {
                                try {
                                    tel.dispatchEvent(new Event("input", { bubbles: false }));
                                } catch (e) {}
                            });
                        }
                        const pv = ((document.querySelector('[name="contact_person_phone"]') || {}).value || "").trim();
                        const ev = ((document.querySelector('[name="contact_person_email"]') || {}).value || "").trim();
                        if (!pv && !ev) {
                            if (liveCheckXhr) {
                                liveCheckXhr.abort();
                                liveCheckXhr = null;
                            }
                            clearProviderContactUniqueErrors();
                            return;
                        }
                        const seq = ++liveCheckSeq;
                        if (liveCheckXhr) {
                            liveCheckXhr.abort();
                        }
                        liveCheckXhr = jQuery.ajax({
                            url: cfg.url,
                            method: "POST",
                            data: {
                                _token: cfg.token,
                                contact_person_phone: (document.querySelector('[name="contact_person_phone"]') || {}).value || "",
                                contact_person_email: (document.querySelector('[name="contact_person_email"]') || {}).value || "",
                                exclude_user_id: cfg.excludeUserId,
                            },
                            success: function (res) {
                                if (seq !== liveCheckSeq) {
                                    return;
                                }
                                clearProviderContactUniqueErrors();
                                if (res && res.valid) {
                                    if (typeof window.refreshProviderCreateStep0ValidationSummary === "function") {
                                        setTimeout(function () {
                                            window.refreshProviderCreateStep0ValidationSummary();
                                        }, 0);
                                    }
                                    return;
                                }
                                if (res && res.field_errors) {
                                    Object.keys(res.field_errors).forEach(function (field) {
                                        var text = res.field_errors[field];
                                        if (text) {
                                            attachProviderContactUniqueError(field, text);
                                        }
                                    });
                                }
                                if (typeof window.refreshProviderCreateStep0ValidationSummary === "function") {
                                    setTimeout(function () {
                                        window.refreshProviderCreateStep0ValidationSummary();
                                    }, 0);
                                }
                            },
                            error: function (xhr, textStatus) {
                                if (textStatus === "abort") {
                                    return;
                                }
                                if (seq !== liveCheckSeq) {
                                    return;
                                }
                                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.field_errors) {
                                    clearProviderContactUniqueErrors();
                                    Object.keys(xhr.responseJSON.field_errors).forEach(function (field) {
                                        var text = xhr.responseJSON.field_errors[field];
                                        if (text) {
                                            attachProviderContactUniqueError(field, text);
                                        }
                                    });
                                    if (typeof window.refreshProviderCreateStep0ValidationSummary === "function") {
                                        setTimeout(function () {
                                            window.refreshProviderCreateStep0ValidationSummary();
                                        }, 0);
                                    }
                                    return;
                                }
                            },
                        });
                    }, 450);
                }

                function isContactPersonIntlTel(el) {
                    return el
                        && el.matches
                        && el.matches('input[type="tel"][data-intl-initialized]')
                        && el.parentElement
                        && el.parentElement.querySelector('input[type="hidden"][name="contact_person_phone"]');
                }

                document.addEventListener("input", function (e) {
                    const el = e.target;
                    if (!el) {
                        return;
                    }
                    if (el.name === "contact_person_email" || el.name === "contact_person_phone") {
                        clearProviderContactUniqueErrors();
                        run();
                        return;
                    }
                    if (isContactPersonIntlTel(el)) {
                        clearProviderContactUniqueErrors();
                        run();
                    }
                });
                document.addEventListener("change", function (e) {
                    const el = e.target;
                    if (!el) {
                        return;
                    }
                    if (el.name === "contact_person_email" || el.name === "contact_person_phone") {
                        clearProviderContactUniqueErrors();
                        run();
                        return;
                    }
                    if (isContactPersonIntlTel(el)) {
                        clearProviderContactUniqueErrors();
                        run();
                    }
                });
                document.addEventListener("countrychange", function (e) {
                    const el = e.target;
                    if (isContactPersonIntlTel(el)) {
                        clearProviderContactUniqueErrors();
                        run();
                    }
                });
                (function attachCountryChangeOnForm() {
                    const f = document.getElementById("create-provider-form");
                    if (!f) {
                        return;
                    }
                    f.addEventListener(
                        "countrychange",
                        function (e) {
                            const el = e.target;
                            if (isContactPersonIntlTel(el)) {
                                clearProviderContactUniqueErrors();
                                run();
                            }
                        },
                        true
                    );
                })();
            })();

            @else
            window.checkOwnerContactUniqueSync = function () {
                return true;
            };
            @endif

            window.getAdminProviderFormZoneIds = function () {
                var form = document.getElementById("create-provider-form");
                if (!form) {
                    return [];
                }
                var boxes = form.querySelectorAll("input.provider-zone-leaf-cb:checked");
                if (boxes.length) {
                    return Array.from(boxes).map(function (c) { return c.value; }).filter(Boolean);
                }
                var sel = form.querySelector('select[name="zone_ids[]"]');
                if (sel && sel.options) {
                    return Array.from(sel.selectedOptions || []).map(function (o) { return o.value; }).filter(Boolean);
                }
                return [];
            };

            @if($isEdit)
            var providerFormEl = document.getElementById("create-provider-form");
            if (providerFormEl) {
                providerFormEl.addEventListener("submit", function (e) {
                    var leaves = providerFormEl.querySelectorAll("input.provider-zone-leaf-cb");
                    if (!leaves.length) {
                        return;
                    }
                    var checked = providerFormEl.querySelectorAll("input.provider-zone-leaf-cb:checked");
                    if (!checked.length) {
                        e.preventDefault();
                        var msg = "{{ addslashes(translate('Select_Zone')) }}";
                        if (typeof toastr !== "undefined") {
                            toastr.error(msg);
                        }
                        var tree = providerFormEl.querySelector(".provider-zone-tree");
                        if (tree && tree.scrollIntoView) {
                            tree.scrollIntoView({ behavior: "smooth", block: "nearest" });
                        }
                    }
                });
            }
            @endif
        })();
    </script>
@endpush

