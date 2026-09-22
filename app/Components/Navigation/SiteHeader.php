<?php

/**
 * The site header: the site name linking home, with a navigation slot. The
 * slot takes already-rendered markup — a Nav component's output — because
 * the header owns placement, not the nav's internals.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Navigation;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class SiteHeader extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $siteName,
        private readonly string $homeUrl,
        private readonly string $navigation = '',
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/site-header.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $siteName = $this->siteName;
        $homeUrl = $this->homeUrl;
        $navigation = $this->navigation;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
