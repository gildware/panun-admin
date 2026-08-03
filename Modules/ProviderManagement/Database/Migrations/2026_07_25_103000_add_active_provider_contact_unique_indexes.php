<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-level uniqueness for active provider contacts.
 *
 * Soft-deleted rows stay out of the unique key (generated column is NULL),
 * and customers may still share a phone with a provider (scoped to provider-admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->assertNoActiveProviderPhoneDuplicates();
        $this->assertNoActiveProviderAdminEmailDuplicates();

        if (! Schema::hasColumn('users', 'provider_admin_phone_digits')) {
            DB::statement("
                ALTER TABLE `users`
                ADD COLUMN `provider_admin_phone_digits` VARCHAR(32)
                    GENERATED ALWAYS AS (
                        IF(
                            `deleted_at` IS NULL
                            AND `user_type` = 'provider-admin'
                            AND `phone` IS NOT NULL
                            AND `phone` <> '',
                            NULLIF(REGEXP_REPLACE(`phone`, '[^0-9]', ''), ''),
                            NULL
                        )
                    ) STORED,
                ADD UNIQUE KEY `users_provider_admin_phone_digits_unique` (`provider_admin_phone_digits`)
            ");
        }

        if (! Schema::hasColumn('users', 'provider_admin_email_key')) {
            DB::statement("
                ALTER TABLE `users`
                ADD COLUMN `provider_admin_email_key` VARCHAR(191)
                    GENERATED ALWAYS AS (
                        IF(
                            `deleted_at` IS NULL
                            AND `user_type` = 'provider-admin'
                            AND `email` IS NOT NULL
                            AND `email` <> '',
                            LOWER(`email`),
                            NULL
                        )
                    ) STORED,
                ADD UNIQUE KEY `users_provider_admin_email_key_unique` (`provider_admin_email_key`)
            ");
        }

        if (! Schema::hasColumn('providers', 'contact_phone_digits')) {
            DB::statement("
                ALTER TABLE `providers`
                ADD COLUMN `contact_phone_digits` VARCHAR(32)
                    GENERATED ALWAYS AS (
                        IF(
                            `deleted_at` IS NULL
                            AND `contact_person_phone` IS NOT NULL
                            AND `contact_person_phone` <> '',
                            NULLIF(REGEXP_REPLACE(`contact_person_phone`, '[^0-9]', ''), ''),
                            NULL
                        )
                    ) STORED,
                ADD UNIQUE KEY `providers_contact_phone_digits_unique` (`contact_phone_digits`)
            ");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('providers', 'contact_phone_digits')) {
            DB::statement('ALTER TABLE `providers` DROP INDEX `providers_contact_phone_digits_unique`, DROP COLUMN `contact_phone_digits`');
        }

        if (Schema::hasColumn('users', 'provider_admin_email_key')) {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_provider_admin_email_key_unique`, DROP COLUMN `provider_admin_email_key`');
        }

        if (Schema::hasColumn('users', 'provider_admin_phone_digits')) {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_provider_admin_phone_digits_unique`, DROP COLUMN `provider_admin_phone_digits`');
        }
    }

    private function assertNoActiveProviderPhoneDuplicates(): void
    {
        $rows = DB::select("
            SELECT REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '') AS digits,
                   COUNT(*) AS c,
                   GROUP_CONCAT(id ORDER BY created_at SEPARATOR ',') AS ids
            FROM users
            WHERE deleted_at IS NULL
              AND user_type = 'provider-admin'
              AND phone IS NOT NULL
              AND phone <> ''
            GROUP BY digits
            HAVING c > 1
        ");

        if ($rows !== []) {
            $sample = collect($rows)->map(fn ($r) => "{$r->digits} => {$r->ids}")->implode('; ');
            throw new \RuntimeException(
                'Cannot add provider phone unique index; active provider-admin phone duplicates exist: '.$sample
            );
        }

        $providerRows = DB::select("
            SELECT REGEXP_REPLACE(COALESCE(contact_person_phone, ''), '[^0-9]', '') AS digits,
                   COUNT(*) AS c,
                   GROUP_CONCAT(id ORDER BY created_at SEPARATOR ',') AS ids
            FROM providers
            WHERE deleted_at IS NULL
              AND contact_person_phone IS NOT NULL
              AND contact_person_phone <> ''
            GROUP BY digits
            HAVING c > 1
        ");

        if ($providerRows !== []) {
            $sample = collect($providerRows)->map(fn ($r) => "{$r->digits} => {$r->ids}")->implode('; ');
            throw new \RuntimeException(
                'Cannot add providers contact phone unique index; active duplicates exist: '.$sample
            );
        }
    }

    private function assertNoActiveProviderAdminEmailDuplicates(): void
    {
        $rows = DB::select("
            SELECT LOWER(email) AS email_key,
                   COUNT(*) AS c,
                   GROUP_CONCAT(id ORDER BY created_at SEPARATOR ',') AS ids
            FROM users
            WHERE deleted_at IS NULL
              AND user_type = 'provider-admin'
              AND email IS NOT NULL
              AND email <> ''
            GROUP BY email_key
            HAVING c > 1
        ");

        if ($rows !== []) {
            $sample = collect($rows)->map(fn ($r) => "{$r->email_key} => {$r->ids}")->implode('; ');
            throw new \RuntimeException(
                'Cannot add provider email unique index; active provider-admin email duplicates exist: '.$sample
            );
        }
    }
};
