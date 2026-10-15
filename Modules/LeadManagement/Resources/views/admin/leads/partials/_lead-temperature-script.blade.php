<script>
    "use strict";
    (function ($) {
        const temperatureColors = {
            hotter: '#9f1239',
            hot: '#c2410c',
            warm: '#b45309',
            cold: '#0369a1'
        };
        const temperatureIcons = {
            hotter: 'local_fire_department',
            hot: 'whatshot',
            warm: 'wb_sunny',
            cold: 'ac_unit'
        };

        function paintTemperatureSelect(select) {
            const pill = select.closest('.lead-temp-pill');
            if (pill) {
                const value = select.value || 'warm';
                pill.classList.remove('lead-temp-pill--hotter', 'lead-temp-pill--hot', 'lead-temp-pill--warm', 'lead-temp-pill--cold');
                pill.classList.add('lead-temp-pill--' + value);
                const icon = pill.querySelector('.material-icons');
                if (icon) {
                    icon.textContent = temperatureIcons[value] || temperatureIcons.warm;
                }
                return;
            }

            const color = temperatureColors[select.value] || '#6c757d';
            select.style.backgroundColor = color;
            select.style.borderColor = color;
            select.style.color = '#fff';
        }

        $(document).on('change', '.lead-temperature-select', function () {
            const select = this;
            const value = select.value;
            const previous = select.getAttribute('data-current') || '';
            if (!value || value === previous) {
                paintTemperatureSelect(select);
                return;
            }

            select.disabled = true;
            $.ajax({
                url: select.getAttribute('data-url'),
                method: 'PUT',
                data: { lead_temperature: value },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(function () {
                select.setAttribute('data-current', value);
                paintTemperatureSelect(select);
                if (window.toastr) {
                    toastr.success('{{ translate('Lead_temperature_updated') }}');
                }
            }).fail(function (xhr) {
                select.value = previous;
                paintTemperatureSelect(select);
                const message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Failed') }}';
                if (window.toastr) {
                    toastr.error(message);
                }
            }).always(function () {
                select.disabled = false;
            });
        });
    })(jQuery);
</script>
