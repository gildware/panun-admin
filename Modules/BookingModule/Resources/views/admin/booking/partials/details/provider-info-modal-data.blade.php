@php
    $providerRows = $providers ?? [];
    $eligibleCount = is_countable($providerRows) ? count($providerRows) : 0;
@endphp
<div class="modal-header border-0 pb-1 pb-lg-1 p-lg-5">
    <div class="d-flex flex-column gap-1">
        <h5>{{ translate('Available Providers') }}</h5>
        <div class="fs-12"><span class="provider-count">{{ $eligibleCount }}</span> {{ translate('Providers are available right now') }}</div>
    </div>
    <button type="button" class="btn-close provider-cross" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-lg-5 pt-3">
    <div class="d-flex gap-2">
        <form action="#" class="search-form flex-grow-1" autocomplete="off">
            <div class="input-group position-relative search-form__input_group rounded-3">
                <span class="search-form__icon">
                    <span class="material-icons">search</span>
                </span>
                <input type="search" class="theme-input-style search-form__input search-form-input"
                       id="search-form__input"
                       placeholder="{{ translate('Search Here') }}" value="{{ $search ?? '' }}">
            </div>
        </form>
        @if($eligibleCount > 0)
            <div class="dropdown">
                <button type="button" class="btn px-3 py-2 border text-capitalize rounded-3 title-color apply-filter-button"
                        data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                    <span class="material-icons">filter_list</span> Sort By
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-3"
                     data-popper-placement="bottom-end">
                    <form id="sort-form">
                        <div class="d-flex flex-column gap-3 title-color">
                            <label class="d-flex gap-2 gap-sm-3 sort-by-class">
                                <input type="radio" name="sort" value="default" class="position-static" {{ ($sort_by ?? 'default') === 'default' ? 'checked' : '' }}>
                                {{ translate('Default') }}
                            </label>
                            <label class="d-flex gap-2 gap-sm-3 sort-by-class">
                                <input type="radio" name="sort" class="position-static"
                                       value="top-rated" {{ ($sort_by ?? '') === 'top-rated' ? 'checked' : '' }}>
                                {{ translate('Top Rated') }}
                            </label>
                            <label class="d-flex gap-2 gap-sm-3 sort-by-class">
                                <input type="radio" name="sort" class="position-static"
                                       value="bookings-completed" {{ ($sort_by ?? '') === 'bookings-completed' ? 'checked' : '' }}>
                                {{ translate('Bookings Completed') }}
                            </label>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="d-flex flex-column">
        @forelse($providerRows as $provider)
            <div class="d-flex gap-2 justify-content-between align-items-center mt-4 pb-3 flex-wrap">
                <div class="media gap-2">
                    <img width="60" class="rounded"
                         src="{{ $provider->logo_full_path }}"
                         alt="{{ translate('provider-logo') }}">
                    <div class="media-body">
                        <h5 class="mb-2">{{ $provider->company_name }}</h5>
                        <div class="mb-1 fs-12"><a href="tel:{{ $provider->contact_person_phone }}">{{ $provider->contact_person_phone }}</a></div>
                        <div class="provider-devider">
                            <ol class="breadcrumb fs-12 mb-0">
                                <li class="breadcrumb-item">
                                    <span class="common-list_rating d-flex gap-1 text-secondary">
                                        <span class="material-icons">star</span>
                                        {{ $provider->avg_rating }} ({{ $provider->reviews_count }})
                                    </span>
                                </li>
                                <li class="breadcrumb-item active">{{ translate('Bookings') }} - {{ $provider->bookings_count }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div>
                    @if($booking->provider_id && (string) $booking->provider_id === (string) $provider->id)
                        <div class="text-success">{{ translate('Currently Assigned') }}</div>
                    @else
                        <button type="button" class="btn btn-primary w-100 max-w320 reassign-provider"
                                data-provider-reassign="{{ $provider->id }}">
                            {{ $booking->provider_id ? translate('Re Assign') : translate('assign provider') }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted mt-4 mb-0">{{ translate('no data available') }}</p>
        @endforelse
    </div>
</div>
