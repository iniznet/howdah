<?php

/**
 * The content feature's composition: the repository the render layer queries
 * core content through, the search index's migration, and the fragment
 * group's invalidation.
 *
 * The indexed-search plumbing — the clause, the fallback report, the two
 * `posts_search` filters and their query vars — is `mahout-db`'s
 * {@see \Iniznet\Mahout\Db\Search\SearchProvider}, registered by the
 * composition root after {@see \Iniznet\Mahout\Db\DbProvider}. This provider
 * only consumes the swap that provider declares.
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
use Iniznet\Howdah\Features\Content\PostMapper;
use Iniznet\Howdah\Support\Cache\FragmentInvalidation;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\PostReader;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;
use Iniznet\Mahout\Db\AddSearchIndex;
use Iniznet\Mahout\Db\Contracts\Migration;
use Iniznet\Mahout\Db\Contracts\SqlConnection;
use Iniznet\Mahout\Db\DdlEmitter;
use Iniznet\Mahout\Db\Search\IndexedSearchSwap;
use Iniznet\Mahout\Db\SearchIndex;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Render\FragmentCache;

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

        // The reader is the mechanics every content feature composes a
        // QuerySpec against; one instance is declared here and shared.
        $container->set(new PostReader());

        $container->set(new ContentRepository(
            mapper: new PostMapper(),
            reader: $container->get(PostReader::class),
            search: $container->get(IndexedSearchSwap::class),
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

        $this->attachInvalidation($container);
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
}
