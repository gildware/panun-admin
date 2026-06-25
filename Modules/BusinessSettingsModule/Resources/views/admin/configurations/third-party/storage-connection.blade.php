@if($webPage == 'storage_connection')
    <div class="tab-content">
        @php
            $credentialsJson = empty($data['s3_storage_credentials']) ? '{}' : $data['s3_storage_credentials'];
            $credentials = json_decode($credentialsJson, true);
            $isR2Configured = is_array($credentials)
                && ! empty($credentials['key'])
                && ! empty($credentials['secret'])
                && ! empty($credentials['bucket']);

            $s3Credentials = business_config('s3_storage_credentials', 'storage_settings');
            $storagePathPrefix = business_config('storage_path_prefix', 'storage_settings');

            if ($s3Credentials !== null && isset($s3Credentials->live_values)) {
                $liveValues = json_decode($s3Credentials->live_values, true) ?: [];
            } else {
                $liveValues = [];
            }

            $accountId = $liveValues['account_id'] ?? '';
            if ($accountId === '' && ! empty($liveValues['endpoint'])) {
                if (preg_match('#https?://([a-f0-9]+)\.r2\.cloudflarestorage\.com#i', $liveValues['endpoint'], $m)) {
                    $accountId = $m[1];
                }
            }

            $configuredPrefix = $storagePathPrefix?->live_values ?? env('STORAGE_PATH_PREFIX', '');
            if ($configuredPrefix === null || $configuredPrefix === '') {
                $configuredPrefix = match (strtolower((string) env('APP_ENV', 'production'))) {
                    'local' => 'local',
                    'production', 'live' => 'prod',
                    default => 'dev',
                };
            }
        @endphp
        <div class="tab-pane fade {{$webPage == 'storage_connection' ? 'show active' : ''}}" id="storage_connection">
            <div class="pick-map mb-3 p-12 rounded d-flex flex-md-nowrap flex-wrap align-items-center gap-1 bg-primary bg-opacity-10">
                <img src="{{ asset('assets/admin-module/img/icons/focus_mode.svg') }}" alt="focus mode icon">
                <p class="fz-12">{{ translate('You can manage all your storage files from') }}
                    <a @can('gallery_view') href="{{ route('admin.business-settings.get-gallery-setup') }}" @endcan target="_blank" class="text-primary fw-semibold text-decoration-underline">{{ translate('Gallery') }}</a>
                </p>
            </div>

            <div class="card mb-20">
                <div class="card-body p-20">
                    <div class="row g-lg-4 g-4 align-items-center">
                        <div class="col-lg-3">
                            <h3 class="mb-2">{{ translate('Storage') }}</h3>
                            <p class="fz-12 mb-xl-3 mb-xxl-4 mb-3">{{ translate('Store uploads on this server or on Cloudflare R2.') }}</p>
                            @if(! $isR2Configured)
                                <div class="bg-warning bg-opacity-10 fs-12 p-12 rounded">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/admin-module/img/icons/alert_info.svg') }}" alt="alert info icon">
                                        <p class="fz-12 fw-normal mb-0">{{ translate('Save your R2 credentials below before switching to Cloudflare R2.') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-lg-9">
                            <div class="bg-light rounded-2 p-20">
                                <label class="text-dark mb-3">{{ translate('Where should new uploads go?') }}</label>
                                <div class="bg-white rounded-2 p-16">
                                    <div class="row g-xl-4 g-3">
                                        <div class="col-md-6">
                                            <div class="custom-radio">
                                                <input type="radio"
                                                       data-name="storage_connection_type"
                                                       id="radio-option-1"
                                                       value="local"
                                                       @checked($data['storage_connection_type'] == 'local' || empty($data['storage_connection_type']))
                                                       class="update-status-modal"
                                                       data-url="{{ route('admin.configuration.change-storage-connection-type') }}"
                                                       data-on-title="{{ translate('Switch to local storage?') }}"
                                                       data-off-title="{{ translate('Switch to local storage?') }}"
                                                       data-on-description="{{ translate('New uploads will be saved on this server.') }}"
                                                       data-off-description="{{ translate('New uploads will be saved on this server.') }}"
                                                       data-on-image="{{ asset('assets/admin-module/img/icons/swap.svg') }}"
                                                       data-off-image="{{ asset('assets/admin-module/img/icons/swap.svg') }}"
                                                >
                                                <label for="radio-option-1">
                                                    <h5 class="mb-1">{{ translate('Local server') }}</h5>
                                                    <p class="fz-12 max-w-250 mb-0">{{ translate('Files stay in storage/app/public on this machine.') }}</p>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6" @if(! $isR2Configured)
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="{{ translate('Save R2 credentials below first.') }}"
                                        @endif>
                                            <div class="custom-radio {{ ! $isR2Configured ? 'disabled' : '' }}">
                                                <input type="radio"
                                                       data-name="storage_connection_type"
                                                       id="radio-option-2"
                                                       value="s3"
                                                       @checked($data['storage_connection_type'] == 's3')
                                                       class="update-status-modal"
                                                       data-url="{{ route('admin.configuration.change-storage-connection-type') }}"
                                                       data-on-title="{{ translate('Switch to Cloudflare R2?') }}"
                                                       data-off-title="{{ translate('Switch to Cloudflare R2?') }}"
                                                       data-on-description="{{ translate('New uploads will be saved in your R2 bucket.') }}"
                                                       data-off-description="{{ translate('New uploads will be saved in your R2 bucket.') }}"
                                                       data-on-image="{{ asset('assets/admin-module/img/icons/swap.svg') }}"
                                                       data-off-image="{{ asset('assets/admin-module/img/icons/swap.svg') }}"
                                                >
                                                <label for="radio-option-2">
                                                    <h5 class="mb-1">{{ translate('Cloudflare R2') }}</h5>
                                                    <p class="fz-12 max-w-250 mb-0">{{ translate('Files go to your R2 bucket (recommended for dev and production).') }}</p>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.configuration.update-storage-connection') }}" id="update-storage-form" method="POST">
                @csrf
                @method('PUT')

                <div class="card mt-3">
                    <div class="card-body p-20">
                        <div class="mb-20">
                            <h4 class="mb-1">{{ translate('Cloudflare R2') }}</h4>
                            <p class="fs-12 mb-0">
                                {{ translate('Copy each value from your Cloudflare dashboard: R2 → your bucket → Settings, and R2 → Manage R2 API tokens.') }}
                                <a href="https://developers.cloudflare.com/r2/api/s3/api/" target="_blank" class="c1 text-decoration-underline">{{ translate('R2 S3 API docs') }}</a>
                            </p>
                        </div>

                        <div class="rounded p-20 body-bg">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="w-400">
                                    <div class="min-w180 mb-1"><strong>{{ translate('Bucket name') }}</strong></div>
                                    <p class="fz-12 mb-0">{{ translate('R2 → Buckets → your bucket name') }}</p>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="text" name="bucket" class="form-control" value="{{ $liveValues['bucket'] ?? '' }}" placeholder="panun-kaergar-media" required>
                                </div>
                            </div>
                        </div>

                        <div class="rounded p-20 body-bg mt-3">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="w-400">
                                    <div class="min-w180 mb-1"><strong>{{ translate('Access Key ID') }}</strong></div>
                                    <p class="fz-12 mb-0">{{ translate('From your R2 API token (Object Read & Write)') }}</p>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="text" name="key" class="form-control" value="{{ $liveValues['key'] ?? '' }}" placeholder="{{ translate('Access Key ID') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="rounded p-20 body-bg mt-3">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="w-400">
                                    <div class="min-w180 mb-1"><strong>{{ translate('Secret Access Key') }}</strong></div>
                                    <p class="fz-12 mb-0">{{ translate('Shown once when you create the R2 API token') }}</p>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="password" name="secret" class="form-control" value="{{ $liveValues['secret'] ?? '' }}" placeholder="{{ translate('Secret Access Key') }}" required autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="rounded p-20 body-bg mt-3">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="w-400">
                                    <div class="min-w180 mb-1"><strong>{{ translate('Account ID') }}</strong></div>
                                    <p class="fz-12 mb-0">{{ translate('R2 overview page, or inside the S3 API endpoint URL') }}</p>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="text" name="account_id" class="form-control" value="{{ $accountId }}" placeholder="45f79b5569b29171364ab59d80ab152a" required>
                                </div>
                            </div>
                        </div>

                        <div class="rounded p-20 body-bg mt-3">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="w-400">
                                    <div class="min-w180 mb-1"><strong>{{ translate('Public bucket URL') }}</strong></div>
                                    <p class="fz-12 mb-0">{{ translate('R2 → bucket → Settings → Public access → r2.dev URL (no trailing slash)') }}</p>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="url" name="url" class="form-control" value="{{ $liveValues['url'] ?? '' }}" placeholder="https://pub-xxxx.r2.dev" required>
                                </div>
                            </div>
                        </div>

                        <div class="rounded p-20 body-bg mt-3">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="w-400">
                                    <div class="min-w180 mb-1"><strong>{{ translate('Environment folder') }}</strong></div>
                                    <p class="fz-12 mb-0">{{ translate('One shared bucket: use local, dev, or prod so environments do not overwrite each other') }}</p>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="text" name="storage_path_prefix" class="form-control" value="{{ $configuredPrefix }}" placeholder="dev">
                                    <p class="fz-12 text-muted mt-2 mb-0">{{ translate('Files are stored as') }} <code>{folder}/category/...</code></p>
                                </div>
                            </div>
                        </div>

                        @can('configuration_update')
                            <div class="d-flex justify-content-end trans3 mt-4">
                                <div class="d-flex justify-content-sm-end justify-content-center gap-2 gap-sm-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                                    <div class="d-flex justify-content-end gap-3">
                                        <button type="reset" class="btn btn--secondary rounded">{{ translate('Reset') }}</button>
                                        <button type="submit" class="btn d-flex align-items-center gap-2 btn--primary demo_check rounded" data-bs-toggle="modal" data-bs-target="#confirmation">
                                            <img src="{{ asset('assets/admin-module/img/icons/save-icon.svg') }}" alt="save icon">
                                            {{ translate('Save') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
