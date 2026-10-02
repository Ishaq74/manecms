<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\ImpersonationSession;
use Illuminate\Http\RedirectResponse;

final class StopImpersonationController
{
    public function __invoke(ImpersonationSession $impersonation): RedirectResponse
    {
        abort_if($impersonation->stop() === null, 404);

        return redirect()->route('platform.dashboard')->with('status', 'impersonation-ended');
    }
}
