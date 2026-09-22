<?php

/**
 * Vertical rhythm: children laid out in a column with a gap. The gap is a
 * Size, never a raw value, so the stylesheet owns the scale. Children arrive
 * as already-rendered HTML — the output of other components — which is why
 * the markup prints them raw.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Stack extends MarkupComponent
{
    /**
     * @param list<string> $children rendered component output, escaped where it was built
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $children,
        private readonly Size $gap = Size::Medium,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/stack.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $gap = $this->gap;
        $children = $this->children;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
