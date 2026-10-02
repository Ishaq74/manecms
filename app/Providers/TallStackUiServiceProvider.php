<?php

namespace App\Providers;

use App\View\Components\TallStackUi\Customizations\FormCustomization;
use App\View\Components\TallStackUi\Customizations\FrameCustomization;
use App\View\Components\TallStackUi\Customizations\OverlayCustomization;
use App\View\Components\TallStackUi\Customizations\SurfaceCustomization;
use Illuminate\Support\ServiceProvider;

/**
 * Paints the TallStackUI internals with the project palette.
 *
 * The package ships its own Tailwind colours, mostly greys. Each customization
 * class replaces them for one family of components with the primary, secondary
 * and dark ramps, which keeps the rendered markup free of off-palette classes.
 */
class TallStackUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new FormCustomization)();
        (new FrameCustomization)();
        (new SurfaceCustomization)();
        (new OverlayCustomization)();
    }
}
