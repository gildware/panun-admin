<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderRegistrationDraft;
use Modules\ProviderManagement\Entities\WhatsAppProviderGroup;
use Modules\ProviderManagement\Entities\WhatsAppProviderGroupContact;
use Modules\WhatsAppModule\Entities\ProviderLead as WhatsAppProviderLead;
use Throwable;

class WhatsAppProviderMatchService
{
    /**
     * Soft pastel backgrounds for match-list rows (unique per group).
     *
     * @var list<string>
     */
    private const GROUP_COLOR_PALETTE = [
        '#DBEAFE', '#DCFCE7', '#FEF3C7', '#FCE7F3', '#E0E7FF',
        '#FFEDD5', '#CCFBF1', '#F3E8FF', '#FEE2E2', '#E0F2FE',
        '#ECFCCB', '#FDE68A', '#FBCFE8', '#C7D2FE', '#BBF7D0',
        '#FECACA', '#A5F3FC', '#DDD6FE', '#FED7AA', '#BFDBFE',
        '#F5D0FE', '#99F6E4', '#FDE047', '#FDA4AF', '#A5B4FC',
        '#86EFAC', '#FDBA74', '#67E8F9', '#E9D5FF', '#FCD34D',
    ];

    public function sourcePath(): string
    {
        return dirname(__DIR__).'/Data/whatsapp_provider_numbers.json';
    }

