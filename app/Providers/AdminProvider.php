<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Admin\ContentModelColumns;
use Iniznet\Howdah\Admin\MigrationFailedNotice;
use Iniznet\Howdah\Admin\MigrationRequiredNotice;
use Iniznet\Howdah\Admin\MigrationSnapshot;
use Iniznet\Howdah\Admin\RunMigrations;
use Iniznet\Howdah\Admin\StatusScreen;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Db\Contracts\SqlConnection;
use Iniznet\Mahout\Db\MigrationRunner;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\Contracts\FieldRegistry;
use Iniznet\Mahout\Fields\Contracts\Panels;
use Iniznet\Mahout\Fields\FieldLeavesTable;
use Iniznet\Mahout\Fields\FieldQuery;
use Iniznet\Mahout\Fields\FieldValuesTable;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Kernel\Diagnostics;

/**
 * The admin seam. Every menu page, notice and list column the theme registers
 * is named here and nowhere else. The field layer's screens are not: they are
 * derived from the declared panels and option screens by `mahout-fields`'
 * Admin\FieldsUiProvider and registered by the composition root, so the one
 * question this provider answers is "what admin surface does the theme itself
 * own". The status screen and the migration notices register unconditionally,
 * because the db package's migrations exist whether or not a feature has
 * declared a panel.
 */
final class AdminProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // The theme declares no service here: the status screen and the
        // notices attach in boot(), the list columns read the panels the
        // editor seam binds, and the settings pages the theme may declare
        // are the editor seam's OptionScreens, derived by the field
        // package's provider.
    }

    public function boot(Container $container): void
    {
        $this->bootStatusScreen($container);
        $this->bootListColumns($container);
    }

    /**
     * Tools > Site status, its run action, and the two migration notices.
     * The screen is the operator's window on the schema; the required notice
     * follows the stored version trailing the code's, and the failed notice
     * only renders on the request a run failed.
     */
    private function bootStatusScreen(Container $container): void
    {
        $migrations = new RunMigrations(
            $container->get(MigrationRunner::class),
            $container->get(Diagnostics::class),
        );

        \add_action(
            Hooks::ADMIN_MENU,
            static function () use ($container, $migrations): void {
                $hook = \add_management_page(
                    \__('Site status', 'howdah'),
                    \__('Site status', 'howdah'),
                    'manage_options',
                    StatusScreen::PAGE_SLUG,
                    static function () use ($container): void {
                        $screen = new StatusScreen(
                            MigrationSnapshot::fromStatus($container->get(MigrationRunner::class)->status()),
                            self::environmentRows(),
                        );

                        // Core's page callback is echo-based; the boundary
                        // echoes renderer output, exactly as index.php does.
                        echo $screen->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    },
                );

                \add_action('load-'.$hook, $migrations->handle(...));
            },
            priority: 10,
            accepted_args: 0,
        );

        \add_action(
            Hooks::ADMIN_NOTICES,
            static function () use ($container, $migrations): void {
                new MigrationRequiredNotice($container->get(MigrationRunner::class)->schemaVersion())->render();
                new MigrationFailedNotice($migrations->failureReference())->render();
            },
            priority: 10,
            accepted_args: 0,
        );
    }

    /**
     * The declared panels' read-only list-screen presence: one column per
     * Table-stored field and one filter dropdown per Choice field, built on
     * the field query builder's bounded statement. The columns write
     * nothing; the listing the user already reaches gates the read. This is
     * the whole of the admin surface the theme owns over a panel — the
     * metabox, the save entry, the value route and the write-failure notice
     * are the field package's, derived from the same declaration.
     */
    private function bootListColumns(Container $container): void
    {
        /** @var Panels $panels */
        $panels = $container->get(Panels::class);

        if ($panels->isEmpty()) {
            return;
        }

        $connection = $container->get(SqlConnection::class);

        $columns = new ContentModelColumns(
            $panels,
            $container->get(FieldReader::class),
            new FieldQuery(
                $container->get(FieldRegistry::class),
                $connection,
                FieldValuesTable::table($connection->prefix(), $connection->charsetCollate()),
                FieldLeavesTable::table($connection->prefix(), $connection->charsetCollate()),
            ),
        );

        \add_action(Hooks::INIT, $columns->register(...), priority: 10, accepted_args: 0);
    }

    /**
     * The environment rows under the migration table: the PHP and WordPress
     * versions, the fragment cache's group and TTL. Name and value travel as
     * an untranslated pair, so the markup file stays a dumb loop.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function environmentRows(): array
    {
        return [
            [\__('PHP version', 'howdah'), PHP_VERSION],
            ['WordPress', \get_bloginfo('version')],
            [\__('Fragment cache group', 'howdah'), \Iniznet\Mahout\Render\FragmentCache::GROUP],
            [\__('Fragment cache TTL', 'howdah'), (string) \Iniznet\Mahout\Render\FragmentCache::TTL_SECONDS],
        ];
    }
}
