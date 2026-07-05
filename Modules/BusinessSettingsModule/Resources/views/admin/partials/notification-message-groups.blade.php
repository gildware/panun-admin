@foreach($groupedNotifications as $categoryKey => $categoryNotifications)
    <div class="notification-category-group {{ !$loop->last ? 'mb-20' : '' }}">
        <div class="d-flex align-items-center gap-3 p-20 py-3 table-toggle-btn cursor-pointer transition active bg-white rounded-top border cus-shadow">
            <span class="rounded-full bg-light w-28 h-28 fz-14 d-inline-flex align-items-center justify-content-center flex-shrink-0">
                <i class="material-symbols-outlined">keyboard_arrow_down</i>
            </span>
            <div class="flex-grow-1">
                <h5 class="fz-16 mb-1 fw-semibold text-dark">
                    {{ translate($categoryLabels[$categoryKey] ?? str_replace('_', ' ', $categoryKey)) }}
                </h5>
                <p class="fz-12 mb-0 text-muted">
                    {{ count($categoryNotifications) }} {{ translate('notification messages') }}
                </p>
            </div>
        </div>

        <div class="table-custom-wrap bg-white border border-top-0 rounded-bottom cus-shadow p-20">
            <div class="row">
                @foreach($categoryNotifications as $notification)
                    <div class="col-md-6">
                        <form method="POST"
                              action="{{ route('admin.configuration.set-message-setting', ['type' => $queryParams]) }}"
                              class="h-100">
                            @csrf
                            @method('PUT')
                            @include('businesssettingsmodule::admin.partials.notification-message-fields', [
                                'notification' => $notification,
                                'settingsType' => $settingsType,
                                'dataValues' => $dataValues,
                                'language' => $language,
                            ])
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
