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

        $now = now();
        $rows = [
            [
                'title' => 'Greeting (name)',
                'body' => 'Hi {customer_name}, thank you for reaching out. My name is {agent_name}—how can I help you today?',
                'sort_order' => 85,
            ],
            [
                'title' => 'Thanks (name)',
                'body' => 'Thank you, {customer_name}. I really appreciate your patience.',
                'sort_order' => 86,
            ],
            [
                'title' => 'Checking (name)',
                'body' => 'Hi {customer_name}, I\'m looking into this for you now and will update you shortly.',
                'sort_order' => 87,
            ],
            [
                'title' => 'Follow up (name)',
                'body' => 'Hi {customer_name}, is there anything else I can help you with today?',
                'sort_order' => 88,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('whatsapp_conversation_templates')
                ->where('title', $row['title'])
                ->exists();
            if ($exists) {
                continue;
            }
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

        DB::table('whatsapp_conversation_templates')->whereIn('title', [
            'Greeting (name)',
            'Thanks (name)',
            'Checking (name)',
            'Follow up (name)',
        ])->delete();
    }
};
