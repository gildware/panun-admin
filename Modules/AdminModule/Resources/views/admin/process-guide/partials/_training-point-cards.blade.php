@if (!empty($slide['point_cards']))
    @php
        $trainingAssetBase = rtrim(asset('assets/admin-module/process-guide/training'), '/') . '/';
        $exampleAssetBase = rtrim(asset('assets/admin-module/process-guide/training/examples'), '/') . '/';
        $trainingAssetVersion = process_guide_training_asset_version();
        $exampleAssetVersion = process_guide_training_asset_version('examples');
    @endphp
    <div
        class="pg-training-point-cards"
        data-pg-training-point-cards
        data-pg-point-cards='@json($slide['point_cards'])'
        data-pg-training-asset-base="{{ $trainingAssetBase }}"
        data-pg-example-asset-base="{{ $exampleAssetBase }}"
        data-pg-training-asset-version="{{ $trainingAssetVersion }}"
        data-pg-example-asset-version="{{ $exampleAssetVersion }}"
    >
        <p class="pg-training-point-hint">
            <span class="material-icons" aria-hidden="true">touch_app</span>
            Click a card for full details, illustrated examples, best practices, and what to avoid.
        </p>

        <div class="pg-training-point-grid">
            @foreach ($slide['point_cards'] as $card)
                <button
                    type="button"
                    class="pg-training-point-card"
                    data-pg-point-card-id="{{ $card['id'] ?? ('card-' . $loop->index) }}"
                    aria-expanded="false"
                >
                    @if (!empty($card['image']))
                        <div class="pg-training-point-card-media">
                            <img
                                src="{{ process_guide_training_asset($card['image']) }}"
                                alt=""
                                loading="lazy"
                                data-pg-fallback-icon="{{ $card['icon'] ?? 'info' }}"
                                onerror="window.pgTrainingImageFallback && window.pgTrainingImageFallback(this)"
                            >
                        </div>
                    @elseif (!empty($card['icon']))
                        <div class="pg-training-point-card-media pg-training-point-card-media--icon">
                            <span class="material-icons" aria-hidden="true">{{ $card['icon'] }}</span>
                        </div>
                    @endif
                    <div class="pg-training-point-card-body">
                        @if (!empty($card['title']))
                            <h5 class="pg-training-point-card-title">{{ $card['title'] }}</h5>
                        @endif
                        @if (!empty($card['description']))
                            <p class="pg-training-point-card-desc">{{ $card['description'] }}</p>
                        @endif
                        <span class="pg-training-point-card-more">View details</span>
                    </div>
                </button>
            @endforeach
        </div>

        <div class="pg-training-point-drawer-backdrop" data-pg-point-drawer-close hidden></div>
        <aside
            class="pg-training-point-drawer"
            data-pg-point-drawer
            hidden
            aria-labelledby="pg-point-drawer-title"
            role="dialog"
            aria-modal="true"
        >
            <header class="pg-training-point-drawer-head">
                <div class="pg-training-point-drawer-head-main">
                    <span class="material-icons pg-training-point-drawer-icon" data-pg-point-drawer-icon aria-hidden="true"></span>
                    <h5 class="pg-training-point-drawer-title" id="pg-point-drawer-title" data-pg-point-drawer-title></h5>
                </div>
                <button
                    type="button"
                    class="pg-training-point-drawer-close"
                    data-pg-point-drawer-close
                    aria-label="Close details"
                >
                    <span class="material-icons" aria-hidden="true">close</span>
                </button>
            </header>
            <div class="pg-training-point-drawer-body">
                <figure class="pg-training-point-drawer-hero" data-pg-point-drawer-hero hidden>
                    <img data-pg-point-drawer-hero-img src="" alt="">
                </figure>

                <div class="pg-training-point-drawer-detail-block">
                    <p class="pg-training-point-drawer-detail" data-pg-point-drawer-detail></p>
                    <ul class="pg-training-point-drawer-detail-points" data-pg-point-drawer-detail-points hidden></ul>
                </div>

                <section class="pg-training-point-drawer-section pg-training-point-drawer-section--examples">
                    <h6 class="pg-training-point-drawer-section-title">
                        <span class="material-icons" aria-hidden="true">lightbulb</span>
                        Examples
                    </h6>
                    <div class="pg-training-point-drawer-examples" data-pg-point-drawer-examples></div>
                </section>

                <section class="pg-training-point-drawer-section pg-training-point-drawer-section--good">
                    <h6 class="pg-training-point-drawer-section-title">
                        <span class="material-icons" aria-hidden="true">check_circle</span>
                        Best practices
                    </h6>
                    <ul class="pg-training-point-drawer-list" data-pg-point-drawer-practices></ul>
                </section>

                <section class="pg-training-point-drawer-section pg-training-point-drawer-section--avoid">
                    <h6 class="pg-training-point-drawer-section-title">
                        <span class="material-icons" aria-hidden="true">cancel</span>
                        What to avoid
                    </h6>
                    <ul class="pg-training-point-drawer-list" data-pg-point-drawer-avoid></ul>
                </section>
            </div>
        </aside>
    </div>
@endif
