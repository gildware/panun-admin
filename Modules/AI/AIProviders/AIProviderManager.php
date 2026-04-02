<?php

namespace Modules\AI\AIProviders;

use Illuminate\Support\Facades\Cache;
use Modules\AI\app\Exceptions\ValidationException;
use Modules\AI\app\Models\AISetting;
use Modules\AI\Services\AIResponseValidatorService;

class AIProviderManager
{
    protected array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function getAvailableProviderObject()
    {
        $activeAiProvider = $this->getActiveAIProvider();
        foreach ($this->providers as $provider) {
            if ($activeAiProvider->ai_name == $provider->getName()) {
                $provider->setApiKey($activeAiProvider->api_key);
                $provider->setOrganization($activeAiProvider->organization_id);
                return $provider;
            }
        }

        throw new \RuntimeException('No AI provider implementation matches the active setting: ' . $activeAiProvider->ai_name);
    }

    private function rowHasUsableCredentials(AISetting $row): bool
    {
        $key = trim((string) ($row->api_key ?? ''));
        if ($key !== '') {
            return true;
        }

        return $row->ai_name === 'Gemini' && trim((string) config('services.gemini.api_key')) !== '';
    }

    public function getActiveAIProvider(): AISetting
    {
        $provider = Cache::remember('active_ai_provider_v2', 60, function () {
            $rows = AISetting::where('status', 1)->orderBy('id')->get();

            $found = $rows->first(fn (AISetting $row) => $this->rowHasUsableCredentials($row));
            if ($found !== null) {
                return $found;
            }

            // WhatsApp uses GEMINI_API_KEY from .env; admin AI content generation only read ai_settings.
            // If the table is empty (never saved Business Settings → AI), bootstrap a Gemini row when env is set.
            $envGeminiKey = trim((string) config('services.gemini.api_key'));
            if ($envGeminiKey === '') {
                return null;
            }

            $geminiRow = AISetting::where('ai_name', 'Gemini')->first();
            if ($geminiRow === null) {
                return AISetting::create([
                    'ai_name' => 'Gemini',
                    'api_key' => '',
                    'organization_id' => null,
                    'status' => 1,
                ]);
            }

            // Row exists but AI toggle is off — do not override admin choice.
            return null;
        });

        if (!$provider) {
            throw new \RuntimeException('No active AI provider available at this moment.');
        }
        return $provider;
    }

    public function generate(string $prompt, ?string $imageUrl = null, array $options = []): string
    {
        $providerObject = $this->getAvailableProviderObject();
        $activeProvider = $this->getActiveAIProvider();
        $response = $providerObject->generate($prompt, $imageUrl);
        $aiValidator = new AIResponseValidatorService();
        $appMode = env('APP_ENV');
        $section = $options['section'] ?? '';

        if ($appMode === 'demo') {
            $ip = request()->header('x-forwarded-for');
            $cacheKey = 'demo_ip_usage_' . $ip;
            $count = Cache::get($cacheKey, 0);
            if ($count >= 10) {
                throw new ValidationException("Demo limit reached: You can only generate 10 times.");
            }
            Cache::forever($cacheKey, $count + 1);
        }
        $validatorMap = [
            'product_name' => 'validateProductTitle',
            'product_short_description' => 'validateProductShortDescription',
            'product_description' => 'validateProductDescription',
            'general_setup' => 'validateProductGeneralSetup',
            'variation_setup' => 'validateProductVariationSetup',
            'generate_product_title_suggestion' => 'validateProductTitleSuggestion',
            'generate_title_from_image' => 'validateImageResponse',
        ];

        if ($section && isset($validatorMap[$section])) {
            $aiValidator->{$validatorMap[$section]}($response, $options['context'] ?? null);
        }

        return $response;
    }

}
