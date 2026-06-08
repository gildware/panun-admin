<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\LeadManagement\Entities\OmniDimensionCallTranscriptTransliteration;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;

class VoiceCallTranscriptHinglishService
{
    private const CACHE_TTL_SECONDS = 86400;

    private const CACHE_VERSION = 'v3';

    private const FULL_TRANSCRIPT_MAX_CHARS = 1200;

    private const FULL_TRANSCRIPT_MAX_LINES = 12;

    private const LINE_HTTP_TIMEOUT = 45;

    private const BATCH_HTTP_TIMEOUT = 75;

    private const BATCH_LINE_SIZE = 6;

    private const FULL_HTTP_TIMEOUT = 90;

    public function __construct(
        private readonly WhatsAppGeminiSupportClient $gemini
    ) {}

    public function containsDevanagari(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return preg_match('/[\x{0900}-\x{097F}]/u', $text) === 1;
    }

    public function translateToHinglish(string $transcript, ?int $callLogId = null): ?string
    {
        $transcript = trim($transcript);
        if ($transcript === '' || !$this->containsDevanagari($transcript)) {
            return $transcript;
        }

        if ($callLogId !== null && $callLogId > 0) {
            $stored = OmniDimensionCallTranscriptTransliteration::findForCall($callLogId, $transcript);
            if ($stored !== null) {
                return (string) $stored->transliterated_transcript;
            }
        }

        $cacheKey = 'voice_call_transcript_roman:' . self::CACHE_VERSION . ':' . md5($transcript);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            if ($callLogId !== null && $callLogId > 0) {
                OmniDimensionCallTranscriptTransliteration::storeForCall($callLogId, $transcript, $cached);
            }

            return $cached;
        }

        $lines = preg_split('/\r\n|\r|\n/', $transcript) ?: [];
        $transliterated = $this->transliterateLines($lines);
        if ($transliterated === null) {
            return null;
        }

        $out = $this->joinLines($transliterated);
        if ($out === $transcript) {
            Log::warning('Voice call transcript transliteration produced no change', [
                'call_log_id' => $callLogId,
                'input_chars' => mb_strlen($transcript),
            ]);

            return null;
        }

        if ($callLogId !== null && $callLogId > 0) {
            OmniDimensionCallTranscriptTransliteration::storeForCall($callLogId, $transcript, $out);
        }

        Cache::put($cacheKey, $out, self::CACHE_TTL_SECONDS);

