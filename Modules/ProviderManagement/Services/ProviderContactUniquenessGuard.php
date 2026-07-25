<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\UserManagement\Entities\User;

/**
 * Race-safe provider contact uniqueness for registration/create.
 *
 * App-level checks alone can pass twice under double-submit; this guard
 * serializes creates per phone (MySQL GET_LOCK) and re-checks before insert.
 * Pair with DB unique indexes on active provider-admin phone/email digits.
 */
class ProviderContactUniquenessGuard
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(string $phone, string $email, callable $callback): mixed
    {
        $lockName = $this->lockName($phone, $email);
        if (! $this->acquireLock($lockName, 15)) {
            throw ValidationException::withMessages([
                'contact_person_phone' => translate('Another registration is already in progress for this contact. Please wait and try again.'),
            ]);
        }

        try {
            $errors = User::providerContactRegistrationErrors($phone, $email);
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            try {
                return $callback();
            } catch (QueryException $e) {
                if ($this->isDuplicateKeyException($e)) {
                    throw ValidationException::withMessages([
                        'contact_person_phone' => translate('The contact person phone has already been taken.'),
                        'contact_person_email' => translate('The contact person email has already been taken.'),
                    ]);
                }

                throw $e;
            }
        } finally {
            $this->releaseLock($lockName);
        }
    }

    private function lockName(string $phone, string $email): string
    {
        $digits = User::normalizeContactPhoneDigits($phone);
        if ($digits !== '') {
            // MySQL GET_LOCK name max length is 64.
            return 'prov_reg_ph_' . substr($digits, -48);
        }

        $emailKey = Str::lower(trim($email));
        if ($emailKey !== '') {
            return 'prov_reg_em_' . substr(hash('sha256', $emailKey), 0, 40);
        }

        return 'prov_reg_empty';
    }

    private function acquireLock(string $name, int $timeoutSeconds): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }

        $row = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$name, $timeoutSeconds]);

        return $row && (int) $row->acquired === 1;
    }

    private function releaseLock(string $name): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000'
            || $driverCode === 1062
            || str_contains(Str::lower($e->getMessage()), 'duplicate');
    }
}
