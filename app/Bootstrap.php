<?php

/**
 * The composition root.
 *
 * Every provider and module is named here, in order, and nowhere else. There
 * is no config file between this list and the registrations it produces.
 */

declare(strict_types=1);

namespace Iniznet\Howdah;

use Iniznet\Howdah\Exception\NotBooted;
use Iniznet\Howdah\Features\Series\SeriesModule;
use Iniznet\Howdah\Providers\AdminProvider;
use Iniznet\Howdah\Providers\AssetsProvider;
use Iniznet\Howdah\Providers\CliProvider;
use Iniznet\Howdah\Providers\ContentProvider;
use Iniznet\Howdah\Providers\EditorProvider;
use Iniznet\Howdah\Providers\RenderProvider;
use Iniznet\Howdah\Providers\ThemeProvider;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Howdah\Surfaces\Surfaces;
use Iniznet\Mahout\Assets\AssetsProvider as AssetsPackageProvider;
use Iniznet\Mahout\Content\ContentProvider as ContentPackageProvider;
use Iniznet\Mahout\Db\DbProvider;
use Iniznet\Mahout\Db\Search\SearchProvider;
use Iniznet\Mahout\Fields\Admin\FieldsUiProvider;
use Iniznet\Mahout\Fields\FieldsProvider;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Kernel;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\SurfaceErrorBoundary;

final class Bootstrap
{
    private static ?Kernel $kernel = null;

    /**
     * Boot the theme exactly once. functions.php is the only caller in
     * production; the test bootstrap is the only caller in the suite.
     */
    public static function run(): void
    {
        $kernel = Kernel::inWordPress();

        $kernel->provider(ThemeProvider::class);
        $kernel->provider(AssetsProvider::class);
        $kernel->provider(AssetsPackageProvider::class);
        $kernel->provider(DbProvider::class);
        // The indexed search swap is built on the connection and the cached
        // index presence DbProvider declares, so it registers after it — and
        // ContentProvider resolves the swap, so it registers after this one.
        $kernel->provider(SearchProvider::class);
        $kernel->provider(FieldsProvider::class);
        // The field package's admin screens: built on the registry, the
        // reader and the writer FieldsProvider binds, and driven by the
        // panels this theme's EditorProvider binds. Nothing is attached when
        // the theme declares no panel.
        $kernel->provider(FieldsUiProvider::class);
        $kernel->provider(ContentPackageProvider::class);
        $kernel->provider(ContentProvider::class);
        // The worked example feature: its content model is declared in
        // config/content-types.php and registered by ContentProvider's init
        // hook; the module declares the repository after the providers it
        // resolves are registered.
        $kernel->module(SeriesModule::class);
        $kernel->provider(EditorProvider::class);
        $kernel->provider(AdminProvider::class);
        $kernel->provider(CliProvider::class);
        $kernel->provider(RenderProvider::class);

        $kernel->boot();

        self::$kernel = $kernel;
    }

    /**
     * The declared service graph, resolved by the keys the composition root
     * registered them under.
     */
    public static function services(): Container
    {
        return self::kernel()->services();
    }

    /**
     * The render boundary every request resolves through. index.php and
     * embed.php are the only templates that call it; the dispatch table has
     * already resolved the plan at send_headers time, so this reads the
     * memoised plan, wraps it in the error boundary, and applies
     * howdah/surface/rendered.
     */
    public static function render(): string
    {
        $ctx = QueryContext::current();
        $services = self::services();
        $plan = Surfaces::plan($ctx, $services);

        $component = new SurfaceErrorBoundary(
            inner: $plan->surface,
            ctx: $ctx,
            diagnostics: $services->get(Diagnostics::class),
            environment: $services->get(Environment::class),
            classes: $services->get(\Iniznet\Mahout\Ui\ClassResolver::class),
        );

        $html = $component->render();

        $filtered = apply_filters(Hooks::SURFACE_RENDERED, $html, $plan->surface, $ctx);

        return \is_string($filtered) ? $filtered : $html;
    }

    private static function kernel(): Kernel
    {
        if (!self::$kernel instanceof Kernel) {
            throw NotBooted::beforeRender();
        }

        return self::$kernel;
    }
}
