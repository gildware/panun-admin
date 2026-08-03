@php
    $rows = $rows ?? [];
@endphp

<div class="mt-4">
    <h5 class="fz-14 mb-1">{{ $title ?? '' }}</h5>
    @if(!empty($subtitle))
        <p class="text-muted fz-12 mb-2">{{ $subtitle }}</p>
    @endif
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>{{ $parentLabel ?? translate('Category') }}</th>
                <th class="text-end">{{ translate('Total') }}</th>
                <th>{{ $childLabel ?? translate('Breakdown') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['label'] ?? '—' }}</td>
                    <td class="text-end">{{ $row['total'] ?? 0 }}</td>
                    <td>
                        @if(!empty($row['breakdown']))
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($row['breakdown'] as $child)
                                    <span class="badge bg-light text-dark border">
                                        {{ $child['label'] ?? '—' }}: <strong>{{ $child['total'] ?? 0 }}</strong>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-3">{{ translate('Data_not_available') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
