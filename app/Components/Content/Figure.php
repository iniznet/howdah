<?php

/**
 * A figure: an image and its caption, associated. The alt text is required —
 * decorative images are rare enough to be a deliberate, empty-string choice.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Figure extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $src,
        private readonly string $alt,
        private readonly string $caption,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/figure.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $src = $this->src;
        $alt = $this->alt;
        $caption = $this->caption;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
