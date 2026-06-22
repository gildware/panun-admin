<?php

namespace Modules\CallCenterModule\Services;

class PhoneNormalizer
{
    public function normalize(?string $phone): string
    {
        $trim = trim((string) $phone);
        if ($trim === '') {
            return '';
        }

        $hasPlus = str_starts_with($trim, '+');
        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        if ($digits === '') {
            return $trim;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        $defaultPrefix = (string) config('callcentermodule.default_country_prefix', '91');
        if (strlen($digits) === 10 && $defaultPrefix !== '') {
            return '+' . $defaultPrefix . $digits;
        }

        return '+' . $digits;
    }

    public function digitsOnly(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }
}
