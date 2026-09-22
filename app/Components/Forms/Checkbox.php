<?php

/**
 * A single checkbox. The label is the control's accessible name; the state
 * is the checked attribute, never a visual substitute.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Checkbox extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $id,
        private readonly string $name,
        private readonly string $label,
        private readonly bool $checked = false,
        private readonly ?string $describedBy = null,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/checkbox.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $id = $this->id;
        $name = $this->name;
        $label = $this->label;
        $checked = $this->checked;
        $describedBy = $this->describedBy;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
