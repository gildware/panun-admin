<?php

namespace Modules\ChattingModule\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\ChattingModule\Entities\ChannelUser;

final class ChatMessageStatusResolver
{
    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_SEEN = 'seen';

    /**
     * @param  Collection<int, ChannelUser>  $recipientChannelUsers
     */
    public function resolve(CarbonInterface $messageCreatedAt, Collection $recipientChannelUsers): string
    {
        if ($recipientChannelUsers->isEmpty()) {
            return self::STATUS_SENT;
        }

        $allSeen = true;
        $anyDelivered = false;

        foreach ($recipientChannelUsers as $recipient) {
            $readAt = $recipient->read_at;

            if ($readAt !== null && $readAt->greaterThanOrEqualTo($messageCreatedAt)) {
                continue;
            }

            $allSeen = false;

            if ($readAt !== null) {
                $anyDelivered = true;
            }
        }

        if ($allSeen) {
            return self::STATUS_SEEN;
        }

        if ($anyDelivered) {
            return self::STATUS_DELIVERED;
        }

        return self::STATUS_SENT;
    }

    public function label(string $status): string
    {
        return match ($status) {
            self::STATUS_SEEN => translate('Seen'),
            self::STATUS_DELIVERED => translate('Delivered'),
            default => translate('sent'),
        };
    }
}
