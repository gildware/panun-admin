<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class SuperfoneCallContact extends Model
{
    protected $fillable = [
        'superfone_call_id',
        'customer_phone_digits',
        'channel',
        'remarks',
        'attachment_disk',
        'attachment_path',
        'attachment_mime',
        'attachment_original_name',
        'employee_user_id',
        'contacted_at',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(SuperfoneCall::class, 'superfone_call_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'chat' => translate('Contacted_by_chat'),
            'not_picked' => translate('Customer_did_not_pick_up'),
            default => translate('Contacted_by_phone'),
        };
    }

    public function channelIcon(): string
    {
        return match ($this->channel) {
            'chat' => 'chat',
            'not_picked' => 'phone_missed',
            default => 'call',
        };
    }

    /**
     * Latest contact mark that applies to each call on the page.
     *
     * @param  iterable<int, SuperfoneCall>  $calls
     * @return array<int, self>
     */
    public static function marksForCalls(iterable $calls): array
    {
        $calls = collect($calls);
        if ($calls->isEmpty()) {
            return [];
        }

        $ids = $calls->pluck('id');
        $digits = $calls->pluck('customer_phone_digits')->filter(fn ($digits) => $digits !== null && $digits !== '')->unique()->values();
        $contacts = static::query()
            ->where(function ($query) use ($ids, $digits) {
                $query->whereIn('superfone_call_id', $ids);
                if ($digits->isNotEmpty()) {
                    $query->orWhereIn('customer_phone_digits', $digits);
                }
            })
            ->orderByDesc('contacted_at')
            ->orderByDesc('id')
            ->get();

        $marks = [];
        foreach ($calls as $call) {
            $start = (string) ($call->getRawOriginal('started_at') ?: $call->getRawOriginal('created_at'));
            $mark = $contacts->first(function (self $contact) use ($call, $start) {
                if ((int) $contact->superfone_call_id === (int) $call->id) {
                    return true;
                }

                $digits = (string) $call->customer_phone_digits;

                return $digits !== ''
                    && $contact->customer_phone_digits === $digits
                    && $start !== ''
                    && (string) $contact->contacted_at >= $start;
            });
            if ($mark) {
                $marks[$call->id] = $mark;
            }
        }

        return $marks;
    }
}