        return $out;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>|null
     */
    private function transliterateLines(array $lines): ?array
    {
        $devanagariLineCount = 0;
        foreach ($lines as $line) {
            if ($this->containsDevanagari((string) $line)) {
                $devanagariLineCount++;
            }
        }

        if ($devanagariLineCount === 0) {
            return $lines;
        }

        $inputChars = mb_strlen($this->joinLines($lines));
        $useFullTranscript = $devanagariLineCount <= self::FULL_TRANSCRIPT_MAX_LINES
            && $inputChars <= self::FULL_TRANSCRIPT_MAX_CHARS;

        if ($useFullTranscript) {
            $full = $this->transliterateFullTranscript($lines);
            if ($full !== null && $this->lineStructureMatches($lines, $full)) {
                return $full;
            }
        }

        return $this->transliterateLinesIndividually($lines);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>|null
     */
    private function transliterateFullTranscript(array $lines): ?array
    {
        $input = $this->joinLines($lines);
        $system = $this->systemPrompt();
        $user = "Transliterate this full call transcript. Return the complete transcript with the same lines in the same order:\n\n<<<\n{$input}\n>>>";

        $out = $this->gemini->generatePlainText(
            $system,
            $user,
            null,
            $this->maxOutputTokensForText($input),
            self::FULL_HTTP_TIMEOUT
        );
        if ($out === null || $out === '') {
            return null;
        }

        $out = $this->sanitizeModelOutput($out);
        if ($out === '') {
            return null;
        }

        return preg_split('/\r\n|\r|\n/', $out) ?: null;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>|null
     */
    private function transliterateLinesIndividually(array $lines): ?array
    {
        $out = array_map(static fn ($line) => (string) $line, $lines);
        $pending = [];

        foreach ($lines as $index => $line) {
            $line = (string) $line;
            if ($this->containsDevanagari($line)) {
                $pending[] = ['index' => $index, 'line' => $line];
            }
        }

        if ($pending === []) {
            return $out;
        }

        $convertedAny = false;

        foreach (array_chunk($pending, self::BATCH_LINE_SIZE) as $chunk) {
            $batchConverted = $this->transliterateLineBatch($chunk);
            if ($batchConverted === null) {
                foreach ($chunk as $item) {
                    $single = $this->transliterateSingleLine($item['line']);
                    if ($single !== null && $single !== $item['line']) {
                        $out[$item['index']] = $single;
                        $convertedAny = true;
                    }
                }

                continue;
            }

            foreach ($batchConverted as $item) {
                $index = (int) ($item['index'] ?? -1);
                $text = (string) ($item['line'] ?? '');
                if ($index < 0 || $text === '') {
                    continue;
                }

                if ($text !== $out[$index]) {
                    $convertedAny = true;
                }

                $out[$index] = $text;
            }
        }

        return $convertedAny ? $out : null;
    }

    /**
     * @param  list<array{index: int, line: string}>  $chunk
     * @return list<array{index: int, line: string}>|null
     */
    private function transliterateLineBatch(array $chunk): ?array
    {
        if ($chunk === []) {
            return [];
        }

        if (count($chunk) === 1) {
            $single = $this->transliterateSingleLine($chunk[0]['line']);
            if ($single === null) {
                return null;
            }

            return [['index' => $chunk[0]['index'], 'line' => $single]];
        }

        $numbered = [];
        $inputChars = 0;
        foreach ($chunk as $item) {
            $numbered[] = ($item['index'] + 1) . '. ' . $item['line'];
            $inputChars += mb_strlen($item['line']);
        }

        $input = implode("\n", $numbered);
        $system = $this->systemPrompt() . "\n\nFor batched input, each line is prefixed with its number and a dot (e.g. \"3. User: ...\"). Return the same numbered lines in the same order with only the Hindi transliterated.";
        $user = "Transliterate these transcript lines. Keep each line number prefix exactly:\n\n<<<\n{$input}\n>>>";

        $out = $this->gemini->generatePlainText(
            $system,
            $user,
            null,
            $this->maxOutputTokensForText($input),
            self::BATCH_HTTP_TIMEOUT
        );
        if ($out === null || $out === '') {
            return null;
        }

        $out = $this->sanitizeModelOutput($out);
        if ($out === '') {
            return null;
        }

        $parsed = [];
        foreach (preg_split('/\r\n|\r|\n/', $out) ?: [] as $row) {
            $row = trim((string) $row);
            if ($row === '') {
                continue;
            }

            if (preg_match('/^(\d+)\.\s*(.*)$/u', $row, $matches) !== 1) {
                return null;
            }

            $lineIndex = (int) $matches[1] - 1;
            $parsed[] = [
                'index' => $lineIndex,
                'line' => (string) ($matches[2] ?? ''),
            ];
        }

        if (count($parsed) !== count($chunk)) {
            return null;
        }

        return $parsed;
    }

    private function transliterateSingleLine(string $line): ?string
    {
        $line = trim($line);
        if ($line === '' || !$this->containsDevanagari($line)) {
            return $line;
        }

        $cacheKey = 'voice_call_transcript_line_roman:' . self::CACHE_VERSION . ':' . md5($line);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $system = $this->systemPrompt();
        $user = "Transliterate this single transcript line. Output only that one line:\n\n<<<\n{$line}\n>>>";

        $out = $this->gemini->generatePlainText(
            $system,
            $user,
            null,
            $this->maxOutputTokensForText($line),
            self::LINE_HTTP_TIMEOUT
        );
        if ($out === null || $out === '') {
            return null;
        }

        $out = $this->sanitizeModelOutput($out);
        if ($out === '') {
            return null;
        }

        $outLines = preg_split('/\r\n|\r|\n/', $out) ?: [];
        $converted = trim((string) ($outLines[0] ?? $out));
        if ($converted === '') {
            return null;
        }

        Cache::put($cacheKey, $converted, self::CACHE_TTL_SECONDS);

        return $converted;
    }

    /**
     * @param  list<string>  $original
     * @param  list<string>  $converted
     */
    private function lineStructureMatches(array $original, array $converted): bool
    {
        if (count($original) !== count($converted)) {
            return false;
        }

        foreach ($original as $index => $line) {
            $orig = (string) $line;
            $conv = (string) ($converted[$index] ?? '');
            if (trim($orig) === '' && trim($conv) === '') {
                continue;
            }
            if (trim($orig) !== '' && trim($conv) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $lines
     */
    private function joinLines(array $lines): string
    {
        return implode("\n", array_map(static fn ($line) => (string) $line, $lines));
    }

    private function maxOutputTokensForText(string $text): int
    {
        $chars = mb_strlen($text);

        return min(8192, max(512, (int) ceil($chars * 1.5)));
    }

    private function sanitizeModelOutput(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (preg_match('/^```(?:\w*\n)?([\s\S]*?)```$/u', $text, $matches) === 1) {
            $text = trim((string) ($matches[1] ?? $text));
        }

        if (preg_match('/^<<<\s*([\s\S]*?)\s*>>>$/u', $text, $matches) === 1) {
            $text = trim((string) ($matches[1] ?? $text));
        }

        $text = preg_replace('/^(?:here(?:\'s| is) the transliterated transcript:?\s*)/iu', '', $text) ?? $text;

        return trim($text);
    }

    private function systemPrompt(): string
    {
        return <<<'SYS'
You transliterate voice-call transcripts for admin staff who cannot read Devanagari Hindi.

Task: TRANSLITERATION ONLY — write Hindi words in Roman/Latin (English) letters. Do NOT translate Hindi into English meaning.

Rules (STRICT):
- Return the FULL transcript — same conversation, same lines, same order, same line count.
- Preserve speaker labels exactly: "User:" and "LLM:" (including spacing after the colon).
- Only change Devanagari Hindi text → Roman-letter Hindi (Hinglish spelling). Example: "मुझे प्लंबर चाहिए" → "Mujhe plumber chahiye" (NOT "I need a plumber").
- Lines already in English or already in Roman letters: copy unchanged.
- Keep numbers, names, places, URLs, and punctuation as in the original unless a Devanagari fragment must be transliterated.
- Never use Devanagari, Arabic, or Persian script in output.
- Do not summarize, omit, merge, split, or rephrase lines.
- Do not add explanations or extra text.
- Output only the transcript.
SYS;
    }
}
