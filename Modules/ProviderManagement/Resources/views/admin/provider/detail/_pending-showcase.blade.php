@if(($pendingShowcaseItems ?? collect())->isNotEmpty())
    <div class="col-12">
        <div class="ob-card">
            <h3 class="ob-card__title">
                <span class="material-symbols-outlined">collections</span>
                {{ translate('Work_Showcase') }} — {{ translate('Pending') }}
            </h3>
            <div class="row g-3">
                @foreach($pendingShowcaseItems as $item)
                    <div class="col-md-4 col-lg-3">
                        <div class="border rounded p-2 h-100">
                            @if($item->media_type === 'video')
                                <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener" class="d-block text-center py-4">{{ translate('video') }}</a>
                            @else
                                <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener">
                                    <img src="{{ $item->media_full_path }}" alt="" class="w-100 rounded" style="max-height:120px;object-fit:cover">
                                </a>
                            @endif
                            <p class="small mb-2 mt-2">{{ $item->title ?: '-' }}</p>
                            @can('onboarding_request_approve_or_deny')
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-soft--danger btn-sm showcase_deny" data-id="{{ $item->id }}">{{ translate('Deny') }}</button>
                                    <button type="button" class="btn btn--success btn-sm showcase_approve" data-id="{{ $item->id }}">{{ translate('Accept') }}</button>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
