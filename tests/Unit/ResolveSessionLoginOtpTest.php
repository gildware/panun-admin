<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolveSessionLoginOtpTest extends TestCase
{
    #[Test]
    public function it_reuses_unexpired_numeric_otp_on_resend(): void
    {
        $existing = (object) [
            'otp' => '482913',
            'expires_at' => Carbon::now()->addMinutes(2),
        ];

        $this->assertSame('482913', resolve_session_login_otp($existing, '+917889729790'));
    }

    #[Test]
    public function it_generates_a_new_otp_when_session_expired(): void
    {
        config(['app.use_dummy_otp' => true, 'app.dummy_login_otp' => '123456']);

        $existing = (object) [
            'otp' => '482913',
            'expires_at' => Carbon::now()->subMinute(),
        ];

        $this->assertSame('123456', resolve_session_login_otp($existing, '+917889729790'));
    }

    #[Test]
    public function it_generates_a_new_otp_when_no_existing_session(): void
    {
        config(['app.use_dummy_otp' => true, 'app.dummy_login_otp' => '123456']);

        $this->assertSame('123456', resolve_session_login_otp(null, '+917889729790'));
    }

    #[Test]
    public function it_ignores_non_numeric_stored_values_like_firebase_session_info(): void
    {
        config(['app.use_dummy_otp' => true, 'app.dummy_login_otp' => '123456']);

        $existing = (object) [
            'otp' => 'AIzaSyNotARealFirebaseSession',
            'expires_at' => Carbon::now()->addMinutes(2),
        ];

        $this->assertSame('123456', resolve_session_login_otp($existing, '+917889729790'));
    }
}
