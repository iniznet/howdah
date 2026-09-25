<?php

/**
 * The series repository: the feature's only reader, and the only place a
 * request's intent becomes a query. The mechanics — the hardened shape, the
 * peek pagination, the priming — belong to mahout-content's PostReader; this
 * file composes a QuerySpec for the declared post type and maps the primed
 * rows. The tagline is read per post through the field layer after the
 * reader's priming, one statement per store.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series;

use Iniznet\Mahout\Content\PostReader;
use Iniznet\Mahout\Content\QuerySpec;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\ObjectRef;

final readonly class SeriesRepository
{
    public function __construct(
        private PostReader $reader,
        private FieldReader $fields,
        private SeriesMapper $mapper,
    ) {
    }

    /**
     * One page of published series, newest first, with the peek deciding
     * hasMore. A page beyond the graph is an empty list, not an error: the
     * listing arm's overflow guard declares that state.
     */
    public function page(int $page, int $perPage): SeriesPage
    {
        $rows = $this->reader->fetch(new QuerySpec(
            filters: [
                'post_type' => 'howdah_series',
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            ],
            perPage: $perPage,
            offset: ($page - 1) * $perPage,
        ));

        $items = [];

        foreach ($rows->posts as $post) {
            $tagline = $this->fields->value('series_tagline', ObjectRef::post((int) $post->ID));
            $items[] = $this->mapper->card($post, null === $tagline ? null : (string) $tagline);
        }

        return new SeriesPage($items, $rows->hasMore);
    }
}
