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

    public function getNameAttribute($value)
    {
        $translations = $this->translations;
        if ($translations->isEmpty()) {
            return $value;
        }

        $normalize = static fn (?string $locale): string => str_replace('_', '-', strtolower((string) $locale));
        $want = $normalize(app()->getLocale());

        foreach ($translations as $translation) {
            $key = $translation->key ?? $translation['key'] ?? null;
            if ($key !== 'zone_name') {
                continue;
            }
            $tLocale = $translation->locale ?? $translation['locale'] ?? '';
            if ($normalize($tLocale) === $want) {
                return $translation->value ?? $translation['value'];
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

    /**
     * Return the given zone id plus all descendant zone ids (recursive).
     *
     * Notes:
     * - We keep this iterative to avoid deep recursion.
     * - We query without the translate global scope since we only need ids.
     *
     * @return array<int, string>
     */
    public static function selfAndDescendantIds(string $zoneId): array
    {
        $seen = [];
        $frontier = [(string) $zoneId];

        while ($frontier !== []) {
            $frontier = array_values(array_unique(array_filter($frontier, static fn ($v) => $v !== null && $v !== '')));
            if ($frontier === []) {
                break;
            }

            foreach ($frontier as $id) {
                $seen[$id] = true;
            }

            $children = static::query()
                ->withoutGlobalScope('translate')
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->filter()
                ->map(static fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            // Only continue with not-yet-seen ids
            $frontier = array_values(array_filter($children, static fn (string $id) => ! isset($seen[$id])));
        }

        return array_keys($seen);
    }
}
