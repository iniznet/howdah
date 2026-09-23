<?php

/**
 * One page of a listing. hasMore comes from the peek — the query asked for
 * per-page + 1 rows — so no pagination count query is ever issued.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content;

use Iniznet\Mahout\Content\PostData;

final readonly class PostList
{
    /**
     * @param list<PostData> $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {
    }
}
