<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

/**
 * Merges WhatsAppUser row with main-app customer or provider profile (same chat phone) for AI booking UX.
 */
final class WhatsAppAiContactProfileResolver
{
    /** @var array<string, array<string, mixed>> */
    private array $snapshotCache = [];

    /**
     * @return array{
     *     merged: array{
     *         name: ?string,
     *         email: ?string,
     *         alternate_phone: ?string,
     *         service_address: ?string,
     *         address_sources: list<string>,
     *     },
     *     system_user_found: bool,
     *     system_role: ?string,
     *     lines_for_prompt: list<string>,
     * }
     */
    public function snapshot(string $phone): array
    {
        if ($phone === '') {
            return $this->emptySnapshot();
        }
        if (isset($this->snapshotCache[$phone])) {
            return $this->snapshotCache[$phone];
        }

        $wa = WhatsAppUser::query()->where('phone', $phone)->first();
        $sys = User::findByContactPhone($phone);

        $merged = [
            'name' => null,
            'email' => null,
            'alternate_phone' => null,
            'service_address' => null,
            'address_sources' => [],
        ];

        $systemRole = null;
        $lines = [];

        if ($sys) {
            if ($sys->user_type === 'provider-admin') {
                $systemRole = 'provider';
                $provider = Provider::query()->where('user_id', $sys->id)->first();
                $this->mergeFromProvider($merged, $sys, $provider, $phone);
                if ($sys->customer_app_access) {
                    $this->mergeFromCustomer($merged, $sys);
                }
            } elseif (in_array($sys->user_type, CUSTOMER_USER_TYPES, true)) {
                $systemRole = 'customer';
                $this->mergeFromCustomer($merged, $sys);
            }
        }

        $this->mergeFromWhatsAppUser($merged, $wa);

        $this->backfillWhatsAppUserFromMerged($phone, $wa, $merged, $systemRole);

        if ($merged['name']) {
            $lines[] = 'Known name: '.$merged['name'];
        }
        if ($merged['email']) {
            $lines[] = 'Known email: '.$merged['email'];
        }
        if ($merged['alternate_phone']) {
            $lines[] = 'Known alternate phone: '.$merged['alternate_phone'];
        }
        if ($merged['service_address']) {
            $lines[] = 'Default service address on file: '.$merged['service_address'];
            $lines[] = '**Address rule:** Do **not** re-ask for the full saved address if they are happy with it. Ask **once** in a warm, short way whether the technician should visit **this same address** or a **different** one. If they confirm **same**, call **upsert_my_draft_booking** with **use_saved_service_address** = true (and you may repeat the same address text in **address** if you prefer). If **different**, collect the new full address and pass it in **address** — the server saves it for their WhatsApp profile.';
        }

        $out = [
            'merged' => $merged,
            'system_user_found' => $sys !== null,
            'system_role' => $systemRole,
            'lines_for_prompt' => $lines,
        ];
        $this->snapshotCache[$phone] = $out;

        return $out;
    }

