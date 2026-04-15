<?php

namespace Modules\WhatsAppModule\Entities\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

trait HasSocialInboxChannelScope
{
    public static function bootHasSocialInboxChannelScope(): void
    {
        static::addGlobalScope('social_inbox_channel', function (Builder $builder) {
            $table = $builder->getModel()->getTable();
            $builder->where($table . '.channel', SocialInboxChannel::current());
        });
    }
}
