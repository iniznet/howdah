<?php

/**
 * The indexed search path must return the same result set as core's LIKE
 * path over the same fixtures (18-throughput §10, SRCH-01's exit check).
 *
 * The fixture words are chosen so no token is a substring of another: a
 * natural-language FULLTEXT match is a word match, and a substring collision
 * would make the two paths legitimately differ.
 *
 * Core's test case runs every test inside a transaction (autocommit = 0),
 * and a FULLTEXT index only reflects committed rows, so the fixtures are
 * committed explicitly and the test owns their removal — tearDown deletes
 * them and commits again, because a rollback can no longer undo them.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Features\Content\SearchTerms;

final class SearchParityTest extends \WP_UnitTestCase
{
    /** @var list<int> */
    private array $fixtures = [];

    public function tearDown(): void
    {
        global $wpdb;

        foreach ($this->fixtures as $postId) {
            \wp_delete_post($postId, true);
        }
        $this->fixtures = [];

        $wpdb->query('COMMIT');

        parent::tearDown();
    }

    public function testTheIndexedPathReturnsCoreSSet(): void
    {
        $repository = Bootstrap::services()->get(ContentRepository::class);

        self::assertTrue($repository->searchIsIndexed(), 'the fixture database must carry the FULLTEXT index.');

        $fixtures = [
            [
                'post_title' => 'Harbor Lights',
                'post_content' => 'The harbor lights guide ships home.',
            ],
            [
                'post_title' => 'Canyon Dawn',
                'post_content' => 'A canyon at dawn is quiet.',
            ],
            [
                'post_title' => 'Harbor Canyon',
                'post_content' => 'The harbor faces the canyon.',
            ],
            [
                'post_title' => 'Glacier Walk',
                'post_content' => 'The glacier groans at noon.',
            ],
        ];

        foreach ($fixtures as $fixture) {
            $this->fixtures[] = (int) self::factory()->post->create([
                'post_title' => $fixture['post_title'],
                'post_content' => $fixture['post_content'],
                'post_date' => '2024-01-01 00:00:00',
            ]);
        }

        global $wpdb;
        $wpdb->query('COMMIT');

        // Natural-language mode is the declared default (18-throughput §4):
        // a single term is a word match, so the indexed path returns the
        // same result set core's LIKE fragment does. A multi-term query in
        // natural-language mode matches any of the words, where core's LIKE
        // fragment ANDs them — the indexed result is the superset, ranked by
        // the index's own score.
        foreach (['harbor', 'canyon', 'glacier'] as $term) {
            self::assertSame(
                self::coreIds($term),
                self::indexedIds($repository, $term),
                "the indexed path must return core's result set for '{$term}'.",
            );
            self::assertNotSame([], self::indexedIds($repository, $term), "the fixture term '{$term}' must match the fixtures.");
        }

        self::assertSame(
            [],
            array_values(array_diff(self::coreIds('harbor canyon'), self::indexedIds($repository, 'harbor canyon'))),
            "a multi-term query must return at least core's result set for 'harbor canyon'.",
        );
        self::assertNotSame([], self::indexedIds($repository, 'harbor canyon'));
    }

    /** @return list<int> */
    private static function coreIds(string $term): array
    {
        $query = new \WP_Query([
            's' => $term,
            'posts_per_page' => 50,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return self::sorted(array_map('intval', (array) $query->posts));
    }

    /** @return list<int> */
    private static function indexedIds(ContentRepository $repository, string $term): array
    {
        return self::sorted(array_map(
            static fn (object $post): int => (int) $post->id,
            $repository->search(SearchTerms::fromString($term), 1)->items,
        ));
    }

    /** @param list<int> $ids @return list<int> */
    private static function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }
}
