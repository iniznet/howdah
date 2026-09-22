<?php

/**
 * A status badge. Its text is its accessible name; a colour alone carries
 * nothing, so the tone never carries meaning on its own.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Badge extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $label,
        private readonly Tone $tone = Tone::Neutral,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/badge.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $label = $this->label;
        $tone = $this->tone;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
