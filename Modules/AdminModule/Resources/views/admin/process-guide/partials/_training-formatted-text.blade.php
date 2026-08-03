@php
    use Modules\AdminModule\Support\ProcessGuideText;
@endphp
<span class="pg-training-formatted">{!! ProcessGuideText::format($text ?? '') !!}</span>
