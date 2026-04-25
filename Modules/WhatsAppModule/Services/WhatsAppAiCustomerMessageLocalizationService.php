<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Rewrites admin-configured template bodies to match English vs Hinglish rules from the
 * customer's last message. Session button labels are never rewritten here.
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
        $user = "The customer's last WhatsApp message (use only to decide English vs Hinglish vs other per system rules):\n\n"
            ."<<<\n".$ref."\n>>>\n\n"
            .'Rewrite the following message following the system rules (English vs Hinglish only; never Kashmiri; Roman letters only in your output). '
            .'Keep all factual details (phone numbers, times, schedule text, names, URLs, booking ids) exactly as in the original unless a transliteration is required for readability. '
            .'Use a single language only — do not add English or other translations in parentheses or extra lines. '
            ."Do not add a preamble or explanation. Output only the rewritten message:\n\n"
            .'<<<'."\n".$template."\n>>>";

        $out = $this->gemini->generatePlainText($system, $user, $recorder);
        if ($out === null || $out === '') {
            return $template;
        }

        $inLen = mb_strlen($template);
        $outLen = mb_strlen($out);
        // Prefer the full admin template over an obviously truncated rewrite (common on long bodies).
        if ($inLen >= 180 && $outLen < (int) max(1, floor($inLen * 0.45))) {
            return $template;
        }

        return $out;
    }

    /**
     * Session quick-reply and template button labels are defined in admin and sent as-is.
     * LLM relabeling corrupted payloads (e.g. appended "[act_human]") and must not run here.
     *
     * @param  array<int, array<string, mixed>>  $metaButtons
     * @return array<int, array<string, mixed>>
     */
    public function localizeMetaButtons(
        array $metaButtons,
        string $referenceUserMessage,
        ?WhatsAppAiExecutionRecorder $recorder = null
    ): array {
        return $metaButtons;
    }

    private function systemPromptForBody(): string
    {
        return <<<'SYS'
You rewrite fixed WhatsApp support messages so they read naturally on WhatsApp.
Allowed output (STRICT): English, OR Hinglish (Hindi mixed with English) in Roman/Latin letters only.
- If the customer's last message is clearly English → reply in English.
- If it is Hinglish (Roman letters, Hindi-English mix) → reply in Hinglish.
- If it is any other language or script (including Kashmiri, Hindi in Devanagari, Arabic/Persian script, etc.) → reply in natural Hinglish OR English (Roman letters only); pick one tone and stay consistent. Do not mirror Kashmiri or non-Latin scripts.
Never use Kashmiri. Never use Devanagari. Never use Arabic/Persian script in your output.
Keep phone numbers, times, schedule text, URLs, and booking ids exactly as in the original unless you must transliterate a fragment for clarity.
Never send translations: one register only — no English gloss in parentheses, no slash-separated duplicate, no second line repeating the same meaning in another language.
SYS;
    }
}
