<div class="category-card-list" id="ServiceListTableContainer">
    @forelse($services as $service)
        <article class="category-card category-card--service" data-service-id="{{ $service->id }}">
            @php($hasServiceThumbnail = filled($service->thumbnail))
            <div class="category-card__media category-card__media--cover {{ $hasServiceThumbnail ? '' : 'is-placeholder' }}">
                <img src="{{ $hasServiceThumbnail ? $service->thumbnail_full_path : '' }}"
                     alt="{{ $service->name }}"
                     loading="lazy"
                     class="{{ $hasServiceThumbnail ? '' : 'd-none' }}"
                     onerror="this.classList.add('d-none'); this.closest('.category-card__media').classList.add('is-placeholder'); this.closest('.category-card__media').querySelector('.category-card__media-placeholder')?.classList.remove('d-none');">
                <span class="category-card__media-placeholder {{ $hasServiceThumbnail ? 'd-none' : '' }}"
                      aria-hidden="true">
                    <span class="material-icons">home_repair_service</span>
                </span>
            </div>

            <div class="category-card__body">
                <div class="category-card__head">
                    <a href="{{ route('admin.service.detail', [$service->id]) }}"
                       class="category-card__name category-list-name-link demo_check"
                       title="{{ $service->name }}"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        {{ $service->name }}
                    </a>

                    <div class="service-card__stats">
                        <div class="service-card__stat">
                            <span class="service-card__stat-label">{{ translate('category') }}</span>
                            <span class="service-card__stat-value"
                                  title="{{ $service->category->name ?? translate('Unavailable') }}">
                                {{ $service->category->name ?? translate('Unavailable') }}
                            </span>
                        </div>

                        <div class="service-card__stat">
                            <span class="service-card__stat-label">{{ translate('sub_category') }}</span>
                            <span class="service-card__stat-value"
                                  title="{{ $service->subCategory->name ?? translate('Unavailable') }}">
                                {{ $service->subCategory->name ?? translate('Unavailable') }}
                            </span>
                        </div>

                        <div class="category-card__meta-line">
                            <span class="category-card__meta-label">{{ translate('variations') }}</span>
                            <span class="category-card__meta-value">{{ $service->variations_count }}</span>
                            @if($service->variations_count > 0)
                                <div class="category-card__meta-view-wrap">
                                    <button type="button"
                                            class="category-card__meta-view"
                                            aria-label="{{ translate('variations') }}"
                                            title="{{ translate('variations') }}">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <div class="category-card__meta-panel">
                                        <div class="category-card__meta-panel-head">{{ translate('variations') }}</div>
                                        <ul class="category-card__meta-panel-list">
                                            @foreach($service->variations->unique('variant') as $variation)
                                                <li>{{ $variation->variant }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="category-card__footer">
                    @can('service_manage_status')
                        <div class="category-card__toggles">
                            <div class="category-card__toggle-row">
                                <span class="category-card__toggle-label">{{ translate('active') }}</span>
                                <label class="switcher category-card__toggle">
                                    <input class="switcher_input route-alert"
                                           type="checkbox"
                                           data-route="{{ route('admin.service.status-update', [$service->id]) }}"
                                           data-message="{{ translate('want_to_update_status') }}"
                                           {{ $service->is_active ? 'checked' : '' }}
                                           aria-label="{{ translate('active') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                    @endcan

                    @canany(['service_delete', 'service_update'])
                        <div class="category-card__actions">
                            <div class="category-card__action-row">
                                @can('service_update')
                                    <a href="{{ route('admin.service.edit', [$service->id]) }}"
                                       class="category-card__action demo_check"
                                       title="{{ translate('edit') }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        <span class="material-icons">edit</span>
                                        <span>{{ translate('edit') }}</span>
                                    </a>
                                @endcan
                                <a href="{{ route('admin.service.detail', [$service->id]) }}"
                                   class="category-card__action demo_check"
                                   title="{{ translate('view') }}"
                                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                    <span class="material-icons">visibility</span>
                                    <span>{{ translate('view') }}</span>
                                </a>
                            </div>
                            @can('service_delete')
                                <button type="button"
                                        data-id="delete-{{ $service->id }}"
                                        data-message="{{ translate('want_to_delete_this_service') }}?"
                                        class="category-card__action category-card__action--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                                        title="{{ translate('delete') }}">
                                    <span class="material-symbols-outlined">delete</span>
                                    <span>{{ translate('delete') }}</span>
                                </button>
                                <form action="{{ route('admin.service.delete', [$service->id]) }}"
                                      method="post"
                                      id="delete-{{ $service->id }}"
                                      class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endcan
                        </div>
                    @endcanany
                </div>
            </div>
        </article>
    @empty
        <div class="category-card-empty">
            <span class="material-icons">home_repair_service</span>
            <p>{{ translate('no data available') }}</p>
        </div>
    @endforelse
</div>

@if($services->hasPages())
    <div class="category-card-pagination d-flex justify-content-end service-list-pagination">
        {!! $services->links() !!}
    </div>
@endif
