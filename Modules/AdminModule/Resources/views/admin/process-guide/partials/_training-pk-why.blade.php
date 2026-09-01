@php
    $art = $slide['slide_image'] ?? 'pk-s3-why-slide.png';
@endphp
<div class="pg-pk-why pg-pk-why--art">
    <img
        class="pg-pk-why-art"
        src="{{ process_guide_training_asset($art) }}"
        alt="{{ $slide['title'] ?? 'Why does Panun Kaergar exist?' }}"
    >
</div>
