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
     * Live variation rows for this key (booking/admin source of truth).
     * Prefer service_id + variant_key — service_variant_id can be missing on older rows.
     */
    public function liveVariationRows(?Service $service = null): \Illuminate\Support\Collection
    {
        $service = $service ?? $this->service;
        $serviceId = $service?->id ?? $this->service_id;

        if ($service && $service->relationLoaded('variations')) {
            $rows = $service->variations->where('variant_key', $this->variant_key)->values();
            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        if ($serviceId) {
            $rows = Variation::withoutGlobalScopes()
                ->where('service_id', $serviceId)
                ->where('variant_key', $this->variant_key)
                ->get();
            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        if ($this->relationLoaded('zonePrices')) {
            return $this->zonePrices;
        }

        return $this->zonePrices()->withoutGlobalScopes()->get();
    }

    /**
     * Price shown in admin variation lists — same basis as the edit form.
     */
    public function displayPrice(?Service $service = null): float
    {
        [$zonePricingOn, $defaultPrice] = $this->resolveAdminPricing($service);
        $live = $this->liveVariationRows($service);
        $positive = $live->pluck('price')->map(fn ($p) => (float) $p)->filter(fn ($p) => $p > 0)->values();

        if ($zonePricingOn && $positive->isNotEmpty()) {
            $unique = $positive->map(fn ($p) => round($p, 4))->unique()->values();
            if ($unique->count() === 1) {
                return (float) $unique->first();
            }

            // Mixed zone prices: prefer admin default when set, else lowest zone price.
            return $defaultPrice > 0 ? $defaultPrice : (float) $positive->min();
        }

        return $defaultPrice;
    }

    /**
     * Default price for edit/view forms — keep in sync with live variations when they agree.
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
        $live = $this->liveVariationRows($service);
        $livePrices = $live->pluck('price')->map(fn ($p) => round((float) $p, 4))->filter(fn ($p) => $p > 0)->values();

        if ($livePrices->isNotEmpty()) {
            $unique = $livePrices->unique()->values();
            // Unanimous live rows beat stale JSON (both for zone-on and zone-off).
            if ($unique->count() === 1) {
                $defaultPrice = (float) $unique->first();
            } elseif ($defaultPrice <= 0) {
                $defaultPrice = (float) $livePrices->min();
            }
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
