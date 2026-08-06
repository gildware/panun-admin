<div class="category-card-list">
    @php
        $catalogListDrillParams = array_filter([
            'search' => request('search') ?: null,
            'status' => in_array(request('status', 'all'), ['active', 'inactive'], true) ? request('status') : null,
        ]);
    @endphp
    @forelse($subCategories as $category)
        <article class="category-card" data-sub-category-id="{{ $category->id }}">
            <div class="category-card__media">
                <img src="{{ $category->image_full_path }}"
                     alt="{{ $category->name }}"
                     loading="lazy">
            </div>

            <div class="category-card__body">
                <div class="category-card__head">
                    @can('category_update')
                        <a href="{{ route('admin.sub-category.edit', [$category->id]) }}"
                           class="category-card__name category-list-name-link demo_check"
                           title="{{ $category->name }}">{{ $category->name }}</a>
                    @else
                        <span class="category-card__name" title="{{ $category->name }}">{{ $category->name }}</span>
                    @endcan

                    <div class="category-card__stats">
                        <div class="category-card__meta-line">
                            <span class="category-card__meta-label">{{ translate('category') }}</span>
                            <span class="category-card__meta-value text-truncate"
                                  style="max-width:5rem"
                                  title="{{ $category->parent->name ?? translate('not_found') }}">
                                {{ $category->parent->name ?? translate('not_found') }}
                            </span>
                            @if($category->parent)
                                <div class="category-card__meta-view-wrap">
                                    <button type="button"
                                            class="category-card__meta-view"
                                            aria-label="{{ translate('category') }}"
                                            title="{{ translate('category') }}">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <div class="category-card__meta-panel">
                                        <div class="category-card__meta-panel-head">{{ translate('category') }}</div>
                                        <ul class="category-card__meta-panel-list">
                                            <li>
                                                @can('category_view')
                                                    <a href="{{ route('admin.sub-category.create', array_merge($catalogListDrillParams, ['parent_id' => $category->parent_id])) }}"
                                                       class="demo_check">{{ $category->parent->name }}</a>
                                                @else
                                                    {{ $category->parent->name }}
                                                @endcan
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="category-card__meta-line">
                            <span class="category-card__meta-label">{{ translate('service_count') }}</span>
                            <span class="category-card__meta-value">{{ $category->services_count }}</span>
                            @if($category->services_count > 0)
                                <div class="category-card__meta-view-wrap">
                                    <button type="button"
                                            class="category-card__meta-view"
                                            aria-label="{{ translate('View_services') }}"
                                            title="{{ translate('View_services') }}">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <div class="category-card__meta-panel">
                                        <div class="category-card__meta-panel-head">{{ translate('services') }}</div>
                                        <ul class="category-card__meta-panel-list">
                                            @foreach($category->services as $service)
                                                <li>
                                                    @can('service_update')
                                                        <a href="{{ route('admin.service.edit', [$service->id]) }}"
                                                           class="demo_check">{{ $service->name }}</a>
                                                    @else
                                                        {{ $service->name }}
                                                    @endcan
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="category-card__footer">
                    @can('category_manage_status')
                        <div class="category-card__toggles">
                            <div class="category-card__toggle-row">
                                <span class="category-card__toggle-label">{{ translate('active') }}</span>
                                <label class="switcher category-card__toggle">
                                    <input class="switcher_input sub-category-status-update"
                                           type="checkbox"
                                           {{ $category->is_active ? 'checked' : '' }}
                                           data-id="{{ $category->id }}"
                                           aria-label="{{ translate('active') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                    @endcan

                    @canany(['category_delete', 'category_update', 'category_view'])
                        <div class="category-card__actions">
                            <div class="category-card__action-row">
                                @can('category_update')
                                    <a href="{{ route('admin.sub-category.edit', [$category->id]) }}"
                                       class="category-card__action demo_check"
                                       title="{{ translate('edit') }}">
                                        <span class="material-icons">edit</span>
                                        <span>{{ translate('edit') }}</span>
                                    </a>
                                @endcan
                                @can('category_view')
                                    <a href="{{ route('admin.service.index', array_merge($catalogListDrillParams, ['category_id' => $category->parent_id, 'sub_category_id' => $category->id])) }}"
                                       class="category-card__action demo_check"
                                       title="{{ translate('services') }}">
                                        <span class="material-icons">home_repair_service</span>
                                        <span>{{ translate('services') }}</span>
                                    </a>
                                @endcan
                            </div>
                            @can('category_delete')
                                @if(($category->services_count ?? 0) > 0)
                                    <button type="button"
                                            class="category-card__action category-card__action--danger category-card__action--disabled demo_check"
                                            title="{{ translate('Cannot_delete_sub_category_with_services') }}"
                                            onclick="toastr.error(@json(translate('Cannot_delete_sub_category_with_services')))">
                                        <span class="material-symbols-outlined">delete</span>
                                        <span>{{ translate('delete') }}</span>
                                    </button>
                                @else
                                    <button type="button"
                                            data-id="delete-{{ $category->id }}"
                                            data-message="{{ translate('want_to_delete_this_sub_category') }}?"
                                            class="category-card__action category-card__action--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                                            title="{{ translate('delete') }}">
                                        <span class="material-symbols-outlined">delete</span>
                                        <span>{{ translate('delete') }}</span>
                                    </button>
                                    <form action="{{ route('admin.sub-category.delete', [$category->id]) }}"
                                          method="post"
                                          id="delete-{{ $category->id }}"
                                          class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            @endcan
                        </div>
                    @endcanany
                </div>
            </div>
        </article>
    @empty
        <div class="category-card-empty">
            <span class="material-icons">category</span>
            <p>{{ translate('no data available') }}</p>
        </div>
    @endforelse
</div>

@if($subCategories->hasPages())
    <div class="category-card-pagination d-flex justify-content-end">
        {!! $subCategories->links() !!}
    </div>
@endif
