<?php

/**
 * The content feature's composition: the repository the render layer queries
 * core content through, the indexed-search filters, the search index's
 * migration, and the fragment group's invalidation.
 *
 * The declarations in config/content-types.php are registered on init
 * through the content package's Contracts surface; the starter declares none,
 * so a plain blog ships with the list empty and nothing registers.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Exception\InvalidContentDeclaration;
use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Features\Content\MatchClause;
use Iniznet\Howdah\Features\Content\PostMapper;
use Iniznet\Howdah\Render\FragmentCache;
use Iniznet\Howdah\Support\Cache\FragmentInvalidation;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;
use Iniznet\Mahout\Db\AddSearchIndex;
use Iniznet\Mahout\Db\Contracts\Migration;
use Iniznet\Mahout\Db\Contracts\SearchIndexPresence;
use Iniznet\Mahout\Db\Contracts\SqlConnection;
use Iniznet\Mahout\Db\DdlEmitter;
use Iniznet\Mahout\Db\SearchIndex;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

final class ContentProvider implements ServiceProvider
{
    /** @var list<PostType|Taxonomy|RestRoute> */
    private array $declarations = [];

    public function register(Container $container): void
    {
        $declarations = require dirname(__DIR__, 2).'/config/content-types.php';

        if (!is_array($declarations)) {
            throw InvalidContentDeclaration::forType(get_debug_type($declarations));
        }

        foreach ($declarations as $declaration) {
            if (!$declaration instanceof PostType
                && !$declaration instanceof Taxonomy
                && !$declaration instanceof RestRoute
            ) {
                throw InvalidContentDeclaration::forType(get_debug_type($declaration));
            }

            $this->declarations[] = $declaration;
        }

        $container->set(new ContentRepository(
            mapper: new PostMapper(),
            clause: MatchClause::fromWordPress(),
            presence: $container->get(SearchIndexPresence::class),
        ));

        // The filter is attached in register(), not boot(): the migration
        // ledger is built when the db provider's boot reads it, and the db
        // provider boots before this one.
        $connection = $container->get(SqlConnection::class);
        $index = SearchIndex::onPosts($connection->prefix());

        \add_filter(
            Hooks::MIGRATIONS,
            static fn (mixed $migrations): array => self::migrations(
                $migrations,
                new AddSearchIndex($connection, $container->get(DdlEmitter::class), $index),
            ),
            priority: 10,
            accepted_args: 1,
        );
    }

    public function boot(Container $container): void
    {
        $registrar = $container->get(Registrar::class);
        $declarations = $this->declarations;

        \add_action(
            Hooks::INIT,
            static function () use ($registrar, $declarations): void {
                foreach ($declarations as $declaration) {
                    if ($declaration instanceof PostType) {
                        $registrar->registerPostType($declaration);

                        continue;
                    }

                    if ($declaration instanceof Taxonomy) {
                        $registrar->registerTaxonomy($declaration);

                        continue;
                    }

                    $registrar->registerRestRoute($declaration);
                }
            },
            priority: 10,
            accepted_args: 0,
        );

        $this->attachSearchFilters();
        $this->attachInvalidation($container);
    }

    /**
     * The indexed search path: the clause and the relevance ordering travel
     * on the query vars, and these two filters swap them in for a query that
     * declared the indexed path. Every other query receives its argument
     * back, unchanged.
     */
    private function attachSearchFilters(): void
    {
        \add_filter(
            Hooks::POSTS_SEARCH,
            static fn (mixed $search, \WP_Query $query): string => self::indexedClause($search, $query),
            priority: 10,
            accepted_args: 2,
        );

        \add_filter(
            Hooks::POSTS_SEARCH_ORDERBY,
            static fn (mixed $orderby, \WP_Query $query): string => self::indexedOrdering($orderby, $query),
            priority: 10,
            accepted_args: 2,
        );
    }

    /** The fragment group's invalidation, coalesced per request. */
    private function attachInvalidation(Container $container): void
    {
        $invalidation = new FragmentInvalidation($container->get(FragmentCache::class));

        \add_action(Hooks::SAVE_POST, $invalidation->saved(...), priority: 10, accepted_args: 2);
        \add_action(Hooks::DELETED_POST, $invalidation->deleted(...), priority: 10, accepted_args: 2);
    }

    /**
     * The validated migration payload: the declared list plus the search
     * index. A wrong shape is refused, never coerced.
     *
     * @return list<Migration>
     */
    private static function migrations(mixed $migrations, Migration $index): array
    {
        if (!\is_array($migrations)) {
            throw InvalidHookResult::notAMigrationList();
        }

        $declared = [];

        foreach ($migrations as $migration) {
            if (!$migration instanceof Migration) {
                throw InvalidHookResult::notAMigration();
            }

            $declared[] = $migration;
        }

        $declared[] = $index;

        return $declared;
    }

    /** The clause core built, or the indexed clause for a declared query. */
    private static function indexedClause(mixed $search, \WP_Query $query): string
    {
        $fragment = self::string($search);

        if (true !== $query->get('howdah_indexed_search')) {
            return $fragment;
        }

        return self::string($query->get('howdah_match_clause'));
    }

    /** The ordering core built, or the indexed relevance for a declared query. */
    private static function indexedOrdering(mixed $orderby, \WP_Query $query): string
    {
        $ordering = self::string($orderby);

        if (true !== $query->get('howdah_indexed_search')) {
            return $ordering;
        }

        return self::string($query->get('howdah_match_orderby'));
    }

    /**
     * Core's documented payload is a string. Anything else a subscriber
     * added is refused, never coerced.
     */
    private static function string(mixed $value): string
    {
        if (!\is_string($value)) {
            throw InvalidHookResult::notASearchFragment();
        }

        return $value;
    }
}
