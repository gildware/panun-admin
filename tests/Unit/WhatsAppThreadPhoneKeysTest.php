<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Support\WhatsAppThreadPhoneKeys;
use PHPUnit\Framework\TestCase;

class WhatsAppThreadPhoneKeysTest extends TestCase
{
    public function test_matches_plus_and_bare_digits(): void
    {
        $closed = [];
        foreach (WhatsAppThreadPhoneKeys::keys('919876543210') as $key) {
            $closed[$key] = true;
        }

        $this->assertTrue(WhatsAppThreadPhoneKeys::isClosed('+919876543210', $closed));
        $this->assertTrue(WhatsAppThreadPhoneKeys::isClosed('9876543210', $closed));
        $this->assertFalse(WhatsAppThreadPhoneKeys::isClosed('911112223334', $closed));
    }

    public function test_matches_last_ten_digits_and_country_prefix(): void
    {
        $this->assertTrue(WhatsAppThreadPhoneKeys::matches('9999740595', '919999740595'));
        $this->assertTrue(WhatsAppThreadPhoneKeys::matches('+91 99997 40595', '9999740595'));
        $this->assertFalse(WhatsAppThreadPhoneKeys::matches('9999740595', '9999740596'));
        $this->assertFalse(WhatsAppThreadPhoneKeys::matches('', '9999740595'));
    }

    public function test_empty_phone_is_not_closed(): void
    {
        $this->assertSame([], WhatsAppThreadPhoneKeys::keys(''));
        $this->assertFalse(WhatsAppThreadPhoneKeys::isClosed('', ['9198' => true]));
    }

    public function test_lookup_matches_plus_prefix_variants(): void
    {
        $user = (object) ['phone' => '+1998018888', 'handled_by' => 'admin-1'];
        $map = WhatsAppThreadPhoneKeys::indexByPhoneKeys([$user]);

        $this->assertSame($user, WhatsAppThreadPhoneKeys::lookup($map, '1998018888'));
        $this->assertSame($user, WhatsAppThreadPhoneKeys::lookup($map, '+1998018888'));
        $this->assertNull(WhatsAppThreadPhoneKeys::lookup($map, '1998018889'));
        $this->assertContains('+1998018888', WhatsAppThreadPhoneKeys::expand(['1998018888']));
    }
}
