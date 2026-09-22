<?php

/**
 * A repeatable column grid. The column count is bounded: a grid wider than
 * the viewport reflows anyway, and an unbounded count would let a call site
 * render unreadable columns.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Grid extends MarkupComponent
{
    public const int MAX_COLUMNS = 6;

    /**
     * @param list<string> $children rendered component output, escaped where it was built
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $children,
        private readonly int $columns = 2,
        private readonly Size $gap = Size::Medium,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/grid.php';
    }

    #[\Override]
    public function render(): string
    {
        $columns = min(max(1, $this->columns), self::MAX_COLUMNS);

        \ob_start();
        $c = $this->classes();
        $gap = $this->gap;
        $children = $this->children;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
