<?php

/**
 * A validation summary: an announced list, every entry linking to its field.
 * The list is not one string — each error is its own link.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class FormSummary extends MarkupComponent
{
    /**
     * @param list<string> $messages a message per failing field
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $messages,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/form-summary.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $messages = $this->messages;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
