<?php

/**
 * A combobox: a text input that owns a listbox. The ARIA wiring is the
 * markup's job — the owned list, the autocomplete contract — because the
 * client script only flips the expanded state the document declares.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Combobox extends MarkupComponent
{
    /**
     * @param list<string> $suggestions
     */
    public function __construct(
        ClassResolver $classes,
        private readonly string $id,
        private readonly string $name,
        private readonly string $label,
        private readonly array $suggestions = [],
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/combobox.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $id = $this->id;
        $name = $this->name;
        $label = $this->label;
        $suggestions = $this->suggestions;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
