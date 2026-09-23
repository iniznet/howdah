<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Admin\ContentModelColumns;
use Iniznet\Howdah\Admin\MigrationFailedNotice;
use Iniznet\Howdah\Admin\MigrationRequiredNotice;
use Iniznet\Howdah\Admin\MigrationSnapshot;
use Iniznet\Howdah\Admin\RunMigrations;
use Iniznet\Howdah\Admin\StatusScreen;
use Iniznet\Howdah\Admin\ThemeSettingsScreen;
use Iniznet\Howdah\Exception\InvalidDisplayOption;
use Iniznet\Howdah\Features\Settings\DisplayOption;
use Iniznet\Howdah\Features\Settings\DisplayOptions;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Db\Contracts\SqlConnection;
use Iniznet\Mahout\Db\MigrationRunner;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\Contracts\FieldRegistry;
use Iniznet\Mahout\Fields\Contracts\Panels;
use Iniznet\Mahout\Fields\FieldQuery;
use Iniznet\Mahout\Fields\FieldValuesTable;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Kernel\Diagnostics;

/**
 * The admin seam. Every menu page, notice and list column the theme registers
 * is named here and nowhere else. The field layer's screens are not: they are
 * derived from the declared panels by `mahout-fields`' Admin\FieldsUiProvider
 * and registered by the composition root, so the one question this provider
 * answers is "what admin surface does the theme itself own". The status screen
 * and the migration notices register unconditionally, because the db package's
 * migrations exist whether or not a feature has declared a panel.
 */
final class AdminProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $declarations = require dirname(__DIR__, 2).'/config/display-options.php';

        if (!\is_array($declarations)) {
            throw InvalidDisplayOption::forType(\get_debug_type($declarations));
        }

        $options = [];

        foreach ($declarations as $declaration) {
            if (!$declaration instanceof DisplayOption) {
                throw InvalidDisplayOption::forType(\get_debug_type($declaration));
            }

            $options[] = $declaration;
        }

        $container->set(new DisplayOptions($options), DisplayOptions::class);
    }

    public function boot(Container $container): void
    {
        $this->bootStatusScreen($container);
        $this->bootThemeSettings($container);
        $this->bootListColumns($container);
    }

    /**
     * Appearance > Theme settings, behind the theme's own edit_theme_options
     * capability. The screen registers only when the theme declares display
     * options; the optionless starter ships no page.
     */
    private function bootThemeSettings(Container $container): void
    {
        /** @var DisplayOptions $options */
        $options = $container->get(DisplayOptions::class);

        if ($options->isEmpty()) {
            return;
        }

        $screen = new ThemeSettingsScreen($options, $container->get(Diagnostics::class));

        \add_action(
            Hooks::ADMIN_MENU,
            static function () use ($screen): void {
                $hook = \add_theme_page(
                    \__('Theme settings', 'howdah'),
                    \__('Theme settings', 'howdah'),
                    \Iniznet\Howdah\Support\Capabilities::EditThemeOptions->value,
                    ThemeSettingsScreen::PAGE_SLUG,
                    static function () use ($screen): void {
                        echo $screen->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    },
                );

                \add_action('load-'.$hook, $screen->handleSave(...));
            },
            priority: 10,
            accepted_args: 0,
        );
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
