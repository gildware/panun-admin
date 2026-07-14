@php
    $variants = ($service->serviceVariants ?? collect())->sortBy('sort_order')->values();
@endphp

<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0 service-variants-table">
        <thead class="text-nowrap">
        <tr>
            <th class="py-2" style="width:40px">{{ translate('SL') }}</th>
            <th class="py-2" style="width:44px">{{ translate('image') }}</th>
            <th class="py-2">{{ translate('title') }}</th>
            <th class="py-2" style="width:90px">{{ translate('price') }}</th>
            <th class="py-2 d-none d-md-table-cell">{{ translate('description') }}</th>
            <th class="py-2" style="width:72px">{{ translate('status') }}</th>
            <th class="py-2 text-center" style="width:110px">{{ translate('action') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($variants as $index => $variant)
            @php
                $defaultPrice = $variant->displayPrice($service);
            @endphp
            <tr>
                <td class="py-1 text-muted">{{ $index + 1 }}</td>
                <td class="py-1">
                    <img src="{{ $variant->image_full_path }}" alt="{{ $variant->title }}"
                         class="rounded" width="32" height="32" style="object-fit: cover;">
                </td>
                <td class="py-1 fw-medium text-dark fs-13">{{ $variant->title }}</td>
                <td class="py-1 fs-13">{{ with_currency_symbol($defaultPrice) }}</td>
                <td class="py-1 text-muted fs-12 d-none d-md-table-cell" style="max-width: 180px;">
                    @if($variant->getRawOriginal('description'))
                        <span class="d-inline-block text-truncate w-100">{{ $variant->getRawOriginal('description') }}</span>
                    @else
                        <span class="fst-italic">—</span>
                    @endif
                </td>
                <td class="py-1">
                    @if($variant->is_active)
                        <span class="badge badge-success">{{ translate('active') }}</span>
                    @else
                        <span class="badge badge-danger">{{ translate('inactive') }}</span>
                    @endif
                </td>
                <td class="py-1">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button"
                                data-variations-panel-url="{{ route('admin.service.variants.show', [$service->id, $variant->id, 'panel' => 1]) }}"
                                class="action-btn btn--light-primary" style="--size: 26px"
                                title="{{ translate('view') }}">
                            <span class="material-icons" style="font-size:16px">visibility</span>
                        </button>
                        @can('service_update')
                            <button type="button"
                                    data-variations-panel-url="{{ route('admin.service.variants.edit', [$service->id, $variant->id, 'panel' => 1]) }}"
                                    class="action-btn btn--light-primary" style="--size: 26px"
                                    title="{{ translate('edit') }}">
                                <span class="material-icons" style="font-size:16px">edit</span>
                            </button>
                            <button type="button"
                                    data-variations-panel-delete="{{ route('admin.service.variants.destroy', [$service->id, $variant->id]) }}"
                                    data-message="{{ translate('want_to_remove_this_variation') }}?"
                                    class="action-btn btn--danger js-variations-panel-delete"
                                    style="--size: 26px"
                                    title="{{ translate('delete') }}">
                                <span class="material-symbols-outlined" style="font-size:16px">delete</span>
                            </button>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-3 fs-13">{{ translate('no_data_found') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
