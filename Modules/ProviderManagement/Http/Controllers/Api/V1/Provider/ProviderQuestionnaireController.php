<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProviderManagement\Services\ProviderQuestionnaireService;
use Modules\ProviderManagement\Support\ProviderOnboardingQuestionnaire;

class ProviderQuestionnaireController extends Controller
{
    public function __construct(
        protected ProviderQuestionnaireService $questionnaireService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $row = $this->questionnaireService->forProvider($provider);
        $answers = is_array($row?->answers) ? $row->answers : [];
        $status = $this->questionnaireService->statusFor($provider);

        $areaOptions = array_values(array_filter(
            ProviderOnboardingQuestionnaire::areaOptions(),
            static fn (array $opt) => ($opt['value'] ?? '') !== 'other'
        ));

        return response()->json(response_formatter(DEFAULT_200, [
            'is_filled' => $status['is_filled'],
            'answered_count' => $status['answered_count'],
            'question_count' => $status['question_count'],
            'answers' => $answers,
            'app_ui' => is_array($answers['app_ui'] ?? null) ? $answers['app_ui'] : [],
            'area_options' => $areaOptions,
            'updated_at' => $row?->updated_at?->toIso8601String(),
        ]), 200);
    }

    public function update(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;
        if (! $provider) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $input = $request->input('answers', []);
        if (! is_array($input)) {
            $input = [];
        }

        // Allow app_ui either nested under answers or top-level.
        if ($request->has('app_ui') && is_array($request->input('app_ui'))) {
            $input['app_ui'] = $request->input('app_ui');
        }

        $row = $this->questionnaireService->save(
            $provider,
            $input,
            $request->user()->id ? (string) $request->user()->id : null
        );

        $answers = is_array($row->answers) ? $row->answers : [];
        $answered = ProviderOnboardingQuestionnaire::answeredCount($answers);
        $total = ProviderOnboardingQuestionnaire::questionCount();

        return response()->json(response_formatter(DEFAULT_UPDATE_200, [
            'is_filled' => $total > 0 && $answered >= $total,
            'answered_count' => $answered,
            'question_count' => $total,
            'answers' => $answers,
            'app_ui' => is_array($answers['app_ui'] ?? null) ? $answers['app_ui'] : [],
            'updated_at' => $row->updated_at?->toIso8601String(),
        ]), 200);
    }
}
