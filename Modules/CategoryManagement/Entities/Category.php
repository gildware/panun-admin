<?php

namespace Modules\CategoryManagement\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\BusinessSettingsModule\Entities\Storage;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\PromotionManagement\Entities\DiscountType;
use Modules\ServiceManagement\Entities\Service;
use Modules\ZoneManagement\Entities\Zone;
use App\Traits\HasUuid;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;
    use HasUuid;

    protected $casts = [
        'position'  => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'integer',
        'slug'      => 'string',
        'commission_custom' => 'integer',
        'commission_tier_setup' => 'array',
        'additional_charge_overrides' => 'array',
        'tax_percentage' => 'float',
    ];

    protected $appends = ['image_full_path', 'image_dark_full_path'];

    protected $fillable = ['slug', 'sort_order'];

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_active', '=', $status);
    }

    public function scopeOfFeatured($query, $status)
    {
        $query->where('is_featured', '=', $status);
    }

    public function scopeOfType($query, $type)
    {
        $value = ($type == 'main') ? 1 : 2;
        $query->where(['position' => $value]);
    }

    /**
     * Sub-categories that have at least one active bookable service.
     */
    public function scopeWithActiveServices($query)
    {
        return $query->whereHas('services', function ($serviceQuery) {
            $serviceQuery->where('is_active', 1);
        });
    }

    /**
     * Main categories visible to customers: at least one active sub-category and at least one active service
     * (on the main category or any of its sub-categories).
     */
    public function scopeMainWithActiveCatalog($query)
    {
        return $query
            ->whereHas('children', function ($childQuery) {
                // Sub-categories inherit zone visibility from the parent; they are often not linked in category_zone.
                $childQuery->withoutGlobalScope('zone_wise_data')
                    ->ofStatus(1)
                    ->ofType('sub');
            })
            ->where(function ($catalogQuery) {
                $catalogQuery
                    ->whereHas('services_by_category', function ($serviceQuery) {
                        $serviceQuery->where('is_active', 1);
                    })
                    ->orWhereHas('children', function ($childQuery) {
                        $childQuery->withoutGlobalScope('zone_wise_data')
                            ->ofStatus(1)
                            ->ofType('sub')
                            ->withActiveServices();
                    });
            });
    }

    public function zones(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'category_zone');
    }

    public function zonesBasicInfo(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'category_zone')
            ->select(['zones.id', 'zones.name', 'zones.parent_id']);
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->ordered();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function category_discount(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DiscountType::class, 'type_wise_id')
            ->whereHas('discount', function ($query) {
                $query->whereIn('discount_type', ['category', 'mixed'])
                    ->where('promotion_type', 'discount')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->where('is_active', 1);
            })->whereHas('discount.discount_types', function ($query) {
                $query->where(['discount_type' => 'zone', 'type_wise_id' => config('zone_id')]);
            })->with(['discount'])->latest();
    }

    public function campaign_discount(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DiscountType::class, 'type_wise_id')
            ->whereHas('discount', function ($query) {
                $query->where('promotion_type', 'campaign')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->where('is_active', 1);
            })->whereHas('discount.discount_types', function ($query) {
                $query->where(['discount_type' => 'zone', 'type_wise_id' => config('zone_id')]);
            })->with(['discount'])->latest();
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Service::class, 'sub_category_id')->ordered();
    }

    public function services_by_category(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Service::class, 'category_id')->ordered();
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function getNameAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'name') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getDescriptionAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function storage()
    {
        return $this->hasOne(Storage::class, 'model_id')
            ->where('model', self::class)
            ->where('model_column', 'image');
    }

    public function getImageFullPathAttribute()
    {
        $image = $this->image;
        $defaultPath = asset('assets/placeholder.png');
        if (request()->is('admin/*')) {
            $defaultPath = asset('assets/admin-module/img/media/upload-file.png');
        }

        if (!$image) {
            if (request()->is('api/*')) {
                $defaultPath = null;
            }
            return $defaultPath;
        }

        $s3Storage = $this->storage;

        $imagePath = resolve_stored_media_key(
            $image,
            \App\Support\MediaStoragePath::legacyPrefixForCategory($this)
        );

        return getSingleImageFullPath(imagePath: $imagePath, s3Storage: $s3Storage, defaultPath: $defaultPath);
    }

    public function darkStorage()
    {
        return $this->hasOne(Storage::class, 'model_id')
            ->where('model', self::class)
            ->where('model_column', 'image_dark');
    }

    public function getImageDarkFullPathAttribute()
    {
        $image = $this->image_dark;
        if (! $image) {
            return request()->is('api/*') ? null : asset('assets/placeholder.png');
        }

        $imagePath = resolve_stored_media_key(
            $image,
            \App\Support\MediaStoragePath::legacyPrefixForCategory($this)
        );

        return getSingleImageFullPath(
            imagePath: $imagePath,
            s3Storage: $this->darkStorage,
            defaultPath: request()->is('api/*') ? null : asset('assets/placeholder.png')
        );
    }

    protected static function generateUniqueSlug($name, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (
        static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }

    protected static function booted()
    {
        static::addGlobalScope('zone_wise_data', function (Builder $builder) {
            if (request()->is('api/*/customer?*') || request()->is('api/*/customer/*')) {
                $builder->whereHas('zones', function ($query) {
                    $query->where('zone_id', Config::get('zone_id'));
                })->with(['category_discount', 'campaign_discount']);
            }
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });

        static::saved(function ($model) {
            $storageType = getDisk();
            if ($model->isDirty('image') && $storageType != 'public') {
                saveSingleImageDataToStorage(model: $model, modelColumn: 'image', storageType: $storageType);
            }
            if ($model->isDirty('image_dark') && $storageType != 'public') {
                saveSingleImageDataToStorage(model: $model, modelColumn: 'image_dark', storageType: $storageType);
            }
        });

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            $originalName = $category->getOriginal('name');
            $currentName = $category->name;

            if ($originalName !== $currentName || empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });
    }
}
