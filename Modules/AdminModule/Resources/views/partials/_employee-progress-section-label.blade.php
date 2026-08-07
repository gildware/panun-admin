<div class="section-label">
    <span class="section-label-text">{{ $label ?? '' }}</span>
    @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $helpKey ?? null, 'size' => 'xs'])
    <span class="section-label-rule" aria-hidden="true"></span>
</div>
