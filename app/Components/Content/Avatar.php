<?php

/**
 * An avatar: a person's image or their initials. The alt text names the
 * person or states that it is decorative, never nothing at all.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Avatar extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $src,
        private readonly string $name,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/avatar.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $src = $this->src;
        $name = $this->name;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
