<?php

namespace Modules\ProviderManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingIgnore;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\BusinessSettingsModule\Entities\Storage;
use Modules\ReviewModule\Entities\Review;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use App\Traits\HasUuid;
use Modules\ZoneManagement\Entities\Zone;

class Provider extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $casts = [
        'order_count' => 'integer',
        'service_man_count' => 'integer',
        'service_capacity_per_day' => 'integer',
        'rating_count' => 'integer',
        'avg_rating' => 'float',
        'commission_status' => 'integer',
        'commission_percentage' => 'float',
        'commission_tier_setup' => 'array',
        'is_active' => 'integer',
        'app_availability' => 'integer',
        'is_approved' => 'integer',
        'coordinates' => 'json',
        'company_identity_images' => 'array',
        'is_active_for_jobs' => 'integer',
    ];

    protected $fillable = [];

    protected $hidden = [];

    protected $appends = ['logo_full_path', 'cover_image_full_path', 'company_identity_images_full_path'];

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_active', '=', $status);
    }

    public function scopeOfApproval($query, $status)
    {
        $query->where('is_approved', '=', $status);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->where('user_type', 'provider-admin');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /**
     * Leaf (and only operational) zones this provider serves — use for eligibility.
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'provider_zone')->withTimestamps();
    }

    public function scopeCoveringLeafZone($query, ?string $leafZoneId)
    {
        if (! $leafZoneId) {
            return $query->whereRaw('1 = 0');
        }

        $table = $query->getModel()->getTable();

        return $query->where(function ($outer) use ($leafZoneId, $table) {
            $outer->whereHas('zones', function ($q) use ($leafZoneId) {
                $q->where('zones.id', $leafZoneId);
            })->orWhere(function ($q) use ($leafZoneId, $table) {
                $q->whereDoesntHave('zones')
                    ->where($table . '.zone_id', $leafZoneId);
            });
        });
    }

    /**
     * Cover providers for a zone, whether it's a leaf or a parent zone.
     * If a parent zone is selected, providers serving any descendant zone are eligible.
     */
    public function scopeCoveringZoneOrDescendants($query, ?string $zoneId)
    {
        if (! $zoneId) {
            return $query->whereRaw('1 = 0');
        }

        $zoneIds = Zone::selfAndDescendantIds((string) $zoneId);
        if ($zoneIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $table = $query->getModel()->getTable();

        return $query->where(function ($outer) use ($zoneIds, $table) {
            $outer->whereHas('zones', function ($q) use ($zoneIds) {
                $q->whereIn('zones.id', $zoneIds);
            })->orWhere(function ($q) use ($zoneIds, $table) {
                $q->whereDoesntHave('zones')
                    ->whereIn($table . '.zone_id', $zoneIds);
            });
        });
    }

    /**
     * Leaf zone IDs this provider serves (pivot), with legacy zone_id fallback.
     *
     * @return array<int, string>
     */
    public function coveredLeafZoneIds(): array
    {
        if ($this->relationLoaded('zones') && $this->zones->isNotEmpty()) {
            return $this->zones->pluck('id')->map(fn ($id) => (string) $id)->unique()->values()->all();
        }

        $ids = $this->zones()->pluck('zones.id')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();
        if ($ids === [] && $this->zone_id) {
            return [(string) $this->zone_id];
        }

        return $ids;
    }

    public function bank_detail(): HasOne
    {
        return $this->hasOne(BankDetail::class, 'provider_id');
    }

    public function bank_details(): HasMany
    {
        return $this->hasMany(BankDetail::class, 'provider_id');
    }

    public function bookings($booking_status = null): HasMany
    {
        if ($booking_status == null) {
            return $this->hasMany(Booking::class, 'provider_id');
        }

        return $this->hasMany(Booking::class, 'provider_id')->where('booking_status', $booking_status);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteProvider::class, 'provider_id', 'id');
    }

    public function subscribed_services(): HasMany
    {
        return $this->hasMany(SubscribedService::class, 'provider_id')->where('is_subscribed', 1);
    }

    public function servicemen(): HasMany
    {
        return $this->hasMany(Serviceman::class, 'provider_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'provider_id', 'id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(\Modules\ProviderManagement\Entities\ProviderIncident::class, 'provider_id', 'id');
    }

    public function storage()
    {
        return $this->hasOne(Storage::class, 'model_id');
    }

    public function packageSubscriptions()
    {
        return $this->hasOne(PackageSubscriber::class);
    }
    public function ignoredBookings()
    {
        return $this->hasMany(BookingIgnore::class);
    }


    public function getLogoFullPathAttribute()
    {
        $image = $this->logo;
        $defaultPath =  asset('assets/provider-module/img/user2x.png');

        if (!$image) {
            if (request()->is('api/*')) {
                $defaultPath = null;
            }
            return $defaultPath;
        }

        $s3Storage = $this->storage;
        $path = 'provider/logo/';
        $imagePath = $path . $image;

        return getSingleImageFullPath(imagePath: $imagePath, s3Storage: $s3Storage, defaultPath: $defaultPath);
    }

    public function getCoverImageFullPathAttribute()
    {
        $image = $this->cover_image;
        $defaultPath =  asset('assets/provider-module/img/user2x.png');

        if (!$image) {
            if (request()->is('api/*')) {
                $defaultPath = null;
            }
            return $defaultPath;
        }

        $s3Storage = $this->storage;
        $path = 'provider/logo/';
        $imagePath = $path . $image;

        return getSingleImageFullPath(imagePath: $imagePath, s3Storage: $s3Storage, defaultPath: $defaultPath);
    }

    public function getCompanyIdentityImagesFullPathAttribute()
    {
        $identityImages = $this->company_identity_images ?? [];
        $defaultImagePath = asset('assets/admin-module/img/media/provider-id.png');
        $path = 'provider/company-identity/';

        return getIdentityImageFullPath(
            identityImages: $identityImages,
            path: $path,
            defaultPath: $defaultImagePath
        );
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            // ... code here
        });

        self::created(function ($model) {
            // ... code here
        });

        self::updating(function ($model) {
            if ($model->isDirty('zone_id')) {
                DB::table('subscribed_services')->where(['provider_id' => $model->id])->update(['is_subscribed' => 0]);
            }
        });

        self::updated(function ($model) {
            // ... code here
        });

        self::deleting(function ($model) {
            // ... code here
        });

        self::deleted(function ($model) {
            $model->servicemen->each(function ($serviceman) {
                $serviceman->user->update(['is_active' => 0]);
            });
        });

        static::saved(function ($model) {
            $storageType = getDisk();
            if($model->isDirty('logo') && $storageType != 'public'){
                saveSingleImageDataToStorage(model: $model, modelColumn : 'logo', storageType : $storageType);
            }
        });
    }
}
