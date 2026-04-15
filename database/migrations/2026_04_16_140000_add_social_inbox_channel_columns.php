<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // --- whatsapp_conversations ---
        if (Schema::hasTable('whatsapp_conversations') && !Schema::hasColumn('whatsapp_conversations', 'channel')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_conversations')->update(['channel' => 'whatsapp']);
            try {
                Schema::table('whatsapp_conversations', function (Blueprint $table) {
                    $table->dropUnique(['phone']);
                });
            } catch (\Throwable) {
            }
            try {
                Schema::table('whatsapp_conversations', function (Blueprint $table) {
                    $table->unique(['channel', 'phone'], 'whatsapp_conversations_channel_phone_unique');
                });
            } catch (\Throwable) {
            }
        }

        // --- whatsapp_messages ---
        if (Schema::hasTable('whatsapp_messages') && !Schema::hasColumn('whatsapp_messages', 'channel')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_messages')->update(['channel' => 'whatsapp']);
            try {
                Schema::table('whatsapp_messages', function (Blueprint $table) {
                    $table->index(['channel', 'phone', 'created_at'], 'whatsapp_messages_channel_phone_created_idx');
                });
            } catch (\Throwable) {
            }
        }

        // --- whatsapp_users ---
        if (Schema::hasTable('whatsapp_users') && !Schema::hasColumn('whatsapp_users', 'channel')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_users')->update(['channel' => 'whatsapp']);
            try {
                Schema::table('whatsapp_users', function (Blueprint $table) {
                    $table->dropUnique(['phone']);
                });
            } catch (\Throwable) {
            }
            try {
                Schema::table('whatsapp_users', function (Blueprint $table) {
                    $table->unique(['channel', 'phone'], 'whatsapp_users_channel_phone_unique');
                });
            } catch (\Throwable) {
            }
        }

        // --- whatsapp_bookings ---
        if (Schema::hasTable('whatsapp_bookings') && !Schema::hasColumn('whatsapp_bookings', 'channel')) {
            Schema::table('whatsapp_bookings', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_bookings')->update(['channel' => 'whatsapp']);
        }

        // --- whatsapp_provider_leads ---
        if (Schema::hasTable('whatsapp_provider_leads') && !Schema::hasColumn('whatsapp_provider_leads', 'channel')) {
            Schema::table('whatsapp_provider_leads', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('lead_id');
            });
            DB::table('whatsapp_provider_leads')->update(['channel' => 'whatsapp']);
        }

        // --- whatsapp_conversation_templates ---
        if (Schema::hasTable('whatsapp_conversation_templates') && !Schema::hasColumn('whatsapp_conversation_templates', 'channel')) {
            Schema::table('whatsapp_conversation_templates', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_conversation_templates')->update(['channel' => 'whatsapp']);
        }

        // --- whatsapp_ai_settings ---
        if (Schema::hasTable('whatsapp_ai_settings') && !Schema::hasColumn('whatsapp_ai_settings', 'channel')) {
            Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_ai_settings')->update(['channel' => 'whatsapp']);
            try {
                Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
                    $table->unique('channel', 'whatsapp_ai_settings_channel_unique');
                });
            } catch (\Throwable) {
            }
            $base = DB::table('whatsapp_ai_settings')->where('channel', 'whatsapp')->orderBy('id')->first();
            if ($base !== null) {
                $nextId = (int) DB::table('whatsapp_ai_settings')->max('id');
                foreach (['instagram', 'facebook'] as $ch) {
                    if (DB::table('whatsapp_ai_settings')->where('channel', $ch)->exists()) {
                        continue;
                    }
                    $row = (array) $base;
                    $nextId++;
                    $row['id'] = $nextId;
                    $row['channel'] = $ch;
                    $row['created_at'] = now();
                    $row['updated_at'] = now();
                    DB::table('whatsapp_ai_settings')->insert($row);
                }
            }
        }

        if (Schema::hasTable('whatsapp_booking_automation_message_logs') && !Schema::hasColumn('whatsapp_booking_automation_message_logs', 'channel')) {
            Schema::table('whatsapp_booking_automation_message_logs', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_booking_automation_message_logs')->update(['channel' => 'whatsapp']);
        }

        // --- Chat configuration ---
        if (Schema::hasTable('whatsapp_chat_statuses') && !Schema::hasColumn('whatsapp_chat_statuses', 'channel')) {
            Schema::table('whatsapp_chat_statuses', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_chat_statuses')->update(['channel' => 'whatsapp']);
            $open = DB::table('whatsapp_chat_statuses')->where('channel', 'whatsapp')->where('bucket', 'open')->orderBy('id')->first();
            $closed = DB::table('whatsapp_chat_statuses')->where('channel', 'whatsapp')->where('bucket', 'closed')->orderBy('id')->first();
            foreach (['instagram', 'facebook'] as $ch) {
                if ($open && !DB::table('whatsapp_chat_statuses')->where('channel', $ch)->where('bucket', 'open')->exists()) {
                    DB::table('whatsapp_chat_statuses')->insert([
                        'name' => $open->name ?? 'Open',
                        'bucket' => 'open',
                        'sort_order' => (int) ($open->sort_order ?? 0),
                        'channel' => $ch,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($closed && !DB::table('whatsapp_chat_statuses')->where('channel', $ch)->where('bucket', 'closed')->exists()) {
                    DB::table('whatsapp_chat_statuses')->insert([
                        'name' => $closed->name ?? 'Closed',
                        'bucket' => 'closed',
                        'sort_order' => (int) ($closed->sort_order ?? 0),
                        'channel' => $ch,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('whatsapp_chat_tags') && !Schema::hasColumn('whatsapp_chat_tags', 'channel')) {
            Schema::table('whatsapp_chat_tags', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp')->after('id');
            });
            DB::table('whatsapp_chat_tags')->update(['channel' => 'whatsapp']);
        }

        if (Schema::hasTable('whatsapp_chat_thread_meta') && !Schema::hasColumn('whatsapp_chat_thread_meta', 'channel')) {
            Schema::table('whatsapp_chat_thread_meta', function (Blueprint $table) {
                $table->string('channel', 32)->default('whatsapp');
            });
            DB::table('whatsapp_chat_thread_meta')->update(['channel' => 'whatsapp']);
        }
    }

    public function down(): void
    {
    }
};
