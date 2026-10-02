<?php

use App\Domain\Audit\Permissions\AuditPermission;
use App\Domain\Tenancy\Permissions\TenancyPermission;

/*
|--------------------------------------------------------------------------
| Permissions
|--------------------------------------------------------------------------
|
| Every context declares its permissions in a backed enum implementing
| App\Domain\Authorization\Permission (todo/todo.md §28). This list is the
| source of truth: `php artisan authorization:sync-permissions` copies it
| into the `permissions` table and grants the system roles of every tenant.
|
*/

return [

    'permissions' => [
        AuditPermission::class,
        TenancyPermission::class,
    ],

];
