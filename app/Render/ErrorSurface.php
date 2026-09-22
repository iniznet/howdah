<?php

/**
 * The production failure render. A defined render, not a degraded mode: the
 * page's data failed, so the boundary renders the one page every failure
 * produces, with the support reference Diagnostics returned. Law 3 governs
 * data and behaviour; the error boundary is the law's defined output.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Support\ClassResolver;

final class ErrorSurface extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $reference,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/error.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $reference = $this->reference;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
