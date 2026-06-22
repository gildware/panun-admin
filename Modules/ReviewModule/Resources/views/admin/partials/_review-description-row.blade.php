<tr class="border-bottom">
    <td colspan="{{ $colspan }}" class="pt-0 pb-3">
        <span class="text-muted small fw-medium">{{ translate('Description') }}:</span>
        <span class="text-muted small">{{ $description ?: translate('No review yet') }}</span>
        @if(!empty($showReply))
            <div class="mt-1">
                <span class="text-muted small fw-medium">{{ translate('Reply') }}:</span>
                <span class="text-muted small">{{ $reply ?: translate('No reply yet') }}</span>
            </div>
        @endif
    </td>
</tr>
