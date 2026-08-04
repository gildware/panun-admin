<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\UserManagement\Entities\User;

class BookingComment extends Model
{
    protected $fillable = [
        'booking_id',
        'created_by',
        'body',
        'mentioned_user_ids',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected $casts = [
        'mentioned_user_ids' => 'array',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function pinnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by', 'id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BookingCommentAttachment::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (BookingComment $comment) {
            $comment->loadMissing('attachments');
            foreach ($comment->attachments as $attachment) {
                try {
                    Storage::disk($attachment->disk ?: getDisk())->delete($attachment->storagePath());
                } catch (\Throwable) {
                    // Ignore storage cleanup failures.
                }
                $attachment->delete();
            }
        });
    }
}
