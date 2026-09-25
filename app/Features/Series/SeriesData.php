<?php

/**
 * One series, mapped: the facts a card renders. The tagline is the field
 * layer's value and arrives read; nothing here resolves a collaborator.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series;

final readonly class SeriesData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $permalink,
        public ?string $tagline,
    ) {
    }
}
