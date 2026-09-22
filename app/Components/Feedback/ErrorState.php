<?php

/**
 * The error state: what failed, in plain language, and a support reference
 * where the failure is unexpected. The reference never carries a path or a
 * value (SEC-01's neighbour rule) — it is a stable code a reader can quote.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Feedback;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class ErrorState extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $title,
        private readonly string $description,
        private readonly ?string $reference = null,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/error-state.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $title = $this->title;
        $description = $this->description;
        $reference = $this->reference;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
