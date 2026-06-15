<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_users', function (Blueprint $table) {
            if (! Schema::hasColumn('channel_users', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('is_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_users', function (Blueprint $table) {
            if (Schema::hasColumn('channel_users', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
};
