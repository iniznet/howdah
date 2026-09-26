<?php

/**
 * The series repository: the feature's only reader, and the only place a
 * request's intent becomes a query. The mechanics — the hardened shape, the
 * peek pagination, the priming — belong to mahout-content's PostReader; this
 * file composes a QuerySpec for the declared post type and maps the primed
 * rows. Every field on the page is read through the field layer after both
 * primings — the reader's, for post and term caches, and the field layer's, for
 * the value table — so a page costs one statement per store whichever target each
 * field was anchored to.
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

        // Filed before mapping, exactly as the meta and term caches are primed before
        // mapping: which target a field happens to use must not decide how many
        // statements a page costs.
        $this->fields->prime(\array_map(
            static fn (\WP_Post $post): ObjectRef => ObjectRef::post((int) $post->ID),
            $rows->posts,
        ));

        $items = [];

        foreach ($rows->posts as $post) {
            $tagline = $this->fields->value('series_tagline', ObjectRef::post((int) $post->ID));
            $items[] = $this->mapper->card($post, null === $tagline ? null : (string) $tagline);
        }

        return new SeriesPage($items, $rows->hasMore);
    }
}
