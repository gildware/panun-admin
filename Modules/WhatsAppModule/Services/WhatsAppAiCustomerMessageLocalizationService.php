<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Rewrites admin-configured template bodies and button labels to match the customer's
 * last message language/register (Roman script rules aligned with WhatsAppAiPromptBuilder).
 */
class WhatsAppAiCustomerMessageLocalizationService
{
    public function __construct(
        protected WhatsAppGeminiSupportClient $gemini,
    ) {}

    public function localizeTemplate(
        string $template,
        string $referenceUserMessage,
        ?WhatsAppAiExecutionRecorder $recorder = null
    ): string {
        $template = trim($template);
        $ref = trim($referenceUserMessage);
        if ($template === '' || $ref === '') {
            return $template;
        }

        $system = $this->systemPromptForBody();
        $user = "The customer's last WhatsApp message (match this language, register, and script):\n\n"
            ."<<<\n".$ref."\n>>>\n\n"
            .'Rewrite the following message for the customer in that same language and script. '
            .'Keep all factual details (phone numbers, times, schedule text, names, URLs, booking ids) exactly as in the original unless a transliteration is required for readability. '
            .'Use a single language only — do not add English or other translations in parentheses or extra lines. '
            ."Do not add a preamble or explanation. Output only the rewritten message:\n\n"
            .'<<<'."\n".$template."\n>>>";

        $out = $this->gemini->generatePlainText($system, $user, $recorder);

        return ($out !== null && $out !== '') ? $out : $template;
    }

    /**
     * @param  array<int, array<string, mixed>>  $metaButtons
     * @return array<int, array<string, mixed>>
     */
    public function localizeMetaButtons(
        array $metaButtons,
        string $referenceUserMessage,
        ?WhatsAppAiExecutionRecorder $recorder = null
    ): array {
        $ref = trim($referenceUserMessage);
        if ($ref === '' || $metaButtons === []) {
            return $metaButtons;
        }

        $labels = [];
        $indexMap = [];
        foreach ($metaButtons as $i => $b) {
            if (!is_array($b)) {
                continue;
            }
            $t = strtoupper((string) ($b['type'] ?? ''));
            $label = trim((string) ($b['text'] ?? ''));
            if ($label === '' || !in_array($t, ['QUICK_REPLY', 'URL', 'PHONE_NUMBER'], true)) {
                continue;
            }
            $indexMap[] = $i;
            $labels[] = $label;
        }

        if ($labels === []) {
            return $metaButtons;
        }

        $translated = $this->localizeStringList($labels, $ref, $recorder);
        if (count($translated) !== count($labels)) {
            return $metaButtons;
        }

        $out = $metaButtons;
        foreach ($indexMap as $j => $i) {
            if (!isset($out[$i]) || !is_array($out[$i])) {
                continue;
            }
            $out[$i]['text'] = $translated[$j];
        }

        return $out;
    }

    /**
     * @param  list<string>  $strings
     * @return list<string>
     */
    private function localizeStringList(
        array $strings,
        string $referenceUserMessage,
        ?WhatsAppAiExecutionRecorder $recorder
    ): array {
        $json = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || $json === '') {
            return $strings;
        }

        $system = $this->systemPromptForLabels()
            .' Respond with ONLY a JSON array of strings, same length and order as the input. No markdown fences, no commentary.';
        $user = "Customer's last message (match language and script):\n<<<\n".$referenceUserMessage."\n>>>\n\n"
            ."Translate these short UI button labels to match:\n".$json;

        $raw = $this->gemini->generatePlainText($system, $user, $recorder);
        if ($raw === null || $raw === '') {
            return $strings;
        }

        $parsed = json_decode($this->stripJsonFence($raw), true);
        if (!is_array($parsed) || count($parsed) !== count($strings)) {
            return $strings;
        }

        $out = [];
        foreach ($strings as $i => $_) {
            $item = $parsed[$i] ?? null;
            $out[] = is_string($item) ? trim($item) : $strings[$i];
        }

        return $out;
    }

    private function stripJsonFence(string $s): string
    {
        $s = trim($s);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```/s', $s, $m)) {
            return trim($m[1]);
        }

        return $s;
    }

    private function systemPromptForBody(): string
    {
        return <<<'SYS'
You rewrite fixed WhatsApp support messages so they read naturally for the customer.
Match the language, dialect, and script of the customer's last message.
Use English when they wrote English. For Hindi/Urdu, use Roman letters only (Latin script) — never Devanagari.
Match Hinglish or Roman Urdu when they mix languages. Keep phone numbers, times, and ids stable.
Never send translations: output must match the customer's last message language only — no English gloss, no slash-separated duplicate, no second line with the same meaning in another language.
SYS;
    }

    private function systemPromptForLabels(): string
    {
        return <<<'SYS'
Rewrite each button label into the customer's last-message language only (Roman script for Hindi/Urdu; no Devanagari). Keep concise.
Never add a second language, slash duplicate, or parenthetical translation on the same label.
SYS;
    }
}
