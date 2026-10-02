<?php

namespace App\View\Components;

use App\Domain\Platform\ImpersonationSession;
use App\Domain\Platform\Models\Impersonation;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Tells the operator, on every page, whose account they are using and until when.
 */
class ImpersonationBanner extends Component
{
    public function __construct(private readonly ImpersonationSession $impersonation) {}

    public function shouldRender(): bool
    {
        return $this->current() instanceof Impersonation;
    }

    public function render(): View
    {
        $current = $this->current();

        return view('components.impersonation-banner', [
            'impersonation' => $current,
            'userName' => $current?->user->name ?? '',
        ]);
    }

    private function current(): ?Impersonation
    {
        return once(fn (): ?Impersonation => $this->impersonation->current());
    }
}
