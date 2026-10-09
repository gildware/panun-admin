<article class="people-ws-card people-ws-mt people-lh-history">
    <h2>Leave history</h2>
    <p class="people-ws-note">When leave was added, held, taken, or put back, which leave it was, and why.</p>
    <div class="people-lh-history-scroll">
    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>Leave</th>
                <th>What happened</th>
                <th>How</th>
                <th>Why</th>
            </tr>
        </thead>
        <tbody>
        @forelse($leaveHistory ?? [] as $row)
            <tr>
                <td>{{ $row['at'] ? $row['at']->timezone(config('app.timezone'))->format('j M Y, g:i a') : '—' }}</td>
                <td>{{ $row['leave'] }}</td>
                <td>{{ $row['what'] }}</td>
                <td>{{ $row['how'] }}</td>
                <td>{{ $row['why'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="people-ws-note">No leave history yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</article>
