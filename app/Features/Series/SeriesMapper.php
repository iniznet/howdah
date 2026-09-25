<?php

/**
 * Maps a primed row to the series DTO. The only file in the feature that
 * names WP_Post; the tagline arrives read through the field layer, so the
 * mapper translates and never queries.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series;

final readonly class SeriesMapper
{
    public function card(\WP_Post $post, ?string $tagline): SeriesData
    {
        return new SeriesData(
            id: (int) $post->ID,
            title: (string) \get_the_title($post),
            permalink: (string) \get_permalink($post),
            tagline: '' === $tagline ? null : $tagline,
        );
    }
}
