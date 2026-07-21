<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdSource extends Model
{
    protected $table = 'adsources';

    protected $fillable = [
        'meta_ad_id',
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

    public function imagePublicUrl(): ?string
    {
        $image = trim((string) ($this->image ?? ''));
        if ($image === '') {
            return null;
        }
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return asset('storage/ad-source/'.ltrim($image, '/'));
    }

    public static function findByMetaSourceId(?string $sourceId): ?self
    {
        $sourceId = trim((string) $sourceId);
        if ($sourceId === '') {
            return null;
        }

        if (Schema::hasColumn((new static)->getTable(), 'meta_ad_id')) {
            $byCol = static::query()->where('meta_ad_id', $sourceId)->first();
            if ($byCol) {
                return $byCol;
            }
        }

        // Boundary-safe: avoid meta_source_id=12 matching meta_source_id=12345.
        return static::query()
            ->where(function ($q) use ($sourceId) {
                $q->where('description', 'like', '%meta_source_id='.$sourceId."\n%")
                    ->orWhere('description', 'like', '%meta_source_id='.$sourceId."\r\n%")
                    ->orWhere('description', 'like', '%meta_source_id='.$sourceId);
            })
            ->get()
            ->first(function (self $row) use ($sourceId) {
                return preg_match('/meta_source_id='.preg_quote($sourceId, '/').'(?:\s|$)/', (string) $row->description) === 1;
            });
    }

    /**
     * One Ad Source per Meta ad id. Never merge different ads that share a headline.
     *
     * @param  array<string, mixed>|null  $referralFull
     */
    public static function ensureFromCtwaReferral(
        ?string $sourceId,
        ?string $headline = null,
        ?string $sourceUrl = null,
        ?string $sourceType = null,
        ?string $body = null,
        ?string $imageUrl = null,
        ?array $referralFull = null
    ): ?self {
        $sourceId = trim((string) $sourceId);
        $headline = trim((string) $headline);
        $sourceUrl = trim((string) $sourceUrl);
        $sourceType = trim((string) $sourceType);
        $body = trim((string) $body);
        $imageUrl = trim((string) ($imageUrl ?: self::creativeImageUrlFromReferral($referralFull)));

        if ($sourceId === '' && $headline === '' && $sourceType === '' && $sourceUrl === '' && $body === '') {
            return null;
        }

        // Prefer Meta ad id. Never key by WhatsApp deep links (shared across ads).
        // Without a Meta ad id, key by creative fingerprint (headline+body+image).
        $identityKey = '';
        if ($sourceId !== '') {
            $identityKey = $sourceId;
        } elseif (self::isDistinctAdLandingUrl($sourceUrl)) {
            $identityKey = 'url:'.substr(hash('sha256', strtolower($sourceUrl)), 0, 24);
        } elseif ($headline !== '' || $body !== '' || $imageUrl !== '') {
            $identityKey = 'creative:'.substr(hash('sha256', strtolower($headline.'|'.mb_substr($body, 0, 200).'|'.$imageUrl)), 0, 24);
        }

        $name = self::ctwaDisplayName($sourceId, $headline, $body);
        $description = self::ctwaDescription($sourceId, $sourceType, $sourceUrl, $body, $headline, $imageUrl, $identityKey);

        $found = null;
        if ($sourceId !== '') {
            $found = self::findByMetaSourceId($sourceId);
        } elseif ($identityKey !== '') {
            $found = static::query()
                ->where('description', 'like', '%meta_identity='.$identityKey.'%')
                ->get()
                ->first(function (self $row) use ($identityKey) {
                    return preg_match('/meta_identity='.preg_quote($identityKey, '/').'(?:\s|$)/', (string) $row->description) === 1;
                });
        }

        // Do NOT fall back to matching by display name — same headline ≠ same ad.

        if ($found) {
            $dirty = false;
            if (Schema::hasColumn($found->getTable(), 'meta_ad_id') && $sourceId !== '' && (string) $found->meta_ad_id !== $sourceId) {
                $found->meta_ad_id = $sourceId;
                $dirty = true;
            }
            // Same identity → keep name/image in sync with this referral (do not leave stale creative).
            if (!self::isBadAdName($name) && (string) $found->name !== $name) {
                $found->name = $name;
                $dirty = true;
            } elseif (self::isBadAdName($found->name) && !self::isBadAdName($name)) {
                $found->name = $name;
                $dirty = true;
            }
            // Refresh description for this exact ad (url/image/body) without colliding other ads.
            if ($description !== (string) $found->description) {
                $found->description = $description;
                $dirty = true;
            }
            if (!$found->is_active) {
                $found->is_active = true;
                $dirty = true;
            }
            if ($imageUrl !== '') {
                $stored = self::downloadCreativeImage($imageUrl, $identityKey !== '' ? $identityKey : $sourceId);
                if ($stored && $stored !== (string) $found->image) {
                    $found->image = $stored;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $found->save();
            }

            return $found;
        }

        $attrs = [
            'name' => $name,
            'description' => $description,
            'image' => $imageUrl !== ''
                ? self::downloadCreativeImage($imageUrl, $identityKey !== '' ? $identityKey : $sourceId)
                : null,
            'is_active' => true,
        ];
        if (Schema::hasColumn((new static)->getTable(), 'meta_ad_id') && $sourceId !== '') {
            $attrs['meta_ad_id'] = $sourceId;
        }

        return static::create($attrs);
    }

    /**
     * Prefer a usable “open the ad” URL (fb.me / Instagram) over WhatsApp deep links.
     *
     * @param  array<string, mixed>|null  $referral
     */
    public static function viewAdUrl(?string $sourceUrl, ?array $referral = null): ?string
    {
        $candidates = [];
        $sourceUrl = trim((string) $sourceUrl);
        if ($sourceUrl !== '') {
            $candidates[] = $sourceUrl;
        }
        if (is_array($referral)) {
            foreach (['source_url', 'ad_url', 'url'] as $key) {
                $v = trim((string) ($referral[$key] ?? ''));
                if ($v !== '') {
                    $candidates[] = $v;
                }
            }
        }

        $fallback = null;
        foreach (array_unique($candidates) as $url) {
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                continue;
            }
            $lower = strtolower($url);
            if (str_contains($lower, 'api.whatsapp.com') || str_contains($lower, 'wa.me/')) {
                $fallback = $fallback ?: $url;
                continue;
            }

            return $url;
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>|null  $referral
     */
    public static function creativeImageUrlFromReferral(?array $referral): ?string
    {
        if (!is_array($referral)) {
            return null;
        }
        foreach (['image_url', 'thumbnail_url'] as $key) {
            $v = trim((string) ($referral[$key] ?? ''));
            if ($v !== '' && (str_starts_with($v, 'http://') || str_starts_with($v, 'https://'))) {
                return $v;
            }
        }

        return null;
    }

    public static function isBadAdName(?string $name): bool
    {
        $n = trim((string) $name);
        if ($n === '') {
            return true;
        }
        $lower = strtolower($n);
        if (str_contains($lower, '://')) {
            return true;
        }
        if (preg_match('/^(www\.)?(api\.)?whatsapp\.com$/i', $lower)) {
            return true;
        }
        if (preg_match('/^(www\.)?(fb\.me|facebook\.com|instagram\.com|ig\.me|l\.facebook\.com)$/i', $lower)) {
            return true;
        }
        if (preg_match('/^[a-z0-9.-]+\.(com|net|org|me|io|co)$/i', $lower) && !str_contains($n, ' ')) {
            return true;
        }

        return false;
    }

    public static function isDistinctAdLandingUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '' || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
            return false;
        }
        $lower = strtolower($url);
        if (str_contains($lower, 'api.whatsapp.com') || str_contains($lower, 'wa.me/')) {
            return false;
        }

        return true;
    }

    protected static function isGenericCtwaName(?string $name): bool
    {
        $n = strtolower(trim((string) $name));

        return $n === 'whatsapp ad'
            || preg_match('/^whatsapp ad \d+$/', $n) === 1
            || $n === 'facebook whatsapp ad'
            || str_starts_with($n, 'facebook whatsapp ad ')
            || $n === 'instagram whatsapp ad'
            || str_starts_with($n, 'instagram whatsapp ad ')
            || $n === 'api.whatsapp.com';
    }

    protected static function ctwaDisplayName(string $sourceId, string $headline, string $body): string
    {
        if (!self::isBadAdName($headline)) {
            return mb_substr($headline, 0, 191);
        }

        $bodyLine = trim((string) preg_split('/\r\n|\r|\n/', $body)[0]);
        if (!self::isBadAdName($bodyLine) && mb_strlen($bodyLine) >= 3) {
            return mb_substr($bodyLine, 0, 191);
        }

        if ($sourceId !== '') {
            return mb_substr('WhatsApp Ad '.$sourceId, 0, 191);
        }

        return 'WhatsApp Ad';
    }

    protected static function ctwaDescription(
        string $sourceId,
        string $sourceType,
        string $sourceUrl,
        string $body,
        string $headline,
        string $imageUrl,
        string $identityKey = ''
    ): string {
        $lines = ['Click-to-WhatsApp (CTWA) ad creative'];
        if ($sourceId !== '') {
            $lines[] = 'meta_source_id='.$sourceId;
        }
        if ($identityKey !== '' && $identityKey !== $sourceId) {
            $lines[] = 'meta_identity='.$identityKey;
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
        if ($imageUrl !== '') {
            $lines[] = 'image_url='.mb_substr($imageUrl, 0, 500);
        }

        return implode("\n", $lines);
    }

    protected static function downloadCreativeImage(string $url, ?string $identityKey = null): ?string
    {
        $url = trim($url);
        if ($url === '' || (!str_starts_with($url, 'https://') && !str_starts_with($url, 'http://'))) {
            return null;
        }

        try {
            $resp = Http::timeout(20)->withHeaders([
                'User-Agent' => 'PanunKaergarCtwa/1.0',
            ])->accept('*/*')->get($url);
        } catch (\Throwable $e) {
            Log::warning('CTWA ad creative download failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if (!$resp->successful()) {
            return null;
        }
        $body = $resp->body();
        if (!is_string($body) || $body === '' || strlen($body) > 8 * 1024 * 1024) {
            return null;
        }

        $contentType = strtolower(trim((string) $resp->header('Content-Type', '')));
        $ext = 'jpg';
        if (str_contains($contentType, 'png')) {
            $ext = 'png';
        } elseif (str_contains($contentType, 'webp')) {
            $ext = 'webp';
        } elseif (str_contains($contentType, 'gif')) {
            $ext = 'gif';
        } elseif (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
            $ext = 'jpg';
        }

        $key = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) $identityKey) ?: '';
        $filename = $key !== ''
            ? 'ctwa-'.substr($key, 0, 80).'.'.$ext
            : 'ctwa-'.Str::uuid()->toString().'.'.$ext;
        try {
            Storage::disk('public')->put('ad-source/'.$filename, $body);
        } catch (\Throwable $e) {
            Log::warning('CTWA ad creative store failed', ['error' => $e->getMessage()]);

            return null;
        }

        return $filename;
    }
}
