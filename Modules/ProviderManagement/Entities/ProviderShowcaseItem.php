<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BusinessSettingsModule\Entities\Storage;

class ProviderShowcaseItem extends Model
{
    use HasUuid;

    protected $table = 'provider_showcase_items';

    public const STATUS_DENIED = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_PENDING = 2;

    protected $casts = [
        'is_active' => 'integer',
        'is_approved' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $fillable = [
        'provider_id',
        'title',
        'description',
        'media_type',
        'file_name',
        'sort_order',
        'is_active',
        'is_approved',
    ];

    public function scopeApproved($query)
    {
        return $query->where('is_approved', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', self::STATUS_PENDING);
    }

    protected $appends = ['media_full_path'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function storage()
    {
        return $this->hasOne(Storage::class, 'model_id');
    }

    public function getMediaFullPathAttribute(): ?string
    {
        $defaultPath = asset('assets/provider-module/img/user2x.png');

        if (!$this->file_name || strlen((string) $this->file_name) < 2) {
            return request()->is('api/*') ? null : $defaultPath;
        }

        $resolved = resolve_media_storage_url(
            (string) $this->file_name,
            'provider/showcase/',
            $this->relationLoaded('storage') ? ($this->storage?->storage_type) : null,
            request()->is('api/*') ? null : $defaultPath
        );

        if (request()->is('api/*') && ($resolved === null || $resolved === '' || $resolved === $defaultPath)) {
            return null;
        }

        return $resolved ?? (request()->is('api/*') ? null : $defaultPath);
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $storageType = getDisk();
            if ($model->isDirty('file_name') && $storageType != 'public') {
                saveSingleImageDataToStorage(model: $model, modelColumn: 'file_name', storageType: $storageType);
            }
        });
    }
}
