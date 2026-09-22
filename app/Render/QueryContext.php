<?php

/**
 * The request facts, read once. $wp_query and the site globals are read in
 * exactly one place in the codebase, and this is it: every ambient value a
 * render depends on is a property of this object. There is no second request
 * context and no second site read.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Exception\NotBooted;

final readonly class QueryContext
{
    /**
     * @param array<string, mixed> $queryVars
     */
    public function __construct(
        public QueryKind $kind,
        public ?string $postType,
        public ?int $objectId,
        public ?string $objectSubtype,
        public array $queryVars,
        public SiteProfile $site,
    ) {
    }

    /** The only \$wp_query read, and the only site read. */
    public static function current(): self
    {
        $query = $GLOBALS['wp_query'] ?? null;

        if (!$query instanceof \WP_Query) {
            throw NotBooted::beforeQuery();
        }

        $postType = null;
        $subtype = null;
        $object = $query->get_queried_object();

        if ($object instanceof \WP_Post) {
            $postType = $object->post_type;
        } elseif ($object instanceof \WP_Term) {
            $subtype = $object->taxonomy;
        } elseif ($object instanceof \WP_Post_Type) {
            $postType = $object->name;
        } elseif ($object instanceof \WP_User) {
            $subtype = 'author';
        }

        $id = (int) $query->get_queried_object_id();

        $queryVars = [];

        foreach ($query->query_vars as $key => $value) {
            if (\is_string($key)) {
                $queryVars[$key] = $value;
            }
        }

        return new self(
            kind: self::kind($query),
            postType: $postType,
            objectId: $id > 0 ? $id : null,
            objectSubtype: $subtype,
            queryVars: $queryVars,
            site: SiteProfile::fromWordPress(),
        );
    }

    private static function kind(\WP_Query $query): QueryKind
    {
        return match (true) {
            $query->is_embed() => QueryKind::Embed,
            $query->is_404() => QueryKind::NotFound,
            $query->is_search() => QueryKind::Search,
            $query->is_front_page() => QueryKind::Front,
            $query->is_home() => QueryKind::Home,
            $query->is_singular() => QueryKind::Singular,
            $query->is_archive() => QueryKind::Archive,
            default => QueryKind::Generic,
        };
    }
}
