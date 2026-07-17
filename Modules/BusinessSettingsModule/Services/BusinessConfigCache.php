<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\DataSetting;
use Modules\BusinessSettingsModule\Entities\LoginSetup;

/**
 * Caches business settings lookups to avoid one DB query per business_config() call.
 */
class BusinessConfigCache
{
    public const TTL = 600;

    /** @var array<string, mixed> */
    private static array $request = [];

    public static function businessConfig(string $key, string $settingsType): ?BusinessSettings
    {
        $lookup = "{$settingsType}:{$key}";

        if (array_key_exists($lookup, self::$request)) {
            return self::$request[$lookup];
        }

        try {
            $all = self::allBusinessSettings();
            $config = $all->get($lookup);
        } catch (\Throwable) {
            $config = null;
        }

        return self::$request[$lookup] = $config instanceof BusinessSettings ? $config : null;
    }

    public static function dataConfig(string $key, string $settingsType): ?DataSetting
    {
        $lookup = "{$settingsType}:{$key}";

        if (array_key_exists("data:{$lookup}", self::$request)) {
            return self::$request["data:{$lookup}"];
        }

        try {
            $all = self::allDataSettings();
            $config = $all->get($lookup);
        } catch (\Throwable) {
            $config = null;
        }

        return self::$request["data:{$lookup}"] = $config instanceof DataSetting ? $config : null;
    }

    public static function loginSetup(string $key): ?LoginSetup
    {
        if (array_key_exists("login:{$key}", self::$request)) {
            return self::$request["login:{$key}"];
        }

        try {
            $all = self::allLoginSetups();
            $config = $all->get($key);
        } catch (\Throwable) {
            $config = null;
        }

        return self::$request["login:{$key}"] = $config instanceof LoginSetup ? $config : null;
    }

    public static function forgetAll(): void
    {
        self::$request = [];
        Cache::forget('business_settings:all:v1');
        Cache::forget('business_settings:all:v2');
        Cache::forget('business_settings:all:v3');
        Cache::forget('data_settings:all:v1');
        Cache::forget('login_setups:all:v1');
    }

    /**
     * @return Collection<string, BusinessSettings>
     */
    private static function allBusinessSettings(): Collection
    {
        return Cache::remember('business_settings:all:v3', self::TTL, function () {
            return BusinessSettings::query()
                ->get()
                ->keyBy(fn (BusinessSettings $row) => "{$row->settings_type}:{$row->key_name}");
        });
    }

    /**
     * @return Collection<string, DataSetting>
     */
    private static function allDataSettings(): Collection
    {
        return Cache::remember('data_settings:all:v1', self::TTL, function () {
            return DataSetting::query()
                ->get()
                ->keyBy(fn (DataSetting $row) => "{$row->type}:{$row->key}");
        });
    }

    /**
     * @return Collection<string, LoginSetup>
     */
    private static function allLoginSetups(): Collection
    {
        return Cache::remember('login_setups:all:v1', self::TTL, function () {
            return LoginSetup::query()
                ->get()
                ->keyBy(fn (LoginSetup $row) => (string) $row->key);
        });
    }
}
