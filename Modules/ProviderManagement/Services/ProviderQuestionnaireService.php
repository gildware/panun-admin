<?php

namespace Modules\ProviderManagement\Services;

use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderQuestionnaire;
use Modules\ProviderManagement\Support\ProviderOnboardingQuestionnaire;

class ProviderQuestionnaireService
{
    public function forProvider(Provider $provider): ?ProviderQuestionnaire
    {
        if ($provider->relationLoaded('questionnaire')) {
            return $provider->questionnaire;
        }

        return ProviderQuestionnaire::query()
            ->with(['recorder', 'updater'])
            ->where('provider_id', $provider->id)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Provider $provider, array $input, ?string $userId): ProviderQuestionnaire
    {
        $answers = ProviderOnboardingQuestionnaire::sanitize($input);

        // App UI selections (presets, category/subcategory ids) for re-edit; not shown in admin formatters.
        if (isset($input['app_ui']) && is_array($input['app_ui'])) {
            $answers['app_ui'] = self::sanitizeAppUi($input['app_ui']);
        }

        $row = ProviderQuestionnaire::query()->where('provider_id', $provider->id)->first();
        if (! $row) {
            $row = new ProviderQuestionnaire();
            $row->provider_id = $provider->id;
            $row->recorded_by = $userId;
        }

        $row->answers = $answers;
        $row->updated_by = $userId;
        $row->save();

        return $row;
    }

    /**
     * @return array{is_filled: bool, answered_count: int, question_count: int}
     */
    public function statusFor(Provider $provider): array
    {
        $row = $this->forProvider($provider);
        $answers = is_array($row?->answers) ? $row->answers : [];
        $answered = ProviderOnboardingQuestionnaire::answeredCount($answers);
        $total = ProviderOnboardingQuestionnaire::questionCount();

        return [
            'is_filled' => $total > 0 && $answered >= $total,
            'answered_count' => $answered,
            'question_count' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $ui
     * @return array<string, mixed>
     */
    private static function sanitizeAppUi(array $ui): array
    {
        $out = [];
        foreach ($ui as $key => $value) {
            $key = is_string($key) ? substr(preg_replace('/[^a-z0-9_]/i', '', $key) ?? '', 0, 64) : '';
            if ($key === '') {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = array_values(array_filter(array_map(static function ($item) {
                    if (! is_scalar($item)) {
                        return '';
                    }
                    $text = trim(strip_tags(ProviderOnboardingQuestionnaire::toValidUtf8((string) $item)));

                    return $text === '' ? '' : mb_substr($text, 0, 100, 'UTF-8');
                }, $value), static fn ($v) => $v !== ''));
                continue;
            }
            if (is_scalar($value)) {
                $text = trim(strip_tags(ProviderOnboardingQuestionnaire::toValidUtf8((string) $value)));
                $out[$key] = $text === '' ? '' : mb_substr($text, 0, 255, 'UTF-8');
            }
        }

        return $out;
    }
}
