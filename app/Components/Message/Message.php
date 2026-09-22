<?php

/**
 * A defined state message: an empty content graph, a not-found address, a
 * search with no usable tokens. One heading, one optional detail line, one
 * escape per output.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Message;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Message extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $heading,
        private readonly ?string $detail = null,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/message.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $heading = $this->heading;
        $detail = $this->detail;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
