<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_chat_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('bucket', 16); // open | closed
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('whatsapp_chat_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('color', 16)->default('#6c757d');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('whatsapp_chat_thread_meta', function (Blueprint $table) {
            $table->string('phone', 50)->primary();
            $table->foreignId('whatsapp_chat_status_id')->nullable()->constrained('whatsapp_chat_statuses')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('whatsapp_chat_thread_tags', function (Blueprint $table) {
            $table->string('phone', 50);
            $table->foreignId('whatsapp_chat_tag_id')->constrained('whatsapp_chat_tags')->cascadeOnDelete();
            $table->primary(['phone', 'whatsapp_chat_tag_id']);
            $table->foreign('phone')->references('phone')->on('whatsapp_chat_thread_meta')->cascadeOnDelete();
        });

        DB::table('whatsapp_chat_statuses')->insert([
            [
                'name' => 'Open',
                'bucket' => 'open',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Closed',
                'bucket' => 'closed',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_thread_tags');
        Schema::dropIfExists('whatsapp_chat_thread_meta');
        Schema::dropIfExists('whatsapp_chat_tags');
        Schema::dropIfExists('whatsapp_chat_statuses');
    }
};
