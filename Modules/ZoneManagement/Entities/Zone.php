<?php

namespace Modules\ZoneManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\BusinessSettingsModule\Entities\Translation;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Zone extends Model
{
    use HasFactory;
    use HasSpatial;
    use HasUuid;

    protected $casts = [
        'is_active' => 'integer',
        'coordinates' => Polygon::class,

    ];

    protected $fillable = [
        'coordinates',
        'description',
    ];

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_active', '=', $status);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class);
    }

    public function parentZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_id');
    }

    public function childZones(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id');
    }

    /**
     * Build ordered rows for a single HTML select: roots first, then depth-first children with indented labels.
     *
     * @param  Collection<int, self>  $zones
     * @return array<int, array{id: string, label: string}>
     */
    public static function flatTreeOptionsForSelect(Collection $zones): array
    {
        if ($zones->isEmpty()) {
            return [];
        }

        $zoneIds = $zones->pluck('id')->all();
        $zoneIdSet = array_flip($zoneIds);
        $sortName = static fn (self $z): string => mb_strtolower((string) ($z->name ?? ''));

        $childrenByParent = $zones->groupBy('parent_id');

        $roots = $zones
            ->filter(function (self $z) use ($zoneIdSet) {
                if ($z->parent_id === null || $z->parent_id === '') {
                    return true;
                }

                return ! isset($zoneIdSet[$z->parent_id]);
            })
            ->unique('id')
            ->sortBy($sortName)
            ->values();

        $out = [];
        $walk = null;
        $walk = function (self $zone, int $depth) use (&$out, &$walk, $childrenByParent, $sortName): void {
            $prefix = $depth > 0
                ? str_repeat("\u{00A0}\u{00A0}\u{00A0}\u{00A0}", $depth)."\u{2514}\u{2500} "
                : '';
            $out[] = [
                'id' => $zone->id,
                'label' => $prefix.($zone->name ?? ''),
            ];
            $kids = $childrenByParent->get($zone->id, collect())->sortBy($sortName);
            foreach ($kids as $child) {
                if ($child->id === $zone->id) {
                    continue;
                }
                $walk($child, $depth + 1);
            }
        };

        foreach ($roots as $root) {
            $walk($root, 0);
        }

        return $out;
    }

    /**
     * Providers serving this zone via the provider_zone pivot (leaf coverage).
     */
    public function coveringProviders(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'provider_zone')->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function getNameAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'zone_name') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }
}
