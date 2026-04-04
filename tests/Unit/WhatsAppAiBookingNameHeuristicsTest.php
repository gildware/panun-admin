<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Services\WhatsAppAiBookingNameHeuristics;
use PHPUnit\Framework\TestCase;

class WhatsAppAiBookingNameHeuristicsTest extends TestCase
{
    public function test_detects_mistary_and_plaster_variants(): void
    {
        foreach (['Mistary', 'mistary', 'mistry', 'Palester', 'plastering work', 'Misty'] as $bad) {
            $this->assertTrue(
                WhatsAppAiBookingNameHeuristics::looksLikeServiceNotPersonName($bad),
                "expected service-like: {$bad}"
            );
        }
    }

    public function test_allows_typical_person_names(): void
    {
        foreach (['Ahmad Khan', 'Priya', 'John', 'Fatima', 'Omar'] as $ok) {
            $this->assertFalse(
                WhatsAppAiBookingNameHeuristics::looksLikeServiceNotPersonName($ok),
                "expected person name: {$ok}"
            );
        }
    }
}
