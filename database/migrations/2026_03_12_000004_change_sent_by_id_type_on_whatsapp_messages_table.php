<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop numeric sent_by_id and re-add as string to support UUIDs.
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'sent_by_id')) {
                $table->dropColumn('sent_by_id');
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('sent_by_id', 64)->nullable()->after('sent_by');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'sent_by_id')) {
                $table->dropColumn('sent_by_id');
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sent_by_id')->nullable()->after('sent_by');
        });
    }
};

