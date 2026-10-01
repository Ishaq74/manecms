<?php

/*
|--------------------------------------------------------------------------
| Table Ownership
|--------------------------------------------------------------------------
|
| Every database table belongs to exactly one bounded context (todo/todo.md
| §371). A migration that creates a table must register it here; the test
| suite fails when a table has no owner or when an entry is stale.
|
*/

return [

    'tables' => [
        'cache' => 'Platform',
        'cache_locks' => 'Platform',
        'failed_jobs' => 'Platform',
        'job_batches' => 'Platform',
        'jobs' => 'Platform',
        'migrations' => 'Platform',

        'passkeys' => 'Identity',
        'password_reset_tokens' => 'Identity',
        'personal_access_tokens' => 'Identity',
        'sessions' => 'Identity',
        'users' => 'Identity',

        'tenant_members' => 'Tenancy',
        'tenants' => 'Tenancy',
        'workspaces' => 'Tenancy',
    ],

];
