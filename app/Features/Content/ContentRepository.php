<?php

/**
 * The content repository: the only file in the theme with WP_Query, and the
 * only reader of core content. Every query is hardened — no found rows, no
 * core meta or term caching, sticky posts ignored, ids only, one page of
 * per-page + 1 rows so no pagination count query is ever issued — and every
 * result set is primed before it is mapped.
 *
 * Search is the indexed path when the FULLTEXT index is present (SRCH-01):
 * the clause and the ordering travel on the query vars and core's own
 * filters swap them in. With the index absent the query is core's own LIKE
 * path, unchanged, and the absence is recorded loudly — once per request.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content;

use Iniznet\Howdah\Render\Exception\SurfaceDataMissing;
use Iniznet\Mahout\Db\Contracts\SearchIndexPresence;

final readonly class ContentRepository
{
    /** The declared per-request cap. Never -1, never unbounded. */
    public const int PER_PAGE_CAP = 50;

    public function __construct(
        private PostMapper $mapper,
        private MatchClause $clause,
        private SearchIndexPresence $presence,
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
                'terms' => [$termId],
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
        return $this->presence->present();
    }

    /**
     * One page of search results. With the index present this is the
     * FULLTEXT path; without it, core's own LIKE path, unchanged.
     */
    public function search(SearchTerms $terms, int $page): PostList
    {
        if (!$terms->hasTokens()) {
            throw SurfaceDataMissing::forQuery($terms->raw);
        }

        $filters = ['s' => $terms->raw];

        if ($this->presence->present()) {
            $joined = $terms->forMatch();
            $match = $this->clause->against($joined);
            // Core glues the filter's payload straight after "WHERE 1=1",
            // so the swapped-in fragment carries core's own AND and the
            // parenthesisation core's LIKE fragment has.
            $filters['howdah_indexed_search'] = true;
            $filters['howdah_match_clause'] = ' AND ('.$match.')';
            $filters['howdah_match_orderby'] = $match.' DESC';
        }

        return $this->listing($filters, $page);
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
     * @param array<string, mixed> $args
     */
    private function singular(array $args, int $page): ?PostData
    {
        $query = $this->run($args, 1, 0);
        $ids = $this->ids($query);

        if ([] === $ids) {
            return null;
        }

        \_prime_post_caches($ids, false, true);
        $this->primeTerms($ids);
        $this->primeAuthors($ids);

        $post = \get_post($ids[0]);

        return $post instanceof \WP_Post ? $this->mapper->post($post, $page) : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function listing(array $filters, int $page): PostList
    {
        $perPage = $this->perPage();
        $query = $this->run($filters, $perPage + 1, ($page - 1) * $perPage);
        $ids = $this->ids($query);

        $hasMore = \count($ids) > $perPage;
        $ids = \array_slice($ids, 0, $perPage);

        if ([] === $ids) {
            return new PostList([], false);
        }

        \_prime_post_caches($ids, false, true);
        $this->primeTerms($ids);
        $this->primeAuthors($ids);

        $items = [];

        foreach ($ids as $id) {
            $post = \get_post($id);

            if ($post instanceof \WP_Post) {
                $items[] = $this->mapper->teaser($post);
            }
        }

        return new PostList($items, $hasMore);
    }

    /**
     * The hardened defaults, merged under the kind's own filters. The peek
     * asks for one row more than the page shows; that row decides hasMore
     * without a count query.
     *
     * @param array<string, mixed> $filters
     */
    private function run(array $filters, int $perPage, int $offset): \WP_Query
    {
        return new \WP_Query([
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'offset' => $offset,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
            'fields' => 'ids',
        ] + $filters);
    }

    /**
     * @return list<int>
     */
    private function ids(\WP_Query $query): array
    {
        $ids = [];

        foreach ($query->posts ?? [] as $id) {
            if (\is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * The terms every mapped teaser or post renders, primed in one query.
     *
     * @param list<int> $ids
     */
    private function primeTerms(array $ids): void
    {
        \update_object_term_cache($ids, 'post');
    }

    /**
     * The authors every mapped teaser or post renders, primed in one query.
     *
     * @param list<int> $ids
     */
    private function primeAuthors(array $ids): void
    {
        $authors = [];

        foreach ($ids as $id) {
            $post = \get_post($id);

            if ($post instanceof \WP_Post && (int) $post->post_author > 0) {
                $authors[(int) $post->post_author] = true;
            }
        }

        if ([] !== $authors) {
            \cache_users(\array_keys($authors));
        }
    }

    private function perPage(): int
    {
        $option = \get_option('posts_per_page', 10);
        $perPage = \is_numeric($option) ? (int) $option : 10;

        return \max(1, \min(self::PER_PAGE_CAP, $perPage));
    }
}
