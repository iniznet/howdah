<?php

/**
 * One series in the archive grid: the title as the link, the tagline as the
 * teaser line. Typed props only — the id is the caller's bookkeeping, the
 * link and the escape live here and nowhere else.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series\Components;

use Iniznet\Howdah\Features\Series\SeriesData;
use Iniznet\Mahout\Render\MarkupComponent;
use Iniznet\Mahout\Ui\ClassResolver;

final class SeriesCard extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly SeriesData $series,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/series-card.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $series = $this->series;
        require __DIR__.'/markup/series-card.php';

        return (string) \ob_get_clean();
    }
}
