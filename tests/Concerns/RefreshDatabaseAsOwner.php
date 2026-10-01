<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Rebuilds the schema as the table owner; the tests run as a role subject to row level security.
 */
trait RefreshDatabaseAsOwner
{
    use RefreshDatabase {
        migrateFreshUsing as private freshMigrationParameters;
    }

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [...$this->freshMigrationParameters(), '--database' => 'pgsql_owner'];
    }
}
