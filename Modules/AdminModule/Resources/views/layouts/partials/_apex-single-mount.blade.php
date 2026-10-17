<script>
    (function () {
        if (window.__apexSingleMount) {
            return;
        }
        window.__apexSingleMount = true;

        function resolveEl(el) {
            if (!el) {
                return null;
            }
            if (typeof el === 'string') {
                return document.querySelector(el);
            }
            return el;
        }

        function clearMount(el) {
            el = resolveEl(el);
            if (!el) {
                return;
            }
            if (el._apexChart && typeof el._apexChart.destroy === 'function') {
                try {
                    el._apexChart.destroy();
                } catch (e) {}
                el._apexChart = null;
            }
            var nodes = el.querySelectorAll('.apexcharts-canvas');
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].remove();
            }
        }

        function wrap(Ctor) {
            if (!Ctor || Ctor.__singleMount) {
                return Ctor;
            }
            function Single(el, config) {
                clearMount(el);
                var chart = new Ctor(el, config);
                var node = resolveEl(el);
                if (node) {
                    node._apexChart = chart;
                }
                return chart;
            }
            try {
                Object.setPrototypeOf(Single, Ctor);
            } catch (e) {}
            Object.keys(Ctor).forEach(function (key) {
                try {
                    Single[key] = Ctor[key];
                } catch (e) {}
            });
            Single.prototype = Ctor.prototype;
            Single.__singleMount = true;
            return Single;
        }

        var current = window.ApexCharts;
        try {
            Object.defineProperty(window, 'ApexCharts', {
                configurable: true,
                enumerable: true,
                get: function () {
                    return current;
                },
                set: function (Ctor) {
                    current = wrap(Ctor);
                }
            });
        } catch (e) {}
        if (current) {
            current = wrap(current);
        }
    })();
</script>
