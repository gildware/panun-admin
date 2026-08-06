<div class="category-card-list">
    @php
        $catalogListDrillParams = array_filter([
            'search' => request('search') ?: null,
            'status' => in_array(request('status', 'all'), ['active', 'inactive'], true) ? request('status') : null,
        ]);
    @endphp
    @forelse($categories as $category)
        <article class="category-card" data-category-id="{{ $category->id }}">
            <div class="category-card__media">
                <img src="{{ $category->image_full_path }}"
                     alt="{{ $category->name }}"
                     loading="lazy">
            </div>

            <div class="category-card__body">
                <div class="category-card__head">
                    @can('category_update')
                        <a href="{{ route('admin.category.edit', [$category->id]) }}"
                           class="category-card__name category-list-name-link demo_check"
                           title="{{ $category->name }}">{{ $category->name }}</a>
                    @else
                        <span class="category-card__name" title="{{ $category->name }}">{{ $category->name }}</span>
                    @endcan

                    <div class="category-card__stats">
                        <div class="category-card__meta-line">
                            <span class="category-card__meta-label">{{ translate('Sub_categories') }}</span>
                            <span class="category-card__meta-value">{{ $category->children_count }}</span>
                            @if($category->children_count > 0)
                                <div class="category-card__meta-view-wrap">
                                    <button type="button"
                                            class="category-card__meta-view"
                                            aria-label="{{ translate('View_sub_categories') }}"
                                            title="{{ translate('View_sub_categories') }}">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <div class="category-card__meta-panel">
                                        <div class="category-card__meta-panel-head">{{ translate('Sub_categories') }}</div>
                                        <ul class="category-card__meta-panel-list">
                                            @foreach($category->children as $child)
                                                <li>
                                                    @can('category_update')
                                                        <a href="{{ route('admin.sub-category.edit', [$child->id]) }}"
                                                           class="demo_check">{{ $child->name }}</a>
                                                    @else
                                                        {{ $child->name }}
                                                    @endcan
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="category-card__meta-line {{ $category->zones_count < 1 ? 'category-card__meta-line--warn' : '' }}">
                            <span class="category-card__meta-label">{{ translate('Added_in_zone') }}</span>
                            <span class="category-card__meta-value">{{ $category->zones_count }}</span>
                            @if($category->zones_count > 0)
                                <div class="category-card__meta-view-wrap">
                                    <button type="button"
                                            class="category-card__meta-view"
                                            aria-label="{{ translate('View_zones') }}"
                                            title="{{ translate('View_zones') }}">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <div class="category-card__meta-panel">
                                        <div class="category-card__meta-panel-head">{{ translate('Service_Zones') }}</div>
                                        <ul class="category-card__meta-panel-list">
                                            @foreach($category->zones as $zone)
                                                <li>{{ $zone->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @elseif($category->zones_count < 1)
                                <span class="material-icons category-card__warn-icon"
                                      data-bs-toggle="tooltip"
                                      data-bs-placement="top"
                                      title="{{ translate('This category is not under any zone. Kindly update the category with zone') }}">info</span>
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
                                    <input class="switcher_input status-update"
                                           type="checkbox"
                                           {{ $category->is_active ? 'checked' : '' }}
                                           data-id="{{ $category->id }}"
                                           aria-label="{{ translate('active') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                            <div class="category-card__toggle-row">
                                <span class="category-card__toggle-label">{{ translate('Is_Featured') }}</span>
                                <label class="switcher category-card__toggle">
                                    <input class="switcher_input feature-update"
                                           type="checkbox"
                                           {{ $category->is_featured ? 'checked' : '' }}
                                           data-featured="{{ $category->id }}"
                                           aria-label="{{ translate('Is_Featured') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                    @endcan

                    @canany(['category_delete', 'category_update', 'category_view'])
                        <div class="category-card__actions">
                            <div class="category-card__action-row">
                                @can('category_update')
                                    <a href="{{ route('admin.category.edit', [$category->id]) }}"
                                       class="category-card__action demo_check"
                                       title="{{ translate('edit') }}">
                                        <span class="material-icons">edit</span>
                                        <span>{{ translate('edit') }}</span>
                                    </a>
                                @endcan
                                @can('category_view')
                                    <a href="{{ route('admin.sub-category.create', array_merge($catalogListDrillParams, ['parent_id' => $category->id])) }}"
                                       class="category-card__action demo_check"
                                       title="{{ translate('Sub_categories') }}">
                                        <span class="material-icons">folder_open</span>
                                        <span>{{ translate('Sub_categories') }}</span>
                                    </a>
                                @endcan
                            </div>
                            @can('category_delete')
                                <button type="button"
                                        data-id="delete-{{ $category->id }}"
                                        data-message="{{ translate('want_to_delete_this_category') }}?"
                                        class="category-card__action category-card__action--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                                        title="{{ translate('delete') }}">
                                    <span class="material-symbols-outlined">delete</span>
                                    <span>{{ translate('delete') }}</span>
                                </button>
                                <form action="{{ route('admin.category.delete', [$category->id]) }}"
                                      method="post"
                                      id="delete-{{ $category->id }}"
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
            <span class="material-icons">category</span>
            <p>{{ translate('no data available') }}</p>
        </div>
    @endforelse
</div>

@if($categories->hasPages())
    <div class="category-card-pagination d-flex justify-content-end">
        {!! $categories->links() !!}
    </div>
@endif
