<?php

/**
 * A link that behaves like a control: it may be external, and an external
 * link says so. A plain permalink does not pass through here — markup links
 * directly — this component is for action-shaped links.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Actions;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Link extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $href,
        private readonly string $label,
        private readonly bool $external = false,
        private readonly bool $quiet = false,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/link.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $href = $this->href;
        $label = $this->label;
        $external = $this->external;
        $quiet = $this->quiet;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
