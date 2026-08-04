<?php

namespace Modules\LeadManagement\Services;

use App\Support\StoragePathPrefix;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;

class LeadInitialCallRecordingTranscriptionService
{
    /**
     * @return array{transcript: string, summary: string, transcribed_at: string, from_cache: bool}
     */
    public function transcribeAndSummarize(Lead $lead, bool $force = false): array
    {
        if (! $lead->hasInitialCallRecording()) {
            throw new \InvalidArgumentException(translate('No_initial_call_recording_for_transcription'));
        }

        if (! $force && $lead->initial_call_recording_transcript && $lead->initial_call_recording_summary) {
            return [
                'transcript' => (string) $lead->initial_call_recording_transcript,
                'summary' => (string) $lead->initial_call_recording_summary,
                'transcribed_at' => $lead->initial_call_recording_transcribed_at?->toIso8601String() ?? '',
                'from_cache' => true,
            ];
        }

        $apiKey = trim((string) config('services.gemini.api_key'));
        if ($apiKey === '') {
            throw new \RuntimeException(translate('Gemini_API_key_is_not_configured'));
        }

        [$audioBytes, $mimeType] = $this->loadRecording($lead);
        $result = $this->callGeminiAudio($apiKey, $audioBytes, $mimeType, $lead);

        $lead->initial_call_recording_transcript = $result['transcript'];
        $lead->initial_call_recording_summary = $result['summary'];
        $lead->initial_call_recording_transcribed_at = now();
        $lead->save();

        return [
            'transcript' => $result['transcript'],
            'summary' => $result['summary'],
            'transcribed_at' => $lead->initial_call_recording_transcribed_at->toIso8601String(),
            'from_cache' => false,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function loadRecording(Lead $lead): array
    {
        $disk = $lead->initial_call_recording_disk ?: getDisk();
        $path = StoragePathPrefix::apply('lead-initial-calls/'.$lead->initial_call_recording_path);

        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException(translate('Recording_file_not_found_in_storage'));
        }

        $bytes = Storage::disk($disk)->get($path);
        if ($bytes === null || $bytes === '') {
            throw new \RuntimeException(translate('Recording_file_is_empty'));
        }

        $mimeType = trim((string) ($lead->initial_call_recording_mime ?: 'audio/wav'));
        if ($mimeType === 'audio/x-wav') {
            $mimeType = 'audio/wav';
        }

        return [$bytes, $mimeType];
    }

    /**
     * @return array{transcript: string, summary: string}
     */
    private function callGeminiAudio(string $apiKey, string $audioBytes, string $mimeType, Lead $lead): array
    {
        $system = <<<'PROMPT'
You transcribe and summarize customer support phone calls for Panun Kaergar (home services marketplace in India).
Return ONLY valid JSON with exactly these keys:
- "transcript": full conversation with one speaker turn per line using labels "Support:" and "User:"
- "summary": one plain-English paragraph (max 90 words) covering reason for call, key service details, outcome, and pending next steps

No markdown fences, no extra keys, no commentary outside JSON.
PROMPT;

        $context = trim(implode("\n", array_filter([
            $lead->remarks ? 'Initial remarks: '.$lead->remarks : null,
        ])));

        $userParts = [
            [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => base64_encode($audioBytes),
                ],
            ],
            [
                'text' => ($context !== '' ? $context."\n\n" : '')
                    .'Transcribe this initial support call recording and summarize it.',
            ],
        ];

        $body = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => $userParts,
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ];

        $models = [
            (string) config('services.gemini.model', 'gemini-2.5-flash'),
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
        ];
        $models = array_values(array_unique(array_filter(array_map(
            static fn (string $model) => str_starts_with($model, 'models/') ? substr($model, 7) : $model,
            $models
        ))));

        $lastError = translate('Failed_to_transcribe_recording');

        foreach ($models as $model) {
            try {
                $response = Http::timeout(180)
                    ->withQueryParameters(['key' => $apiKey])
                    ->acceptJson()
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/'
                        .rawurlencode($model)
                        .':generateContent',
                        $body
                    );

                if ($response->status() === 404) {
                    continue;
                }

                if ($response->failed()) {
                    $lastError = $this->extractGeminiError($response->json(), $response->body())
                        ?: $lastError;
                    Log::warning('Lead initial call transcription Gemini request failed', [
                        'lead_id' => $lead->id,
                        'model' => $model,
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                $text = $this->extractGeminiText($response->json());
                $parsed = $this->parseJsonResponse($text);

                return [
                    'transcript' => $this->truncateTranscript($parsed['transcript'], 12000),
                    'summary' => $this->truncateSummary($parsed['summary'], 1900),
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage() ?: $lastError;
                Log::warning('Lead initial call transcription exception', [
                    'lead_id' => $lead->id,
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException($lastError);
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractGeminiText(?array $json): string
    {
        $parts = $json['candidates'][0]['content']['parts'] ?? [];
        if (! is_array($parts)) {
            return '';
        }

        $texts = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        return trim(implode("\n", $texts));
    }

    /**
     * @return array{transcript: string, summary: string}
     */
    private function parseJsonResponse(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException(translate('Transcription_response_was_empty'));
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException(translate('Could_not_parse_transcription_response'));
        }

        $transcript = LeadFollowup::formatTranscript(trim((string) ($decoded['transcript'] ?? '')));
        $summary = trim((string) ($decoded['summary'] ?? ''));

        if ($transcript === '' || $summary === '') {
            throw new \RuntimeException(translate('Transcription_response_missing_required_fields'));
        }

        return compact('transcript', 'summary');
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractGeminiError(?array $json, string $rawBody): ?string
    {
        if (is_array($json) && isset($json['error']['message'])) {
            return trim((string) $json['error']['message']);
        }

        $raw = trim($rawBody);

        return $raw !== '' ? mb_substr($raw, 0, 300) : null;
    }

    private function truncateSummary(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }

    private function truncateTranscript(string $text, int $max): string
    {
        $text = LeadFollowup::formatTranscript($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }
}
