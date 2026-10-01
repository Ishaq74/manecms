<?php

namespace App\Domain\Tenancy\Actions\Concerns;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ValidatesNames
{
    /**
     * @throws ValidationException
     */
    private function validatedName(string $name): string
    {
        $name = Str::squish($name);

        Validator::make(
            ['name' => $name],
            ['name' => ['required', 'string', 'min:2', 'max:120']],
        )->validate();

        return $name;
    }

    /**
     * @throws ValidationException
     */
    private function ensureWorkspaceNameIsFree(Tenant $tenant, string $name, ?string $ignoredWorkspaceId = null): void
    {
        $taken = $tenant->workspaces()
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->when($ignoredWorkspaceId !== null, fn ($query) => $query->whereKeyNot($ignoredWorkspaceId))
            ->exists();

        if ($taken) {
            throw $this->workspaceNameTaken();
        }
    }

    private function workspaceNameTaken(): ValidationException
    {
        return ValidationException::withMessages([
            'name' => __('A workspace with this name already exists.'),
        ]);
    }
}
