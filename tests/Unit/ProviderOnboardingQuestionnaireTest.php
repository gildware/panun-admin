<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\ProviderQuestionnaireService;
use Modules\ProviderManagement\Support\ProviderOnboardingQuestionnaire;
use Tests\TestCase;

class ProviderOnboardingQuestionnaireTest extends TestCase
{
    public function test_catalog_includes_all_paper_questions(): void
    {
        $ids = array_column(ProviderOnboardingQuestionnaire::allQuestions(), 'id');

        $this->assertSame(13, ProviderOnboardingQuestionnaire::questionCount());
        $this->assertSame([
            'expertise',
            'working_area',
            'days_available',
            'max_travel',
            'working_hours',
            'emergency_off_day',
            'years_experience',
            'job_types',
            'travel_mode',
            'current_earnings',
            'min_job_value',
            'problems',
            'expectations',
        ], $ids);
    }

    public function test_sanitize_drops_invalid_options_and_keeps_other_text_only_when_selected(): void
    {
        $clean = ProviderOnboardingQuestionnaire::sanitize([
            'job_types' => ['small', 'not-a-real-option', 'large'],
            'travel_mode' => 'teleport',
            'travel_mode_other' => 'spaceship',
            'problems' => ['other', 'payment_delays'],
            'problems_other' => '<b>cash only</b>',
            'emergency_off_day' => 'no',
            'emergency_conditions' => 'extra charge',
            'main_expertise' => '  Electrician  ',
        ]);

        $this->assertSame(['small', 'large'], $clean['job_types']);
        $this->assertSame('', $clean['travel_mode']);
        $this->assertSame('', $clean['travel_mode_other']);
        $this->assertSame(['other', 'payment_delays'], $clean['problems']);
        $this->assertSame('cash only', $clean['problems_other']);
        $this->assertSame('no', $clean['emergency_off_day']);
        $this->assertSame('', $clean['emergency_conditions']);
        $this->assertSame('Electrician', $clean['main_expertise']);
    }

    public function test_format_and_answered_count(): void
    {
        $answers = ProviderOnboardingQuestionnaire::sanitize([
            'main_expertise' => 'Plumber',
            'other_services' => 'Tap repair',
            'job_types' => ['small', 'medium'],
            'emergency_off_day' => 'yes',
            'emergency_conditions' => 'Double fare',
            'earning_daily' => '800',
            'working_hours_from' => '9:00',
            'working_hours_from_period' => 'AM',
            'working_hours_to' => '6:00',
            'working_hours_to_period' => 'PM',
        ]);

        $questions = [];
        foreach (ProviderOnboardingQuestionnaire::allQuestions() as $question) {
            $questions[$question['id']] = $question;
        }

        $this->assertSame(
            'Main Expertise: Plumber · Other Services/Work: Tap repair',
            ProviderOnboardingQuestionnaire::formatAnswer($questions['expertise'], $answers)
        );
        $this->assertSame(
            'Small Jobs — Up to half a day, Medium Jobs — 1-2 days',
            ProviderOnboardingQuestionnaire::formatAnswer($questions['job_types'], $answers)
        );
        $this->assertSame(
            'Yes, If Yes, under what conditions? Double fare',
            ProviderOnboardingQuestionnaire::formatAnswer($questions['emergency_off_day'], $answers)
        );
        $this->assertSame(
            '9:00 AM to 6:00 PM',
            ProviderOnboardingQuestionnaire::formatAnswer($questions['working_hours'], $answers)
        );
        $this->assertSame(
            'Daily ₹ 800',
            ProviderOnboardingQuestionnaire::formatAnswer($questions['current_earnings'], $answers)
        );
        $this->assertNull(ProviderOnboardingQuestionnaire::formatAnswer($questions['working_area'], $answers));
        $this->assertSame(5, ProviderOnboardingQuestionnaire::answeredCount($answers));
    }

    public function test_service_records_and_updates_answers(): void
    {
        Schema::dropIfExists('provider_questionnaires');
        Schema::create('provider_questionnaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('provider_id')->unique();
            $table->json('answers')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        $provider = new Provider();
        $provider->id = (string) Str::uuid();
        $userId = (string) Str::uuid();

        $service = new ProviderQuestionnaireService();
        $saved = $service->save($provider, [
            'main_expertise' => 'Carpenter',
            'job_types' => ['long_term'],
        ], $userId);

        $this->assertSame($provider->id, $saved->provider_id);
        $this->assertSame($userId, $saved->recorded_by);
        $this->assertSame('Carpenter', $saved->answers['main_expertise']);
        $this->assertSame(['long_term'], $saved->answers['job_types']);

        $updated = $service->save($provider, [
            'main_expertise' => 'Senior carpenter',
            'job_types' => ['small', 'long_term'],
        ], (string) Str::uuid());

        $this->assertSame((string) $saved->id, (string) $updated->id);
        $this->assertSame($userId, $updated->recorded_by);
        $this->assertSame('Senior carpenter', $updated->answers['main_expertise']);
        $this->assertSame(['small', 'long_term'], $updated->answers['job_types']);
        $this->assertSame(1, \Modules\ProviderManagement\Entities\ProviderQuestionnaire::query()->where('provider_id', $provider->id)->count());
    }
}