    /**
     * Prefill empty draft fields from merged profile (address only via explicit tool flag elsewhere).
     */
    public function prefillDraftBookingFromKnownProfile(string $phone, \Modules\WhatsAppModule\Entities\WhatsAppBooking $booking, array $args): void
    {
        $snap = $this->snapshot($phone);
        $m = $snap['merged'];

        if (trim((string) $booking->name) === '' && $m['name']) {
            $booking->name = Str::limit(trim((string) $m['name']), 255, '');
        }
        if (trim((string) $booking->alt_phone) === '' && $m['alternate_phone']) {
            $booking->alt_phone = Str::limit(trim((string) $m['alternate_phone']), 50, '');
        }

        $prefill = is_array($booking->admin_prefill_json) ? $booking->admin_prefill_json : [];
        if (empty($prefill['contact_email']) && $m['email']) {
            $prefill['contact_email'] = Str::limit(trim((string) $m['email']), 191, '');
            $booking->admin_prefill_json = $prefill;
        }

        if (! empty($args['use_saved_service_address']) && $this->argTruthy($args['use_saved_service_address'])) {
            $addr = trim((string) ($m['service_address'] ?? ''));
            if ($addr !== '' && trim((string) $booking->address) === '') {
                $booking->address = $addr;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function persistAfterDraftUpsert(string $phone, \Modules\WhatsAppModule\Entities\WhatsAppBooking $booking, array $args): void
    {
        $wu = WhatsAppUser::firstOrNew(['phone' => $phone]);
        $dirty = false;

        if (array_key_exists('address', $args)) {
            $a = trim((string) $args['address']);
            if ($a !== '') {
                $wu->address = $a;
                $dirty = true;
            }
        } elseif (trim((string) $booking->address) !== '') {
            $wu->address = trim((string) $booking->address);
            $dirty = true;
        }

        if (array_key_exists('alternate_phone', $args)) {
            $ap = trim((string) $args['alternate_phone']);
            if ($ap !== '') {
                $wu->alternate_phone = Str::limit($ap, 50, '');
                $dirty = true;
            }
        } elseif (trim((string) $booking->alt_phone) !== '') {
            $wu->alternate_phone = Str::limit(trim((string) $booking->alt_phone), 50, '');
            $dirty = true;
        }

        if (array_key_exists('email', $args)) {
            $em = Str::lower(trim((string) $args['email']));
            if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $wu->email = Str::limit($em, 191, '');
                $dirty = true;
            }
        } else {
            $prefill = is_array($booking->admin_prefill_json) ? $booking->admin_prefill_json : [];
            $em = Str::lower(trim((string) ($prefill['contact_email'] ?? '')));
            if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL) && trim((string) ($wu->email ?? '')) === '') {
                $wu->email = Str::limit($em, 191, '');
                $dirty = true;
            }
        }

        if (array_key_exists('name', $args)) {
            $nmArg = trim((string) $args['name']);
            if ($nmArg !== '' && ! WhatsAppAiBookingNameHeuristics::looksLikeServiceNotPersonName($nmArg)) {
                $wu->name = Str::limit($nmArg, 255, '');
                $dirty = true;
            }
        } else {
            $nm = trim((string) $booking->name);
            if ($nm !== '' && ! WhatsAppAiBookingNameHeuristics::looksLikeServiceNotPersonName($nm) && trim((string) ($wu->name ?? '')) === '') {
                $wu->name = Str::limit($nm, 255, '');
                $dirty = true;
            }
        }

        if ($dirty || ! $wu->exists) {
            $wu->handled_by = $wu->handled_by ?: 'AI';
            $wu->save();
        }
    }

    /**
     * @param  array{
     *     name: ?string,
     *     email: ?string,
     *     alternate_phone: ?string,
     *     service_address: ?string,
     *     address_sources: list<string>,
     * }  $merged
     */
    private function mergeFromWhatsAppUser(array &$merged, ?WhatsAppUser $wa): void
    {
        if (! $wa) {
            return;
        }
        if (trim((string) $wa->name) !== '' && $merged['name'] === null) {
            $merged['name'] = trim((string) $wa->name);
        }
        if (trim((string) $wa->email) !== '' && $merged['email'] === null) {
            $merged['email'] = trim((string) $wa->email);
        }
        if (trim((string) $wa->alternate_phone) !== '' && $merged['alternate_phone'] === null) {
            $merged['alternate_phone'] = trim((string) $wa->alternate_phone);
        }
        if (trim((string) $wa->address) !== '' && $merged['service_address'] === null) {
            $merged['service_address'] = trim((string) $wa->address);
            $merged['address_sources'][] = 'whatsapp_profile';
        }
    }

    /**
     * @param  array{
     *     name: ?string,
     *     email: ?string,
     *     alternate_phone: ?string,
     *     service_address: ?string,
     *     address_sources: list<string>,
     * }  $merged
     */
    private function mergeFromCustomer(array &$merged, User $user): void
    {
        $full = trim(trim((string) $user->first_name).' '.trim((string) $user->last_name));
        if ($full !== '' && $merged['name'] === null) {
            $merged['name'] = $full;
        }
        if (trim((string) $user->email) !== '' && $merged['email'] === null) {
            $merged['email'] = trim((string) $user->email);
        }

        $addr = UserAddress::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->first(fn (UserAddress $a) => $this->userAddressLine($a) !== '');

        if ($addr && $merged['service_address'] === null) {
            $line = $this->userAddressLine($addr);
            if ($line !== '') {
                $merged['service_address'] = $line;
                $merged['address_sources'][] = 'customer_saved_address';
            }
        }
    }

