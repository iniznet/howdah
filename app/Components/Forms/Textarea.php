<?php

/**
 * A multi-line control. The same contract as Input: the label is required
 * and associated, help text is referenced by id.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Textarea extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $id,
        private readonly string $name,
        private readonly string $label,
        private readonly string $value = '',
        private readonly int $rows = 5,
        private readonly bool $required = false,
        private readonly bool $invalid = false,
        private readonly ?string $describedBy = null,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/textarea.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $id = $this->id;
        $name = $this->name;
        $label = $this->label;
        $value = $this->value;
        $rows = $this->rows;
        $required = $this->required;
        $invalid = $this->invalid;
        $describedBy = $this->describedBy;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
