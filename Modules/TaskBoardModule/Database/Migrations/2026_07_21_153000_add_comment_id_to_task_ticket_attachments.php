<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_ticket_attachments', function (Blueprint $table) {
            $table->uuid('comment_id')->nullable()->after('ticket_id')->index();
            $table->string('file_type', 32)->nullable()->after('stored_name');
        });
    }

    public function down(): void
    {
        Schema::table('task_ticket_attachments', function (Blueprint $table) {
            $table->dropColumn(['comment_id', 'file_type']);
        });
    }
};
