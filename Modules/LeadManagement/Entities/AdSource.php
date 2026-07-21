<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSource extends Model
{
    protected $table = 'adsources';

    protected $fillable = [
        'name',
        'description',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'ad_source_id');
    }

    /**
     * Find or create an Ad Source for a Click-to-WhatsApp Meta ad referral.
     * Prefers matching by Meta source_id; display name uses ad headline when available.
     */
    public static function ensureFromCtwaReferral(
        ?string $sourceId,
        ?string $headline = null,
        ?string $sourceUrl = null,
        ?string $sourceType = null,
        ?string $body = null
    ): ?self {
        $sourceId = trim((string) $sourceId);
        $headline = trim((string) $headline);
        $sourceUrl = trim((string) $sourceUrl);
        $sourceType = trim((string) $sourceType);
        $body = trim((string) $body);

        if ($sourceId === '' && $headline === '' && $sourceType === '' && $sourceUrl === '') {
            return null;
        }

        $name = self::ctwaDisplayName($sourceId, $headline);
        $description = self::ctwaDescription($sourceId, $sourceType, $sourceUrl, $body, $headline);

        if ($sourceId !== '') {
            $found = static::query()
                ->where('description', 'like', '%meta_source_id='.$sourceId.'%')
                ->first();
            if ($found) {
                $dirty = false;
                // Prefer a real headline over the fallback id-based name.
                if ($headline !== '' && $found->name !== $name && str_starts_with((string) $found->name, 'Facebook WhatsApp Ad')) {
                    $found->name = $name;
                    $dirty = true;
                }
                if (empty($found->description) || !str_contains((string) $found->description, 'meta_source_id='.$sourceId)) {
                    $found->description = $description;
                    $dirty = true;
                }
                if (!$found->is_active) {
                    $found->is_active = true;
                    $dirty = true;
                }
                if ($dirty) {
                    $found->save();
                }

                return $found;
            }
        }

        $foundByName = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();
        if ($foundByName) {
            $dirty = false;
            if ($sourceId !== '' && !str_contains((string) ($foundByName->description ?? ''), 'meta_source_id='.$sourceId)) {
                $foundByName->description = $description;
                $dirty = true;
            }
            if (!$foundByName->is_active) {
                $foundByName->is_active = true;
                $dirty = true;
            }
            if ($dirty) {
                $foundByName->save();
            }

            return $foundByName;
        }

        return static::create([
            'name' => $name,
            'description' => $description,
            'image' => null,
            'is_active' => true,
        ]);
    }

    protected static function ctwaDisplayName(string $sourceId, string $headline): string
    {
        if ($headline !== '') {
            return mb_substr($headline, 0, 191);
        }
        if ($sourceId !== '') {
            return mb_substr('Facebook WhatsApp Ad '.$sourceId, 0, 191);
        }

        return 'Facebook WhatsApp Ad';
    }

    protected static function ctwaDescription(
        string $sourceId,
        string $sourceType,
        string $sourceUrl,
        string $body,
        string $headline
    ): string {
        $lines = ['Click-to-WhatsApp (CTWA) Facebook/Instagram ad'];
        if ($sourceId !== '') {
            $lines[] = 'meta_source_id='.$sourceId;
        }
        if ($sourceType !== '') {
            $lines[] = 'meta_source_type='.$sourceType;
        }
        if ($sourceUrl !== '') {
            $lines[] = 'meta_source_url='.$sourceUrl;
        }
        if ($headline !== '') {
            $lines[] = 'headline='.$headline;
        }
        if ($body !== '') {
            $lines[] = 'body='.mb_substr($body, 0, 500);
        }

        return implode("\n", $lines);
    }
}
