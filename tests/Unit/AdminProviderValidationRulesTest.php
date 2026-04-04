<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

/**
 * Mirrors critical admin web/API validation behaviour for provider onboarding
 * without exercising HTTP or DB-heavy provider creation.
 */
class AdminProviderValidationRulesTest extends TestCase
{
    public function test_contact_person_email_nullable_accepts_null_and_valid_address(): void
    {
        $this->assertTrue(Validator::make(
            ['contact_person_email' => null],
            ['contact_person_email' => 'nullable|email|max:191']
        )->passes());

        $this->assertTrue(Validator::make(
            ['contact_person_email' => 'owner@example.com'],
            ['contact_person_email' => 'nullable|email|max:191']
        )->passes());
    }

    public function test_contact_person_email_rejects_invalid_format_when_present(): void
    {
        $this->assertTrue(Validator::make(
            ['contact_person_email' => 'not-an-email'],
            ['contact_person_email' => 'nullable|email|max:191']
        )->fails());
    }

    public function test_company_email_required_when_provider_type_is_company(): void
    {
        $this->assertTrue(Validator::make(
            [
                'provider_type' => 'company',
                'company_email' => '',
            ],
            ['company_email' => 'required_if:provider_type,company|email']
        )->fails());

        $this->assertTrue(Validator::make(
            [
                'provider_type' => 'company',
                'company_email' => 'company@example.com',
            ],
            ['company_email' => 'required_if:provider_type,company|email']
        )->passes());

        $this->assertTrue(Validator::make(
            [
                'provider_type' => 'individual',
                'company_email' => '',
            ],
            ['company_email' => 'required_if:provider_type,company|email']
        )->passes());
    }

    public function test_zone_ids_must_contain_valid_uuids(): void
    {
        $this->assertTrue(Validator::make(
            ['zone_ids' => ['not-a-uuid']],
            [
                'zone_ids' => 'required|array|min:1',
                'zone_ids.*' => 'uuid',
            ]
        )->fails());

        $this->assertTrue(Validator::make(
            ['zone_ids' => [Str::uuid()->toString()]],
            [
                'zone_ids' => 'required|array|min:1',
                'zone_ids.*' => 'uuid',
            ]
        )->passes());
    }

    public function test_provider_contact_registration_errors_accepts_empty_email_string(): void
    {
        $errors = User::providerContactRegistrationErrors('+910000000000', '');
        $this->assertIsArray($errors);
        $this->assertArrayNotHasKey('contact_person_email', $errors);
    }

    public function test_web_identity_type_rule_matches_expected_document_types(): void
    {
        $identityIn = 'passport,driving_license,nid';
        $rules = [
            'identity_type' => 'required|in:'.$identityIn,
        ];

        $this->assertTrue(Validator::make(['identity_type' => 'nid'], $rules)->passes());
        $this->assertTrue(Validator::make(['identity_type' => 'passport'], $rules)->passes());
        $this->assertTrue(Validator::make(['identity_type' => 'trade_license'], $rules)->fails());
    }
}
