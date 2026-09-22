<?php

/**
 * A single-line control. The label travels with the control and is
 * associated through the id — placeholder text is never the label.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Input extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $id,
        private readonly string $name,
        private readonly string $label,
        private readonly InputType $type = InputType::Text,
        private readonly string $value = '',
        private readonly bool $required = false,
        private readonly bool $invalid = false,
        private readonly ?string $describedBy = null,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/input.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $id = $this->id;
        $name = $this->name;
        $label = $this->label;
        $type = $this->type;
        $value = $this->value;
        $required = $this->required;
        $invalid = $this->invalid;
        $describedBy = $this->describedBy;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
