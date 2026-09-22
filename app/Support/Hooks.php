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
     * @param QueryContext $ctx the request facts
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
     * @param SurfacePlan  $plan the resolved plan
     * @param QueryContext $ctx  the request facts
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
     * @param string       $html    the rendered page
     * @param Component    $surface the Surface that rendered it
     * @param QueryContext $ctx     the request facts
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
    public const string SURFACE_FAILED = 'howdah/surface/failed';

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
}
