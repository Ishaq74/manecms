<?php

namespace App\Domain\Authorization\Console;

use App\Domain\Authorization\Actions\SyncPermissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('authorization:sync-permissions')]
#[Description('Copy the permissions declared in code into the database and grant the system roles of every tenant')]
final class SyncPermissionsCommand extends Command
{
    public function handle(SyncPermissions $syncPermissions): int
    {
        $result = $syncPermissions();

        $this->components->info(sprintf(
            '%d permissions synchronised, %d removed, %d tenants updated.',
            $result['permissions'],
            $result['removed'],
            $result['tenants'],
        ));

        return self::SUCCESS;
    }
}
