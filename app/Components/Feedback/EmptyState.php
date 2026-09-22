<?php

/**
 * The empty state: a translated message and a next action, with no layout
 * collapse. The action is already-rendered markup — usually a Button or a
 * Link — because the state owns placement, not the action's internals.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Feedback;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class EmptyState extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $title,
        private readonly string $description,
        private readonly string $actions = '',
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/empty-state.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $title = $this->title;
        $description = $this->description;
        $actions = $this->actions;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
