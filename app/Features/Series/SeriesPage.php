<?php

/**
 * One page of the series listing, with the peek's answer carried alongside
 * so the pagination needs no second query.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series;

final readonly class SeriesPage
{
    /**
     * @param list<SeriesData> $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {
    }
}
