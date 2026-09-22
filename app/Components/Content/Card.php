<?php

/**
 * A generic card: a title, an optional body, and a slot for actions. It is
 * the design-system's own card; the Post listing card composes it.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Card extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $title,
        private readonly string $body,
        private readonly string $actions = '',
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/card.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $title = $this->title;
        $actions = $this->actions;
        $body = $this->body;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
