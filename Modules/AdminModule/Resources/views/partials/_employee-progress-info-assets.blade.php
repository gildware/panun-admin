@once
    @include('adminmodule::partials._employee-progress-info-dropdown')
    <script data-always-activate="1">
        window.PanunProgressHelp = Object.assign(
            {},
            window.PanunProgressHelp || {},
            @json(\Modules\AdminModule\Services\EmployeeProgressMetricHelp::registry())
        );
    </script>
    <script src="{{ asset('assets/admin-module/js/employee-progress-info.js') }}?v=20260817rank2" data-always-activate="1"></script>
@endonce
