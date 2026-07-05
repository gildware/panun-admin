<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_pinned_nav')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('admin_pinned_nav')->nullable()->after('last_visited_page');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_pinned_nav')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_pinned_nav');
            });
        }
    }
};
