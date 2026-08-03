<div class="pg-flow-card">
    <div class="pg-flow-toolbar-top">
        <div>
            <h3>{{ $title }}</h3>
            <p class="pg-flow-sub">Native copy of your Miro board — same positions, colors, shapes, and arrows.</p>
        </div>
        <a href="{{ $miroBoardUrl ?? '#' }}" target="_blank" rel="noopener noreferrer" class="pg-miro-link">Edit in Miro</a>
    </div>

    <div class="pg-flow is-hero is-miro-canvas" data-pg-board-url="{{ $boardJsonUrl }}">
        <div class="pg-flow-toolbar">
            <div class="pg-flow-toolbar-start">
                <span class="pg-flow-toolbar-hint">Drag to pan · scroll or +/− to zoom · Fit all · Full screen · click a step</span>
            </div>
            <div class="pg-flow-actions">
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
        </div>
    </div>
</div>
