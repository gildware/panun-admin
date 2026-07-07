<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\ProviderCompletedServicesCounter;
use PHPUnit\Framework\TestCase;

class ProviderCompletedServicesCounterTest extends TestCase
{
    public function test_it_exposes_single_and_batch_count_helpers(): void
    {
        $counter = new class extends ProviderCompletedServicesCounter {
            public array $batchResult = ['provider-a' => 4];

            public function countsForProviders(array $providerIds): array
            {
                return $this->batchResult;
            }
        };

        $this->assertSame(4, $counter->countForProvider('provider-a'));
        $this->assertSame(0, $counter->countForProvider('provider-missing'));
    }
}
