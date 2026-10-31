<?php

namespace Modules\LeadManagement\Entities;

use App\Support\StoragePathPrefix;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Services\SuperfoneCallService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class SuperfoneCall extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cdr_uuid',
        'cdr_id',
        'cdr_request_uuid',
        'last_event',
        'direction',
        'disposition',
        'outcome',
        'duration_seconds',
        'ringing_seconds',
        'started_at',
        'answered_at',
        'ended_at',
        'customer_phone',
        'customer_phone_digits',
        'superfone_number',
        'organization_name',
        'staff_first_name',
        'staff_last_name',
        'staff_phone',
        'staff_name',
        'employee_user_id',
        'lead_id',
        'lead_followup_id',
        'provider_id',
        'customer_user_id',
        'booking_id',
        'booking_followup_id',
        'manual_unmatched',
        'called_party_type',
        'recording_url',
        'recording_url_expires_at',
        'recording_file_expires_at',
        'recording_path',
        'recording_disk',
        'recording_mime',
        'recording_original_name',
        'recording_error',
        'summary_text',
        'summary_insights',
        'payloads',
        'deleted_by',
    ];

    protected $casts = [
        'cdr_id' => 'integer',
        'duration_seconds' => 'integer',
        'ringing_seconds' => 'integer',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'recording_url_expires_at' => 'datetime',
        'recording_file_expires_at' => 'datetime',
        'deleted_at' => 'datetime',
        'summary_insights' => 'array',
        'payloads' => 'array',
        'manual_unmatched' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
    }

    public function followup(): BelongsTo
    {
        return $this->belongsTo(LeadFollowup::class, 'lead_followup_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function needsLeadLink(): bool
    {
        return $this->lead_id === null && $this->customer_user_id === null;
    }

    /**
     * Inbound (and direction-unknown) calls tagged missed. Outbound misses are unanswered.
     */
    public function scopeMissed($query)
    {
        return $query->where('outcome', 'missed')->where(function ($inner) {
            $inner->whereNull('direction')->orWhere('direction', '!=', 'OUTBOUND');
        });
    }

    /**
     * Inbound calls that still raise the Calls badge: misses, and calls Superfone
     * never assigned to an agent. One later outbound call, or a manual
     * "mark contacted", clears every earlier one from that number.
     * The call rows keep their own result.
     */
    public function scopeAwaitingCallback($query)
    {
        return $query->where(function ($candidate) {
            $candidate->where('outcome', 'missed')
                ->orWhere(function ($unallocated) {
                    $unallocated->where('disposition', 'UNALLOCATED')
                        ->where(function ($notAnswered) {
                            $notAnswered->whereNull('outcome')->orWhere('outcome', '!=', 'answered');
                        });
                });
        })->where(function ($inbound) {
            $inbound->whereNull('direction')->orWhere('direction', '!=', 'OUTBOUND');
        })->where(function ($open) {
            $open->where(function ($withoutNumber) {
                $withoutNumber->where(function ($blank) {
                    $blank->whereNull('superfone_calls.customer_phone_digits')
                        ->orWhere('superfone_calls.customer_phone_digits', '');
                })->whereNotExists(function ($contact) {
                    $contact->selectRaw('1')
                        ->from('superfone_call_contacts')
                        ->whereColumn('superfone_call_contacts.superfone_call_id', 'superfone_calls.id');
                });
            })->orWhere(function ($withNumber) {
                $withNumber->whereNotNull('superfone_calls.customer_phone_digits')
                    ->where('superfone_calls.customer_phone_digits', '!=', '')
                    ->whereNotExists(function ($callback) {
                        $callback->selectRaw('1')
                            ->from('superfone_calls as callbacks')
                            ->whereColumn('callbacks.customer_phone_digits', 'superfone_calls.customer_phone_digits')
                            ->where('callbacks.customer_phone_digits', '!=', '')
                            ->where('callbacks.direction', 'OUTBOUND')
                            ->whereNull('callbacks.deleted_at')
                            ->whereRaw('COALESCE(callbacks.started_at, callbacks.created_at) >= COALESCE(superfone_calls.started_at, superfone_calls.created_at)');
                    })
                    ->whereNotExists(function ($contact) {
                        $contact->selectRaw('1')
                            ->from('superfone_call_contacts')
                            ->where(function ($match) {
                                $match->whereColumn('superfone_call_contacts.superfone_call_id', 'superfone_calls.id')
                                    ->orWhere(function ($later) {
                                        $later->whereColumn('superfone_call_contacts.customer_phone_digits', 'superfone_calls.customer_phone_digits')
                                            ->where('superfone_call_contacts.customer_phone_digits', '!=', '')
                                            ->whereRaw('superfone_call_contacts.contacted_at >= COALESCE(superfone_calls.started_at, superfone_calls.created_at)');
                                    });
                            });
                    });
            });
        });
    }

    /**
     * Calls an employee marked contacted, including earlier misses cleared by that mark.
     */
    public function scopeContacted($query)
    {
        return $query->where(function ($marked) {
            $marked->whereExists(function ($contact) {
                $contact->selectRaw('1')
                    ->from('superfone_call_contacts')
                    ->whereColumn('superfone_call_contacts.superfone_call_id', 'superfone_calls.id');
            })->orWhereExists(function ($contact) {
                $contact->selectRaw('1')
                    ->from('superfone_call_contacts')
                    ->whereColumn('superfone_call_contacts.customer_phone_digits', 'superfone_calls.customer_phone_digits')
                    ->where('superfone_call_contacts.customer_phone_digits', '!=', '')
                    ->whereRaw('superfone_call_contacts.contacted_at >= COALESCE(superfone_calls.started_at, superfone_calls.created_at)');
            });
        });
    }

    public function hasRecording(): bool
    {
        return ! empty($this->recording_path);
    }

    public function formatIst(string $attribute): string
    {
        $keys = [
            'started_at' => 'cdr_start',
            'answered_at' => 'cdr_answer',
            'ended_at' => 'cdr_end',
            'recording_url_expires_at' => 'cdr_recording_url_expires_at',
            'recording_file_expires_at' => 'cdr_recording_expire_at',
        ];

        $moment = isset($keys[$attribute])
            ? SuperfoneCallService::instantFromPayloads(is_array($this->payloads) ? $this->payloads : [], $keys[$attribute])
            : null;

        if ($moment === null && $this->{$attribute} instanceof CarbonInterface) {
            $moment = $this->{$attribute};
        }

        return $moment ? $moment->format('d M Y, h:i A') : '—';
    }

    public function durationLabel(): string
    {
        if ($this->duration_seconds === null) {
            return '—';
        }

        $seconds = max(0, (int) $this->duration_seconds);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function outcomeLabel(): string
    {
        return match ($this->outcome) {
            'answered' => translate('Answered'),
            'unanswered' => translate('Unanswered'),
            'missed' => $this->direction === 'OUTBOUND' ? translate('Unanswered') : translate('Missed'),
            'other' => $this->disposition ?: translate('Other'),
            default => translate('Unknown'),
        };
    }

    public function directionLabel(): string
    {
        return match ($this->direction) {
            'INBOUND' => translate('Inbound'),
            'OUTBOUND' => translate('Outbound'),
            default => $this->direction ?: '—',
        };
    }

    public function recordingStoragePath(): ?string
    {
        if (! $this->hasRecording()) {
            return null;
        }

        return StoragePathPrefix::apply('lead-followups/'.$this->recording_path);
    }

    public function recordingDisk(): string
    {
        return $this->recording_disk ?: getDisk();
    }

    public function recordingUrl(): ?string
    {
        $path = $this->recordingStoragePath();
        if ($path === null) {
            return null;
        }

        try {
            return Storage::disk($this->recordingDisk())->url($path);
        } catch (\Throwable) {
            return null;
        }
    }
};