    /**
     * @param  array{
     *     name: ?string,
     *     email: ?string,
     *     alternate_phone: ?string,
     *     service_address: ?string,
     *     address_sources: list<string>,
     * }  $merged
     */
    private function mergeFromProvider(array &$merged, User $user, ?Provider $provider, string $chatPhone): void
    {
        if ($provider) {
            $nm = trim((string) $provider->contact_person_name);
            if ($nm !== '' && $merged['name'] === null) {
                $merged['name'] = $nm;
            }
            $em = trim((string) $provider->contact_person_email);
            if ($em !== '' && $merged['email'] === null) {
                $merged['email'] = $em;
            }
            $cp = trim((string) $provider->contact_person_phone);
            if ($cp !== '' && $this->digits($cp) !== $this->digits($chatPhone) && $merged['alternate_phone'] === null) {
                $merged['alternate_phone'] = $cp;
            }
            $biz = trim((string) $provider->company_address);
            if ($biz !== '' && $merged['service_address'] === null) {
                $merged['service_address'] = $biz;
                $merged['address_sources'][] = 'provider_business_address';
            }
        }

        $full = trim(trim((string) $user->first_name).' '.trim((string) $user->last_name));
        if ($full !== '' && $merged['name'] === null) {
            $merged['name'] = $full;
        }
        if (trim((string) $user->email) !== '' && $merged['email'] === null) {
            $merged['email'] = trim((string) $user->email);
        }
    }

    private function userAddressLine(?UserAddress $a): string
    {
        if (! $a) {
            return '';
        }
        $chunks = array_filter([
            trim((string) $a->address),
            trim((string) $a->street),
            trim((string) $a->landmark),
            trim((string) $a->city),
            trim((string) $a->zip_code),
        ]);

        return trim(implode(', ', $chunks));
    }

    private function digits(string $p): string
    {
        return preg_replace('/\D+/', '', $p) ?? '';
    }

    /**
     * @param  array{
     *     name: ?string,
     *     email: ?string,
     *     alternate_phone: ?string,
     *     service_address: ?string,
     *     address_sources: list<string>,
     * }  $merged
     */
    private function backfillWhatsAppUserFromMerged(string $phone, ?WhatsAppUser $wa, array $merged, ?string $systemRole): void
    {
        $wu = $wa ?? new WhatsAppUser(['phone' => $phone]);
        if (! $wu->exists) {
            $wu->phone = $phone;
        }
        $dirty = false;
        if ($merged['name'] && trim((string) ($wu->name ?? '')) === '') {
            $wu->name = Str::limit(trim((string) $merged['name']), 255, '');
            $dirty = true;
        }
        if ($merged['email'] && trim((string) ($wu->email ?? '')) === '') {
            $wu->email = Str::limit(trim((string) $merged['email']), 191, '');
            $dirty = true;
        }
        if ($merged['alternate_phone'] && trim((string) ($wu->alternate_phone ?? '')) === '') {
            $wu->alternate_phone = Str::limit(trim((string) $merged['alternate_phone']), 50, '');
            $dirty = true;
        }
        if ($merged['service_address'] && trim((string) ($wu->address ?? '')) === '') {
            $wu->address = trim((string) $merged['service_address']);
            $dirty = true;
        }
        if (($wu->type === null || $wu->type === '') && in_array('provider_business_address', $merged['address_sources'], true)) {
            $wu->type = 'PROVIDER';
            $dirty = true;
        } elseif (($wu->type === null || $wu->type === '') && ($systemRole === 'customer' || in_array('customer_saved_address', $merged['address_sources'], true))) {
            $wu->type = 'CUSTOMER';
            $dirty = true;
        }
        if ($dirty) {
            $wu->handled_by = $wu->handled_by ?: 'AI';
            $wu->save();
        }
    }

    /**
     * @return array{
     *     merged: array{name: ?string, email: ?string, alternate_phone: ?string, service_address: ?string, address_sources: list<string>},
     *     system_user_found: bool,
     *     system_role: ?string,
     *     lines_for_prompt: list<string>,
     * }
     */
    private function argTruthy(mixed $v): bool
    {
        if ($v === true || $v === 1 || $v === '1') {
            return true;
        }
        if (is_string($v) && strtolower(trim($v)) === 'true') {
            return true;
        }

        return false;
    }

    /**
     * @return array{
     *     merged: array{name: ?string, email: ?string, alternate_phone: ?string, service_address: ?string, address_sources: list<string>},
     *     system_user_found: bool,
     *     system_role: ?string,
     *     lines_for_prompt: list<string>,
     * }
     */
    private function emptySnapshot(): array
    {
        return [
            'merged' => [
                'name' => null,
                'email' => null,
                'alternate_phone' => null,
                'service_address' => null,
                'address_sources' => [],
            ],
            'system_user_found' => false,
            'system_role' => null,
            'lines_for_prompt' => [],
        ];
    }
}