    public function usesDatabase(): bool
    {
        try {
            return Schema::hasTable('whatsapp_provider_groups')
                && Schema::hasTable('whatsapp_provider_group_contacts');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Import groups from a JSON file (bundled seed or recovered live backup).
     *
     * @return array{ok: bool, message: string, groups: int, contacts: int}
     */
    public function importFromJsonFile(string $path, bool $replaceExisting = false): array
    {
        if (! $this->usesDatabase()) {
            return ['ok' => false, 'message' => 'WhatsApp_group_tables_missing', 'groups' => 0, 'contacts' => 0];
        }

        if (! is_file($path)) {
            return ['ok' => false, 'message' => 'WhatsApp_group_import_file_not_found', 'groups' => 0, 'contacts' => 0];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return ['ok' => false, 'message' => 'WhatsApp_group_import_file_invalid', 'groups' => 0, 'contacts' => 0];
        }

        $parsed = $this->parseJsonPayload($decoded);
        if ($parsed['groups'] === []) {
            return ['ok' => false, 'message' => 'WhatsApp_group_import_file_empty', 'groups' => 0, 'contacts' => 0];
        }

        $contactCount = 0;
        foreach ($parsed['groups'] as $contacts) {
            $contactCount += count($contacts);
        }

        try {
            DB::transaction(function () use ($parsed, $replaceExisting) {
                if ($replaceExisting) {
                    WhatsAppProviderGroupContact::query()->delete();
                    WhatsAppProviderGroup::query()->delete();
                }

                $existing = WhatsAppProviderGroup::query()->get()->keyBy(
                    fn (WhatsAppProviderGroup $group) => $this->normalizeGroupName($group->name)
                );

                $sort = (int) (WhatsAppProviderGroup::query()->max('sort_order') ?? -1) + 1;
                foreach ($parsed['groups'] as $groupName => $contacts) {
                    $key = $this->normalizeGroupName($groupName);
                    $color = $parsed['colors'][$groupName] ?? null;
                    $model = $existing->get($key);

                    if ($model) {
                        if ($color !== null) {
                            $model->color = $color;
                        }
                        $model->save();
                        $this->syncGroupContacts($model, $contacts);
                        continue;
                    }

                    $model = WhatsAppProviderGroup::query()->create([
                        'name' => $groupName,
                        'color' => $color ?? $this->nextUniqueColor(
                            WhatsAppProviderGroup::query()->pluck('color', 'name')->all()
                        ),
                        'sort_order' => $sort++,
                    ]);
                    $existing->put($key, $model);
                    $this->syncGroupContacts($model, $contacts);
                }
            });
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Failed_to_save_WhatsApp_group', 'groups' => 0, 'contacts' => 0];
        }

        return [
            'ok' => true,
            'message' => 'WhatsApp_groups_imported_successfully',
            'groups' => count($parsed['groups']),
            'contacts' => $contactCount,
        ];
    }

    /**
     * Parse pasted/typed phone input into unique last-10 digit numbers (order preserved).
     * Accepts newlines/commas/semicolons as separators, and keeps spaced numbers like +91 88250 59511.
     *
     * @return list<string>
     */
    public function parsePhones(string $raw): array
    {
        $out = [];
        $seen = [];

        // Split first so newline-separated numbers never merge into one match.
        $chunks = preg_split('/[\n\r,;|]+/', $raw) ?: [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            // Within a chunk, allow spaced formats like "+91 88250 59511" (spaces/tabs only, not newlines).
            if (preg_match_all('/\+?\d[\d \t\-()]{6,}\d/', $chunk, $matches) && ! empty($matches[0])) {
                foreach ($matches[0] as $part) {
                    $this->pushPhone($part, $out, $seen);
                }
                continue;
            }

            $this->pushPhone($chunk, $out, $seen);
        }

        return $out;
    }

    /**
     * Normalize contact rows from the admin form (name optional, phone required).
     *
     * @param  array<int, mixed>  $rawContacts
     * @return list<array{phone: string, name: string}>
     */
    public function parseContacts(array $rawContacts): array
    {
        $out = [];
        $seen = [];

        foreach ($rawContacts as $row) {
            if (! is_array($row)) {
                $digits = $this->last10($row);
                if ($digits === null || isset($seen[$digits])) {
                    continue;
                }
                $seen[$digits] = true;
                $out[] = ['phone' => $digits, 'name' => ''];
                continue;
            }

            $digits = $this->last10($row['phone'] ?? $row['number'] ?? null);
            if ($digits === null) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

            if (isset($seen[$digits])) {
                $index = $seen[$digits];
                if ($name !== '' && ($out[$index]['name'] ?? '') === '') {
                    $out[$index]['name'] = $name;
                }
                continue;
            }

            $seen[$digits] = count($out);
            $out[] = ['phone' => $digits, 'name' => $name];
        }

        return $out;
    }

    /**
     * @param  list<string>  $out
     * @param  array<string, true>  $seen
     */
    private function pushPhone(mixed $value, array &$out, array &$seen): void
    {
        $digits = $this->last10($value);
        if ($digits === null || isset($seen[$digits])) {
            return;
        }
        $seen[$digits] = true;
        $out[] = $digits;
    }

    /**
     * Lookup arbitrary phone numbers against providers, onboarding, and leads.
     *
     * @param  list<string>  $phones
     * @return array{results: list<array<string, mixed>>, stats: array<string, int>, phones: list<string>}
     */
    public function lookup(array $phones): array
    {
        $phones = array_values(array_unique(array_filter(array_map(fn ($p) => $this->last10($p), $phones))));
        if ($phones === []) {
            return [
                'results' => [],
                'stats' => ['searched' => 0, 'matched' => 0, 'none' => 0],
                'phones' => [],
            ];
        }

        $providerByPhone = $this->indexProviders($phones);
        $leadByPhone = $this->indexLeads($phones);
        $waByPhone = $this->indexWhatsAppLeads($phones);
        $draftByPhone = $this->indexDrafts($phones);

        $results = [];
        foreach ($phones as $phone) {
            $providers = array_map(
                fn (array $p) => $this->enrichProvider($p),
                $providerByPhone[$phone] ?? []
            );
            $list = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 1));
            $onboarding = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 2));
            $other = array_values(array_filter($providers, fn ($p) => ! in_array((int) $p['is_approved'], [1, 2], true)));

            $leads = array_merge(
                $leadByPhone[$phone] ?? [],
                $waByPhone[$phone] ?? [],
                $draftByPhone[$phone] ?? []
            );

            $hasAny = $list !== [] || $onboarding !== [] || $other !== [] || $leads !== [];

