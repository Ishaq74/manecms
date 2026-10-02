<?php

namespace App\Domain\Platform\Console;

use App\Domain\Platform\Actions\AssignPlatformRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('platform:revoke-operator {email : Email of the account}')]
#[Description('Remove the platform operator role from an account')]
final class RevokeOperatorCommand extends Command
{
    public function handle(AssignPlatformRole $assignPlatformRole): int
    {
        try {
            $user = $assignPlatformRole((string) $this->argument('email'), null);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$user->email} is no longer a platform operator.");

        return self::SUCCESS;
    }
}
