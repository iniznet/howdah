<?php

/**
 * A section of the document: a labelled landmark region. An unlabelled
 * section is a div with extra steps, so the label is required — it names the
 * region for assistive technology and for the reader.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Section extends MarkupComponent
{
    /**
     * @param list<string> $children rendered component output, escaped where it was built
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $children,
        private readonly string $label,
        private readonly Size $gap = Size::Medium,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/section.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $gap = $this->gap;
        $label = $this->label;
        $children = $this->children;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
