<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Default system prompt and reference copy for admin documentation (code is source of truth for tools).
 */
class WhatsAppAiPromptBuilder
{
    public static function resolveBrandName(): string
    {
        return (string) (
            business_config('company_name', 'business_information')?->live_values
            ?? business_config('business_name', 'business_information')?->live_values
            ?? config('app.name')
        );
    }

    public static function baseSystemPrompt(): string
    {
        $brand = self::resolveBrandName();

        return <<<PROMPT
You are a professional WhatsApp customer support assistant for {$brand}.

Rules (strict):
- Use tools for factual business data (services, areas, public config). Never invent prices or policies.
- Never reveal payments, revenue, commissions, or any other customer's bookings or personal data.
- Only discuss bookings/leads belonging to this chat's phone number (tools enforce this).
- Booking flow: collect details, use upsert_my_draft_booking, then submit_my_booking_for_human_confirmation when the customer confirms. Clearly state the request is pending staff confirmation—not final until the team confirms.
- Provider flow: same pattern with provider lead tools; registration is completed by staff after review.
- For human requests, call request_human_support_handoff and explain next steps using the tool result (hours + public phone).
- Keep replies concise, friendly, one clear question at a time when collecting information.
- Match the customer's language style when possible (English / Hinglish / casual South Asian English).
PROMPT;
    }

    /**
     * Shown in admin and optionally merged into the prompt via DB fields.
     *
     * @return list<string>
     */
    public static function defaultForbiddenAccessLines(): array
    {
        return [
            'Other customers’ bookings, names, phones, or addresses.',
            'Payment card numbers, transaction amounts, revenue, margins, or provider earnings.',
            'Internal admin notes, raw database IDs meant for staff, or API keys.',
            'Staff personal contact details not published for customers.',
            'Anything not returned by a tool or present in the current chat context — do not guess.',
        ];
    }

    /**
     * High-level summary of what tools allow (details match WhatsAppAiToolExecutor).
     *
     * @return list<string>
     */
    public static function defaultAllowedAccessLines(): array
    {
        return [
            'Read public business snapshot: company-facing info, sample service names, zone names, visiting-charge notes from settings (get_public_business_info).',
            'List and read WhatsApp booking requests tied to this chat phone only.',
            'Create/update a draft booking and submit it as pending human confirmation.',
            'Create/update a draft provider lead and submit it as pending human confirmation.',
            'Ask for human handoff context (support hours + public phone display).',
        ];
    }

    public static function defaultFlowMermaid(): string
    {
        return <<<'MERMAID'
flowchart TD
    subgraph Inbound["Inbound"]
        W[Meta webhook: messages] --> P[Save to whatsapp_messages]
        P --> J{AI enabled + Gemini key?}
        J -->|No| E1[Stop — no auto-reply]
        J -->|Yes| Q[Queue ProcessWhatsAppAiSupportJob]
    end

    subgraph Gate["Per-phone gate"]
        Q --> L[Lock + latest IN message check]
        L --> H{handled_by = AI?}
        H -->|No| E2[Stop — human has thread]
        H -->|Yes| U{User intent}
    end

    U -->|Talk to human| HR[Send work-hours / phone message]
    U -->|First hi + greeting buttons on| GB[Send welcome + quick buttons]
    U -->|Normal chat| G[Gemini + tools loop]

    subgraph Tools["Tools (server-enforced scope)"]
        G --> T1[Public business info]
        G --> T2[My bookings only]
        G --> T3[Draft / submit booking]
        G --> T4[Draft / submit provider lead]
        G --> T5[Human handoff info]
    end

    Tools --> R[Final reply text]
    R --> S[WhatsApp Cloud sendText]
    S --> O[Save OUT message]
MERMAID;
    }
}
