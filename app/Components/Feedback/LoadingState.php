<?php

/**
 * The loading state: a spinner and a text alternative, so the wait is
 * announced and legible under prefers-reduced-motion, where the spinner
 * itself holds still.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Feedback;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class LoadingState extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $label,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/loading-state.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $label = $this->label;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
