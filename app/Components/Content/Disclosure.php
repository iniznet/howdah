<?php

/**
 * A collapsed region: the native details/summary element. No script is
 * needed to open it, and the open state is a user choice the document
 * remembers nothing about.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Disclosure extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $summary,
        private readonly string $body,
        private readonly bool $open = false,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/disclosure.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $summary = $this->summary;
        $body = $this->body;
        $open = $this->open;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
