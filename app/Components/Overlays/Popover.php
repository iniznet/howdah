<?php

/**
 * A popover: the native popover attribute pair, so opening, closing and the
 * light dismiss are the browser's behaviour and no script owns them. The
 * trigger and the region are one component, because their ids are a pair.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Overlays;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Popover extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $id,
        private readonly string $triggerLabel,
        private readonly string $body,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/popover.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $id = $this->id;
        $triggerLabel = $this->triggerLabel;
        $body = $this->body;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
