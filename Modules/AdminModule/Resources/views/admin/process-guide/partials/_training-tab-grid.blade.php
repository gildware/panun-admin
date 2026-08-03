@if (!empty($tabGroups))
    <div class="pg-training-tab-grid" aria-label="Booking list tabs">
        @foreach ($tabGroups as $group)
            <div class="pg-training-tab-grid-group">
                @if (!empty($group['label']))
                    <h5 class="pg-training-tab-grid-group-label">{{ $group['label'] }}</h5>
                @endif
                <div class="pg-training-tab-grid-items">
                    @foreach ($group['tabs'] ?? [] as $tab)
                        <article class="pg-training-tab-badge pg-training-tab-badge--{{ $tab['tone'] ?? 'neutral' }}">
                            <span class="pg-training-tab-badge-name">{{ $tab['name'] ?? '' }}</span>
                            @if (!empty($tab['desc']))
                                <span class="pg-training-tab-badge-desc">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $tab['desc']])</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
