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
        'icon',
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

    /**
     * Price shown in admin variation lists.
     * Prefer live zone rows (same source as the edit form), not only JSON variation_pricing.
     */
    public function displayPrice(?Service $service = null): float
    {
        $service = $service ?? $this->service;
        $config = $service
            ? Variation::variationPricingConfig($service, $this->variant_key)
            : ['use_zone_pricing' => false, 'default_price' => 0.0];
        $jsonDefault = (float) ($config['default_price'] ?? 0);
        $live = $this->relationLoaded('zonePrices') ? $this->zonePrices : $this->zonePrices()->get();

        if (! empty($config['use_zone_pricing'])) {
            $minPositive = $live->where('price', '>', 0)->min('price');
            if ($minPositive !== null) {
                return (float) $minPositive;
            }

            return $jsonDefault > 0 ? $jsonDefault : (float) ($live->first()->price ?? 0);
        }

        if ($live->isNotEmpty()) {
            return (float) ($live->first()->price ?? $jsonDefault);
        }

        return $jsonDefault;
    }

    /**
     * Default price for edit/view forms — keep in sync with live variations when zone pricing is off.
     *
     * @return array{0: bool, 1: float} [use_zone_pricing, default_price]
     */
    public function resolveAdminPricing(?Service $service = null): array
    {
        $service = $service ?? $this->service;
        $vp = is_array($service?->variation_pricing) ? $service->variation_pricing : [];
        $stored = $vp[$this->variant_key] ?? null;
        $zonePricingOn = is_array($stored) ? (bool) ($stored['use_zone_pricing'] ?? false) : false;
        $defaultPrice = is_array($stored) ? (float) ($stored['default_price'] ?? 0) : 0;
        $live = $this->relationLoaded('zonePrices') ? $this->zonePrices : $this->zonePrices()->get();
        $livePrices = $live->pluck('price')->map(fn ($p) => round((float) $p, 4));

        if (! $zonePricingOn && $livePrices->isNotEmpty()) {
            $unique = $livePrices->unique()->values();
            // When all zone rows share one price, that is the real default (may be ahead of JSON).
            if ($unique->count() === 1) {
                $defaultPrice = (float) $unique->first();
            } elseif ($defaultPrice <= 0) {
                $defaultPrice = (float) ($live->first()->price ?? 0);
            }
        } elseif ($zonePricingOn && $defaultPrice <= 0) {
            $minPositive = $live->where('price', '>', 0)->min('price');
            $defaultPrice = (float) ($minPositive ?? $live->first()->price ?? 0);
        }

        return [$zonePricingOn, $defaultPrice];
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
