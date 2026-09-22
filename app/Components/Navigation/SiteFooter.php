<?php

/**
 * The site footer: a colophon slot and the year. The year is rendered from
 * the current time, not from a stored value, so it cannot go stale.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Navigation;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class SiteFooter extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $colophon = '',
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/site-footer.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $colophon = $this->colophon;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
