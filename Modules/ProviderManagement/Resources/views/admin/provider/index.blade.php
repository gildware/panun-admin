@extends('adminmodule::layouts.master')

@section('title',translate('provider_list'))

@push('css_or_js')
    <style>
        .provider-list-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .provider-list-header .page-title {
            margin: 0;
            flex: 0 0 auto;
        }
        .provider-list-header > .d-flex {
            flex: 0 0 auto;
        }
        .provider-list-stats {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            flex: 1 1 auto;
            justify-content: flex-end;
            min-width: 0;
        }
        .provider-list-stats .provider-stat-chip {
            display: inline-flex !important;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.65rem !important;
            min-height: 1.85rem;
            line-height: 1.2;
            border-radius: 0.375rem;
            color: #fff !important;
            flex: 0 1 auto;
            max-width: 100%;
            overflow: visible;
            position: relative;
            z-index: 1;
        }
        .provider-list-stats .provider-stat-chip::after {
            display: none !important;
            content: none !important;
        }
        .provider-list-stats .provider-stat-chip h3,
        .provider-list-stats .provider-stat-chip h2 {
            margin: 0 !important;
            color: #fff !important;
            line-height: 1.2;
        }
        .provider-list-stats .provider-stat-chip h3 {
            font-size: 0.72rem;
            font-weight: 500;
            opacity: 0.95;
            white-space: nowrap;
        }
        .provider-list-stats .provider-stat-chip h2 {
            font-size: 0.9rem;
            font-weight: 700;
            margin-inline-start: 0.15rem !important;
        }
        .provider-list-stats .provider-stat-chip .absolute-img {
            display: none !important;
        }
        .provider-list-stats .statistics-card__total_provider {
            background: linear-gradient(180deg, #0177CD 0%, #0166b0 100%) !important;
        }
        .provider-list-stats .statistics-card__ongoing {
            background: linear-gradient(180deg, #FFA800 0%, #e69500 100%) !important;
        }
        .provider-list-stats .statistics-card__newly_joined {
            background: linear-gradient(180deg, #3BB104 0%, #36A900 100%) !important;
        }
        .provider-list-stats .statistics-card__not_served {
            background: linear-gradient(180deg, #FF6D6D 0%, #e85a5a 100%) !important;
        }
        .provider-list-filters .form-select,
        .provider-list-filters .form-control,
        .provider-list-filters .theme-input-style {
            min-height: 2.25rem;
            height: 2.25rem;
            font-size: 0.8125rem;
            padding-top: 0.3rem;
            padding-bottom: 0.3rem;
        }
        .provider-list-filters .form-label {
            font-size: 0.7rem;
            margin-bottom: 0.15rem;
            opacity: 0.75;
            white-space: nowrap;
        }
        .provider-list-filters-bar {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-end;
            gap: 0.5rem;
            width: 100%;
            overflow-x: auto;
            padding-bottom: 0.15rem;
        }
        .provider-list-filters-bar #provider-list-filter-form {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-end;
            gap: 0.5rem;
            flex: 1 1 auto;
            min-width: max-content;
        }
        .provider-list-filters-bar .provider-filter-search-wrap {
            flex: 0 0 20rem;
            width: 20rem;
            min-width: 20rem;
        }
        .provider-list-filters-bar .provider-filter-search-wrap .border {
            width: 100%;
        }
        .provider-list-filters-bar .provider-filter-field {
            flex: 0 0 auto;
        }
        .provider-list-filters-bar .provider-filter-actions {
            flex: 0 0 auto;
            margin-inline-start: auto;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="provider-list-header">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h2 class="page-title">{{translate('Provider_List')}}</h2>
                    @can('provider_add')
                        <a href="{{ route('admin.provider.create') }}" class="btn btn--primary">
                            <span class="material-icons">add</span>
                            {{ translate('Add_New_Provider') }}
                        </a>
                    @endcan
                </div>
                <div class="provider-list-stats">
                    <div class="provider-stat-chip statistics-card statistics-card__total_provider">
                        <h3>{{translate('Total_Providers')}}</h3>
                        <h2>{{$topCards['total_providers']}}</h2>
                    </div>
                    <div class="provider-stat-chip statistics-card statistics-card__ongoing">
                        <h3>{{translate('Onboarding_Request')}}</h3>
                        <h2>{{$topCards['total_onboarding_requests']}}</h2>
                    </div>
                    <div class="provider-stat-chip statistics-card statistics-card__newly_joined">
                        <h3>{{translate('Active_Providers')}}</h3>
                        <h2>{{$topCards['total_active_providers']}}</h2>
                    </div>
                    <div class="provider-stat-chip statistics-card statistics-card__not_served">
                        <h3>{{translate('Inactive_Providers')}}</h3>
                        <h2>{{$topCards['total_inactive_providers']}}</h2>
                    </div>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-tab-pane">
                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top mb-3 provider-list-filters">
                                <div class="provider-list-filters-bar">
                                    <form id="provider-list-filter-form" action="{{ url()->current() }}" method="GET">
                                        <div class="provider-filter-search-wrap">
                                            <label class="form-label d-block" for="provider-filter-search">{{ translate('Search') }}</label>
                                            <div class="d-flex align-items-center gap-0 border rounded">
                                                <input id="provider-filter-search"
                                                       type="search"
                                                       class="theme-input-style border-0 rounded block-size-36 w-100"
                                                       name="search"
                                                       value="{{$search}}"
                                                       placeholder="{{translate('search_here')}}"
                                                       autocomplete="off"
                                                       autofocus>
                                                <span class="bg-light border-0 px-2 block-size-36 rounded-end d-flex align-items-center justify-content-center flex-shrink-0">
                                                    <span class="material-symbols-outlined fz-20 opacity-75">search</span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="provider-filter-field">
                                            <label class="form-label d-block" for="provider-filter-status">{{ translate('Status') }}</label>
                                            <select id="provider-filter-status" name="status" class="form-select" style="width: 7.5rem;" onchange="this.form.submit()">
                                                <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                                                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                                            </select>
                                        </div>

                                        <div class="provider-filter-field">
                                            <label class="form-label d-block" for="provider-filter-performance">{{ translate('Performance_Status') }}</label>
                                            <select id="provider-filter-performance" name="performance_filter" class="form-select" style="width: 8.5rem;" onchange="this.form.submit()">
                                                <option value="all" {{ ($performanceFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                                <option value="warning" {{ ($performanceFilter ?? '') === 'warning' ? 'selected' : '' }}>{{ translate('Warning') }}</option>
                                                <option value="blacklisted" {{ ($performanceFilter ?? '') === 'blacklisted' ? 'selected' : '' }}>{{ translate('Blacklisted') }}</option>
                                            </select>
                                        </div>

                                        <div class="provider-filter-field">
                                            <label class="form-label d-block" for="provider-filter-category">{{ translate('Category') }}</label>
                                            <select id="provider-filter-category" name="category_id" class="form-select" style="width: 9rem;" onchange="this.form.submit()">
                                                <option value="">{{ translate('All') }}</option>
                                                <option value="none" {{ ($categoryId ?? '') === 'none' ? 'selected' : '' }}>{{ translate('No_Category') }}</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ ($categoryId ?? '') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="provider-filter-field">
                                            <label class="form-label d-block" for="provider-filter-zone">{{ translate('Zone') }}</label>
                                            <select id="provider-filter-zone" name="zone_id" class="form-select" style="width: 9rem;" onchange="this.form.submit()">
                                                <option value="">{{ translate('All') }}</option>
                                                @foreach($zones as $zone)
                                                    <option value="{{ $zone->id }}" {{ ($zoneId ?? '') == $zone->id ? 'selected' : '' }}>
                                                        {{ $zone->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="provider-filter-field">
                                            <label class="form-label d-block" for="provider-filter-sort">{{ translate('Sort') }}</label>
                                            <select id="provider-filter-sort" name="sort" class="form-select" style="width: 9.5rem;" onchange="this.form.submit()">
                                                <option value="latest" {{ ($sort ?? 'latest') === 'latest' ? 'selected' : '' }}>{{ translate('Newest') }}</option>
                                                <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>{{ translate('Oldest') }}</option>
                                                <option value="name_asc" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>{{ translate('Name_A_Z') }}</option>
                                                <option value="name_desc" {{ ($sort ?? '') === 'name_desc' ? 'selected' : '' }}>{{ translate('Name_Z_A') }}</option>
                                                <option value="rating_desc" {{ ($sort ?? '') === 'rating_desc' ? 'selected' : '' }}>{{ translate('Highest_Rating') }}</option>
                                                <option value="bookings_desc" {{ ($sort ?? '') === 'bookings_desc' ? 'selected' : '' }}>{{ translate('Most_Bookings') }}</option>
                                            </select>
                                        </div>
                                    </form>
                                    @can('provider_export')
                                        <div class="dropdown provider-filter-actions">
                                            <button type="button"
                                                    class="btn rounded btn--secondary text-capitalize dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                <span
                                                    class="material-icons">file_download</span> {{translate('download')}}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                <a class="dropdown-item"
                                                   href="{{route('admin.provider.download')}}?search={{$search}}">
                                                    {{translate('excel')}}
                                                </a>
                                            </ul>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
                                    <thead class="align-middle">
                                    <tr>
                                        <th>{{translate('Sl')}}</th>
                                        <th>{{translate('Provider')}}</th>
                                        <th class="min-w-120">{{translate('Contact_Info')}}</th>
                                        <th class="min-w-160">{{translate('Categories')}}</th>
                                        <th class="min-w-120">{{translate('Bookings')}}</th>
                                        <th class="min-w-120">{{translate('Score')}}</th>
                                        <th class="min-w-120">{{translate('Performance_Status')}}</th>
                                        @can('provider_manage_status')
                                            <th>{{translate('Service Availability')}}</th>
                                            <th>{{translate('Status')}}</th>
                                        @endcan
                                        @can('provider_update')
                                            <th>{{translate('Action')}}</th>
                                        @endcan
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($providers as $key => $provider)
                                        <tr>
                                            <td>{{$key+$providers->firstItem()}}</td>
                                            <td>
                                                <div class="media align-items-center gap-3 min-w-200">
                                                    <div class="avatar avatar-lg">
                                                        <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}">
                                                            <img class="avatar-img radius-5"
                                                                 src="{{ $provider->list_avatar_full_path }}"
                                                                 alt="{{ translate('provider-logo') }}"
                                                                 onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                                        </a>
                                                    </div>
                                                    <div class="media-body">
                                                        <h5 class="mb-1">
                                                            <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}&provider={{ $provider->id}}">
                                                                {{$provider->company_name}}
                                                                @php($restrictionLabel = \Modules\ProviderManagement\Services\ProviderManualPerformanceEnforcement::primaryRestrictionLabel($provider))
                                                                @if($restrictionLabel)
                                                                    <span class="text-danger fz-12">({{ $restrictionLabel }})</span>
                                                                @endif
                                                            </a>
                                                        </h5>
                                                        <span
                                                            class="common-list_rating d-flex align-items-center gap-1">
                                                            <span class="material-icons">star</span>
                                                            {{$provider->avg_rating}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h5 class="mb-1">{{Str::limit($provider->contact_person_name, 30)}}</h5>
                                                    <a class="fz-12"
                                                       href="mobileto:{{$provider->contact_person_phone}}">{{$provider->contact_person_phone}}</a>
                                                    <a class="fz-12"
                                                       href="mobileto:{{$provider->contact_person_email}}">{{$provider->contact_person_email}}</a>
                                                </div>
                                            </td>
                                            @php(
                                                $subscribedCategories = $provider->subscribed_services
                                                    ? $provider->subscribed_services->pluck('category.name')->filter()->unique()->values()
                                                    : collect()
                                            )
                                            <td>
                                                <p class="mb-0 fz-12" title="{{ $subscribedCategories->implode(', ') }}">
                                                    {{ $subscribedCategories->isNotEmpty() ? $subscribedCategories->implode(', ') : '—' }}
                                                </p>
                                            </td>
                                            <td>{{$provider->bookings_count}}</td>
                                            @php($providerListPerformance = \Modules\ProviderManagement\Services\ProviderManualPerformanceEnforcement::providerListPerformance($provider))
                                            <td>{{ (int)($provider->performance_score ?? 0) }}</td>
                                            <td>
                                                <span class="badge {{ $providerListPerformance['badge'] }}">{{ $providerListPerformance['label'] }}</span>
                                                @if(!empty($providerListPerformance['items']))
                                                    <div class="fz-12 text-danger mt-1">
                                                        {{ $providerListPerformance['items'][0]['label'] }}
                                                        @if(!empty($providerListPerformance['items'][0]['until']))
                                                            · {{ translate('Until') }} {{ $providerListPerformance['items'][0]['until'] }}
                                                        @endif
                                                    </div>
                                                    <a class="fz-12 text-primary text-decoration-underline"
                                                       href="{{ route('admin.provider.details', [$provider->id, 'web_page' => 'overview']) }}">
                                                        {{ translate('View details') }}
                                                    </a>
                                                @endif
                                            </td>
                                            @can('provider_manage_status')
                                                <td>
                                                    <label class="switcher" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.service_availability', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider->service_availability?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>


                                                <td>
                                                    <label class="switcher" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.status_update', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider?->owner?->is_active?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>
                                            @endcan
                                            @can('provider_update')
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{route('admin.provider.edit',[$provider->id])}}"
                                                           class="action-btn btn--light-primary"
                                                           style="--size: 30px">
                                                            <span class="material-icons">edit</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                    <tr>
                                        <td colspan="14">
                                            <div class="review-empty-state py-5">
                                                <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2 my-5">
                                                    <img src="{{asset('assets/admin-module/img/provider-empty-state.svg')}}" alt="No data">
                                                    <h5 class="m-0 text-muted opacity-50">{{translate('No Provider Found')}}</h5>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end">
                                {!! $providers->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('provider-list-filter-form');
            var input = document.getElementById('provider-filter-search');
            if (!form || !input) {
                return;
            }

            var debounceTimer = null;
            var lastSubmitted = (input.value || '').trim();

            function submitSearch() {
                var next = (input.value || '').trim();
                if (next === lastSubmitted) {
                    return;
                }
                lastSubmitted = next;
                form.submit();
            }

            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitSearch, 400);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    submitSearch();
                }
            });

            // Keep caret at end after autofocus reload while searching.
            if (document.activeElement === input || input.hasAttribute('autofocus')) {
                var len = input.value.length;
                try {
                    input.setSelectionRange(len, len);
                } catch (err) {}
            }
        })();
    </script>
@endpush
