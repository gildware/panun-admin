<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        return static::query()
            ->where('description', 'like', '%meta_source_id='.$sourceId.'%')
            ->first();
    }

    /**
     * Find or create an Ad Source for a Click-to-WhatsApp Meta ad referral.
     * Name = ad headline/body (never a URL host). Image = creative from Meta when available.
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

        $name = self::ctwaDisplayName($sourceId, $headline, $body);
        $description = self::ctwaDescription($sourceId, $sourceType, $sourceUrl, $body, $headline, $imageUrl);

        $found = null;
        if ($sourceId !== '') {
            $found = static::query()
                ->where('description', 'like', '%meta_source_id='.$sourceId.'%')
                ->first();
        }
        if (!$found) {
            $found = static::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
                ->first();
        }

        if ($found) {
            $dirty = false;
            if (self::isBadAdName($found->name) || (self::isGenericCtwaName($found->name) && !self::isGenericCtwaName($name))) {
                $found->name = $name;
                $dirty = true;
            } elseif (!self::isBadAdName($name) && self::isGenericCtwaName($found->name) && $found->name !== $name) {
                $found->name = $name;
                $dirty = true;
            }
            if ($sourceId !== '' || $description !== (string) $found->description) {
                $found->description = $description;
                $dirty = true;
            }
            if (!$found->is_active) {
                $found->is_active = true;
                $dirty = true;
            }
            if (empty($found->image) && $imageUrl !== '') {
                $stored = self::downloadCreativeImage($imageUrl);
                if ($stored) {
                    $found->image = $stored;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $found->save();
            }

            return $found;
        }

        $storedImage = $imageUrl !== '' ? self::downloadCreativeImage($imageUrl) : null;

        return static::create([
            'name' => $name,
            'description' => $description,
            'image' => $storedImage,
            'is_active' => true,
        ]);
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
        // Bare domains / WA deep-link hosts Meta sometimes surfaces instead of creative text.
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

    protected static function isGenericCtwaName(?string $name): bool
    {
        $n = strtolower(trim((string) $name));

        return $n === 'whatsapp ad'
            || str_starts_with($n, 'whatsapp ad ')
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
        string $imageUrl
    ): string {
        $lines = ['Click-to-WhatsApp (CTWA) ad creative'];
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
        if ($imageUrl !== '') {
            $lines[] = 'image_url='.mb_substr($imageUrl, 0, 500);
        }

        return implode("\n", $lines);
    }

    protected static function downloadCreativeImage(string $url): ?string
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

        $filename = 'ctwa-'.Str::uuid()->toString().'.'.$ext;
        try {
            Storage::disk('public')->put('ad-source/'.$filename, $body);
        } catch (\Throwable $e) {
            Log::warning('CTWA ad creative store failed', ['error' => $e->getMessage()]);

            return null;
        }

        return $filename;
    }
}
