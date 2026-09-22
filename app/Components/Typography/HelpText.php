<?php

/**
 * A control's help text. It carries the id a control's aria-describedby
 * points at, because programmatic association is the whole point.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Typography;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class HelpText extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $id,
        private readonly string $text,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/help-text.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $id = $this->id;
        $text = $this->text;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
