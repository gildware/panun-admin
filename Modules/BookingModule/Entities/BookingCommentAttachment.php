<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Support\StoragePathPrefix;
use Modules\UserManagement\Entities\User;

class BookingCommentAttachment extends Model
{
    protected $fillable = [
        'booking_comment_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'file_type',
        'disk',
    ];

    protected $appends = ['url'];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(BookingComment::class, 'booking_comment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    public function getUrlAttribute(): string
    {
        $path = StoragePathPrefix::apply('booking-comments/'.$this->stored_name);

        try {
            return Storage::disk($this->disk ?: getDisk())->url($path);
        } catch (\Throwable) {
            return asset('storage/'.$path);
        }
    }

    public function isImage(): bool
    {
        if (str_starts_with((string) $this->file_type, 'image/')) {
            return true;
        }

        return in_array(
            strtolower(pathinfo((string) $this->stored_name, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            true
        );
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->file_type, 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with((string) $this->file_type, 'audio/');
    }

    public function storagePath(): string
    {
        return StoragePathPrefix::apply('booking-comments/'.$this->stored_name);
    }
}
