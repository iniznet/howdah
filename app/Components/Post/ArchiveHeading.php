<?php

/**
 * The archive's in-content heading: what is being listed, as the site's own
 * label resolved it. One escape per output.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class ArchiveHeading extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $heading,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/archive-heading.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $heading = $this->heading;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
