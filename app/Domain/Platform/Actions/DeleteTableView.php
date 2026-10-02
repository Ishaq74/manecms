<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\SavedTableView;
use App\Models\User;

final readonly class DeleteTableView
{
    /**
     * Delete one of the caller's saved views; row level security already hides the others.
     */
    public function __invoke(User $user, string $viewId): void
    {
        SavedTableView::query()->whereKey($viewId)->where('user_id', $user->id)->delete();
    }
}
