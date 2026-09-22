<?php

/**
 * A button. Its label is text, its type and variant are enums, and it renders
 * no icon — an icon inside a button with no text is IconButton's job.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Actions;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Button extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $label,
        private readonly ButtonType $type = ButtonType::Button,
        private readonly ButtonVariant $variant = ButtonVariant::Primary,
        private readonly bool $disabled = false,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/button.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $label = $this->label;
        $type = $this->type;
        $variant = $this->variant;
        $disabled = $this->disabled;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
