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
}
