@php
    $calendar = $leaveCalendar ?? ['months' => []];
    $months = $calendar['months'] ?? [];
@endphp
<section class="people-lc" data-leave-cal aria-label="3 months leaves and attendance">
    <div class="people-lc-head">
        <div>
            <h2>3 Months Leaves and Attendant Record</h2>
            <ul class="people-lc-legend">
                <li><i class="people-lc-dot is-present"></i> Present</li>
                <li><i class="people-lc-dot is-leave"></i> Leave</li>
                <li><i class="people-lc-dot is-pending"></i> Pending</li>
                <li><i class="people-lc-dot is-off"></i> Off</li>
            </ul>
        </div>
        <div class="people-lc-pager">
            <button type="button" data-cal-prev aria-label="Previous 3 months">
                <span class="material-icons" aria-hidden="true">chevron_left</span>
            </button>
            <span>3 Months</span>
            <button type="button" data-cal-next aria-label="Next 3 months">
                <span class="material-icons" aria-hidden="true">chevron_right</span>
            </button>
        </div>
    </div>
    <div class="people-lc-board">
        @foreach($months as $month)
            <div class="people-lc-row" data-cal-month data-current="{{ $month['current'] ? '1' : '0' }}" @if(empty($month['visible'])) hidden @endif>
                <div class="people-lc-month">{{ $month['label'] }}</div>
                <div class="people-lc-weeks" style="--weeks: {{ count($month['weeks']) }}">
                    @foreach($month['weeks'] as $week)
                        <div class="people-lc-week">
                            <div class="people-lc-dow" aria-hidden="true">
                                <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                            </div>
                            <div class="people-lc-days">
                                @foreach($week as $day)
                                    @if($day['in'])
                                        <span class="people-lc-dot is-{{ $day['kind'] }}{{ $day['half'] ? ' is-half' : '' }}" title="{{ $day['title'] }}">
                                            <span class="people-lc-num">{{ $day['day'] }}</span>
                                            @if(in_array($day['kind'], ['leave', 'pending'], true) && $day['code'] !== '')
                                                <span class="people-lc-code">{{ $day['code'] }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="people-lc-dot is-pad"></span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
<script>
(function () {
    document.querySelectorAll('[data-leave-cal]:not([data-ready])').forEach(function (root) {
        root.setAttribute('data-ready', '1');
        var rows = Array.prototype.slice.call(root.querySelectorAll('[data-cal-month]'));
        var size = 3;
        var end = rows.length - 1;
        rows.forEach(function (row, index) {
            if (row.getAttribute('data-current') === '1') end = index;
        });
        var prev = root.querySelector('[data-cal-prev]');
        var next = root.querySelector('[data-cal-next]');
        function paint() {
            var start = Math.max(0, end - (size - 1));
            if (end - start < size - 1) end = Math.min(rows.length - 1, start + size - 1);
            start = Math.max(0, end - (size - 1));
            rows.forEach(function (row, index) {
                row.hidden = index < start || index > end;
            });
            if (prev) prev.disabled = start <= 0;
            if (next) next.disabled = end >= rows.length - 1;
        }
        if (prev) prev.addEventListener('click', function () {
            end = Math.max(size - 1, end - size);
            paint();
        });
        if (next) next.addEventListener('click', function () {
            end = Math.min(rows.length - 1, end + size);
            paint();
        });
        paint();
    });
})();
</script>