            $results[] = [
                'phone' => $phone,
                'provider_list' => $list,
                'onboarding' => $onboarding,
                'other_providers' => $other,
                'leads' => $leads,
                'in_list' => $list !== [],
                'in_onboarding' => $onboarding !== [],
                'in_lead' => $leads !== [],
                'has_any' => $hasAny,
            ];
        }

        return [
            'results' => $results,
            'stats' => [
                'searched' => count($results),
                'matched' => count(array_filter($results, fn ($r) => $r['has_any'])),
                'none' => count(array_filter($results, fn ($r) => ! $r['has_any'])),
            ],
            'phones' => $phones,
        ];
    }

    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   stats: array<string, int>,
     *   categories: array<int, string>,
     *   colors: array<string, string>
     * }
     */
    public function build(): array
    {
        $source = $this->loadSourceData();
        $byCategory = $source['groups'];
        $colors = $source['colors'];
        $phoneSet = [];
        foreach ($byCategory as $contacts) {
            foreach ($contacts as $contact) {
                $phoneSet[$contact['phone']] = true;
            }
        }

        $providerByPhone = $this->indexProviders(array_keys($phoneSet));
        $leadByPhone = $this->indexLeads(array_keys($phoneSet));
        $waByPhone = $this->indexWhatsAppLeads(array_keys($phoneSet));
        $draftByPhone = $this->indexDrafts(array_keys($phoneSet));

        $rows = [];
        foreach ($byCategory as $category => $contacts) {
            $categoryRows = [];
            $groupColor = $colors[$category] ?? $this->fallbackColorForGroup($category);
            foreach ($contacts as $contact) {
                $phone = $contact['phone'];
                $providers = $providerByPhone[$phone] ?? [];
                $list = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 1));
                $onboarding = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 2));
                $other = array_values(array_filter($providers, fn ($p) => ! in_array((int) $p['is_approved'], [1, 2], true)));

                $leads = array_merge(
                    $leadByPhone[$phone] ?? [],
                    $waByPhone[$phone] ?? [],
                    $draftByPhone[$phone] ?? []
                );

                $inList = $list !== [];
                $inOnboarding = $onboarding !== [];
                $inLead = $leads !== [];
                $hasAny = $inList || $inOnboarding || $inLead || $other !== [];

                $categoryRows[] = [
                    'phone' => $phone,
                    'contact_name' => $contact['name'],
                    'category' => $category,
                    'color' => $groupColor,
                    'provider_profile' => array_merge($list, $other),
                    'onboarding' => $onboarding,
                    'provider_lead' => $leads,
                    'in_list' => $inList,
                    'in_onboarding' => $inOnboarding,
                    'in_lead' => $inLead,
                    'has_any' => $hasAny,
                ];
            }

            usort($categoryRows, function (array $a, array $b) {
                if ($a['has_any'] !== $b['has_any']) {
                    return $a['has_any'] ? -1 : 1;
                }

                return strcmp($a['phone'], $b['phone']);
            });

            array_push($rows, ...$categoryRows);
        }

        $stats = [
            'unique_phones' => count($phoneSet),
            'rows' => count($rows),
            'matched' => count(array_filter($rows, fn ($r) => $r['has_any'])),
            'in_list' => count(array_filter($rows, fn ($r) => $r['in_list'])),
            'in_onboarding' => count(array_filter($rows, fn ($r) => $r['in_onboarding'])),
            'in_lead' => count(array_filter($rows, fn ($r) => $r['in_lead'])),
            'none' => count(array_filter($rows, fn ($r) => ! $r['has_any'])),
        ];

        return [
            'rows' => $rows,
            'stats' => $stats,
            'categories' => array_keys($byCategory),
            'colors' => $colors,
        ];
    }

    /**
     * @return array<string, list<array{phone: string, name: string}>>
     */
    public function groups(): array
    {
        return $this->loadSourceData()['groups'];
    }

    /**
     * Sorted WhatsApp group names for admin filters / dropdowns.
     *
     * @return list<string>
     */
    public function groupNames(): array
    {
        $names = array_keys($this->groups());
        natcasesort($names);

        return array_values($names);
    }

    /**
     * @return array<string, string>
     */
    public function groupColors(): array
    {
        return $this->loadSourceData()['colors'];
    }

    /**
     * Map provider IDs to the WhatsApp groups their phones belong to.
     *
     * @param  iterable<mixed>  $providers
     * @return array<string, list<array{name: string, color: string}>>
     */
    public function groupsForProviders(iterable $providers): array
    {
        $source = $this->loadSourceData();
        $groupsByPhone = $this->indexGroupsByPhone($source['groups'], $source['colors']);
        $out = [];

        foreach ($providers as $provider) {
            $id = (string) (is_object($provider) ? ($provider->id ?? '') : ($provider['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $rawPhones = [];
            if (is_object($provider)) {
                $rawPhones = [
                    $provider->company_phone ?? null,
                    $provider->contact_person_phone ?? null,
                    $provider->contact_phone_digits ?? null,
                    is_object($provider->owner ?? null) ? ($provider->owner->phone ?? null) : null,
                    is_object($provider->owner ?? null) ? ($provider->owner->provider_admin_phone_digits ?? null) : null,
                ];
            } elseif (is_array($provider)) {
                $rawPhones = [
                    $provider['company_phone'] ?? null,
                    $provider['contact_person_phone'] ?? null,
                    $provider['owner_phone'] ?? null,
                ];
            }

            $phones = [];
            foreach ($rawPhones as $raw) {
                $digits = $this->last10($raw);
                if ($digits !== null) {
                    $phones[$digits] = true;
                }
            }

            $byName = [];
            foreach (array_keys($phones) as $phone) {
                foreach ($groupsByPhone[$phone] ?? [] as $group) {
                    $byName[$group['name']] = $group;
                }
            }

            $groups = array_values($byName);
            usort($groups, static fn (array $a, array $b) => strcmp($a['name'], $b['name']));
            $out[$id] = $groups;
        }

        return $out;
    }

    /**
     * Provider IDs whose phones belong to the given WhatsApp group.
     *
     * @return list<string>
     */
    public function providerIdsInGroup(string $groupName): array
    {
        $groupName = $this->normalizeGroupName($groupName);
        if ($groupName === '') {
            return [];
        }

        $groups = $this->groups();
        $matchedName = null;
        foreach (array_keys($groups) as $name) {
            if ($this->normalizeGroupName((string) $name) === $groupName) {
                $matchedName = (string) $name;
                break;
            }
        }

        if ($matchedName === null) {
            return [];
        }

        return $this->providerIdsMatchingPhones($this->phonesFromContacts($groups[$matchedName] ?? []));
    }

    /**
     * Provider IDs whose phones belong to any configured WhatsApp group.
     *
     * @return list<string>
     */
    public function providerIdsInAnyGroup(): array
    {
        $phones = [];
        foreach ($this->groups() as $contacts) {
            foreach ($this->phonesFromContacts($contacts) as $phone) {
                $phones[$phone] = true;
            }
        }

        return $this->providerIdsMatchingPhones(array_keys($phones));
    }

    /**
     * @param  list<array{phone?: string, name?: string}|mixed>  $contacts
     * @return list<string>
     */
    private function phonesFromContacts(array $contacts): array
    {
        $phones = [];
        foreach ($contacts as $contact) {
            $raw = is_array($contact) ? ($contact['phone'] ?? null) : $contact;
            $digits = $this->last10($raw);
            if ($digits !== null) {
                $phones[$digits] = true;
            }
        }

        return array_keys($phones);
    }

    /**
     * @param  list<string>  $phones
     * @return list<string>
     */
    private function providerIdsMatchingPhones(array $phones): array
    {
        $phones = array_values(array_unique(array_filter(array_map(
            fn ($phone) => $this->last10($phone),
            $phones
        ))));
        if ($phones === []) {
            return [];
        }

        $wanted = array_fill_keys($phones, true);
        $ids = [];

        Provider::query()
            ->with(['owner:id,phone,provider_admin_phone_digits'])
            ->select([
                'id',
                'user_id',
                'company_phone',
                'contact_person_phone',
                'contact_phone_digits',
            ])
            ->whereNull('deleted_at')
            ->chunkById(200, function (Collection $chunk) use (&$ids, $wanted) {
                foreach ($chunk as $provider) {
                    $candidates = [
                        $this->last10($provider->company_phone),
                        $this->last10($provider->contact_person_phone),
                        $this->last10($provider->contact_phone_digits),
                        $this->last10($provider->owner?->phone),
                        $this->last10($provider->owner?->provider_admin_phone_digits),
                    ];
                    foreach ($candidates as $candidate) {
                        if ($candidate !== null && isset($wanted[$candidate])) {
                            $ids[(string) $provider->id] = true;
                            break;
                        }
                    }
                }
            });

        return array_keys($ids);
    }

    /**
     * @param  array<int, mixed>|string  $contactsOrRawPhones
     * @return array{ok: bool, message: string, group: string, count: int}
     */
    public function upsertGroup(string $groupName, array|string $contactsOrRawPhones, ?string $originalName = null, bool $requirePhones = true): array
    {
        $groupName = $this->normalizeGroupName($groupName);
        if ($groupName === '') {
            return ['ok' => false, 'message' => 'WhatsApp_group_name_required', 'group' => '', 'count' => 0];
        }

        $contacts = is_array($contactsOrRawPhones)
            ? $this->parseContacts($contactsOrRawPhones)
            : $this->parseContacts(array_map(
                static fn (string $phone) => ['phone' => $phone, 'name' => ''],
                $this->parsePhones($contactsOrRawPhones)
            ));

        if ($requirePhones && $contacts === []) {
            return ['ok' => false, 'message' => 'No_valid_phone_numbers_found_in_input', 'group' => $groupName, 'count' => 0];
        }

        if (! $this->usesDatabase()) {
            return ['ok' => false, 'message' => 'WhatsApp_group_tables_missing', 'group' => $groupName, 'count' => count($contacts)];
        }

        $originalName = $originalName !== null ? $this->normalizeGroupName($originalName) : null;

        try {
            DB::transaction(function () use ($groupName, $contacts, $originalName) {
                $existingByName = WhatsAppProviderGroup::query()->get()->keyBy(
                    fn (WhatsAppProviderGroup $group) => $this->normalizeGroupName($group->name)
                );

                $model = null;
                if ($originalName !== null && $originalName !== '') {
                    $model = $existingByName->get($originalName);
                }
                if (! $model) {
                    $model = $existingByName->get($groupName);
                }

                if ($originalName !== null && $originalName !== '' && $originalName !== $groupName) {
                    $conflict = $existingByName->get($groupName);
                    if ($conflict && (! $model || $conflict->id !== $model->id)) {
                        throw new \RuntimeException('WhatsApp_group_name_already_exists');
                    }
                }

                if ($model) {
                    $model->name = $groupName;
                    if (! $this->normalizeColor($model->color)) {
                        $model->color = $this->nextUniqueColor(
                            WhatsAppProviderGroup::query()->pluck('color', 'name')->all()
                        );
                    }
                    $model->save();
                } else {
                    $model = WhatsAppProviderGroup::query()->create([
                        'name' => $groupName,
                        'color' => $this->nextUniqueColor(
                            WhatsAppProviderGroup::query()->pluck('color', 'name')->all()
                        ),
                        'sort_order' => (int) (WhatsAppProviderGroup::query()->max('sort_order') ?? -1) + 1,
                    ]);
                }

                $this->syncGroupContacts($model, $contacts);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'WhatsApp_group_name_already_exists') {
                return ['ok' => false, 'message' => 'WhatsApp_group_name_already_exists', 'group' => $groupName, 'count' => count($contacts)];
            }

            return ['ok' => false, 'message' => 'Failed_to_save_WhatsApp_group', 'group' => $groupName, 'count' => count($contacts)];
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Failed_to_save_WhatsApp_group', 'group' => $groupName, 'count' => count($contacts)];
        }

        return [
            'ok' => true,
            'message' => 'WhatsApp_group_saved_successfully',
            'group' => $groupName,
            'count' => count($contacts),
        ];
    }

    /**
     * @return array{ok: bool, message: string, group: string}
     */
    public function deleteGroup(string $groupName): array
    {
        $groupName = $this->normalizeGroupName($groupName);
        if ($groupName === '') {
            return ['ok' => false, 'message' => 'WhatsApp_group_name_required', 'group' => ''];
        }

        if (! $this->usesDatabase()) {
            return ['ok' => false, 'message' => 'WhatsApp_group_tables_missing', 'group' => $groupName];
        }

        $model = WhatsAppProviderGroup::query()->get()->first(
            fn (WhatsAppProviderGroup $group) => $this->normalizeGroupName($group->name) === $groupName
        );

        if (! $model) {
            return ['ok' => false, 'message' => 'WhatsApp_group_not_found', 'group' => $groupName];
        }

        try {
            $model->delete();
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Failed_to_save_WhatsApp_group', 'group' => $groupName];
        }

        return ['ok' => true, 'message' => 'WhatsApp_group_deleted_successfully', 'group' => $groupName];
    }

    private function normalizeGroupName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    }

    /**
     * @return array{
     *   groups: array<string, list<array{phone: string, name: string}>>,
     *   colors: array<string, string>
     * }
     */
    private function loadSourceData(): array
    {
        if ($this->usesDatabase()) {
            $rows = WhatsAppProviderGroup::query()
                ->with('contacts')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            if ($rows->isEmpty()) {
                // Bootstrap once from bundled JSON if DB tables exist but are empty.
                $path = $this->sourcePath();
                if (is_file($path)) {
                    $this->importFromJsonFile($path, false);
                    $rows = WhatsAppProviderGroup::query()
                        ->with('contacts')
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get();
                }
            }

            $groups = [];
            $colors = [];
            foreach ($rows as $group) {
                $name = (string) $group->name;
                $groups[$name] = $group->contacts
                    ->map(static fn (WhatsAppProviderGroupContact $contact) => [
                        'phone' => (string) $contact->phone,
                        'name' => (string) ($contact->name ?? ''),
                    ])
                    ->values()
                    ->all();
                $color = $this->normalizeColor($group->color) ?? $this->fallbackColorForGroup($name);
                $colors[$name] = $color;
                if ($this->normalizeColor($group->color) === null) {
                    $group->color = $color;
                    $group->save();
                }
            }

            return ['groups' => $groups, 'colors' => $colors];
        }

        return $this->loadJsonSourceData();
    }

    /**
     * Legacy JSON fallback (pre-migration / local without migrate).
     *
     * @return array{
     *   groups: array<string, list<array{phone: string, name: string}>>,
     *   colors: array<string, string>
     * }
     */
    private function loadJsonSourceData(): array
    {
        $path = $this->sourcePath();
        if (! is_file($path)) {
            return ['groups' => [], 'colors' => []];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return ['groups' => [], 'colors' => []];
        }

        return $this->parseJsonPayload($decoded);
    }

    /**
     * @param  array<mixed>  $decoded
     * @return array{
     *   groups: array<string, list<array{phone: string, name: string}>>,
     *   colors: array<string, string>
     * }
     */
    private function parseJsonPayload(array $decoded): array
    {
        $groups = [];
        $colors = [];

        foreach ($decoded as $category => $value) {
            $groupName = $this->normalizeGroupName((string) $category);
            if ($groupName === '' || $groupName === '_meta') {
                continue;
            }

            $contactsRaw = null;
            $color = null;

            if (is_array($value) && $this->isListArray($value)) {
                $contactsRaw = $value;
            } elseif (is_array($value)) {
                $color = $this->normalizeColor($value['color'] ?? null);
                $contactsRaw = $value['contacts'] ?? $value['phones'] ?? null;
                if (! is_array($contactsRaw)) {
                    continue;
                }
            } else {
                continue;
            }

            $groups[$groupName] = $this->parseContacts($contactsRaw);
            if ($color !== null) {
                $colors[$groupName] = $color;
            }
        }

        foreach ($groups as $groupName => $_) {
            if (! isset($colors[$groupName])) {
                $colors[$groupName] = $this->nextUniqueColor($colors);
            }
        }

        return ['groups' => $groups, 'colors' => $colors];
    }

    /**
     * @param  list<array{phone: string, name: string}>  $contacts
     */
    private function syncGroupContacts(WhatsAppProviderGroup $group, array $contacts): void
    {
        WhatsAppProviderGroupContact::query()->where('group_id', $group->id)->delete();

        $rows = [];
        $now = now();
        foreach (array_values($contacts) as $index => $contact) {
            $rows[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'group_id' => $group->id,
                'phone' => $contact['phone'],
                'name' => ($contact['name'] ?? '') !== '' ? $contact['name'] : null,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            WhatsAppProviderGroupContact::query()->insert($chunk);
        }
    }

    /**
     * @param  array<string, string>  $usedColors
     */
    private function nextUniqueColor(array $usedColors): string
    {
        $used = [];
        foreach ($usedColors as $color) {
            $normalized = $this->normalizeColor($color);
            if ($normalized !== null) {
                $used[$normalized] = true;
            }
        }

        foreach (self::GROUP_COLOR_PALETTE as $color) {
            if (! isset($used[strtoupper($color)])) {
                return strtoupper($color);
            }
        }

        $index = count($usedColors);
        for ($i = 0; $i < 360; $i++) {
            $hue = (($index * 47) + $i * 13) % 360;
            $color = $this->hslToHex($hue, 70, 86);
            if (! isset($used[$color])) {
                return $color;
            }
        }

        return self::GROUP_COLOR_PALETTE[$index % count(self::GROUP_COLOR_PALETTE)];
    }

    private function fallbackColorForGroup(string $groupName): string
    {
        $hash = crc32($groupName);
        $index = (int) sprintf('%u', $hash) % count(self::GROUP_COLOR_PALETTE);

        return strtoupper(self::GROUP_COLOR_PALETTE[$index]);
    }

    private function normalizeColor(mixed $color): ?string
    {
        if (! is_string($color)) {
            return null;
        }
        $color = trim($color);
        if (! preg_match('/^#([A-Fa-f0-9]{6})$/', $color)) {
            return null;
        }

        return strtoupper($color);
    }

    private function hslToHex(int $h, int $s, int $l): string
    {
        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0.0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0.0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0.0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0.0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0.0, $c];
        } else {
            [$r, $g, $b] = [$c, 0.0, $x];
        }

        $toHex = static fn (float $v) => str_pad(dechex((int) round(($v + $m) * 255)), 2, '0', STR_PAD_LEFT);

        return strtoupper('#'.$toHex($r).$toHex($g).$toHex($b));
    }

    /**
     * @param  array<mixed>  $value
     */
    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexProviders(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        Provider::query()
            ->with(['owner:id,phone,provider_admin_phone_digits'])
            ->select([
                'id',
                'user_id',
                'company_name',
                'contact_person_name',
                'company_phone',
                'contact_person_phone',
                'contact_phone_digits',
                'is_approved',
                'is_active',
            ])
            ->whereNull('deleted_at')
            ->chunkById(200, function (Collection $chunk) use (&$indexed, $wanted) {
                foreach ($chunk as $provider) {
                    $candidates = [
                        $this->last10($provider->company_phone),
                        $this->last10($provider->contact_person_phone),
                        $this->last10($provider->contact_phone_digits),
                        $this->last10($provider->owner?->phone),
                        $this->last10($provider->owner?->provider_admin_phone_digits),
                    ];
                    $matched = [];
                    foreach ($candidates as $candidate) {
                        if ($candidate !== null && isset($wanted[$candidate])) {
                            $matched[$candidate] = true;
                        }
                    }
                    if ($matched === []) {
                        continue;
                    }

                    $info = [
                        'id' => (string) $provider->id,
                        'name' => (string) ($provider->company_name ?: $provider->contact_person_name ?: 'Unnamed'),
                        'contact_person_name' => (string) ($provider->contact_person_name ?: ''),
                        'company_phone' => (string) ($provider->company_phone ?: ''),
                        'contact_person_phone' => (string) ($provider->contact_person_phone ?: ''),
                        'owner_phone' => (string) ($provider->owner?->phone ?: ''),
                        'is_approved' => (int) $provider->is_approved,
                        'is_active' => (int) $provider->is_active === 1,
                        'kind' => 'provider',
                    ];

                    foreach (array_keys($matched) as $phone) {
                        $indexed[$phone][] = $info;
                    }
                }
            });

        return $indexed;
    }

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    private function enrichProvider(array $provider): array
    {
        $approved = (int) ($provider['is_approved'] ?? 0);
        $provider['status_label'] = match ($approved) {
            1 => 'Added in admin panel',
            2 => 'Onboarding',
            0 => 'Denied',
            default => 'Status '.$approved,
        };
        $provider['url'] = $approved === 2
            ? route('admin.provider.onboarding_details', $provider['id'])
            : route('admin.provider.details', [$provider['id'], 'web_page' => 'overview']);

        return $provider;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexLeads(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        $providerLeadTypes = [Lead::TYPE_PROVIDER, Lead::TYPE_FUTURE_PROVIDER];

        Lead::query()
            ->select(['id', 'name', 'phone_number', 'lead_type', 'lead_temperature', 'hunting_status'])
            ->whereIn('lead_type', $providerLeadTypes)
            ->whereNull('deleted_at')
            ->chunkById(500, function (Collection $chunk) use (&$indexed, $wanted) {
                foreach ($chunk as $lead) {
                    $phone = $this->last10($lead->phone_number);
                    if ($phone === null || ! isset($wanted[$phone])) {
                        continue;
                    }
                    $indexed[$phone][] = $this->formatLead($lead, 'primary');
                }
            });

        try {
            PartyAlternatePhone::query()
                ->where('owner_type', PartyAlternatePhone::OWNER_PROVIDER_LEAD)
                ->select(['id', 'phone_digits', 'owner_id'])
                ->chunkById(500, function (Collection $chunk) use (&$indexed, $wanted, $providerLeadTypes) {
                    $ownerIds = $chunk->pluck('owner_id')->filter()->unique()->values()->all();
                    if ($ownerIds === []) {
                        return;
                    }
                    $leads = Lead::query()
                        ->whereIn('id', $ownerIds)
                        ->whereIn('lead_type', $providerLeadTypes)
                        ->whereNull('deleted_at')
                        ->get(['id', 'name', 'lead_type', 'lead_temperature', 'hunting_status'])
                        ->keyBy('id');

                    foreach ($chunk as $alt) {
                        $phone = $this->last10($alt->phone_digits);
                        if ($phone === null || ! isset($wanted[$phone])) {
                            continue;
                        }
                        $lead = $leads->get($alt->owner_id);
                        if (! $lead) {
                            continue;
                        }
                        if (collect($indexed[$phone] ?? [])->contains(fn ($row) => ($row['id'] ?? null) === (string) $lead->id)) {
                            continue;
                        }
                        $indexed[$phone][] = $this->formatLead($lead, 'alternate');
                    }
                });
        } catch (Throwable) {
            // Alternate-phone table may be unavailable on older DBs.
        }

        return $indexed;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexWhatsAppLeads(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        try {
            foreach (WhatsAppProviderLead::query()->select(['lead_id', 'phone', 'name', 'service', 'status'])->get() as $lead) {
                $phone = $this->last10($lead->phone);
                if ($phone === null || ! isset($wanted[$phone])) {
                    continue;
                }
                $indexed[$phone][] = [
                    'id' => (string) $lead->lead_id,
                    'name' => (string) ($lead->name ?: $lead->lead_id),
                    'label' => trim('WA draft: '.($lead->name ?: $lead->lead_id).' ['.($lead->status ?: '').'] '.($lead->service ?: '')),
                    'kind' => 'whatsapp',
                    'url' => null,
                ];
            }
        } catch (Throwable) {
            // WhatsApp lead table optional.
        }

        return $indexed;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexDrafts(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        try {
            foreach (ProviderRegistrationDraft::query()->select(['id', 'phone', 'current_step', 'provider_type'])->get() as $draft) {
                $phone = $this->last10($draft->phone);
                if ($phone === null || ! isset($wanted[$phone])) {
                    continue;
                }
                $indexed[$phone][] = [
                    'id' => (string) $draft->id,
                    'name' => 'Registration draft',
                    'label' => 'Reg draft step '.($draft->current_step ?: '?').' ('.($draft->provider_type ?: 'n/a').')',
                    'kind' => 'draft',
                    'url' => null,
                ];
            }
        } catch (Throwable) {
            // Draft table optional.
        }

        return $indexed;
    }

    private function formatLead(Lead $lead, string $via): array
    {
        $bits = array_values(array_filter([
            $lead->lead_type ?: null,
            $lead->lead_temperature ?: null,
            $via === 'alternate' ? 'alt phone' : null,
        ]));

        $name = (string) ($lead->name ?: 'Unnamed');

        return [
            'id' => (string) $lead->id,
            'name' => $name,
            'label' => $bits === [] ? $name : $name.' ('.implode(', ', $bits).')',
            'kind' => 'lead',
            'url' => route('admin.lead.show', $lead->id),
        ];
    }

    /**
     * Reverse index: last-10 phone => list of WhatsApp groups (name + color).
     *
     * @param  array<string, list<array{phone: string, name: string}>>  $byCategory
     * @param  array<string, string>  $colors
     * @return array<string, list<array{name: string, color: string}>>
     */
    private function indexGroupsByPhone(array $byCategory, array $colors): array
    {
        $indexed = [];
        foreach ($byCategory as $category => $contacts) {
            $color = $colors[$category] ?? $this->fallbackColorForGroup($category);
            foreach ($contacts as $contact) {
                $phone = $contact['phone'];
                if (! isset($indexed[$phone])) {
                    $indexed[$phone] = [];
                }
                foreach ($indexed[$phone] as $existing) {
                    if (($existing['name'] ?? '') === $category) {
                        continue 2;
                    }
                }
                $indexed[$phone][] = [
                    'name' => $category,
                    'color' => $color,
                ];
            }
        }

        return $indexed;
    }

    private function last10(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }

        return strlen($digits) >= 8 ? $digits : null;
    }
}
