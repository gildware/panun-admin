<div class="pg-training-deck-guide">
    @if (!empty($slide['terms']))
        <section class="pg-training-deck-section">
            <h5 class="pg-training-deck-section-title">
                <span class="material-icons pg-training-block-icon" aria-hidden="true">menu_book</span>
                {{ $slide['terms_title'] ?? 'Terms we use' }}
            </h5>
            <dl class="pg-training-deck-terms">
                @foreach ($slide['terms'] as $item)
                    <div class="pg-training-deck-term">
                        <dt>{{ $item['term'] }}</dt>
                        <dd>{{ $item['definition'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif
</div>
