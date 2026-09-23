<?php

/**
 * The content repository: the theme's only reader of core content, and the
 * only place a request's intent becomes a query. The mechanics -- the
 * hardened WP_Query shape, the peek pagination, the priming -- belong to
 * mahout-content's {@see PostReader}; this file composes a {@see QuerySpec}
 * per intent and maps the primed rows to the theme's DTOs.
 *
 * Search is the indexed path when the FULLTEXT index is present (SRCH-01).
 * The declaration -- the clause, the relevance ordering and the query vars
 * that carry them -- is `mahout-db`'s {@see IndexedSearchSwap}, so this file
 * states no search grammar. With the index absent the swap answers with
 * core's own search args, the LIKE path runs unchanged, and the absence is
 * recorded loudly by the package's fallback report -- once per request.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content;

use Iniznet\Mahout\Content\PostData;
use Iniznet\Mahout\Content\PostList;
use Iniznet\Mahout\Content\PostReader;
use Iniznet\Mahout\Content\QuerySpec;
use Iniznet\Mahout\Db\Search\IndexedSearchSwap;
use Iniznet\Mahout\Db\Search\SearchTerms;

final readonly class ContentRepository
{
    public function __construct(
        private PostMapper $mapper,
        private PostReader $reader,
        private IndexedSearchSwap $search,
    ) {
    }

    /** One singular post, by id. Core resolved the object as viewable. */
    public function post(int $id, int $page = 1): ?PostData
    {
        return $this->singular(['p' => $id, 'post_type' => 'post'], $page);
    }

    /** One singular page, by id. */
    public function page(int $id, int $page = 1): ?PostData
    {
        return $this->singular(['page_id' => $id, 'post_type' => 'page'], $page);
    }

    /** One page of the posts index, newest first. */
    public function home(int $page): PostList
    {
        return $this->listing([
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC',
        ], $page);
    }

    /** One page of a term archive, for any public taxonomy. */
    public function term(int $termId, int $page): PostList
    {
        $term = \get_term($termId);

        if (!$term instanceof \WP_Term) {
            return new PostList([], false);
        }

        return $this->listing([
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [[
                'taxonomy' => (string) $term->taxonomy,
                'field' => 'term_id',
                'terms' => $termId,
            ]],
        ], $page);
    }

    /** One page of an author archive. */
    public function author(int $authorId, int $page): PostList
    {
        return $this->listing([
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC',
            'author' => $authorId,
        ], $page);
    }

    /** One page of a date archive; omitted parts are simply absent. */
    public function date(int $year, ?int $month, ?int $day, int $page): PostList
    {
        $filters = ['post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC', 'year' => $year];

        if (null !== $month) {
            $filters['monthnum'] = $month;
        }

        if (null !== $day) {
            $filters['day'] = $day;
        }

        return $this->listing($filters, $page);
    }

    /**
     * Whether the search query travels the indexed path. The fallback
     * (core's own LIKE query) is core's query, unchanged; its cost is
     * reported by the Surface that renders it, not silently here.
     */
    public function searchIsIndexed(): bool
    {
        return $this->search->isIndexed();
    }

    /**
     * One page of search results. With the index present this is the
     * FULLTEXT path; without it, core's own LIKE path, unchanged.
     *
     * The swap refuses a term with no usable token on either path, and the
     * Surface that renders a term has already rendered its empty state for
     * one, so no query is issued for it.
     */
    public function search(SearchTerms $terms, int $page): PostList
    {
        return $this->listing($this->search->args($terms), $page);
    }

    /** The queried term's name, or null when the term does not exist. */
    public function termName(int $termId): ?string
    {
        $term = \get_term($termId);

        return $term instanceof \WP_Term ? (string) $term->name : null;
    }

    /** The queried taxonomy's singular label, or null when it is unknown. */
    public function taxonomyLabel(string $taxonomy): ?string
    {
        $object = \get_taxonomy($taxonomy);

        if (!$object instanceof \WP_Taxonomy) {
            return null;
        }

        $label = $object->labels->singular_name ?? null;

        return \is_string($label) && '' !== $label ? $label : null;
    }

    /** The queried author's display name, or null when the user is unknown. */
    public function authorName(int $authorId): ?string
    {
        $user = \get_userdata($authorId);

        return $user instanceof \WP_User ? $user->display_name : null;
    }

    /**
     * The singular intent: one row, fully mapped.
     *
     * @param array<string, mixed> $args
     */
    private function singular(array $args, int $page): ?PostData
    {
        $rows = $this->reader->fetch(new QuerySpec(
            filters: $args,
            perPage: 1,
        ));

        $post = $rows->posts[0] ?? null;

        return $post instanceof \WP_Post ? $this->mapper->post($post, $page) : null;
    }

    /**
     * The listing intent: the site's page size, the reader's peek, the
     * theme's teaser mapping.
     *
     * @param array<string, mixed> $filters
     */
    private function listing(array $filters, int $page): PostList
    {
        $perPage = $this->perPage();
        $rows = $this->reader->fetch(new QuerySpec(
            filters: $filters,
            perPage: $perPage,
            offset: ($page - 1) * $perPage,
        ));

        $items = [];

        foreach ($rows->posts as $post) {
            $items[] = $this->mapper->teaser($post);
        }

        return new PostList($items, $rows->hasMore);
    }

    /**
     * The site's own page size, as the editor set it. The per-request cap is
     * the reader's bound; the option is the site's declaration.
     */
    private function perPage(): int
    {
        $option = \get_option('posts_per_page', 10);
        $perPage = \is_numeric($option) ? (int) $option : 10;

        return \max(1, $perPage);
    }
}
