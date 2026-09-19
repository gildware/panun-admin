<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Support\WhatsAppChatHandler;
use PHPUnit\Framework\TestCase;

class WhatsAppChatHandlerTest extends TestCase
{
    public function test_ai_null_and_empty_are_unassigned(): void
    {
        $this->assertTrue(WhatsAppChatHandler::isUnassigned(null));
        $this->assertTrue(WhatsAppChatHandler::isUnassigned(''));
        $this->assertTrue(WhatsAppChatHandler::isUnassigned('AI'));
        $this->assertTrue(WhatsAppChatHandler::isUnassigned('ai'));
        $this->assertFalse(WhatsAppChatHandler::isUnassigned(WhatsAppChatHandler::UNMATCHED));
        $this->assertFalse(WhatsAppChatHandler::isUnassigned('admin-uuid'));
    }

    public function test_ai_filter_excludes_unmatched_and_assigned(): void
    {
        $this->assertTrue(WhatsAppChatHandler::matchesFilters('AI', ['ai']));
        $this->assertTrue(WhatsAppChatHandler::matchesFilters('', ['ai']));
        $this->assertFalse(WhatsAppChatHandler::matchesFilters(WhatsAppChatHandler::UNMATCHED, ['ai']));
        $this->assertFalse(WhatsAppChatHandler::matchesFilters('admin-uuid', ['ai']));
        $this->assertTrue(WhatsAppChatHandler::matchesFilters('admin-uuid', ['admin-uuid']));
        $this->assertTrue(WhatsAppChatHandler::matchesFilters('AI', []));
    }
}
