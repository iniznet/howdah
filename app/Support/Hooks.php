<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

/**
 * Every hook this theme emits or observes. Names are declared once, here.
 *
 * The core hooks the render pipeline participates in are constants too: the
 * rule that bans a raw hook name at an emit site applies to a core hook as
 * much as to a theme one.
 */
final class Hooks
{
    /**
     * Core's theme-setup hook. ThemeProvider loads the text domain and
     * declares the theme supports here, at priority 10.
     *
     * @since 1.0
     *
     * @action
     */
    public const string AFTER_SETUP_THEME = 'after_setup_theme';

    /**
     * Core's init hook. ContentProvider registers the declared content model
     * here, after every provider and module booted.
     *
     * @since 1.0
     *
     * @action
     */
    public const string INIT = 'init';

    /**
     * Core's response-header filter, inside WP::send_headers() and before
     * any header is sent. RenderProvider resolves the request's SurfacePlan
     * here, once, and merges the HeaderPolicy's headers into core's.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param array<string, string|false> $headers the headers core will send
     * @param \WP                         $wp      the resolved request
     */
    public const string WP_HEADERS = 'wp_headers';

    /**
     * Runs before the dispatch table matches. Subscribers receive the
     * QueryContext and return one; a subscriber that returns anything else
     * is refused, never coerced.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param \Iniznet\Mahout\Render\QueryContext $ctx the request facts
     */
    public const string SURFACE_CONTEXT = 'howdah/surface/context';

    /**
     * Runs after the dispatch table matched. Subscribers receive the
     * SurfacePlan and return one; a Surface is replaced through this filter,
     * typed and greppable, never through filename shadowing. A plan's
     * cacheability declaration survives the filter because the value object
     * is readonly.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param \Iniznet\Mahout\Render\SurfacePlan  $plan the resolved plan
     * @param \Iniznet\Mahout\Render\QueryContext $ctx  the request facts
     */
    public const string SURFACE_RESOLVE = 'howdah/surface/resolve';

    /**
     * Runs after the page's bytes exist. Subscribers receive the HTML, the
     * Surface that produced it and the request facts.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param string                              $html    the rendered page
     * @param \Iniznet\Mahout\Render\Component    $surface the Surface that rendered it
     * @param \Iniznet\Mahout\Render\QueryContext $ctx     the request facts
     */
    public const string SURFACE_RENDERED = 'howdah/surface/rendered';

    /**
     * Fires when a Surface threw and the error boundary recorded it. The
     * payload names the condition and carries the support reference; it
     * never carries a substitute page.
     *
     * @since 1.0
     *
     * @action
     *
     * @param \Throwable $e         the failure
     * @param string     $reference the support reference Diagnostics returned
     */

    /**
     * The cache purge seam. The theme owns the seam and emits this action
     * when a content change invalidates a fragment; the client owns the
     * endpoint that listens for it. No vendor purge API is ever called.
     *
     * Declared in this scaffold; the invalidation service that fires it is a
     * feature slice's, and nothing fires it yet.
     *
     * @since 1.0
     *
     * @action
     *
     * @param list<string> $keys the fragment keys the purge covers
     */
    public const string PURGE = 'howdah/cache/purge';

    /**
     * Core's admin-menu action, where the status screen's management page
     * registers. It fires once per admin request, before the page's load
     * hook, so the screen and its run action are named in one listener.
     *
     * @since 1.0
     *
     * @action
     */
    public const string ADMIN_MENU = 'admin_menu';

    /**
     * Core's admin notice action, which the migration notices observe. It
     * fires once per admin screen render, after the screen's load hook, so a
     * same-request failure state is already known. The field layer's own
     * write-failure notice observes the same action, attached by the field
     * package's admin provider at priority 20 — behind the screens' messages.
     *
     * @since 1.0
     *
     * @action
     */
    public const string ADMIN_NOTICES = 'admin_notices';

    /**
     * WP-CLI's registration action, fired before WP-CLI dispatches. The
     * migration command registers here, behind the provider's WP_CLI gate;
     * a site without WP-CLI never reaches this hook.
     *
     * @since 1.0
     *
     * @action
     */
    public const string CLI_INIT = 'cli_init';

    /**
     * Core's list-screen filter-dropdown action. The declared panels' Choice
     * filters render here, one dropdown per filterable field.
     *
     * @since 1.0
     *
     * @action
     *
     * @param string $postType the list screen's post type
     * @param string $taxonomy the screen's taxonomy, when one is being filtered
     */
    public const string RESTRICT_MANAGE_POSTS = 'restrict_manage_posts';

    /**
     * Core's query-integration action, where the declared panels' field
     * filters narrow the main list query through the bounded statement.
     *
     * @since 1.0
     *
     * @action
     *
     * @param \WP_Query $query the query being assembled
     */
    public const string PRE_GET_POSTS = 'pre_get_posts';

    /**
     * Core's post-save action. FragmentInvalidation observes it, skips
     * autosaves and revisions, bumps the fragment group once per request and
     * fires the purge seam once. The field layer's save handler observes it
     * at priority 10, per declared panel, through the field package's own
     * hook constant.
     *
     * @since 1.0
     *
     * @action
     *
     * @param int      $postId the saved post's id
     * @param \WP_Post $post   the saved post
     * @param bool     $update whether this is an update of an existing post
     */
    public const string SAVE_POST = 'save_post';

    /**
     * Core's post-deletion action, which mahout-db's orphan path also
     * observes.
     *
     * @since 1.0
     *
     * @action
     *
     * @param int      $postId the deleted post's id
     * @param \WP_Post $post   the deleted post
     */
    public const string DELETED_POST = 'deleted_post';

    /**
     * Core's content filter. PostMapper runs raw post content through it —
     * kses, blocks and shortcodes — in the one boundary where content leaves
     * the request's hands; the mapper is the taint escape's owner.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param string $content the post content core is filtering
     */
    public const string THE_CONTENT = 'the_content';

    /**
     * The migration filter mahout-db reads the site's declared migrations
     * from. ContentProvider registers the search index's migration here, in
     * register() — the ledger is built when the db provider boots, which is
     * before this provider's boot.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param list<\Iniznet\Mahout\Db\Contracts\Migration> $migrations the declared migrations, in order
     */
    public const string MIGRATIONS = 'mahout/db/migrations';
}
