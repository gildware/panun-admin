<?php

namespace Modules\ProviderManagement\Support;

/**
 * When provider_type=company, the company and contact phone fields can hold the same value.
 * intl-tel hidden fields sometimes POST an unchanged company_phone while contact_person_phone updates.
 * If the two were equal before save, keep them aligned with the new contact number.
 */
final class ProviderPhoneUpdateNormalizer
{
    public static function alignCompanyPhoneWithContactIfPreviouslyPaired(
        string $providerType,
        ?string $oldCompany,
        ?string $oldContact,
        string $newCompany,
        string $newContact
    ): string {
        $oldCompany = trim((string) $oldCompany);
        $oldContact = trim((string) $oldContact);
        $newCompany = trim($newCompany);
        $newContact = trim($newContact);

        if ($providerType !== 'company' || $oldCompany === '' || $oldContact === '') {
            return $newCompany;
        }

        if ($oldCompany !== $oldContact) {
            return $newCompany;
        }

        if ($newContact === '' || $newCompany === '') {
            return $newCompany;
        }

        // Company hidden still shows the old shared number; contact field carries the new one.
        if ($newCompany === $oldCompany && $newContact !== $oldContact) {
            return $newContact;
        }

        return $newCompany;
    }
}
