<div class="pg-flow-card">
    <div class="pg-flow-toolbar-top">
        <div>
            <h3>{{ $title }}</h3>
            <p class="pg-flow-sub">Use <strong>Edit flowchart</strong> to add shapes, drag to rearrange, and manage step groups — or open Miro for advanced editing.</p>
        </div>
        <a href="{{ $miroBoardUrl ?? '#' }}" target="_blank" rel="noopener noreferrer" class="pg-miro-link">Edit in Miro</a>
    </div>

    <div class="pg-flow-legend" aria-label="Flowchart shape legend">
        <span class="pg-legend-item pg-legend-group"><i></i> Step group · click ⓘ</span>
        <span class="pg-legend-item pg-legend-channel"><i></i> Channel</span>
        <span class="pg-legend-item pg-legend-action"><i></i> Action</span>
        <span class="pg-legend-item pg-legend-decision"><i></i> Decision</span>
        <span class="pg-legend-item pg-legend-message"><i></i> Message</span>
        <span class="pg-legend-item pg-legend-end-state"><i></i> End state</span>
        <span class="pg-legend-item pg-legend-end-terminal"><i></i> Closed / cancel</span>
        <span class="pg-legend-item pg-legend-arrow"><i></i> Flow arrow</span>
    </div>

    <div class="pg-flow-layout">
        @include('adminmodule::admin.process-guide.partials._editor')

        <div
            class="pg-flow is-hero is-miro-canvas"
            data-pg-board-url="{{ $boardJsonUrl }}"
            data-pg-board-save-url="{{ $boardSaveUrl ?? '' }}"
            data-pg-groups-save-url="{{ $groupsSaveUrl ?? '' }}"
            data-pg-groups='@json($processGuideGroups ?? [])'
        >
            <div class="pg-flow-toolbar">
                <div class="pg-flow-toolbar-start">
                    <span class="pg-flow-toolbar-hint">Drag to pan · scroll or +/− to zoom · click ⓘ on a group for details</span>
                </div>
                <div class="pg-flow-actions">
                    <button type="button" data-pg-edit-toggle title="Edit shapes and groups">Edit flowchart</button>
                    <button type="button" data-pg-fit-all title="Fit whole board">Fit all</button>
                    <button type="button" data-pg-recenter title="Center board">Recenter</button>
                    <button type="button" data-pg-zoom-out title="Zoom out">−</button>
                    <span class="pg-zoom-label" data-pg-zoom-label aria-live="polite"></span>
                    <button type="button" data-pg-zoom-in title="Zoom in">+</button>
                    <button type="button" data-pg-fullscreen title="Full screen">Full screen</button>
                </div>
            </div>
            <div class="pg-flow-fallback">
                Board did not load.
                <br>
                <button type="button" data-pg-retry>Retry</button>
            </div>
            <div class="pg-flow-viewport is-loading">
                <div class="pg-flow-stage pg-miro-stage"></div>
                <aside class="pg-group-detail" data-pg-group-detail hidden aria-hidden="true">
                    <div class="pg-group-detail-header">
                        <div>
                            <span class="pg-group-detail-step" data-pg-detail-step></span>
                            <h4 class="pg-group-detail-title" data-pg-detail-title></h4>
                            <p class="pg-group-detail-sub" data-pg-detail-sub></p>
                        </div>
                        <button type="button" class="pg-group-detail-close" data-pg-detail-close aria-label="Close details">&times;</button>
                    </div>
                    <div class="pg-group-detail-body" data-pg-detail-body></div>
                </aside>
            </div>
        </div>
    </div>
</div>
