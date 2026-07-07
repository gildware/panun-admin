<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\ProviderSubscribedServicesCounter;
use PHPUnit\Framework\TestCase;

class ProviderSubscribedServicesCounterTest extends TestCase
{
    public function test_it_exposes_single_and_batch_count_helpers(): void
    {
        $counter = new class extends ProviderSubscribedServicesCounter {
            public array $batchResult = ['provider-a' => 12];

            public function countsForProviders(array $providerIds): array
            {
                return $this->batchResult;
            }
        };

        $this->assertSame(12, $counter->countForProvider('provider-a'));
        $this->assertSame(0, $counter->countForProvider('provider-missing'));
    }
}
