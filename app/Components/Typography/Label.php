<?php

/**
 * A form control's label. The for attribute is required — a label that is
 * not programmatically associated is decoration.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Typography;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Label extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $for,
        private readonly string $text,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/label.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $for = $this->for;
        $text = $this->text;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
