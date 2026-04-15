<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatTag;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Support\WhatsAppActiveChatsListCache;

/**
 * Seeds demo WhatsApp chats for /admin/whatsapp/conversations (dummy +19990000xxxx threads).
 *
 * Run: php artisan db:seed --class=WhatsAppDummyConversationsSeeder
 */
class WhatsAppDummyConversationsSeeder extends Seeder
{
    private const PHONE_PREFIX = '+19990000';

    public function run(): void
    {
        if (!Schema::hasTable('whatsapp_messages') || !Schema::hasTable('whatsapp_users')) {
            $this->command?->warn('WhatsApp tables are missing; skip dummy conversations seed.');

            return;
        }

        $dummyPhones = [
            self::PHONE_PREFIX . '0001',
            self::PHONE_PREFIX . '0002',
            self::PHONE_PREFIX . '0003',
            self::PHONE_PREFIX . '0004',
            self::PHONE_PREFIX . '0005',
            self::PHONE_PREFIX . '0006',
        ];

        $this->purgeDummyThreads($dummyPhones);

        $adminIds = DB::table('users')->orderBy('id')->limit(5)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        while (count($adminIds) < 3) {
            $adminIds[] = $adminIds[0] ?? '1';
        }

        $tags = $this->ensureTags();
        $statusByName = $this->ensureStatuses();

        $threads = [
            [
                'phone' => $dummyPhones[0],
                'name' => 'Demo Customer — Plumbing',
                'handled_by' => 'AI',
                'status' => $statusByName['Open'] ?? null,
                'tag_keys' => ['billing', 'vip'],
                'human_support' => false,
                'messages' => [
                    ['IN', 'Hi, I need someone to fix a leaking tap tomorrow morning.', null, true],
                    ['OUT', 'Hello! I can help you book a plumber. Which area are you in?', 'read', true],
                    ['IN', 'Gulberg Phase 2, Lahore.', null, false],
                ],
            ],
            [
                'phone' => $dummyPhones[1],
                'name' => 'Demo Customer — AC',
                'handled_by' => $adminIds[0],
                'status' => $statusByName['Pending reply'] ?? $statusByName['Open'] ?? null,
                'tag_keys' => ['follow_up', 'new_lead'],
                'human_support' => false,
                'messages' => [
                    ['IN', 'Is AC gas refill included in the service?', null, true],
                    ['OUT', 'Usually it is quoted separately — I will confirm with dispatch.', 'delivered', true],
                    ['IN', 'Ok please confirm today.', null, false],
                ],
            ],
            [
                'phone' => $dummyPhones[2],
                'name' => 'Demo Provider — Onboarding',
                'handled_by' => $adminIds[1],
                'status' => $statusByName['Escalated'] ?? $statusByName['Open'] ?? null,
                'tag_keys' => ['urgent'],
                'human_support' => false,
                'messages' => [
                    ['IN', 'My documents were rejected but I uploaded the right CNIC.', null, true],
                    ['OUT', 'Thanks — I am escalating to verification now.', 'read', true],
                    ['IN', 'Please update me on WhatsApp when it is cleared.', null, false],
                ],
            ],
            [
                'phone' => $dummyPhones[3],
                'name' => 'Demo Customer — Reschedule',
                'handled_by' => $adminIds[2],
                'status' => $statusByName['Closed'] ?? null,
                'tag_keys' => ['billing'],
                'human_support' => false,
                'messages' => [
                    ['IN', 'Can we move my booking from Friday to Saturday?', null, true],
                    ['OUT', 'Done — you are now on Saturday 11am slot.', 'read', true],
                    ['IN', 'Perfect, thank you!', null, true],
                ],
            ],
            [
                'phone' => $dummyPhones[4],
                'name' => 'Demo Customer — General',
                'handled_by' => $adminIds[3] ?? $adminIds[0],
                'status' => $statusByName['Open'] ?? null,
                'tag_keys' => ['new_lead', 'vip'],
                'human_support' => false,
                'messages' => [
                    ['IN', 'What are your service hours on Sunday?', null, true],
                    ['OUT', 'We operate 9am–6pm on Sunday for most categories.', 'read', true],
                ],
            ],
            [
                'phone' => $dummyPhones[5],
                'name' => 'Demo — Human support queue',
                'handled_by' => 'AI',
                'status' => $statusByName['Open'] ?? null,
                'tag_keys' => ['urgent', 'follow_up'],
                'human_support' => true,
                'messages' => [
                    ['IN', 'I really need to speak to a human about my refund.', null, true],
                    ['OUT', 'Connecting you with our team shortly.', 'sent', true],
                ],
            ],
        ];

        $tz = (string) config('whatsappmodule.message_timezone', config('app.timezone'));
        $base = Carbon::now($tz);

        foreach ($threads as $idx => $def) {
            $phone = $def['phone'];
            $t0 = $base->copy()->subHours(6 - $idx)->subMinutes($idx * 7);

            WhatsAppUser::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $def['name'],
                    'type' => str_contains((string) $def['name'], 'Provider') ? 'PROVIDER' : 'CUSTOMER',
                    'handled_by' => $def['handled_by'],
                    'human_support_requested_at' => !empty($def['human_support']) ? $t0->copy()->subMinutes(2) : null,
                ]
            );

