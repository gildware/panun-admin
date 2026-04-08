<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGeminiSupportClient
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $runtimeResolver
    ) {}

    /** @var list<string> */
    private const DEPRECATED_20_MODELS = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-2.0-pro',
    ];

    /**
     * @param  list<array<string, mixed>>  $contents
     * @param  list<array<string, mixed>>  $functionDeclarations
     * @return array{type: 'text', text: string}|array{type: 'function_calls', calls: list<array{name: string, args: array<string, mixed>}>}|array{type: 'blocked', reason: string}
     */
    public function generateTurn(
        string $systemText,
        array $contents,
        array $functionDeclarations,
        ?WhatsAppAiExecutionRecorder $recorder = null
    ): array {
        $t0 = microtime(true);
        $withTools = $functionDeclarations !== [];
        $turn = $this->generateTurnInternal($systemText, $contents, $functionDeclarations);
        $ms = (int) round((microtime(true) - $t0) * 1000);

        if ($recorder !== null) {
            $detail = [
                'ms' => $ms,
                'with_tools' => $withTools,
                'turn_type' => $turn['type'],
                'reason' => $turn['type'] === 'blocked' ? ($turn['reason'] ?? null) : null,
                'contents_turns' => count($contents),
            ];
            if ($turn['type'] === 'text') {
                $detail['text_preview'] = mb_substr((string) ($turn['text'] ?? ''), 0, 240);
            }
            if ($turn['type'] === 'function_calls' && isset($turn['calls'])) {
                $detail['tools_called'] = array_map(static fn (array $c): string => (string) ($c['name'] ?? ''), $turn['calls']);
            }
            $stepStatus = $turn['type'] === 'blocked' ? 'fail' : 'ok';
            $recorder->step('gemini.generate_turn', 'Gemini generateContent', $stepStatus, $detail);
        }

        return $turn;
    }

    /**
     * Single user turn, no tools — for template localization and similar short tasks.
     */
    public function generatePlainText(
        string $systemText,
        string $userText,
        ?WhatsAppAiExecutionRecorder $recorder = null
    ): ?string {
        $t0 = microtime(true);
        $turn = $this->generateTurnInternal(
            $systemText,
            [['role' => 'user', 'parts' => [['text' => $userText]]]],
            []
        );
        $ms = (int) round((microtime(true) - $t0) * 1000);

        if ($recorder !== null) {
            $detail = [
                'ms' => $ms,
                'turn_type' => $turn['type'],
                'reason' => $turn['type'] === 'blocked' ? ($turn['reason'] ?? null) : null,
            ];
            if ($turn['type'] === 'text') {
                $detail['text_preview'] = mb_substr((string) ($turn['text'] ?? ''), 0, 240);
            }
            $stepStatus = $turn['type'] === 'blocked' ? 'fail' : 'ok';
            $recorder->step('gemini.localize_plain', 'Gemini plain text (localization)', $stepStatus, $detail);
        }

        if ($turn['type'] !== 'text') {
            return null;
        }

        $out = trim((string) ($turn['text'] ?? ''));

        return $out === '' ? null : $out;
    }

    /**
     * @return list<string>
     */
    private function modelCandidates(): array
    {
        $primary = $this->normalizeGeminiModelId(
            $this->runtimeResolver->geminiModel()
        );

        $ordered = [];
        if (in_array($primary, self::DEPRECATED_20_MODELS, true)) {
            Log::warning('WHATSAPP_GEMINI_MODEL points at a deprecated Gemini 2.0 model (often HTTP 404). Trying gemini-2.5-flash first.', [
                'configured' => $primary,
            ]);
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

    private function normalizeGeminiModelId(string $model): string
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
     * @param  list<array<string, mixed>>  $contents
     * @param  list<array<string, mixed>>  $functionDeclarations
     * @return array{type: 'text', text: string}|array{type: 'function_calls', calls: list<array{name: string, args: array<string, mixed>}>}|array{type: 'blocked', reason: string}
     */
    private function generateTurnInternal(string $systemText, array $contents, array $functionDeclarations): array
    {
        $key = (string) config('services.gemini.api_key');
        if ($key === '') {
            return ['type' => 'blocked', 'reason' => 'missing_api_key'];
        }

        $maxOut = (int) config('whatsappmodule.gemini_max_output_tokens', 896);
        $temp = (float) config('whatsappmodule.gemini_temperature', 0.35);
        $gen = [
            'maxOutputTokens' => $maxOut,
            'temperature' => $temp,
        ];
        $body = [
            'systemInstruction' => [
                'parts' => [['text' => $systemText]],
            ],
            'contents' => $contents,
            'generationConfig' => $gen,
        ];
        if ($functionDeclarations !== []) {
            $body['tools'] = [
                ['functionDeclarations' => $functionDeclarations],
            ];
            $body['toolConfig'] = [
                'functionCallingConfig' => [
                    'mode' => 'AUTO',
                ],
            ];
        }

        $candidates = $this->modelCandidates();
        $last404Body = '';

        try {
            foreach ($candidates as $model) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
                    . rawurlencode($model)
                    . ':generateContent';

                $timeout = (int) config('whatsappmodule.gemini_http_timeout', 32);
                $response = Http::timeout($timeout)
                    ->withQueryParameters(['key' => $key])
                    ->acceptJson()
                    ->post($url, $body);

                if ($response->status() === 404) {
                    $last404Body = mb_substr($response->body(), 0, 800);
                    Log::info('Gemini generateContent 404 for model; trying next candidate', [
                        'model' => $model,
                        'body_preview' => $last404Body,
                    ]);

                    continue;
                }

                if ($response->failed()) {
                    $snippet = mb_substr($response->body(), 0, 2500);
                    Log::warning('Gemini generateContent failed', [
                        'model' => $model,
                        'status' => $response->status(),
                        'body' => $snippet,
                    ]);

                    return ['type' => 'blocked', 'reason' => 'http_' . $response->status()];
                }

                return $this->parseGenerateContentSuccess($response->json(), $model);
            }

            Log::warning('Gemini generateContent: all model candidates returned 404', [
                'tried' => $candidates,
                'last_body_preview' => $last404Body,
            ]);

            return ['type' => 'blocked', 'reason' => 'http_404_all_models'];
        } catch (\Throwable $e) {
            Log::warning('Gemini exception', ['error' => $e->getMessage()]);

            return ['type' => 'blocked', 'reason' => 'exception'];
        }
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @return array{type: 'text', text: string}|array{type: 'function_calls', calls: list<array{name: string, args: array<string, mixed>}>}|array{type: 'blocked', reason: string}
     */
    private function parseGenerateContentSuccess(?array $json, string $modelUsed): array
    {
        if (!is_array($json)) {
            return ['type' => 'blocked', 'reason' => 'invalid_json'];
        }

        if (isset($json['error']) && is_array($json['error'])) {
            $msg = (string) ($json['error']['message'] ?? 'error');
            Log::warning('Gemini API error in response body', [
                'model' => $modelUsed,
                'error' => $json['error'],
            ]);

            return ['type' => 'blocked', 'reason' => 'api_' . mb_substr($msg, 0, 120)];
        }

        $feedback = $json['promptFeedback'] ?? null;
        if (is_array($feedback)) {
            $br = $feedback['blockReason'] ?? '';
            if (is_string($br) && $br !== '' && $br !== 'BLOCK_REASON_UNSPECIFIED') {
                Log::info('Gemini promptFeedback block', ['blockReason' => $br]);

                return ['type' => 'blocked', 'reason' => 'prompt_' . $br];
            }
        }

        $candidate = $json['candidates'][0] ?? null;
        if (!is_array($candidate)) {
            Log::warning('Gemini no candidates', [
                'model' => $modelUsed,
                'keys' => array_keys($json),
            ]);

            return ['type' => 'blocked', 'reason' => 'no_candidate'];
        }

        $finish = (string) ($candidate['finishReason'] ?? '');
        $okFinishes = ['', 'STOP', 'MAX_TOKENS'];

        $parts = $candidate['content']['parts'] ?? null;
        if (!is_array($parts)) {
            if ($finish !== '' && !in_array($finish, $okFinishes, true)) {
                return ['type' => 'blocked', 'reason' => 'finish_' . $finish];
            }

            return ['type' => 'blocked', 'reason' => 'no_parts'];
        }

        $calls = [];
        $texts = [];
        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }
            if (isset($part['functionCall']['name'])) {
                $fn = $part['functionCall'];
                $rawArgs = $fn['args'] ?? null;
                if (is_array($rawArgs)) {
                    $args = $rawArgs;
                } elseif (is_string($rawArgs) && $rawArgs !== '') {
                    $decoded = json_decode($rawArgs, true);
                    $args = is_array($decoded) ? $decoded : [];
                } else {
                    $args = [];
                }
                $calls[] = [
                    'name' => (string) $fn['name'],
                    'args' => $args,
                ];
            }
            if (isset($part['text']) && is_string($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        if ($calls !== []) {
            return ['type' => 'function_calls', 'calls' => $calls];
        }

        $merged = trim(implode("\n", $texts));

        if ($merged === '' && $finish !== '' && !in_array($finish, $okFinishes, true)) {
            Log::info('Gemini empty text with finishReason', ['finishReason' => $finish]);

            return ['type' => 'blocked', 'reason' => 'finish_' . $finish];
        }

        if ($finish !== '' && !in_array($finish, $okFinishes, true)) {
            Log::info('Gemini finishReason', ['reason' => $finish]);
        }

        return ['type' => 'text', 'text' => $merged];
    }
}
