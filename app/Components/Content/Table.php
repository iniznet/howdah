<?php

/**
 * A data table. Headers and rows are structured, not a dumped string, so a
 * header cell always exists for a data cell to point at.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Table extends MarkupComponent
{
    /**
     * @param list<string>       $headers
     * @param list<list<string>> $rows
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $headers,
        private readonly array $rows,
        private readonly string $caption = '',
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/table.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $headers = $this->headers;
        $rows = $this->rows;
        $caption = $this->caption;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
