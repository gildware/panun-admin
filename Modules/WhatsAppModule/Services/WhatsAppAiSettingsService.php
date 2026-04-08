<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppAiSetting;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class WhatsAppAiSettingsService
{
    public function __construct(
        protected WhatsAppSupportWorkHours $workHours,
        protected WhatsAppAiRuntimeResolver $runtimeResolver,
    ) {}

    public function settings(): WhatsAppAiSetting
    {
        return WhatsAppAiSetting::singleton();
    }

    /**
     * Values used in customer templates and exposed to the model via get_public_business_info.
     * Empty DB fields fall back to .env / business settings where noted.
     *
     * @return array{
     *     schedule: string,
     *     phone: string,
     *     brand: string,
     *     email: string,
     *     website: string,
     *     address: string,
     *     tagline: string,
     *     provider_onboarding: string,
     * }
     */
    public function resolvedMessagePlaceholders(): array
    {
        $row = $this->settings();

        $schedule = $this->workHours->humanReadableSchedule();
        $phone = $this->runtimeResolver->supportPhoneDisplay();

        $brand = trim((string) $row->placeholder_brand);
        if ($brand === '') {
            $brand = WhatsAppAiPromptBuilder::resolveBrandName();
        }

        $email = trim((string) $row->placeholder_email);
        if ($email === '') {
            $email = $this->businessSnippet('email', 'business_information');
        }
        if ($email === '') {
            $email = trim((string) config('whatsappmodule.placeholder_default_email', ''));
        }

        $website = trim((string) $row->placeholder_website);
        if ($website === '') {
            $website = $this->businessSnippet('web_url', 'business_information')
                ?: $this->businessSnippet('website', 'business_information');
        }
        if ($website === '') {
            $website = trim((string) config('whatsappmodule.placeholder_default_website', ''));
        }

        $address = trim((string) $row->placeholder_address);
        if ($address === '') {
            $address = $this->businessSnippet('address', 'business_information');
        }
        if ($address === '') {
            $address = trim((string) config('whatsappmodule.placeholder_default_address', ''));
        }

        $tagline = trim((string) $row->placeholder_tagline);
        if ($tagline === '') {
            $tagline = trim((string) config('whatsappmodule.placeholder_default_tagline', ''));
        }

        $providerOnboarding = trim((string) $row->placeholder_provider_onboarding);
        if ($providerOnboarding === '') {
            $providerOnboarding = trim((string) config('whatsapp_ai_support.provider_onboarding_form_url', ''));
        }

        return [
            'schedule' => $schedule,
            'phone' => $phone,
            'brand' => $brand,
            'email' => $email,
            'website' => $website,
            'address' => $address,
            'tagline' => $tagline,
            'provider_onboarding' => $providerOnboarding,
        ];
    }

    /**
     * Optional placeholder overrides (brand, address, …). Support days/hours/phone are edited separately in the view.
     *
     * @return list<array{token: string, meaning_key: string, default_value: string, override_display: string, effective_value: string, is_overridden: bool, field_key: string, input_type: 'text'|'textarea', raw_value: string}>
     */
    public function businessConfigurationViewRows(): array
    {
        $s = $this->settings();
        $resolved = $this->resolvedMessagePlaceholders();

        $trimOrEmpty = static fn (?string $v): string => ($v !== null && trim((string) $v) !== '') ? trim((string) $v) : '';

        $rows = [];

        $phMap = [
            'brand' => ['meaning' => 'whatsapp_ai.placeholder_brand', 'field' => 'placeholder_brand', 'textarea' => false],
            'email' => ['meaning' => 'whatsapp_ai.placeholder_email', 'field' => 'placeholder_email', 'textarea' => false],
            'website' => ['meaning' => 'whatsapp_ai.placeholder_website', 'field' => 'placeholder_website', 'textarea' => false],
            'address' => ['meaning' => 'whatsapp_ai.placeholder_address', 'field' => 'placeholder_address', 'textarea' => true],
            'tagline' => ['meaning' => 'whatsapp_ai.placeholder_tagline', 'field' => 'placeholder_tagline', 'textarea' => false],
            'provider_onboarding' => ['meaning' => 'whatsapp_ai.placeholder_provider_onboarding', 'field' => 'placeholder_provider_onboarding', 'textarea' => false],
        ];

        foreach ($phMap as $key => $meta) {
            $raw = match ($key) {
                'brand' => $s->placeholder_brand,
                'email' => $s->placeholder_email,
                'website' => $s->placeholder_website,
                'address' => $s->placeholder_address,
                'tagline' => $s->placeholder_tagline,
                'provider_onboarding' => $s->placeholder_provider_onboarding,
                default => null,
            };
            $od = $trimOrEmpty($raw);
            $auto = $this->placeholderAutoValueForBusinessTable($key);
            $eff = $resolved[$key] ?? '';
            $fk = $meta['field'];
            $rows[] = [
                'token' => '{'.$key.'}',
                'meaning_key' => $meta['meaning'],
                'default_value' => $auto !== '' ? $auto : '—',
                'override_display' => $od,
                'effective_value' => $eff !== '' ? $eff : '—',
                'is_overridden' => $od !== '',
                'field_key' => $fk,
                'input_type' => $meta['textarea'] ? 'textarea' : 'text',
                'raw_value' => (string) ($raw ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * Value used when the placeholder field is left empty (same logic as resolvedMessagePlaceholders).
     */
    private function placeholderAutoValueForBusinessTable(string $key): string
    {
        return match ($key) {
            'brand' => WhatsAppAiPromptBuilder::resolveBrandName(),
            'email' => $this->businessSnippetOrConfigDefault('email', 'placeholder_default_email'),
            'website' => $this->businessSnippet('web_url', 'business_information')
                ?: $this->businessSnippet('website', 'business_information')
                ?: trim((string) config('whatsappmodule.placeholder_default_website', '')),
            'address' => $this->businessSnippetOrConfigDefault('address', 'placeholder_default_address'),
            'tagline' => trim((string) config('whatsappmodule.placeholder_default_tagline', '')),
            'provider_onboarding' => trim((string) config('whatsapp_ai_support.provider_onboarding_form_url', '')),
            default => '',
        };
    }

    private function businessSnippetOrConfigDefault(string $businessKey, string $configKey): string
    {
        $fromBusiness = $this->businessSnippet($businessKey, 'business_information');

        return $fromBusiness !== '' ? $fromBusiness : trim((string) config('whatsappmodule.'.$configKey, ''));
    }

    /**
     * Merge admin-written templates.
     * Core: {schedule}, {phone}, {brand}, {email}, {website}, {address}, {tagline}, {provider_onboarding}.
     * Greeting (when phone passed): {customer_name}, {customer_name_lead_in} from {@see WhatsAppUser} if stored.
     */
    public function mergeCustomerMessagePlaceholders(string $template, ?string $phone = null): string
    {
        $template = str_replace(['{custom_1}', '{custom_2}'], '', $template);
        $p = $this->resolvedMessagePlaceholders();
        $search = [
            '{schedule}', '{phone}', '{brand}', '{email}', '{website}', '{address}',
            '{tagline}', '{provider_onboarding}',
        ];
        $replace = [
            $p['schedule'], $p['phone'], $p['brand'], $p['email'], $p['website'], $p['address'],
            $p['tagline'], $p['provider_onboarding'],
        ];
        $out = str_replace($search, $replace, $template);

        $customerName = '';
        $customerNameLeadIn = '';
        if ($phone !== null && $phone !== '') {
            $customerName = $this->customerFirstNameForPhone($phone);
            if ($customerName !== '') {
                $customerNameLeadIn = ', '.$customerName;
            }
        }
        $out = str_replace(['{customer_name}', '{customer_name_lead_in}'], [$customerName, $customerNameLeadIn], $out);

        return trim($out);
    }

    public function defaultGreetingMessageTemplate(): string
    {
        return "✨ Assalam-u-Alaikum & welcome to Panun Kaergar! 🏠\n\n"
            ."I'm Kaera, your AI assistant 🤖\n"
            ."We provide trusted and reliable home services across Kashmir — from electricians ⚡, plumbers 🔧 and carpenters 🪚 to appliance repair 🛠️, cleaning 🧹 and more.\n\n"
            ."I can help you:\n"
            ."📅 Book a home service\n"
            ."🛠️ Troubleshoot an issue before booking\n"
            ."🤝 Guide you if you want to work with Panun Kaergar\n\n"
            .'Please choose an option below to get started 👇';
    }

    /**
     * Default greeting quick-reply labels (≤20 chars each — WhatsApp session button limit).
     *
     * @return list<string>
     */
    private static function defaultGreetingQuickReplyTexts(): array
    {
        return ['🏠 Book home service', '🛠️ Troubleshoot help', '🤝 Join Panun Kaergar'];
    }

    /**
     * @return list<string>
     */
    private static function legacyDefaultGreetingQuickReplyTexts(): array
    {
        return ['Book a service', 'Join as provider', 'Talk to a person'];
    }

    /**
     * Session meta for the first-message greeting when DB has no button JSON.
     *
     * @return list<array{type: string, text: string}>
     */
    public function defaultGreetingMetaButtons(): array
    {
        $out = [];
        foreach (self::defaultGreetingQuickReplyTexts() as $text) {
            $out[] = ['type' => 'QUICK_REPLY', 'text' => $text];
        }

        return $out;
    }

    /**
     * Quick-reply payload ids for greeting session buttons. Matches {@see WhatsAppSessionInteractiveSequence::send()} order.
     *
     * @param  array<int, array<string, mixed>>  $meta
     * @return list<string>
     */
    public function greetingQuickReplyPayloadIdsForMeta(array $meta): array
    {
        $titles = $this->normalizedQuickReplyTitlesFromMeta($meta);
        $n = count($titles);
        if ($n === 0) {
            return [];
        }
        if ($n === 3) {
            if ($titles === self::normalizedGreetingTitleTriplet(self::defaultGreetingQuickReplyTexts())) {
                return ['act_book', 'act_troubleshoot', 'act_provider'];
            }
            if ($titles === self::normalizedGreetingTitleTriplet(self::legacyDefaultGreetingQuickReplyTexts())) {
                return ['act_book', 'act_provider', 'act_human'];
            }

            return ['act_book', 'act_provider', 'act_human'];
        }
        if ($n === 2) {
            return ['act_book', 'act_provider'];
        }

        return ['act_book'];
    }

    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    private static function normalizedGreetingTitleTriplet(array $texts): array
    {
        $out = [];
        foreach (array_slice($texts, 0, 3) as $t) {
            $out[] = mb_substr(trim((string) $t), 0, 20);
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $meta
     * @return list<string>
     */
    private function normalizedQuickReplyTitlesFromMeta(array $meta): array
    {
        $out = [];
        foreach ($meta as $b) {
            if (strtoupper((string) ($b['type'] ?? '')) !== 'QUICK_REPLY') {
                continue;
            }
            $out[] = mb_substr(trim((string) ($b['text'] ?? '')), 0, 20);
            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    public function resolvedGreetingMessage(?string $phone = null): string
    {
        $raw = trim((string) $this->settings()->db_greeting_message);
        if ($raw !== '') {
            return $this->mergeCustomerMessagePlaceholders($raw, $phone);
        }

        return $this->mergeCustomerMessagePlaceholders($this->defaultGreetingMessageTemplate(), $phone);
    }

    /**
     * First word of the stored WhatsApp profile name, for a friendly greeting.
     */
    private function customerFirstNameForPhone(string $phone): string
    {
        if ($phone === '') {
            return '';
        }
        $u = WhatsAppUser::query()->where('phone', $phone)->first(['name']);
        if (!$u) {
            return '';
        }
        $n = trim((string) $u->name);
        if ($n === '') {
            return '';
        }
        $parts = preg_split('/\s+/u', $n, -1, PREG_SPLIT_NO_EMPTY);

        return $parts !== [] ? (string) $parts[0] : $n;
    }

    /**
     * Greeting quick-reply rows for the admin form (add/remove, max 3). Empty DB uses built-in defaults.
     *
     * @return list<array{title: string, action: string}>
     */
    public function greetingButtonsForEdit(): array
    {
        $defaults = [
            ['title' => '🏠 Book home service', 'action' => 'book'],
            ['title' => '🛠️ Troubleshoot help', 'action' => 'troubleshoot'],
            ['title' => '🤝 Join Panun Kaergar', 'action' => 'provider'],
        ];

        $j = $this->settings()->db_greeting_buttons_json;
        if (!is_array($j) || $j === []) {
            return $defaults;
        }

        $valid = ['book', 'troubleshoot', 'provider', 'human'];
        $out = [];
        foreach ($j as $row) {
            if (!is_array($row)) {
                continue;
            }
            $action = (string) ($row['action'] ?? '');
            if (!in_array($action, $valid, true)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = ['title' => $title, 'action' => $action];
            if (count($out) >= 3) {
                break;
            }
        }

        return $out === [] ? $defaults : $out;
    }

    /**
     * @deprecated Use greetingButtonsForEdit()
     *
     * @return list<array{title: string, action: string}>
     */
    public function greetingButtonRowsForForm(): array
    {
        return $this->greetingButtonsForEdit();
    }

    /**
     * Quick-reply buttons sent with the first-message greeting (WhatsApp allows 1–3).
     *
     * @return list<array{id: string, title: string}>
     */
    public function resolvedGreetingButtonsForWhatsApp(): array
    {
        $map = [
            'book' => 'act_book',
            'troubleshoot' => 'act_troubleshoot',
            'provider' => 'act_provider',
            'human' => 'act_human',
        ];

        $rows = $this->greetingButtonsForEdit();
        $out = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $action = (string) ($row['action'] ?? '');
            if ($title === '' || !isset($map[$action])) {
                continue;
            }
            $out[] = [
                'id' => $map[$action],
                'title' => mb_substr($title, 0, 20),
            ];
            if (count($out) >= 3) {
                break;
            }
        }

        if ($out !== []) {
            return $out;
        }

        foreach ($map as $action => $id) {
            $out[] = [
                'id' => $id,
                'title' => match ($action) {
                    'book' => '🏠 Book home service',
                    'troubleshoot' => '🛠️ Troubleshoot help',
                    'provider' => '🤝 Join Panun Kaergar',
                    'human' => 'Talk to a person',
                    default => 'Continue',
                },
            ];
        }

        return array_slice($out, 0, 3);
    }

    /**
     * @param  array<string, mixed>  $input  Request input (greeting_buttons[] or legacy greeting_btn_*)
     * @return array<int, array{action: string, title: string}>|null  null = store NULL in DB (built-in defaults)
     */
    public function greetingButtonsJsonFromAdminInput(array $input): ?array
    {
        $valid = ['book', 'troubleshoot', 'provider', 'human'];
        $seen = [];
        $out = [];

        $rows = $input['greeting_buttons'] ?? null;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $title = trim((string) ($row['title'] ?? ''));
                $action = (string) ($row['action'] ?? 'book');
                if ($title === '') {
                    continue;
                }
                if (!in_array($action, $valid, true)) {
                    continue;
                }
                if (isset($seen[$action])) {
                    continue;
                }
                $seen[$action] = true;
                $out[] = ['action' => $action, 'title' => mb_substr($title, 0, 20)];
                if (count($out) >= 3) {
                    break;
                }
            }

            return $out === [] ? null : $out;
        }

        for ($i = 1; $i <= 3; $i++) {
            $title = trim((string) ($input['greeting_btn_'.$i.'_title'] ?? ''));
            $action = (string) ($input['greeting_btn_'.$i.'_action'] ?? 'book');
            if ($title === '') {
                continue;
            }
            if (!in_array($action, $valid, true)) {
                continue;
            }
            if (isset($seen[$action])) {
                continue;
            }
            $seen[$action] = true;
            $out[] = ['action' => $action, 'title' => mb_substr($title, 0, 20)];
            if (count($out) >= 3) {
                break;
            }
        }

        return $out === [] ? null : $out;
    }

    /**
     * Optional quick-reply titles for handoff / escalation (plain labels; IDs are generated when sending).
     *
     * @return array<int, array{title: string}>|null
     */
    public function simpleQuickReplyButtonsJsonFromAdminInput(array $input, string $prefix): ?array
    {
        $out = [];
        for ($i = 1; $i <= 3; $i++) {
            $title = trim((string) ($input[$prefix.'_btn_'.$i.'_title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = ['title' => mb_substr($title, 0, 20)];
            if (count($out) >= 3) {
                break;
            }
        }

        return $out === [] ? null : $out;
    }

    /**
     * @param  array<int, array{title?: string}>|null  $json
     * @return list<array{id: string, title: string}>
     */
    public function resolvedSimpleQuickReplyButtonsForWhatsApp(?array $json, string $idPrefix): array
    {
        if (!is_array($json) || $json === []) {
            return [];
        }
        $out = [];
        $n = 0;
        foreach ($json as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = mb_substr(trim((string) ($row['title'] ?? '')), 0, 20);
            if ($title === '') {
                continue;
            }
            $n++;
            $out[] = [
                'id' => $idPrefix.'_'.$n,
                'title' => $title,
            ];
            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<array{id: string, title: string}>
     */
    public function resolvedHandoffButtonsForWhatsApp(bool $inHours): array
    {
        $j = $inHours
            ? $this->settings()->db_handoff_in_buttons_json
            : $this->settings()->db_handoff_out_buttons_json;

        return $this->resolvedSimpleQuickReplyButtonsForWhatsApp(is_array($j) ? $j : null, $inHours ? 'handoff_in' : 'handoff_out');
    }

    /**
     * Sanitize interactive button payloads (e.g. from tool orchestrator).
     *
     * @param  array<int, mixed>  $buttons
     * @return list<array{id: string, title: string}>
     */
    public function normalizeWhatsAppInteractiveButtons(array $buttons): array
    {
        $out = [];
        foreach ($buttons as $b) {
            if (!is_array($b)) {
                continue;
            }
            $id = trim((string) ($b['id'] ?? ''));
            $title = mb_substr(trim((string) ($b['title'] ?? '')), 0, 20);
            if ($id === '' || $title === '') {
                continue;
            }
            $out[] = ['id' => $id, 'title' => $title];
            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    /**
     * Button rows for the admin UI (add/remove rows in the form; max 10). Empty DB → no rows.
     *
     * @return list<array{kind: string, text: string, url: string, phone: string}>
     */
    public function templateStyleButtonRowsForForm(string $which): array
    {
        $col = match ($which) {
            'greeting' => 'db_greeting_buttons_json',
            'handoff_in' => 'db_handoff_in_buttons_json',
            'handoff_out' => 'db_handoff_out_buttons_json',
            'non_text' => 'db_non_text_buttons_json',
            default => 'db_greeting_buttons_json',
        };
        $raw = $this->settings()->{$col};
        if (!is_array($raw) || $raw === []) {
            if ($which === 'non_text') {
                return $this->defaultNonTextButtonFormRows();
            }
            if ($which === 'greeting') {
                $rows = [];
                foreach ($this->defaultGreetingMetaButtons() as $b) {
                    $rows[] = [
                        'kind' => 'QUICK_REPLY',
                        'text' => (string) ($b['text'] ?? ''),
                        'url' => '',
                        'phone' => '',
                    ];
                }

                return $rows;
            }

            return [];
        }
        $rows = [];
        if (isset($raw[0]['type'])) {
            foreach ($raw as $i => $b) {
                if ($i >= 10 || !is_array($b)) {
                    break;
                }
                $t = strtoupper((string) ($b['type'] ?? ''));
                $kind = match ($t) {
                    'QUICK_REPLY' => 'QUICK_REPLY',
                    'URL' => 'URL',
                    'PHONE_NUMBER' => 'PHONE_NUMBER',
                    default => '',
                };
                $rows[] = [
                    'kind' => $kind,
                    'text' => (string) ($b['text'] ?? ''),
                    'url' => (string) ($b['url'] ?? ''),
                    'phone' => (string) ($b['phone_number'] ?? ''),
                ];
            }

            return $rows;
        }
        if (isset($raw[0]['action'])) {
            foreach ($raw as $b) {
                if (count($rows) >= 10 || !is_array($b)) {
                    break;
                }
                $title = trim((string) ($b['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $rows[] = ['kind' => 'QUICK_REPLY', 'text' => $title, 'url' => '', 'phone' => ''];
            }

            return $rows;
        }
        foreach ($raw as $b) {
            if (count($rows) >= 10 || !is_array($b)) {
                break;
            }
            $title = trim((string) ($b['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $rows[] = ['kind' => 'QUICK_REPLY', 'text' => $title, 'url' => '', 'phone' => ''];
        }

        return $rows;
    }

    /**
     * Meta-format button payloads (same as template BUTTONS component) for session sends.
     *
     * @return array<int, array<string, mixed>>
     */
    public function metaButtonsForContext(string $context): array
    {
        $col = match ($context) {
            'greeting' => 'db_greeting_buttons_json',
            'handoff_in' => 'db_handoff_in_buttons_json',
            'handoff_out' => 'db_handoff_out_buttons_json',
            'non_text' => 'db_non_text_buttons_json',
            default => 'db_greeting_buttons_json',
        };
        $raw = $this->settings()->{$col};
        if (!is_array($raw) || $raw === []) {
            if ($context === 'non_text') {
                return $this->defaultNonTextMetaButtons();
            }
            if ($context === 'greeting') {
                return $this->defaultGreetingMetaButtons();
            }

            return [];
        }

        return $this->normalizeDbJsonToMetaButtons($raw, $context);
    }

    /**
     * @param  array<string, mixed>  $requestAll
     * @return list<array{kind: string, text: string, url: string, phone: string}>
     */
    public function templateButtonRowsFromRequest(array $requestAll, string $prefix): array
    {
        $rows = $requestAll[$prefix] ?? null;
        if (!is_array($rows)) {
            return array_fill(0, 10, ['kind' => '', 'text' => '', 'url' => '', 'phone' => '']);
        }
        $out = [];
        foreach (array_values($rows) as $i => $r) {
            if ($i >= 10) {
                break;
            }
            if (!is_array($r)) {
                $out[] = ['kind' => '', 'text' => '', 'url' => '', 'phone' => ''];

                continue;
            }
            $out[] = [
                'kind' => strtoupper(trim((string) ($r['kind'] ?? ''))),
                'text' => trim((string) ($r['text'] ?? '')),
                'url' => trim((string) ($r['url'] ?? '')),
                'phone' => preg_replace('/\s+/', '', (string) ($r['phone'] ?? '')),
            ];
        }
        while (count($out) < 10) {
            $out[] = ['kind' => '', 'text' => '', 'url' => '', 'phone' => ''];
        }

        return $out;
    }

    /**
     * @param  list<array{kind: string, text: string, url: string, phone: string}>  $rows
     * @return list<array<string, string>>
     */
    public function filterRowsForTemplateValidator(array $rows): array
    {
        $validatorRows = [];
        foreach ($rows as $r) {
            $kind = $r['kind'] ?? '';
            $text = trim((string) ($r['text'] ?? ''));
            if ($kind === '' && $text === '') {
                continue;
            }
            $one = ['kind' => $kind, 'text' => $text];
            if (($r['url'] ?? '') !== '') {
                $one['url'] = (string) $r['url'];
            }
            if (($r['phone'] ?? '') !== '') {
                $one['phone'] = (string) $r['phone'];
            }
            $validatorRows[] = $one;
        }

        return $validatorRows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDbJsonToMetaButtons(?array $json, string $context): array
    {
        unset($context);
        if (!is_array($json) || $json === []) {
            return [];
        }
        if (isset($json[0]['type'])) {
            return $json;
        }
        if (isset($json[0]['action'])) {
            $out = [];
            foreach ($json as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $title = trim((string) ($row['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $out[] = ['type' => 'QUICK_REPLY', 'text' => mb_substr($title, 0, 25)];
            }

            return $out;
        }
        $out = [];
        foreach ($json as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = ['type' => 'QUICK_REPLY', 'text' => mb_substr($title, 0, 25)];
        }

        return $out;
    }

    private function businessSnippet(string $key, string $type): string
    {
        try {
            $cfg = business_config($key, $type);
            $v = $cfg?->live_values ?? null;
            if (is_string($v) || is_numeric($v)) {
                return trim((string) $v);
            }
        } catch (\Throwable) {
        }

        return '';
    }

    public function handoffMessageForCustomer(bool $inHours): string
    {
        $row = $this->settings();
        $raw = $inHours
            ? trim((string) $row->handoff_message_in_hours)
            : trim((string) $row->handoff_message_out_hours);
        if ($raw !== '') {
            return $this->mergeCustomerMessagePlaceholders($raw);
        }

        return $this->defaultHandoffMessageForCustomer($inHours);
    }

    /**
     * Reply when the customer sends image, audio, document, etc. (not plain text / not button reply).
     * Same placeholders as other customer templates. Buttons: see Message Configuration → Non-text inbound.
     */
    public function nonTextInboundMessageForCustomer(?string $phone = null): string
    {
        $raw = trim((string) $this->settings()->db_non_text_inbound_message);
        if ($raw !== '') {
            return $this->mergeCustomerMessagePlaceholders($raw, $phone);
        }

        return $this->mergeCustomerMessagePlaceholders($this->defaultNonTextInboundMessageTemplate(), $phone);
    }

    public function defaultNonTextInboundMessageTemplate(): string
    {
        return "⚠️ I'm unable to view images, listen to voice notes, or open files in this chat.\n\n"
            ."Please type your message here and I'll do my best to help 😊\n\n"
            .'If you need to share a photo, voice note, or file, please tap *Chat with Agent* so our team can review it for you — or use the call button below to speak with us directly 📞';
    }

    /**
     * Default session buttons: quick reply (chat with human) + optional call support when phone is valid E.164.
     *
     * @return list<array<string, mixed>>
     */
    public function defaultNonTextMetaButtons(): array
    {
        $out = [
            ['type' => 'QUICK_REPLY', 'text' => 'Chat with Agent'],
        ];
        $tel = $this->supportPhoneE164ForTemplateButtons();
        if ($tel !== '') {
            $out[] = ['type' => 'PHONE_NUMBER', 'text' => 'Call us Now', 'phone_number' => $tel];
        }

        return $out;
    }

    /**
     * @return list<array{kind: string, text: string, url: string, phone: string}>
     */
    public function defaultNonTextButtonFormRows(): array
    {
        $rows = [];
        foreach ($this->defaultNonTextMetaButtons() as $b) {
            $t = strtoupper((string) ($b['type'] ?? ''));
            if ($t === 'QUICK_REPLY') {
                $rows[] = [
                    'kind' => 'QUICK_REPLY',
                    'text' => (string) ($b['text'] ?? ''),
                    'url' => '',
                    'phone' => '',
                ];
            } elseif ($t === 'PHONE_NUMBER') {
                $rows[] = [
                    'kind' => 'PHONE_NUMBER',
                    'text' => (string) ($b['text'] ?? ''),
                    'url' => '',
                    'phone' => (string) ($b['phone_number'] ?? ''),
                ];
            }
        }

        return $rows;
    }

    /**
     * Support number from placeholders, normalized for template PHONE_NUMBER (E.164 with +).
     */
    private function supportPhoneE164ForTemplateButtons(): string
    {
        $p = trim($this->resolvedMessagePlaceholders()['phone'] ?? '');
        $p = preg_replace('/[^\d+]/', '', $p) ?? $p;
        if ($p !== '' && !str_starts_with($p, '+')) {
            $p = '+'.$p;
        }
        if ($p !== '' && preg_match('/^\+[1-9]\d{6,14}$/', $p)) {
            return $p;
        }

        return '';
    }

    public function defaultHandoffMessageForCustomer(bool $inHours): string
    {
        $p = $this->resolvedMessagePlaceholders();
        $schedule = $p['schedule'];
        $displayPhone = $p['phone'];

        if ($inHours) {
            $msg = "We're connecting you with our team.\n\n"
                . "Someone will pick up this chat during support hours ({$schedule}).\n";
            if ($displayPhone !== '') {
                $msg .= "\nYou can also call: {$displayPhone}";
            }

            return $msg;
        }

        $msg = "Our live team is currently outside support hours.\n\n"
            . "We're available {$schedule}.\n";
        if ($displayPhone !== '') {
            $msg .= "\nLeave your message here or call {$displayPhone} and we'll get back to you.";
        } else {
            $msg .= "\nLeave your message here and we'll reply when we're back.";
        }

        return $msg;
    }

    /**
     * Full system instruction sent to Gemini (DB overrides / addenda applied).
     */
    public function resolvedSystemPrompt(): string
    {
        $row = $this->settings();

        if ($row->use_full_custom_prompt && trim((string) $row->custom_system_prompt) !== '') {
            return trim((string) $row->custom_system_prompt);
        }

        $parts = [WhatsAppAiPromptBuilder::baseSystemPrompt()];

        if (trim((string) $row->assistant_persona) !== '') {
            $parts[] = "### Assistant identity, tone, and behaviour (admin-configured)\n" . trim((string) $row->assistant_persona);
        }

        if (trim((string) $row->allowed_policy) !== '') {
            $parts[] = "### Configured policy: allowed access / behaviour\n" . trim((string) $row->allowed_policy);
        }

        if (trim((string) $row->forbidden_policy) !== '') {
            $parts[] = "### Configured policy: forbidden access / behaviour\n" . trim((string) $row->forbidden_policy);
        }

        if (trim((string) $row->prompt_addendum) !== '') {
            $parts[] = "### Additional instructions (admin-configured)\n" . trim((string) $row->prompt_addendum);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Tool schemas sent to Gemini: code defaults merged with admin enable/disable and description overrides.
     *
     * @return list<array<string, mixed>>
     */
    public function mergedToolDeclarations(): array
    {
        $base = WhatsAppAiToolExecutor::functionDeclarations();
        $cfg = $this->settings()->tools_config;
        if (!is_array($cfg) || $cfg === []) {
            return $base;
        }

        $out = [];
        foreach ($base as $decl) {
            $name = (string) ($decl['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $entry = $cfg[$name] ?? [];
            $enabled = $entry['enabled'] ?? true;
            if ($enabled === false || $enabled === 0 || $enabled === '0') {
                continue;
            }
            $copy = $decl;
            $desc = $entry['description'] ?? null;
            if (is_string($desc) && trim($desc) !== '') {
                $copy['description'] = trim($desc);
            }
            $out[] = $copy;
        }

        return $out;
    }

    /**
     * @return list<array{name: string, default_description: string, enabled: bool, description_override: string}>
     */
    public function toolReferenceForAdmin(): array
    {
        $cfg = $this->settings()->tools_config ?? [];
        $out = [];
        foreach (WhatsAppAiToolExecutor::functionDeclarations() as $decl) {
            $name = (string) ($decl['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $c = is_array($cfg) ? ($cfg[$name] ?? []) : [];
            $enabled = $c['enabled'] ?? true;
            $enabled = !($enabled === false || $enabled === 0 || $enabled === '0');
            $ov = $c['description'] ?? null;

            $out[] = [
                'name' => $name,
                'default_description' => (string) ($decl['description'] ?? ''),
                'enabled' => $enabled,
                'description_override' => is_string($ov) ? $ov : '',
            ];
        }

        return $out;
    }

    /**
     * Rich Mermaid flowchart for the admin Visual flow tab — mirrors the real orchestrator
     * (guards → intent branches → Gemini ↔ tools loop) and lists enabled/disabled tools.
     */
    public function liveFlowMermaidFromSettings(): string
    {
        $runtime = $this->runtimeResolver->adminRuntimeStatus();
        $sum = $this->aiBehaviorSummary();

        $e = static function (string $s): string {
            $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $s) ?? $s;

            return str_replace(['"', '#', '[', ']', '(', ')'], ['\'', '', ' ', ' ', ' ', ' '], $s);
        };

        $promptHint = $sum['prompt_mode'] === 'full_custom'
            ? 'Full custom system prompt'
            : 'Layered: base + persona + access + addendum';

        $greetingOn = (bool) ($runtime['greeting_buttons'] ?? true);

        $enNames = array_map(static fn (array $t): string => $t['name'], $sum['enabled_tools']);
        $disNames = $sum['disabled_tool_names'];

        $lines = [
            'flowchart TD',
            '  subgraph INB["Inbound pipeline"]',
            '    W[Meta webhook] --> S[Save IN to DB]',
            '    S --> G1{AI support enabled?}',
            '  end',
            '  G1 -->|No| E1[Skip — AI disabled]',
            '  G1 -->|Yes| G2{Gemini API key set?}',
            '  G2 -->|No| E2[Skip — no model]',
            '  G2 -->|Yes| G3{Trigger is latest IN for phone?}',
            '  G3 -->|No| E3[Skip — not latest message]',
            '  G3 -->|Yes| G4{handled_by empty or AI?}',
            '  G4 -->|No staff thread| E4[Skip — human owns chat]',
            '  G4 -->|Yes| NT{Text or interactive?}',
            '  NT -->|No| NTM[Non-text — template + session buttons]',
            '  NTM --> OUT',
            '  NT -->|Yes| I1{Human handoff intent?}',
            '  I1 -->|Yes| HF[Send handoff / hours text]',
        ];

        if ($greetingOn) {
            $lines[] = '  I1 -->|No| I2{Greeting-only first message?}';
            $lines[] = '  I2 -->|Yes| GW[Welcome + quick buttons]';
            $lines[] = '  I2 -->|No| MAIN[Main AI path]';
        } else {
            $lines[] = '  I1 -->|No| MAIN[Main AI path]';
        }

        $lines[] = '  MAIN --> CTX[Load conversation from DB]';
        $lines[] = '  CTX --> NC{Usable text for Gemini context?}';
        $lines[] = '  NC -->|No| E5[Skip — no usable context]';
        $lines[] = '  NC -->|Yes| PH["'.$e($promptHint).'"]';
        $lines[] = '  PH --> APP[Append session context if any]';

        if ($enNames === []) {
            $lines[] = '  APP --> GEM[Gemini — no tools enabled]';
            $lines[] = '  GEM --> TXT[Final assistant text]';
            $lines[] = '  TXT --> OUT[Persist OUT + WhatsApp send]';
        } else {
            $lines[] = '  subgraph LOOP["Gemini loop — up to 8 turns"]';
            $lines[] = '    direction TB';
            $lines[] = '    GEM[Gemini with tool declarations]';
            $lines[] = '    FC{Model returns function calls?}';
            $lines[] = '    RUN[Run tool on server — scoped data]';
            $lines[] = '    TXT2[Final assistant text]';
            $lines[] = '    GEM --> FC';
            $lines[] = '    FC -->|Yes| RUN';
            $lines[] = '    RUN --> GEM';
            $lines[] = '    FC -->|No| TXT2';
            $lines[] = '  end';
            $lines[] = '  APP --> GEM';
            $lines[] = '  TXT2 --> OUT[Persist OUT + WhatsApp send]';
        }

        $lines[] = '  HF --> OUT';
        if ($greetingOn) {
            $lines[] = '  GW --> OUT';
        }

        if ($enNames !== []) {
            $lines[] = '  subgraph TON["Tools enabled in admin ('.count($enNames).')"]';
            $lines[] = '    direction TB';
            $ti = 0;
            foreach (array_slice($enNames, 0, 28) as $name) {
                $lines[] = '    te'.$ti++.'["'.$e(mb_substr($name, 0, 44)).'"]';
            }
            if (count($enNames) > 28) {
                $lines[] = '    tem["... '.(count($enNames) - 28).' more"]';
            }
            $lines[] = '  end';
            $lines[] = '  RUN -.-> te0';
        }

        if ($disNames !== []) {
            $lines[] = '  subgraph TOF["Tools disabled in admin ('.count($disNames).')"]';
            $lines[] = '    direction TB';
            $di = 0;
            foreach (array_slice($disNames, 0, 20) as $name) {
                $lines[] = '    td'.$di++.'["'.$e(mb_substr($name, 0, 44)).'"]';
            }
            if (count($disNames) > 20) {
                $lines[] = '    tdm["... '.(count($disNames) - 20).' more"]';
            }
            $lines[] = '  end';
        }

        return implode("\n", $lines);
    }

    /**
     * Structured snapshot for the admin “Summary” tab (effective prompt shape + tools).
     *
     * @return array{
     *     prompt_mode: 'full_custom'|'assembled',
     *     assembled_sections: array{persona: bool, allowed_policy: bool, forbidden_policy: bool, addendum: bool},
     *     enabled_tools: list<array{name: string, description: string, default_description: string, description_override: string}>,
     *     disabled_tool_names: list<string>,
     * }
     */
    public function aiBehaviorSummary(): array
    {
        $row = $this->settings();
        $fullCustom = $row->use_full_custom_prompt && trim((string) $row->custom_system_prompt) !== '';

        $tools = $this->toolReferenceForAdmin();
        $enabled = array_values(array_filter($tools, static fn (array $t): bool => $t['enabled']));
        $disabled = array_values(array_filter($tools, static fn (array $t): bool => ! $t['enabled']));

        $desc = static function (array $t): string {
            $o = trim((string) ($t['description_override'] ?? ''));

            return $o !== '' ? $o : (string) ($t['default_description'] ?? '');
        };

        return [
            'prompt_mode' => $fullCustom ? 'full_custom' : 'assembled',
            'assembled_sections' => [
                'persona' => trim((string) $row->assistant_persona) !== '',
                'allowed_policy' => trim((string) $row->allowed_policy) !== '',
                'forbidden_policy' => trim((string) $row->forbidden_policy) !== '',
                'addendum' => trim((string) $row->prompt_addendum) !== '',
            ],
            'enabled_tools' => array_map(static fn (array $t): array => [
                'name' => $t['name'],
                'description' => $desc($t),
                'default_description' => (string) ($t['default_description'] ?? ''),
                'description_override' => trim((string) ($t['description_override'] ?? '')),
            ], $enabled),
            'disabled_tool_names' => array_map(static fn (array $t): string => $t['name'], $disabled),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminRuntimeStatus(): array
    {
        $r = $this->runtimeResolver->adminRuntimeStatus();
        $r['support_hours'] = $this->workHours->humanReadableSchedule();

        return $r;
    }

    /**
     * Effective weekdays (1=Mon … 7=Sun) for the Business Configuration form.
     *
     * @return list<int>
     */
    public function effectiveSupportWorkDays(): array
    {
        return $this->runtimeResolver->supportWorkDays();
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    public function toolReference(): array
    {
        $out = [];
        foreach ($this->mergedToolDeclarations() as $decl) {
            $out[] = [
                'name' => (string) ($decl['name'] ?? ''),
                'description' => (string) ($decl['description'] ?? ''),
            ];
        }

        return $out;
    }
}
