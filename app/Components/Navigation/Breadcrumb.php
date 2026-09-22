<?php

/**
 * A breadcrumb trail. The last crumb is the current location: it renders as
 * text with aria-current, never as a link to the page the reader is on.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Navigation;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Breadcrumb extends MarkupComponent
{
    /**
     * @param list<Crumb> $crumbs
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $crumbs,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/breadcrumb.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $crumbs = $this->crumbs;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
