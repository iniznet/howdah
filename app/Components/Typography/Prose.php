<?php

/**
 * A long-form reading region: the post body, an excerpted block. The body is
 * trusted, already-escaped HTML — it is the render output of the content
 * pipeline, not visitor input — so the markup prints it raw. Anything that
 * reaches Prose has passed through the pipeline's own sanitising first.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Typography;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Prose extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $body,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/prose.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $body = $this->body;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
