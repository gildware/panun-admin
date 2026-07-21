<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Click-to-WhatsApp (CTWA) ad referral fields from Meta inbound webhooks,
 * plus a log for Conversions API for Business Messaging events.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_messages')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('whatsapp_messages', 'referral_json')) {
                    $table->json('referral_json')->nullable();
                }
                if (!Schema::hasColumn('whatsapp_messages', 'ctwa_clid')) {
                    $table->string('ctwa_clid', 512)->nullable()->index();
                }
                if (!Schema::hasColumn('whatsapp_messages', 'referral_source_id')) {
                    $table->string('referral_source_id', 64)->nullable()->index();
                }
                if (!Schema::hasColumn('whatsapp_messages', 'referral_source_type')) {
                    $table->string('referral_source_type', 32)->nullable()->index();
                }
                if (!Schema::hasColumn('whatsapp_messages', 'referral_source_url')) {
                    $table->string('referral_source_url', 512)->nullable();
                }
                if (!Schema::hasColumn('whatsapp_messages', 'referral_headline')) {
                    $table->string('referral_headline', 512)->nullable();
                }
                if (!Schema::hasColumn('whatsapp_messages', 'referral_body')) {
                    $table->text('referral_body')->nullable();
                }
            });
        }

        if (Schema::hasTable('whatsapp_users')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                if (!Schema::hasColumn('whatsapp_users', 'referral_json')) {
                    $table->json('referral_json')->nullable();
                }
                if (!Schema::hasColumn('whatsapp_users', 'ctwa_clid')) {
                    $table->string('ctwa_clid', 512)->nullable()->index();
                }
                if (!Schema::hasColumn('whatsapp_users', 'referral_source_id')) {
                    $table->string('referral_source_id', 64)->nullable()->index();
                }
                if (!Schema::hasColumn('whatsapp_users', 'referral_source_type')) {
                    $table->string('referral_source_type', 32)->nullable()->index();
                }
                if (!Schema::hasColumn('whatsapp_users', 'referral_source_url')) {
                    $table->string('referral_source_url', 512)->nullable();
                }
                if (!Schema::hasColumn('whatsapp_users', 'referral_headline')) {
                    $table->string('referral_headline', 512)->nullable();
                }
                if (!Schema::hasColumn('whatsapp_users', 'referral_body')) {
                    $table->text('referral_body')->nullable();
                }
                if (!Schema::hasColumn('whatsapp_users', 'referral_captured_at')) {
                    $table->timestamp('referral_captured_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('whatsapp_meta_capi_events')) {
            Schema::create('whatsapp_meta_capi_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('channel', 32)->default('whatsapp')->index();
                $table->string('phone', 50)->index();
                $table->string('event_name', 64)->index();
                $table->string('event_id', 128)->unique();
                $table->string('ctwa_clid', 512)->nullable()->index();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->string('booking_id', 64)->nullable()->index();
                $table->string('status', 20)->default('pending')->index();
                $table->json('request_payload')->nullable();
                $table->json('response_json')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_meta_capi_events');

        if (Schema::hasTable('whatsapp_users')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                $cols = [
                    'referral_json',
                    'ctwa_clid',
                    'referral_source_id',
                    'referral_source_type',
                    'referral_source_url',
                    'referral_headline',
                    'referral_body',
                    'referral_captured_at',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('whatsapp_users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('whatsapp_messages')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $cols = [
                    'referral_json',
                    'ctwa_clid',
                    'referral_source_id',
                    'referral_source_type',
                    'referral_source_url',
                    'referral_headline',
                    'referral_body',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('whatsapp_messages', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
