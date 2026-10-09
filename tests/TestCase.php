<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Test-database safety guard: destructive test operations (RefreshDatabase,
     * DatabaseMigrations, DatabaseTruncation) may target only a dedicated test
     * database. This runs before parent::setUpTraits(), i.e. before any trait
     * can migrate, seed, or truncate — a setUp()-level check would fire too late.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! is_string($database) || stripos($database, 'test') === false) {
            $this->fail(
                'Refusing to run tests: default connection ['.$connection.'] does not point to a dedicated test database (got: '.var_export($database, true).').'
            );
        }

        return parent::setUpTraits();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
