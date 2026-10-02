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

        'impersonations' => 'Platform',
        'saved_table_views' => 'Platform',

        'passkeys' => 'Identity',
        'password_reset_tokens' => 'Identity',
        'personal_access_tokens' => 'Identity',
        'sessions' => 'Identity',
        'user_devices' => 'Identity',
        'users' => 'Identity',

        'audit_events' => 'Audit',

        'permissions' => 'Authorization',
        'role_permissions' => 'Authorization',
        'roles' => 'Authorization',

        'tenant_invitations' => 'Tenancy',
        'tenant_member_workspaces' => 'Tenancy',
        'tenant_members' => 'Tenancy',
        'tenants' => 'Tenancy',
        'workspaces' => 'Tenancy',
    ],

];
