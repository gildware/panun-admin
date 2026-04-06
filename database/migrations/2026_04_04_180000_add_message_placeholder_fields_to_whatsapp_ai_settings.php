<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $cols = [
                'placeholder_schedule',
                'placeholder_phone',
                'placeholder_brand',
                'placeholder_email',
                'placeholder_website',
                'placeholder_address',
                'placeholder_tagline',
                'placeholder_custom_1',
                'placeholder_custom_2',
            ];
            foreach ($cols as $col) {
                if (! Schema::hasColumn('whatsapp_ai_settings', $col)) {
                    if (in_array($col, ['placeholder_schedule', 'placeholder_address', 'placeholder_custom_1', 'placeholder_custom_2'], true)) {
                        $table->text($col)->nullable();
                    } else {
                        $table->string($col, 512)->nullable();
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'placeholder_schedule',
                'placeholder_phone',
                'placeholder_brand',
                'placeholder_email',
                'placeholder_website',
                'placeholder_address',
                'placeholder_tagline',
                'placeholder_custom_1',
                'placeholder_custom_2',
            ] as $c) {
                if (Schema::hasColumn('whatsapp_ai_settings', $c)) {
                    $drop[] = $c;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
