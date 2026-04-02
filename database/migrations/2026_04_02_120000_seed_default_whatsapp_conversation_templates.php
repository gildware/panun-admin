<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_conversation_templates')) {
            return;
        }

        if (DB::table('whatsapp_conversation_templates')->exists()) {
            return;
        }

        $now = now();
        $rows = [
            [
                'title' => 'Welcome',
                'body' => 'Welcome! My name is {agent_name}. How can I help you today?',
                'sort_order' => 10,
            ],
            [
                'title' => 'Please hold',
                'body' => 'Please hold for a moment while I look into this for you.',
                'sort_order' => 20,
            ],
            [
                'title' => 'Thank you',
                'body' => 'Thank you for your patience. I appreciate it.',
                'sort_order' => 30,
            ],
            [
                'title' => 'Checking',
                'body' => 'I\'m checking that for you now and will update you shortly.',
                'sort_order' => 40,
            ],
            [
                'title' => 'Feedback',
                'body' => 'We\'d love to hear your feedback on your experience with us. How did we do today?',
                'sort_order' => 50,
            ],
            [
                'title' => 'More detail',
                'body' => 'Could you please share a bit more detail so I can assist you better?',
                'sort_order' => 60,
            ],
            [
                'title' => 'Follow up',
                'body' => 'Is there anything else I can help you with today?',
                'sort_order' => 70,
            ],
            [
                'title' => 'Closing',
                'body' => 'Thanks for reaching out. Have a great day!',
                'sort_order' => 80,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('whatsapp_conversation_templates')->insert([
                'title' => $row['title'],
                'body' => $row['body'],
                'sort_order' => $row['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_conversation_templates')) {
            return;
        }

        $titles = [
            'Welcome',
            'Please hold',
            'Thank you',
            'Checking',
            'Feedback',
            'More detail',
            'Follow up',
            'Closing',
        ];

        DB::table('whatsapp_conversation_templates')->whereIn('title', $titles)->delete();
    }
};