            WhatsAppConversation::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'active_module' => 'DEMO',
                    'current_step' => 'seeded',
                    'after_hours' => false,
                ]
            );

            $seq = 0;
            foreach ($def['messages'] as $row) {
                [$direction, $text, $status, $seen] = $row;
                $created = $t0->copy()->addMinutes(++$seq);
                $m = new WhatsAppMessage([
                    'phone' => $phone,
                    'message_text' => $text,
                    'direction' => $direction,
                    'message_type' => 'TEXT',
                    'wa_message_id' => 'demo_wa_' . md5($phone . $seq),
                    'status' => $status,
                    'admin_seen_at' => $direction === 'IN' && $seen ? $created->copy()->addMinute() : null,
                    'sent_by_id' => $direction === 'OUT' && $def['handled_by'] !== 'AI' ? $def['handled_by'] : null,
                ]);
                $m->created_at = $created;
                $m->save();
            }

            if ($this->chatMetaTablesPresent()) {
                $meta = WhatsAppChatThreadMeta::query()->firstOrCreate(['phone' => $phone]);
                if (!empty($def['status'])) {
                    $meta->whatsapp_chat_status_id = $def['status']->id;
                    $meta->save();
                }
                $tagIds = collect($def['tag_keys'] ?? [])
                    ->map(fn ($k) => $tags[$k]->id ?? null)
                    ->filter()
                    ->values()
                    ->all();
                if ($tagIds !== []) {
                    $meta->tags()->sync($tagIds);
                }
            }
        }

        WhatsAppActiveChatsListCache::forgetAll();
        $this->command?->info('Seeded ' . count($threads) . ' dummy WhatsApp conversation threads.');
    }

    private function purgeDummyThreads(array $phones): void
    {
        if ($phones === []) {
            return;
        }

        if (Schema::hasTable('whatsapp_chat_thread_tags')) {
            DB::table('whatsapp_chat_thread_tags')->whereIn('phone', $phones)->delete();
        }
        if (Schema::hasTable('whatsapp_chat_thread_meta')) {
            DB::table('whatsapp_chat_thread_meta')->whereIn('phone', $phones)->delete();
        }
        if (Schema::hasTable('whatsapp_messages')) {
            DB::table('whatsapp_messages')->whereIn('phone', $phones)->delete();
        }
        if (Schema::hasTable('whatsapp_users')) {
            DB::table('whatsapp_users')->whereIn('phone', $phones)->delete();
        }
        if (Schema::hasTable('whatsapp_conversations')) {
            DB::table('whatsapp_conversations')->whereIn('phone', $phones)->delete();
        }
    }

    /**
     * @return array<string, WhatsAppChatTag>
     */
    private function ensureTags(): array
    {
        if (!Schema::hasTable('whatsapp_chat_tags')) {
            return [];
        }

        $defs = [
            ['name' => 'VIP', 'key' => 'vip', 'color' => '#6f42c1', 'sort_order' => 10],
            ['name' => 'Billing', 'key' => 'billing', 'color' => '#fd7e14', 'sort_order' => 20],
            ['name' => 'Urgent', 'key' => 'urgent', 'color' => '#dc3545', 'sort_order' => 30],
            ['name' => 'Follow-up', 'key' => 'follow_up', 'color' => '#0d6efd', 'sort_order' => 40],
            ['name' => 'New lead', 'key' => 'new_lead', 'color' => '#198754', 'sort_order' => 50],
        ];

        $out = [];
        foreach ($defs as $d) {
            $tag = WhatsAppChatTag::query()->firstOrCreate(
                ['name' => $d['name']],
                ['color' => $d['color'], 'sort_order' => $d['sort_order']]
            );
            $out[$d['key']] = $tag;
        }

        return $out;
    }

    /**
     * @return array<string, WhatsAppChatStatus>
     */
    private function ensureStatuses(): array
    {
        if (!Schema::hasTable('whatsapp_chat_statuses')) {
            return [];
        }

        $byName = WhatsAppChatStatus::query()->get()->keyBy('name');

        $extra = [
            ['name' => 'Pending reply', 'bucket' => 'open', 'sort_order' => 5],
            ['name' => 'Escalated', 'bucket' => 'open', 'sort_order' => 10],
        ];
        foreach ($extra as $row) {
            if (!$byName->has($row['name'])) {
                $byName[$row['name']] = WhatsAppChatStatus::query()->create($row);
            }
        }

        return WhatsAppChatStatus::query()->get()->keyBy('name')->all();
    }

    private function chatMetaTablesPresent(): bool
    {
        return Schema::hasTable('whatsapp_chat_statuses')
            && Schema::hasTable('whatsapp_chat_tags')
            && Schema::hasTable('whatsapp_chat_thread_meta')
            && Schema::hasTable('whatsapp_chat_thread_tags');
    }
}
