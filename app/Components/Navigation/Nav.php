<?php

/**
 * A navigation region. The label is required — multiple navs are otherwise
 * indistinguishable to assistive technology — and the current item is marked
 * with aria-current, not with a class alone.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Navigation;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Nav extends MarkupComponent
{
    /**
     * @param list<NavItem> $items
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $items,
        private readonly string $label,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/nav.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $items = $this->items;
        $label = $this->label;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
