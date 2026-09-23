<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Admin\ContentModelColumns;
use Iniznet\Howdah\Admin\FieldWriteFailedNotice;
use Iniznet\Howdah\Admin\MigrationFailedNotice;
use Iniznet\Howdah\Admin\MigrationRequiredNotice;
use Iniznet\Howdah\Admin\MigrationSnapshot;
use Iniznet\Howdah\Admin\RunMigrations;
use Iniznet\Howdah\Admin\StatusScreen;
use Iniznet\Howdah\Admin\ThemeSettingsScreen;
use Iniznet\Howdah\Exception\InvalidDisplayOption;
use Iniznet\Howdah\Features\Fields\FieldPanels;
use Iniznet\Howdah\Features\Settings\DisplayOption;
use Iniznet\Howdah\Features\Settings\DisplayOptions;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Howdah\Support\Request;
use Iniznet\Mahout\Db\Contracts\SqlConnection;
use Iniznet\Mahout\Db\MigrationRunner;
use Iniznet\Mahout\Fields\Admin\FieldEditor;
use Iniznet\Mahout\Fields\Admin\FieldMetabox;
use Iniznet\Mahout\Fields\Admin\FieldRestRoute;
use Iniznet\Mahout\Fields\Admin\FieldSaveHandler;
use Iniznet\Mahout\Fields\Admin\FieldTypeRegistry;
use Iniznet\Mahout\Fields\Admin\WriteFailureNotice;
use Iniznet\Mahout\Fields\Capabilities;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\Contracts\FieldRegistry;
use Iniznet\Mahout\Fields\Contracts\FieldWriter;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Kernel\Diagnostics;

/**
 * The admin seam. Every metabox, menu page, notice and list column the theme
 * registers is named here and nowhere else. The field layer's panels derive
 * from the declared FieldPanels collection; the status screen and the
 * migration notices register unconditionally, because the db package's
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
        $this->bootFieldPanels($container);
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
     * The field panels: one metabox per (post type, group) pair inside
     * add_meta_boxes, gated on edit_post for the object being edited; the
     * save_post entry through the field package's handler; the value route
     * and its read bindings for the block editor; and the write-failure
     * notice. A theme that declares no panels attaches none of this.
     */
    private function bootFieldPanels(Container $container): void
    {
        /** @var FieldPanels $panels */
        $panels = $container->get(FieldPanels::class);

        if ($panels->isEmpty()) {
            return;
        }

        $registry = $container->get(FieldRegistry::class);
        $reader = $container->get(FieldReader::class);
        $writer = $container->get(FieldWriter::class);
        $diagnostics = $container->get(Diagnostics::class);

        $metabox = new FieldMetabox(
            new FieldEditor(new FieldTypeRegistry(), $registry, $reader),
            $registry,
        );

        $handler = new FieldSaveHandler(
            Request::panel(),
            $writer,
            $registry,
            $diagnostics,
        );

        \add_action(
            Hooks::ADD_META_BOXES,
            static function (string $postType, \WP_Post $post) use ($panels, $metabox): void {
                // A panel the user cannot edit the post for is an
                // information leak; the metabox is not rendered, not just
                // its values withheld.
                if (!\current_user_can(Capabilities::EditPost->value, $post->ID)) {
                    return;
                }

                foreach ($panels->forPostType($postType) as $panel) {
                    $metabox->register($panel->postType, $panel->group->id);
                }
            },
            priority: 10,
            accepted_args: 2,
        );

        \add_action(Hooks::SAVE_POST, $handler->handle(...), priority: 10, accepted_args: 3);

        \add_action(
            Hooks::REST_API_INIT,
            static function () use ($panels, $registry, $writer, $reader, $diagnostics): void {
                $route = new FieldRestRoute($registry, $writer, $reader, $diagnostics);
                $route->register();

                foreach ($panels as $panel) {
                    $route->registerReads(
                        $panel->postType,
                        ...\array_map(static fn ($field): string => $field->id, $panel->group->fields),
                    );
                }
            },
            priority: 10,
            accepted_args: 0,
        );

        \add_action(
            Hooks::ADMIN_NOTICES,
            static fn () => new FieldWriteFailedNotice(new WriteFailureNotice())->render(),
            priority: 20,
            accepted_args: 0,
        );

        $this->bootListColumns($container, $panels);
    }

    /**
     * The declared panels' read-only list-screen presence: one column per
     * Table-stored field and one filter dropdown per Choice field, built on
     * the field query builder's bounded statement. The columns write
     * nothing; the listing the user already reaches gates the read.
     */
    private function bootListColumns(Container $container, FieldPanels $panels): void
    {
        $connection = $container->get(SqlConnection::class);

        $columns = new ContentModelColumns(
            $panels,
            $container->get(FieldReader::class),
            new \Iniznet\Mahout\Fields\FieldQuery(
                $container->get(FieldRegistry::class),
                $connection,
                \Iniznet\Mahout\Fields\FieldValuesTable::table($connection->prefix(), $connection->charsetCollate()),
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
