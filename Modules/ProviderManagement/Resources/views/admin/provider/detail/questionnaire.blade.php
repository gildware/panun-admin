@extends('adminmodule::layouts.master')

@section('title', translate('provider_details'))

@php
    $val = function (string $key, $default = '') use ($answers) {
        return old('answers.'.$key, $answers[$key] ?? $default);
    };
    $vals = function (string $key) use ($answers) {
        $value = old('answers.'.$key, $answers[$key] ?? []);
        return is_array($value) ? $value : [];
    };
    $updaterName = trim(($questionnaire?->updater?->first_name ?? '').' '.($questionnaire?->updater?->last_name ?? ''));
@endphp

@push('css_or_js')
    <style>
        .pk-q-hero {
            background: linear-gradient(90deg, #1b2a4e 0%, #243868 72%, #f5b400 100%);
            color: #fff;
            border-radius: .75rem;
            padding: 1.1rem 1.25rem;
        }
        .pk-q-hero h3,
        .pk-q-hero p,
        .pk-q-hero strong {
            color: #fff !important;
        }
        .pk-q-hero h3 {
            margin: 0;
            font-size: 1.15rem;
            letter-spacing: .02em;
        }
        .pk-q-hero p {
            margin: .35rem 0 0;
            opacity: .85;
            font-size: .86rem;
        }
        .pk-q-section-title {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #1b2a4e;
            border-bottom: 2px solid #1b2a4e;
            padding-bottom: .35rem;
            margin: 1.5rem 0 1rem;
        }
        .pk-q-block {
            border: 1px solid rgba(27, 42, 78, .08);
            border-radius: .75rem;
            padding: 1rem 1.1rem;
            margin-bottom: .85rem;
            background: #fff;
        }
        .pk-q-label {
            font-weight: 600;
            color: #1b2a4e;
            margin-bottom: .7rem;
            line-height: 1.45;
            cursor: default;
        }
        .pk-q-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: .7rem 1.25rem;
        }
        .pk-q-options .custom-radio,
        .pk-q-options .form-check {
            min-height: 1.5rem;
        }
        .pk-q-options .custom-radio label,
        .pk-q-options .form-check-label {
            font-weight: 500;
            color: #2f3b49;
            margin: 0;
            cursor: pointer;
        }
        .pk-q-other {
            margin-top: .65rem;
            max-width: 28rem;
        }
        .pk-q-answer {
            color: #1f2d3d;
            font-size: .95rem;
        }
        .pk-q-empty {
            color: #8a93a0;
            font-style: italic;
        }
        .pk-q-meta {
            font-size: .82rem;
            color: #6b7380;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                @include('providermanagement::admin.provider.partials._provider-detail-tabs', ['webPage' => $webPage])
            </div>

            <div class="pk-q-hero mb-3 d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3>{{ translate('Onboarding_Questionnaire') }}</h3>
                    <p>
                        {{ translate('Questionnaire_progress') }}:
                        <strong>{{ (int) $answeredCount }}/{{ (int) $totalCount }}</strong>
                        @if($questionnaire?->updated_at)
                            · {{ translate('Last_updated') }} {{ $questionnaire->updated_at->format('d M Y, h:i A') }}
                            @if($updaterName !== '')
                                ({{ $updaterName }})
                            @endif
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if($editing && $questionnaire)
                        <a href="{{ route('admin.provider.details', [$provider->id, 'web_page' => 'questionnaire']) }}" class="btn btn-light">
                            {{ translate('Cancel') }}
                        </a>
                    @elseif($canEdit && !$editing)
                        <a href="{{ route('admin.provider.details', [$provider->id, 'web_page' => 'questionnaire', 'edit' => 1]) }}" class="btn btn-light">
                            {{ $questionnaire ? translate('Edit_Answers') : translate('Record_Answers') }}
                        </a>
                    @endif
                </div>
            </div>

            @if(!$editing)
                <div class="card">
                    <div class="card-body p-30">
                        @if(!$questionnaire)
                            <p class="mb-0 pk-q-empty">{{ translate('No_questionnaire_answers_yet') }}</p>
                        @else
                            @foreach($sections as $section)
                                <h4 class="pk-q-section-title">{{ $section['title'] }}</h4>
                                @foreach($section['questions'] as $question)
                                    @php
                                        $formatted = \Modules\ProviderManagement\Support\ProviderOnboardingQuestionnaire::formatAnswer($question, $answers);
                                    @endphp
                                    <div class="pk-q-block">
                                        <div class="pk-q-label">{{ $question['label'] }}</div>
                                        @if($formatted)
                                            <div class="pk-q-answer">{{ $formatted }}</div>
                                        @else
                                            <div class="pk-q-empty">{{ translate('Not_answered') }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        @endif
                    </div>
                </div>
            @else
                <form action="{{ route('admin.provider.details.questionnaire.update', $provider->id) }}" method="post">
                    @csrf
                    <div class="card">
                        <div class="card-body p-30">
                            @foreach($sections as $section)
                                <h4 class="pk-q-section-title">{{ $section['title'] }}</h4>
                                @foreach($section['questions'] as $question)
                                    @include('providermanagement::admin.provider.partials._questionnaire-question', [
                                        'question' => $question,
                                        'val' => $val,
                                        'vals' => $vals,
                                    ])
                                @endforeach
                            @endforeach

                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button type="submit" class="btn btn--primary">{{ translate('Save_Answers') }}</button>
                                @if($questionnaire)
                                    <a href="{{ route('admin.provider.details', [$provider->id, 'web_page' => 'questionnaire']) }}" class="btn btn--secondary">
                                        {{ translate('Cancel') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@if($editing)
    @push('script')
        <script>
            (function () {
                function syncQuestionnaireFields() {
                    $('.pk-q-other').each(function () {
                        var $wrap = $(this);
                        var checked = $wrap.closest('.pk-q-block').find('input[value="other"]:checked').length > 0;
                        $wrap.toggleClass('d-none', !checked);
                        $wrap.find('input, textarea').prop('disabled', !checked);
                    });
                    $('[data-q-followup]').each(function () {
                        var $wrap = $(this);
                        var when = String($wrap.data('show-when') || '');
                        var checked = $wrap.closest('.pk-q-block').find('input[value="' + when + '"]:checked').length > 0;
                        $wrap.toggleClass('d-none', !checked);
                        $wrap.find('input, textarea').prop('disabled', !checked);
                    });
                }
                $(document).on('change', '.pk-q-block input[type="radio"], .pk-q-block input[type="checkbox"]', syncQuestionnaireFields);
                syncQuestionnaireFields();
            })();
        </script>
    @endpush
@endif
