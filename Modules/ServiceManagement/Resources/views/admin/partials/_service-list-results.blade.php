<div class="table-responsive" id="ServiceListTableContainer">
    <table id="service-list-table" class="table align-middle">
        <thead>
        <tr>
            <th>{{ translate('name') }}</th>
            <th>{{ translate('category') }}</th>
            <th>{{ translate('sub_category') }}</th>
            <th>{{ translate('variations') }}</th>
            <th>{{ translate('Minimum Bidding Price') }}</th>
            @can('service_manage_status')
                <th>{{ translate('status') }}</th>
            @endcan
            @canany(['service_delete', 'service_update'])
                <th>{{ translate('action') }}</th>
            @endcan
        </tr>
        </thead>
        <tbody>
        @forelse($services as $service)
            <tr>
                <td>
                    <a href="{{ route('admin.service.detail', [$service->id]) }}"
                       class="category-list-name-link d-flex align-items-center gap-3 text-decoration-none demo_check title-color"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        <div class="avatar avatar-sm flex-shrink-0">
                            <img class="avatar-img radius-5"
                                 src="{{ $service->thumbnail_full_path }}"
                                 alt="{{ $service->name }}">
                        </div>
                        <span class="fw-medium">{{ Str::limit($service->name, 50) }}</span>
                    </a>
                </td>
                <td>
                    @if($service->category)
                        {{ $service->category->name }}
                    @else
                        <div class="d-flex">
                            <span>{{ translate('Unavailable') }}</span>
                            <i class="material-icons" data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               title="{{ translate('Update the service category') }}">info</i>
                        </div>
                    @endif
                </td>
                <td>
                    @if($service->subCategory)
                        {{ $service->subCategory->name }}
                    @else
                        <div class="d-flex">
                            <span>{{ translate('Unavailable') }}</span>
                            <i class="material-icons" data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               title="{{ translate('Update the service sub category') }}">info</i>
                        </div>
                    @endif
                </td>
                <td>{{ $service->variations_count }}</td>
                <td>
                    {{ with_currency_symbol($service->min_bidding_price) }}
                    @if($service->min_bidding_price == 0)
                        <i class="text-warning material-icons px-1"
                           data-bs-toggle="tooltip" data-bs-placement="top"
                           title="{{ translate('Update the minimum bidding price') }}">warning</i>
                    @endif
                </td>
                @can('service_manage_status')
                    <td>
                        <label class="switcher" data-bs-toggle="modal"
                               data-bs-target="#deactivateAlertModal">
                            <input class="switcher_input route-alert"
                                   data-route="{{ route('admin.service.status-update', [$service->id]) }}"
                                   data-message="{{ translate('want_to_update_status') }}"
                                   type="checkbox" {{ $service->is_active ? 'checked' : '' }}>
                            <span class="switcher_control"></span>
                        </label>
                    </td>
                @endcan
                @canany(['service_delete', 'service_update'])
                    <td>
                        <div class="d-flex gap-2">
                            @can('service_update')
                                <a href="{{ route('admin.service.edit', [$service->id]) }}"
                                   class="action-btn btn--light-primary demo_check"
                                   style="--size: 30px"
                                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                    <span class="material-icons">edit</span>
                                </a>
                            @endcan
                            @can('service_delete')
                                <button type="button"
                                        data-id="delete-{{ $service->id }}"
                                        data-message="{{ translate('want_to_delete_this_service') }}?"
                                        class="action-btn btn--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                                        style="--size: 30px">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                <form action="{{ route('admin.service.delete', [$service->id]) }}"
                                      method="post" id="delete-{{ $service->id }}"
                                      class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endcan
                        </div>
                    </td>
                @endcanany
            </tr>
        @empty
            <tr class="text-center">
                <td colspan="7">{{ translate('no data available') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end service-list-pagination">
    {!! $services->links() !!}
</div>
