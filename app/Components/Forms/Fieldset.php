<?php

/**
 * A group of related controls with its legend. Groups of related controls
 * use fieldset and legend — radio groups above all — so the grouping is
 * announced.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Fieldset extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $legend,
        private readonly string $controls,
        private readonly ?string $describedBy = null,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/fieldset.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $legend = $this->legend;
        $controls = $this->controls;
        $describedBy = $this->describedBy;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
