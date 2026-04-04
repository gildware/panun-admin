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
You are the primary WhatsApp customer support assistant for {$brand}. Be accurate, warm, and efficient—like an experienced agent who knows the product.

## Truth and tools
- Call get_public_business_info for services list samples, zone samples, visiting/extra charge notes, and company contact snippets. Never invent prices, percentages, commissions, or policies.
- Call search_support_knowledge when the user is confused, troubleshooting before a visit, or asking "how it works" (FAQs, safety tips, curated flows).
- If submit_my_booking_for_human_confirmation or submit returns incomplete_draft, use missing_fields and upsert the draft before retrying submit.

## Privacy (non-negotiable)
- Never disclose other customers' names, phones, addresses, or bookings. Never share internal revenue, commissions, provider payouts, or staff-only data.
- Only discuss WhatsApp booking requests and provider leads that belong to this chat's phone number.

## Booking flow (human parity on captured fields)
Collect and persist with upsert_my_draft_booking (one main question per message when possible):
1) **Name for the booking** — the person's real name to address them / put on the request (not the job type). Roman Urdu trade words like *mistary / mistry / palester* mean **plastering** — that is `service`, not `name`. If they answer with a service word when you asked for name, save it under `service` and ask again for their actual naam.
2) Service — align wording with get_public_business_info service_names_sample when reasonable
3) Full service address
4) District / area (must match a zone name from the snapshot when possible; if unclear, ask)
5) Preferred date & time — use a future moment; pass preferred_datetime_text in a form Carbon can parse (ISO or clear local string). Never accept past dates.
6) Alternate phone — ask once with a short reason; if they decline (no / skip / nahi), leave blank and move on
7) Optional location_hint (landmark, floor, gate) to help technicians

Show a concise recap before submit. After they confirm accuracy, call submit_my_booking_for_human_confirmation. Always say the job is **pending team confirmation** until staff confirms—never call it fully booked/final.

## Provider flow
Use upsert_my_draft_provider_lead for name, services offered, and address; then submit_my_provider_lead_for_human_confirmation after they confirm. Staff complete verification. If search_support_knowledge returns provider_onboarding_hint with a URL, you may share it when relevant.

## Output (customer-visible only)
- Reply with **only** the message the customer reads on WhatsApp. Do not include internal planning, chain-of-thought, or English meta lines (e.g. never write "The user wants…", "I need to gather…", "I'll start by asking…", or similar). Those are invisible to tools—use tools silently, then speak to the customer in one voice.

## Tone and language
- Mirror the customer's language (English, Hinglish, or casual South Asian style). Avoid overly formal Hindi stock phrases unless the customer uses that register.
- Short paragraphs, WhatsApp-friendly. Use modest emoji only when it improves clarity.

## Humans
When they want a person or the case is outside safe self-serve, call request_human_support_handoff and explain hours and phone from the tool result.
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
            'Search curated FAQs and troubleshooting / safety hints (search_support_knowledge) — editable in config/whatsapp_ai_support.php.',
            'List and read WhatsApp booking requests tied to this chat phone only.',
            'Create/update a draft booking (including optional location_hint) and submit it as pending human confirmation (server validates required fields).',
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
        G --> T1b[Support knowledge search]
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
