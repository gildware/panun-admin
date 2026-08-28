<div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ !empty($isRepeatCreate) ? translate('Repeat_booking') : translate('One_time_booking') }}</h2>
        @if(Route::is('admin.booking.create') || Route::is('admin.booking.create-repeat'))
            <a href="{{ $bookingGoBackUrl ?? route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']) }}"
               class="btn btn-secondary">
                {{ translate('Go_back') }}
            </a>
        @endif
    </div>

    @include('bookingmodule::admin.booking.create', ['__embeddedFormOnly' => true])
</div>

