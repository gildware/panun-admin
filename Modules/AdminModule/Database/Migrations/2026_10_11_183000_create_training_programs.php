<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->string('status', 20)->default('draft');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('text_title', 160)->nullable();
            $table->text('text_body')->nullable();
            $table->text('audio_script')->nullable();
            $table->text('video_note')->nullable();
            $table->text('ai_situation')->nullable();
            $table->unsignedTinyInteger('question_pass_mark')->default(1);
            $table->unsignedTinyInteger('ai_pass_mark')->default(1);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('training_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_program_id')->constrained('training_programs')->cascadeOnDelete();
            $table->text('prompt');
            $table->json('options');
            $table->unsignedTinyInteger('correct_index')->default(0);
            $table->string('explanation', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_marking_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_program_id')->constrained('training_programs')->cascadeOnDelete();
            $table->string('label', 255);
            $table->string('keywords', 500);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_program_id')->constrained('training_programs')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('assigned_by')->nullable();
            $table->timestamps();
            $table->unique(['training_program_id', 'user_id']);
        });

        Schema::create('training_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('content_done_at')->nullable();
            $table->unsignedTinyInteger('quiz_correct')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->text('ai_answer')->nullable();
            $table->json('ai_hits')->nullable();
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->timestamp('certified_at')->nullable();
            $table->timestamps();
            $table->unique('training_assignment_id');
        });

        $this->seedCustomerSupport();
    }

    public function down(): void
    {
        Schema::dropIfExists('training_progress');
        Schema::dropIfExists('training_assignments');
        Schema::dropIfExists('training_marking_points');
        Schema::dropIfExists('training_questions');
        Schema::dropIfExists('training_programs');
    }

    private function seedCustomerSupport(): void
    {
        if (DB::table('training_programs')->where('name', 'Customer Support')->exists()) {
            return;
        }

        $now = now();
        $programId = (string) Str::uuid();
        DB::table('training_programs')->insert([
            'id' => $programId,
            'name' => 'Customer Support',
            'status' => 'published',
            'version' => 1,
            'text_title' => 'Qualifying an unknown contact',
            'text_body' => "A contact is unknown when the message does not name a service and does not say they want to work with Panun Kaergar.\n\n1. Do they need a service, or do they want to work with us?\n2. Which service, or which trade?\n3. Which area?\n4. What timing do they need?\n\nSet the type only after they answer. Do not promise a time that is not confirmed.",
            'audio_script' => 'On an unknown message, call the same day. Ask the four questions. Leave the type unknown until they answer. Give only the published price, and confirm the time after a provider is set.',
            'video_note' => "A short message arrives with no service named. Call the same day and ask the four questions. If they need a plumber in Rajbagh, give the published price and confirm the time only after a provider is set. Save the type, service, area, what they said, and a next time.",
            'ai_situation' => 'A customer says: “My sink is blocked in Rajbagh. Can someone come in 30 minutes?” Write what you will say, and what you will save.',
            'question_pass_mark' => 3,
            'ai_pass_mark' => 3,
            'created_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $questions = [
            ['A message says “Hi, I called you earlier.” No service is named. What do you do first?', ['Set the type to Customer', 'Call the same day and ask the four questions before you set a type', 'Close it as invalid'], 1, 'Call first. A short message does not set the type.'],
            ['They say they are an electrician in Budgam and want to work with Panun Kaergar. Which path is this?', ['Customer booking', 'Provider: record the trade and area, then follow up for the agreement', 'Leave it unknown for a week'], 1, 'Wanting to work is the provider path.'],
            ['They ask you to promise a plumber in 30 minutes. No provider is confirmed. What do you say?', ['Yes, 30 minutes', 'I can give the published price. I will confirm the time after a provider is set', 'I can lower the price if they book now'], 1, 'Published price only. No promised time, no discount.'],
            ['You could not book the job yet. What must be saved?', ['Only the phone number', 'Type, service, area, what they said, and a next time', 'A price you think is fair'], 1, 'The case needs a type, the details, and a next time.'],
        ];
        foreach ($questions as $index => $question) {
            DB::table('training_questions')->insert([
                'id' => (string) Str::uuid(),
                'training_program_id' => $programId,
                'prompt' => $question[0],
                'options' => json_encode($question[1]),
                'correct_index' => $question[2],
                'explanation' => $question[3],
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $points = [
            ['Does not promise a time', "cannot promise|can't promise|do not promise|not promise a time|will not promise"],
            ['Gives only the published price', 'published price'],
            ['Saves Customer, plumbing, and Rajbagh', 'customer,plumb,rajbagh'],
            ['Sets a follow-up when the job is not booked', 'follow-up|follow up'],
        ];
        foreach ($points as $index => $point) {
            DB::table('training_marking_points')->insert([
                'id' => (string) Str::uuid(),
                'training_program_id' => $programId,
                'label' => $point[0],
                'keywords' => $point[1],
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
