<div class="chart-card-head">
    <div class="chart-card-head-inner">
        <div>
            @if(! empty($icon))
                <h3>
                    @include('adminmodule::partials._material-icon', ['name' => $icon, 'class' => 'mso'])
                    <span>{{ $title ?? '' }}</span>
                    @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $helpKey ?? null, 'size' => 'xs'])
                </h3>
            @else
                <h3>
                    <span>{{ $title ?? '' }}</span>
                    @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $helpKey ?? null, 'size' => 'xs'])
                </h3>
            @endif
            @if(! empty($subtitle))
                <p>{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</div>
