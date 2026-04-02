<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_marketing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('meta_template_id', 64)->nullable()->index();
            $table->string('name', 512);
            $table->string('language', 32);
            $table->string('category', 64)->nullable();
            $table->string('status', 32)->default('APPROVED');
            $table->unsignedSmallInteger('body_parameter_count')->default(0);
            $table->json('components')->nullable();
            $table->text('preview_text')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language']);
        });

        Schema::create('whatsapp_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('whatsapp_marketing_template_id');
            $table->string('audience_type', 64);
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('csv_path', 512)->nullable();
            $table->json('variable_mapping')->nullable();
            $table->string('status', 32)->default('queued');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('whatsapp_marketing_template_id', 'fk_wa_mkt_cmp_tpl')
                ->references('id')
                ->on('whatsapp_marketing_templates')
                ->cascadeOnDelete();
        });

        Schema::create('whatsapp_marketing_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_marketing_campaign_id');
            $table->string('recipient_name', 255)->nullable();
            $table->string('phone_e164', 32)->index();
            $table->string('status', 32)->default('pending');
            $table->string('wa_message_id', 255)->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->json('body_parameters')->nullable();
            $table->timestamps();

            $table->foreign('whatsapp_marketing_campaign_id', 'fk_wa_mkt_msg_cmp')
                ->references('id')
                ->on('whatsapp_marketing_campaigns')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_marketing_messages');
        Schema::dropIfExists('whatsapp_marketing_campaigns');
        Schema::dropIfExists('whatsapp_marketing_templates');
    }
};
