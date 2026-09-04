<?php

namespace App\Support;

use Illuminate\Http\Request;
use Modules\LeadManagement\Entities\Source;

final class EmployeeLeadNav
{
    public static function aiChatSourceId(): ?int
    {
        static $id = null;
        static $resolved = false;

        if ($resolved) {
            return $id;
        }

        $resolved = true;
        $source = Source::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(Source::NAME_AI_CHAT)])
            ->first();

        $id = $source ? (int) $source->id : null;

        return $id;
    }

    public static function aiBookingsUrl(): string
    {
        $sourceId = self::aiChatSourceId();

        if ($sourceId === null) {
            return route('admin.lead.index');
        }

        return route('admin.lead.index', ['source_id' => [$sourceId]]);
    }

    public static function isAiBookingsActive(?Request $request = null): bool
    {
        $request = $request ?? request();
        $sourceId = self::aiChatSourceId();

        if ($sourceId === null || ! self::isLeadIndexRequest($request)) {
            return false;
        }

        $sourceIds = array_map('intval', (array) $request->input('source_id', []));

        return $sourceIds === [$sourceId];
    }

    public static function isAllLeadSourcesActive(?Request $request = null): bool
    {
        $request = $request ?? request();

        return self::isLeadIndexRequest($request) && ! self::isAiBookingsActive($request);
    }

    private static function isLeadIndexRequest(Request $request): bool
    {
        return ($request->is('admin/lead') || $request->is('admin/lead/*'))
            && ! $request->is('admin/lead/create*')
            && ! $request->is('admin/lead/configuration*')
            && ! $request->is('admin/lead/reports*')
            && ! $request->is('admin/lead/outbound-enquiry*')
            && ! $request->is('admin/lead/todays-followups*')
            && ! $request->is('admin/lead/hunting-board*');
    }
}
