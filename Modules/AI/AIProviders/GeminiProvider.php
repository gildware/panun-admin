<?php

namespace Modules\AI\AIProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Contracts\AIProviderInterface;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey = '';

    protected ?string $organization = null;

    /** @var list<string> */
    private const DEPRECATED_20_MODELS = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-2.0-pro',
    ];

    public function getName(): string
    {
        return 'Gemini';
    }

    public function setApiKey($apikey): void
    {
        $this->apiKey = (string) ($apikey ?? '');
    }

    public function setOrganization($organization): void
    {
        $this->organization = $organization !== null && $organization !== '' ? (string) $organization : null;
    }

    public function generate(string $prompt, ?string $imageUrl = null, array $options = []): string
    {
        $key = trim($this->apiKey) !== '' ? trim($this->apiKey) : (string) config('services.gemini.api_key');
        if ($key === '') {
            throw new \RuntimeException('Gemini API key is not configured. Set GEMINI_API_KEY in .env or save the API key in AI configuration.');
        }

        $parts = [['text' => $prompt]];
        if ($imageUrl !== null && $imageUrl !== '') {
            $inline = $this->fetchImageAsInlineData($imageUrl);
            if ($inline !== null) {
                $parts[] = ['inlineData' => $inline];
            }
        }

        $body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
            ],
        ];

        $configuredModel = $this->resolveConfiguredModel();
        $lastError = '';

        foreach ($this->modelCandidates($configuredModel) as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
                . rawurlencode($model)
                . ':generateContent';

            $response = Http::timeout(90)
                ->withQueryParameters(['key' => $key])
                ->acceptJson()
                ->post($url, $body);

            if ($response->status() === 404) {
                Log::info('AI Gemini: model returned 404, trying next', ['model' => $model]);

                continue;
            }

            if ($response->failed()) {
                $lastError = 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500);
                Log::warning('AI Gemini generateContent failed', [
                    'model' => $model,
                    'status' => $response->status(),
                ]);

                continue;
            }

            $text = $this->extractTextFromResponse($response->json());
            if ($text !== null && $text !== '') {
                return $text;
            }
            $lastError = 'empty_response';
        }

        throw new \RuntimeException('Gemini did not return usable text.' . ($lastError !== '' ? ' ' . $lastError : ''));
    }

    private function resolveConfiguredModel(): string
    {
        $fromOrg = trim((string) ($this->organization ?? ''));
        if ($fromOrg !== '') {
            return $this->normalizeModelId($fromOrg);
        }

        return $this->normalizeModelId((string) config('services.gemini.model', 'gemini-2.5-flash'));
    }

    private function normalizeModelId(string $model): string
    {
        $m = trim($model);
        if ($m === '') {
            return 'gemini-2.5-flash';
        }
        if (str_starts_with($m, 'models/')) {
            $m = substr($m, strlen('models/'));
        }

        return $m;
    }

    /**
     * @return list<string>
     */
    private function modelCandidates(string $primary): array
    {
        $primary = $this->normalizeModelId($primary);
        $ordered = [];
        if (in_array($primary, self::DEPRECATED_20_MODELS, true)) {
            $ordered[] = 'gemini-2.5-flash';
        }
        $ordered[] = $primary;
        $fallbacks = [
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-flash-latest',
            'gemini-1.5-flash',
        ];

        return array_values(array_unique(array_merge($ordered, $fallbacks)));
    }

    /**
     * @return array{mimeType: string, data: string}|null
     */
    private function fetchImageAsInlineData(string $imageUrl): ?array
    {
        try {
            $response = Http::timeout(30)->get($imageUrl);
            if ($response->failed()) {
                Log::warning('AI Gemini: could not fetch image URL', ['url' => $imageUrl, 'status' => $response->status()]);

                return null;
            }
            $binary = $response->body();
            if (strlen($binary) > 4 * 1024 * 1024) {
                Log::warning('AI Gemini: image too large for inline data');

                return null;
            }
            $mime = $response->header('Content-Type');
            if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
                $mime = 'image/jpeg';
            }

            return [
                'mimeType' => explode(';', $mime)[0],
                'data' => base64_encode($binary),
            ];
        } catch (\Throwable $e) {
            Log::warning('AI Gemini: image fetch exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractTextFromResponse(?array $json): ?string
    {
        if (!is_array($json)) {
            return null;
        }
        if (isset($json['error']) && is_array($json['error'])) {
            Log::warning('AI Gemini API error body', ['error' => $json['error']]);

            return null;
        }
        $candidate = $json['candidates'][0] ?? null;
        if (!is_array($candidate)) {
            return null;
        }
        $parts = $candidate['content']['parts'] ?? null;
        if (!is_array($parts)) {
            return null;
        }
        $texts = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        $merged = trim(implode("\n", $texts));

        return $merged === '' ? null : $merged;
    }
}
