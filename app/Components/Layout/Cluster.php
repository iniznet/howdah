<?php

/**
 * Horizontal rhythm: children flow in a wrapping row with a gap. The row
 * wraps because a cluster that overflows must reflow, not scroll.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Cluster extends MarkupComponent
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
        return __DIR__.'/markup/cluster.php';
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
