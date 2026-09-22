<?php

/**
 * The width cap a page's content sits inside. The measure is a token, not a
 * per-call value, so every reading surface keeps the same line length.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Container extends MarkupComponent
{
    /**
     * @param list<string> $children rendered component output, escaped where it was built
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $children,
        private readonly Width $width = Width::Reading,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/container.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $width = $this->width;
        $children = $this->children;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
