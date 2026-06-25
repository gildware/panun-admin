<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstDestructiveDatabaseUse();
    }

    /**
     * Never allow PHPUnit to touch a real MySQL/MariaDB database.
     */
    protected function guardAgainstDestructiveDatabaseUse(): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        $default = (string) config('database.default', '');
        $connection = config('database.connections.'.$default, []);
        $driver = (string) ($connection['driver'] ?? $default);
        $database = (string) ($connection['database'] ?? '');

        $isInMemorySqlite = $driver === 'sqlite' && $database === ':memory:';

        if ($isInMemorySqlite) {
            return;
        }

        throw new \RuntimeException(
            'SAFETY STOP: PHPUnit is configured to use "'.$driver.'" database "'.$database.'". '.
            'Tests must use sqlite :memory: only (see phpunit.xml). '.
            'This prevents migrate:fresh from wiping your real data.'
        );
    }
}
