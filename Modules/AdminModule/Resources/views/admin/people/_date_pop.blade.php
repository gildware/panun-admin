<div class="people-date-pop" id="people-date-pop" hidden>
    <div class="people-date-pop-bar">
        <button type="button" data-date-nav="-1" aria-label="Previous month">‹</button>
        <select data-date-month aria-label="Month"></select>
        <select data-date-year aria-label="Year"></select>
        <button type="button" data-date-nav="1" aria-label="Next month">›</button>
    </div>
    <div class="people-date-week" aria-hidden="true"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div>
    <div class="people-date-grid" data-date-grid></div>
</div>
<script>
    (function () {
        var pop = document.getElementById('people-date-pop');
        if (!pop || pop.dataset.ready) return;
        pop.dataset.ready = '1';
        document.body.appendChild(pop);
        var monthSelect = pop.querySelector('[data-date-month]');
        var yearSelect = pop.querySelector('[data-date-year]');
        var grid = pop.querySelector('[data-date-grid]');
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var view = new Date();
        var active = null;
        var thisYear = new Date().getFullYear();

        months.forEach(function (name, index) {
            var option = document.createElement('option');
            option.value = String(index);
            option.textContent = name;
            monthSelect.appendChild(option);
        });
        for (var year = thisYear + 5; year >= 1940; year--) {
            var option = document.createElement('option');
            option.value = String(year);
            option.textContent = String(year);
            yearSelect.appendChild(option);
        }

        function iso(year, month, day) {
            return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        function place() {
            if (!active) return;
            var rect = active.getBoundingClientRect();
            pop.hidden = false;
            var top = rect.bottom + 6;
            if (top + pop.offsetHeight > window.innerHeight - 8) {
                top = Math.max(8, rect.top - pop.offsetHeight - 6);
            }
            pop.style.top = top + 'px';
            pop.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - pop.offsetWidth - 8)) + 'px';
        }

        function render() {
            var year = view.getFullYear();
            var month = view.getMonth();
            if (![...yearSelect.options].some(function (option) { return option.value === String(year); })) {
                var extra = document.createElement('option');
                extra.value = String(year);
                extra.textContent = String(year);
                yearSelect.appendChild(extra);
            }
            monthSelect.value = String(month);
            yearSelect.value = String(year);
            grid.replaceChildren();
            var start = (new Date(year, month, 1).getDay() + 6) % 7;
            var days = new Date(year, month + 1, 0).getDate();
            var selected = active && active.value;
            var today = iso(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());
            for (var i = 0; i < start; i++) grid.appendChild(document.createElement('span'));
            for (var day = 1; day <= days; day++) {
                var button = document.createElement('button');
                var value = iso(year, month, day);
                button.type = 'button';
                button.textContent = String(day);
                button.dataset.value = value;
                if (value === selected) button.className = 'is-selected';
                if (value === today) button.classList.add('is-today');
                var reason = active && typeof window.peopleDateBlockReason === 'function'
                    ? (window.peopleDateBlockReason(active, value) || '')
                    : '';
                if (reason) {
                    button.disabled = true;
                    button.title = reason;
                    button.classList.add('is-disabled');
                }
                grid.appendChild(button);
            }
        }

        function open(input) {
            active = input;
            var parsed = input.value ? new Date(input.value + 'T00:00:00') : new Date();
            if (isNaN(parsed.getTime())) parsed = new Date();
            view = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
            render();
            place();
        }

        document.addEventListener('click', function (event) {
            var input = event.target.closest('.people-date-field');
            if (!input) return;
            event.preventDefault();
            if (active === input && !pop.hidden) {
                pop.hidden = true;
                active = null;
                return;
            }
            open(input);
        });

        pop.addEventListener('click', function (event) {
            event.stopPropagation();
            var nav = event.target.closest('[data-date-nav]');
            if (nav) {
                view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.dateNav), 1);
                render();
                place();
                return;
            }
            var day = event.target.closest('[data-value]');
            if (!day || !active || day.disabled) return;
            var input = active;
            input.value = day.dataset.value;
            pop.hidden = true;
            active = null;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        pop.addEventListener('change', function (event) {
            if (event.target === monthSelect || event.target === yearSelect) {
                view = new Date(Number(yearSelect.value), Number(monthSelect.value), 1);
                render();
                place();
            }
        });

        pop.addEventListener('mousedown', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('mousedown', function (event) {
            if (pop.hidden || !active) return;
            if (pop.contains(event.target) || event.target === active) return;
            pop.hidden = true;
            active = null;
        });

        window.addEventListener('resize', function () {
            if (!pop.hidden) place();
        });

        window.peopleDateRefresh = function () {
            if (!pop.hidden && active) render();
        };
    })();
</script>
