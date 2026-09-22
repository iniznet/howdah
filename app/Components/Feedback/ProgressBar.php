<?php

/**
 * A progress bar. The value is announced through the native role's
 * properties, not through colour or motion.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Feedback;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class ProgressBar extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $label,
        private readonly int $value,
        private readonly int $max = 100,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/progress-bar.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $label = $this->label;
        $value = max(0, min($this->value, $this->max));
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
