<div class="table-responsive mt-2">
    <table class="table table-sm align-middle mb-0">
        <thead>
        <tr>
            <th>{{ translate('Name') }}</th>
            <th class="text-end">{{ translate('Leads') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows ?? [] as $row)
            <tr>
                <td>{{ $row['label'] ?? '—' }}</td>
                <td class="text-end">{{ $row['total'] ?? 0 }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="text-center text-muted py-2 fz-12">{{ translate('Data_not_available') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
