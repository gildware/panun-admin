<?php

namespace Modules\BusinessSettingsModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\BusinessSettingsModule\Services\BusinessConfigCache;

class LoginSetup extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = ['key','value'];

    protected static function newFactory()
    {
        return \Modules\BusinessSettingsModule\Database\factories\LoginSetupFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(fn () => BusinessConfigCache::forgetAll());
        static::deleted(fn () => BusinessConfigCache::forgetAll());
    }
}
