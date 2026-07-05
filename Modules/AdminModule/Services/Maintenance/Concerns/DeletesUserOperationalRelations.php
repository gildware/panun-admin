<?php

namespace Modules\AdminModule\Services\Maintenance\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;

trait DeletesUserOperationalRelations
{
  /**
   * @param  list<string>  $userIds
   */
  protected function deleteChatDataForUsers(array $userIds): void
  {
    if ($userIds === [] || ! Schema::hasTable('channel_users')) {
      return;
    }

    $channelIds = DB::table('channel_users')
      ->whereIn('user_id', $userIds)
      ->pluck('channel_id')
      ->unique()
      ->values()
      ->all();

    foreach ($channelIds as $channelId) {
      $conversationIds = Schema::hasTable('channel_conversations')
        ? DB::table('channel_conversations')->where('channel_id', $channelId)->pluck('id')->all()
        : [];

      if ($conversationIds !== []) {
        if (Schema::hasTable('conversation_reactions')) {
          DB::table('conversation_reactions')->whereIn('conversation_id', $conversationIds)->delete();
        }
        if (Schema::hasTable('conversation_files')) {
          DB::table('conversation_files')->whereIn('conversation_id', $conversationIds)->delete();
        }
        DB::table('channel_conversations')->where('channel_id', $channelId)->delete();
      }

      DB::table('channel_users')->where('channel_id', $channelId)->delete();

      if (Schema::hasTable('channel_lists')) {
        DB::table('channel_lists')->where('id', $channelId)->delete();
      }
    }
  }

  /**
   * @param  list<string>  $userIds
   */
  protected function deleteInAppCallsForUsers(array $userIds): void
  {
    if ($userIds === [] || ! Schema::hasTable('in_app_calls')) {
      return;
    }

    DB::table('in_app_calls')
      ->where(function ($query) use ($userIds) {
        $query->whereIn('caller_user_id', $userIds)
          ->orWhereIn('callee_user_id', $userIds);
      })
      ->delete();
  }

  /**
   * @param  list<string>  $userIds
   */
  protected function deleteMobileAppAiForUsers(array $userIds): void
  {
    if ($userIds === []) {
      return;
    }

    MobileAppAiConversation::query()->whereIn('user_id', $userIds)->delete();
  }

  protected function deleteRowsForUser(string $table, string $column, string $userId): void
  {
    if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
      return;
    }

    DB::table($table)->where($column, $userId)->delete();
  }

  /**
   * @param  list<string>  $userIds
   */
  protected function deleteRowsForUsers(string $table, string $column, array $userIds): void
  {
    if ($userIds === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
      return;
    }

    DB::table($table)->whereIn($column, $userIds)->delete();
  }
}
