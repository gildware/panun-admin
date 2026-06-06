<?php

namespace Modules\ChattingModule\Console;

use Illuminate\Console\Command;
use Modules\ChattingModule\Services\StaffGroupChannelService;
use Modules\UserManagement\Entities\User;

class SyncStaffGroupChannelCommand extends Command
{
    protected $signature = 'chatting:sync-staff-group';

    protected $description = 'Create the general staff group channel and sync all active staff members';

    public function handle(StaffGroupChannelService $staffGroupChannelService): int
    {
        $staff = User::query()
            ->ofType(ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->get();

        if ($staff->isEmpty()) {
            $this->warn('No active staff members found.');

            return self::SUCCESS;
        }

        $channel = $staffGroupChannelService->ensureGroupForUser($staff->first());

        if (! $channel) {
            $this->error('Unable to create staff group channel.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Staff group channel synced with %d member(s).',
            $staffGroupChannelService->memberCount($channel)
        ));

        return self::SUCCESS;
    }
}
