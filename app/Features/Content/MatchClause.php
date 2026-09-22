<?php

/**
 * The FULLTEXT clause and its relevance ordering, built for the index
 * mahout-db owns. The column list is read from the index declaration and
 * never retyped — MATCH must name the index's columns exactly, or the server
 * raises error 1191. Every value travels through a placeholder; a
 * visitor-supplied term reaches this class only through the tokeniser's
 * word runs, and never in boolean mode (SRCH-01).
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content;

use Iniznet\Howdah\Exception\NotBooted;
use Iniznet\Howdah\Render\Exception\SurfaceDataMissing;
use Iniznet\Mahout\Db\SearchIndex;

final readonly class MatchClause
{
    public const string INDEX_NAME = 'howdah_search';

    private SearchIndex $index;

    private function __construct(private \wpdb $wpdb)
    {
        $this->index = SearchIndex::onPosts($this->wpdb->prefix);
    }

    /**
     * The composition-root named constructor: the connection object, read
     * once, inside one boundary. It resolves no collaborator.
     */
    public static function fromWordPress(): self
    {
        $wpdb = $GLOBALS['wpdb'] ?? null;

        if (!$wpdb instanceof \wpdb) {
            throw NotBooted::beforeQuery();
        }

        return new self($wpdb);
    }

    /**
     * The index's column list, from the index declaration.
     *
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->index->columns();
    }

    /** MATCH (…) AGAINST (%s IN NATURAL LANGUAGE MODE), prepared. */
    public function against(string $joined): string
    {
        $columns = \implode(', ', $this->index->columns());
        $prepared = $this->wpdb->prepare(
            'MATCH ('.$columns.') AGAINST (%s IN NATURAL LANGUAGE MODE)',
            $joined,
        );

        if (!\is_string($prepared) || '' === $prepared) {
            throw SurfaceDataMissing::forQuery($joined);
        }

        return $prepared;
    }

    /** The same MATCH expression, descending: the index's own relevance score. */
    public function relevance(string $joined): string
    {
        return $this->against($joined).' DESC';
    }
}
