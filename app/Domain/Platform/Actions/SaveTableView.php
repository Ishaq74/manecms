<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\SavedTableView;
use App\Domain\Tenancy\Context\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class SaveTableView
{
    public const int MAX_VIEWS_PER_TABLE = 20;

    public function __construct(private TenantContext $context) {}

    /**
     * Save, or overwrite by name, the caller's view of a table in the current tenant.
     *
     * @param  array<string, mixed>  $state  already normalised by the table
     *
     * @throws ValidationException
     */
    public function __invoke(User $user, string $tableKey, string $name, array $state): SavedTableView
    {
        $tenantId = $this->context->tenant()->id;
        $name = trim($name);

        Validator::make(['viewName' => $name], ['viewName' => ['required', 'string', 'max:80']])->validate();

        $existing = SavedTableView::query()
            ->where('user_id', $user->id)
            ->where('table_key', $tableKey)
            ->where('name', $name)
            ->first();

        if ($existing === null && $this->countViews($user, $tableKey) >= self::MAX_VIEWS_PER_TABLE) {
            throw ValidationException::withMessages([
                'viewName' => __('You can save up to :count views for this table.', ['count' => self::MAX_VIEWS_PER_TABLE]),
            ]);
        }

        $view = $existing ?? new SavedTableView([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'table_key' => $tableKey,
            'name' => $name,
        ]);

        $view->state = $state;
        $view->save();

        return $view;
    }

    private function countViews(User $user, string $tableKey): int
    {
        return SavedTableView::query()->where('user_id', $user->id)->where('table_key', $tableKey)->count();
    }
}
