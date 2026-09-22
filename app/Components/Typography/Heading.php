<?php

/**
 * A heading: a level and its text. The level is the document outline; visual
 * scale comes from the stylesheet, never from the tag choice.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Typography;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Heading extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly HeadingLevel $level,
        private readonly string $text,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/heading.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $level = $this->level;
        $text = $this->text;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
