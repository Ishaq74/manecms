<?php

namespace App\Domain\Platform\Console;

use App\Domain\Platform\Actions\AssignPlatformRole;
use App\Domain\Platform\Enums\PlatformRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('platform:grant-operator {email : Email of the account}')]
#[Description('Make an account a platform operator (the only way to grant the role)')]
final class GrantOperatorCommand extends Command
{
    public function handle(AssignPlatformRole $assignPlatformRole): int
    {
        try {
            $user = $assignPlatformRole((string) $this->argument('email'), PlatformRole::Operator);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$user->email} is now a platform operator.");

        return self::SUCCESS;
    }
}
