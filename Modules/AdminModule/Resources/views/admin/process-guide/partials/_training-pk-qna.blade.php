<div class="pg-pk-qna">
    <p class="pg-pk-qna-kicker">Q&amp;A</p>
    <h2 class="pg-pk-qna-title">{{ $slide['title'] }}</h2>
    <ul>
        @foreach ($slide['prompts'] ?? [] as $prompt)
            <li>{{ $prompt }}</li>
        @endforeach
    </ul>
    @if (!empty($slide['highlight']))
        <p class="pg-pk-qna-talk">{{ $slide['highlight'] }}</p>
    @endif
</div>
