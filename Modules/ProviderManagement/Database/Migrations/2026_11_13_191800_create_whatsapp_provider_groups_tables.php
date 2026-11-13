<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_provider_groups')) {
            Schema::create('whatsapp_provider_groups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 191);
                $table->string('color', 7)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique('name');
                $table->index('sort_order');
            });
        }

        if (! Schema::hasTable('whatsapp_provider_group_contacts')) {
            Schema::create('whatsapp_provider_group_contacts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('group_id');
                $table->string('phone', 20);
                $table->string('name', 120)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('group_id')
                    ->references('id')
                    ->on('whatsapp_provider_groups')
                    ->cascadeOnDelete();
                $table->unique(['group_id', 'phone'], 'wa_provider_group_contacts_group_phone_uq');
                $table->index('phone');
            });
        }

        $this->seedFromBundledJsonIfEmpty();
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_provider_group_contacts');
        Schema::dropIfExists('whatsapp_provider_groups');
    }

    private function seedFromBundledJsonIfEmpty(): void
    {
        if (! Schema::hasTable('whatsapp_provider_groups')) {
            return;
        }

        if (DB::table('whatsapp_provider_groups')->exists()) {
            return;
        }

        $path = module_path('ProviderManagement', 'Data/whatsapp_provider_numbers.json');
        if (! is_file($path)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || $decoded === []) {
            return;
        }

        $now = now();
        $sort = 0;

        foreach ($decoded as $groupName => $value) {
            $groupName = trim((string) $groupName);
            if ($groupName === '' || $groupName === '_meta' || ! is_array($value)) {
                continue;
            }

            $color = null;
            $contactsRaw = null;
            if (array_is_list($value)) {
                $contactsRaw = $value;
            } else {
                $rawColor = isset($value['color']) && is_string($value['color']) ? trim($value['color']) : null;
                if (is_string($rawColor) && preg_match('/^#[A-Fa-f0-9]{6}$/', $rawColor)) {
                    $color = strtoupper($rawColor);
                }
                $contactsRaw = $value['contacts'] ?? $value['phones'] ?? null;
            }

            if (! is_array($contactsRaw)) {
                continue;
            }

            $groupId = (string) Str::uuid();
            DB::table('whatsapp_provider_groups')->insert([
                'id' => $groupId,
                'name' => $groupName,
                'color' => $color,
                'sort_order' => $sort++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $seen = [];
            $contactSort = 0;
            $rows = [];
            foreach ($contactsRaw as $contact) {
                $phoneRaw = is_array($contact)
                    ? ($contact['phone'] ?? $contact['number'] ?? null)
                    : $contact;
                $digits = preg_replace('/\D+/', '', (string) $phoneRaw) ?? '';
                if ($digits === '') {
                    continue;
                }
                $phone = strlen($digits) >= 10 ? substr($digits, -10) : $digits;
                if (strlen($phone) < 8 || isset($seen[$phone])) {
                    continue;
                }
                $seen[$phone] = true;

                $contactName = '';
                if (is_array($contact)) {
                    $contactName = trim((string) ($contact['name'] ?? ''));
                    $contactName = trim(preg_replace('/\s+/u', ' ', $contactName) ?? '');
                }

                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'group_id' => $groupId,
                    'phone' => $phone,
                    'name' => $contactName !== '' ? $contactName : null,
                    'sort_order' => $contactSort++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table('whatsapp_provider_group_contacts')->insert($chunk);
                }
            }
        }
    }
};
