<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Tests\TestCase;

class BusinessConfigScalarTest extends TestCase
{
    public function test_array_cast_cannot_read_legacy_plain_text_values(): void
    {
        $row = new BusinessSettings();
        $row->setRawAttributes([
            'live_values' => 'Hi! Please use this {CODE} at time of registration.',
        ], true);

        $this->assertNull($row->live_values);
    }

    public function test_array_cast_reads_json_encoded_string_values(): void
    {
        $row = new BusinessSettings();
        $row->setRawAttributes([
            'live_values' => '"Saved JSON message {CODE}"',
        ], true);

        $this->assertSame('Saved JSON message {CODE}', $row->live_values);
    }
}
