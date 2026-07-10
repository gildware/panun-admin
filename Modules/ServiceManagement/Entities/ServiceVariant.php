<?php

namespace Modules\ServiceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\BusinessSettingsModule\Entities\Storage;
use Modules\BusinessSettingsModule\Entities\Translation;

class ServiceVariant extends Model
{
    use HasUuid;

    protected $fillable = [
        'service_id',
        'variant_key',
        'title',
        'description',
        'note',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_full_path'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function zonePrices(): HasMany
    {
        return $this->hasMany(Variation::class, 'service_variant_id', 'id');
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function storage_image()
    {
        return $this->hasOne(Storage::class, 'model_id')->where('model_column', 'image');
    }

    public function getTitleAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] === 'title') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getDescriptionAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] === 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getNoteAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] === 'note') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getImageFullPathAttribute(): ?string
    {
        $image = $this->image;
        $defaultPath = request()->is('*/edit/*') || request()->is('*/variant/*')
            ? asset('assets/admin-module/img/media/upload-file.png')
            : asset('assets/admin-module/img/placeholder.png');

        if (! $image) {
            if (request()->is('api/*')) {
                return null;
            }

            return $defaultPath;
        }

        $imagePath = resolve_stored_media_key($image, \App\Support\MediaStoragePath::legacyPrefixForService());

        return getSingleImageFullPath(
            imagePath: $imagePath,
            s3Storage: $this->storage_image,
            defaultPath: request()->is('api/*') ? null : $defaultPath
        );
    }

    protected static function booted(): void
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });

        static::saved(function ($model) {
            $storageType = getDisk();
            if ($model->isDirty('image') && $storageType !== 'public') {
                saveSingleImageDataToStorage(model: $model, modelColumn: 'image', storageType: $storageType);
            }
        });

        static::deleting(function ($model) {
            if ($model->image) {
                file_remover('service/', $model->image);
            }
            $model->translations()->delete();
        });
    }
}
