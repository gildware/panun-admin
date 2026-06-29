<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ensure review notification keys exist and use the correct author/receiver message copy.
     */
    public function up(): void
    {
        sync_review_notification_default_messages();
    }

    public function down(): void
    {
        // Non-reversible; admins may have edited templates after this migration.
    }
};
