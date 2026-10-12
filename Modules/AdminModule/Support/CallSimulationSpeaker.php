<?php

namespace Modules\AdminModule\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallSimulationSpeaker
{
    /**
     * Spoken clip for one caller line. Uses the cheaper Gemini speech model
     * and only the line itself, so a turn does not pay for a long prompt.
     *
     * @return array{audio: string, mime: string}|null
     */
    public function clip(string $line, string $lang, string $callerName, string $scenarioId, string $difficulty = 'standard', string $tone = 'warm'): ?array
    {
        $line = trim($line);
        $key = trim((string) config('services.gemini.api_key'));
        if ($line === '' || $key === '' || Cache::get('training_call_gemini_blocked') === 'credits') {
            return null;
        }

        $difficulty = in_array($difficulty, ['foundation', 'standard', 'advanced'], true) ? $difficulty : 'standard';
        $tone = in_array($tone, ['calm', 'warm', 'firm', 'upset'], true) ? $tone : 'warm';
        $voice = $this->voice($callerName, $scenarioId);
        $cacheKey = 'training_call_voice_'.md5($voice.'|'.$difficulty.'|'.$tone.'|'.$line);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['audio'])) {
            return $cached;
        }

        $spoken = $this->fetch($key, $line, $lang, $voice, $scenarioId, $difficulty, $tone);
        if ($spoken !== null) {
            Cache::put($cacheKey, $spoken, now()->addMinutes(20));
        }

        return $spoken;
    }

    /**
     * @return array{audio: string, mime: string}|null
     */
    private function fetch(string $key, string $line, string $lang, string $voice, string $scenarioId, string $difficulty, string $tone, ?string $style = null): ?array
    {
        $models = $lang === 'ur'
            ? ['gemini-3.8-flash-tts', 'gemini-3.8-flash-lite-tts']
            : ['gemini-3.8-flash-lite-tts', 'gemini-3.8-flash-tts'];

        foreach ($models as $model) {
            $spoken = $this->request($key, $model, $line, $voice, $scenarioId, $difficulty, $tone, true, $style);
            if ($spoken === false) {
                continue;
            }
            if ($spoken === null && Cache::get('training_call_gemini_blocked') !== 'credits') {
                $spoken = $this->request($key, $model, $line, $voice, $scenarioId, $difficulty, $tone, false, $style);
            }
            if ($spoken === false) {
                continue;
            }
            if (is_array($spoken)) {
                return $spoken;
            }
            if (Cache::get('training_call_gemini_blocked') === 'credits') {
                break;
            }
        }

        return null;
    }

    /**
     * @return array{audio: string, mime: string}|null|false
     */
    private function request(string $key, string $model, string $line, string $voice, string $scenarioId, string $difficulty, string $tone, bool $styled, ?string $style = null): array|null|false
    {
        $part = ['text' => $line];
        if ($styled) {
            $part['speech_metadata'] = ['style' => $style ?? $this->style($scenarioId, $difficulty, $tone)];
        }

        try {
            $response = Http::timeout(20)
                ->withQueryParameters(['key' => $key])
                ->acceptJson()
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent', [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [$part],
                    ]],
                    'generationConfig' => [
                        'responseModalities' => ['AUDIO'],
                        'speechConfig' => [
                            'voiceConfig' => ['voice' => $voice],
                        ],
                    ],
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Call simulation voice skipped', ['message' => $exception->getMessage()]);

            return null;
        }

        if ($response->status() === 402) {
            Cache::put('training_call_gemini_blocked', 'credits', now()->addMinutes(2));
            Log::warning('Call simulation voice skipped', ['status' => 402, 'model' => $model]);

            return null;
        }

        if ($response->status() === 404) {
            return false;
        }

        if ($response->failed()) {
            if ($styled && $response->status() === 400) {
                return null;
            }
            Log::warning('Call simulation voice skipped', [
                'status' => $response->status(),
                'model' => $model,
            ]);

            return null;
        }

        $inline = data_get($response->json(), 'candidates.0.content.parts.0.inlineData')
            ?: data_get($response->json(), 'candidates.0.content.parts.0.inline_data');
        if (! is_array($inline)) {
            return null;
        }

        $encoded = (string) ($inline['data'] ?? '');
        $binary = base64_decode($encoded, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = strtolower((string) ($inline['mimeType'] ?? $inline['mime_type'] ?? ''));
        if (! str_starts_with($binary, 'RIFF')) {
            if (str_contains($mime, 'l16') || str_contains($mime, 'pcm') || $mime === '') {
                $binary = $this->wav($binary);
            }
        }

        return [
            'audio' => base64_encode($binary),
            'mime' => 'audio/wav',
        ];
    }

    private function voice(string $callerName, string $scenarioId): string
    {
        $female = ['Rukhsana', 'Aisha', 'Saima', 'Nazia', 'Mehnaz'];
        if ($scenarioId === 'angry-complaint') {
            return 'Kore';
        }

        return in_array($callerName, $female, true) ? 'Sulafat' : 'Achird';
    }

    private function style(string $scenarioId, string $difficulty, string $tone): string
    {
        $mood = match ($scenarioId) {
            'angry-complaint', 'failed-repair', 'provider-late' => 'upset person on a phone call, not an announcer',
            'hesitant-cancel' => 'unsure person on a phone call, quiet, not an announcer',
            'provider-onboarding' => 'person hoping for work, natural phone call, not an announcer',
            default => 'ordinary person on a phone call, natural, not an announcer',
        };
        $level = match ($difficulty) {
            'foundation' => 'Patient and easy to follow.',
            'advanced' => 'Pressing, still a real person.',
            default => 'A normal phone manner.',
        };
        $voice = match ($tone) {
            'calm' => 'Calm and gentle.',
            'firm' => 'Firm and direct.',
            'upset' => 'Bothered, not shouting.',
            default => 'Warm and friendly.',
        };

        return $mood.' '.$level.' '.$voice;
    }

    private function wav(string $pcm): string
    {
        $channels = 1;
        $sampleRate = 24000;
        $bits = 16;
        $byteRate = $sampleRate * $channels * (int) ($bits / 8);
        $blockAlign = $channels * (int) ($bits / 8);

        return 'RIFF'
            .pack('V', 36 + strlen($pcm))
            .'WAVEfmt '
            .pack('VvvVVvv', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bits)
            .'data'
            .pack('V', strlen($pcm))
            .$pcm;
    }
}
