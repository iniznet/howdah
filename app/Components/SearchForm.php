<?php

/**
 * The search surface's form. A GET search is a public lookup, not a state
 * change: no nonce, no capability, the form's action and the echoed term
 * arrive as resolved props and nothing here reads a superglobal.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components;

use Iniznet\Mahout\Render\MarkupComponent;
use Iniznet\Mahout\Ui\ClassResolver;

final class SearchForm extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $action,
        private readonly string $term = '',
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/search-form.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $action = $this->action;
        $term = $this->term;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
