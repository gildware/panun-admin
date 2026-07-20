<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (! $this->indexExists('bookings_provider_id_index')) {
            DB::statement('ALTER TABLE `bookings` ADD INDEX `bookings_provider_id_index` (`provider_id`)');
        }

        if (! $this->indexExists('bookings_provider_id_booking_status_index')) {
            DB::statement('ALTER TABLE `bookings` ADD INDEX `bookings_provider_id_booking_status_index` (`provider_id`, `booking_status`)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if ($this->indexExists('bookings_provider_id_booking_status_index')) {
            DB::statement('ALTER TABLE `bookings` DROP INDEX `bookings_provider_id_booking_status_index`');
        }

        if ($this->indexExists('bookings_provider_id_index')) {
            DB::statement('ALTER TABLE `bookings` DROP INDEX `bookings_provider_id_index`');
        }
    }

    private function indexExists(string $indexName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, 'bookings', $indexName]
        );

        return $row !== null;
    }
};
