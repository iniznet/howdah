<?php

/**
 * The dispatch table: the only place where "what renders this request" is
 * decided, and therefore the only place that decides what may be cached.
 *
 * Every arm returns a SurfacePlan and declares its Cacheability and
 * FragmentScope; every Uncacheable arm states why. The default arm is
 * written, never implied. docs/reference/surfaces.md is generated from this
 * file and drift-checked (the caching contract, §4).
 *
 * A Shared surface is a pure function of the context and the content graph:
 * it registers no nonce and reads no request state, so the same stored
 * fragment serves anonymous and authenticated visitors alike. Its key parts
 * are enumerable from the site's own content graph — a surface class name, a
 * scope word, an object id, a page within the content's own bound, a date
 * triple drawn from the queried URL, the locale — and never request input or
 * free text, which is why the search arm is Uncacheable. A page beyond the
 * content graph is a key space an anonymous visitor can invent, so every
 * listing arm refuses to store it and states the reason.
 *
 * Reading collaborators out of the services graph here is composition, not
 * service location: this is the single boundary at which Surfaces are
 * constructed. The repositories themselves are never resolved from the
 * graph inside a Surface or a Component.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Features\Content\Surfaces\BlogIndex;
use Iniznet\Howdah\Features\Content\Surfaces\ContentArchive;
use Iniznet\Howdah\Features\Content\Surfaces\EmbedContent;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePage;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePost;
use Iniznet\Howdah\Render\Surfaces\GenericList;
use Iniznet\Howdah\Render\Surfaces\NotFound;
use Iniznet\Howdah\Render\Surfaces\SearchResults;
use Iniznet\Howdah\Support\ClassResolver;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Diagnostics;

final class Surfaces
{
    private static ?SurfacePlan $plan = null;

    public static function resolve(QueryContext $ctx, Container $services): SurfacePlan
    {
        $context = apply_filters(Hooks::SURFACE_CONTEXT, $ctx);

        if (!$context instanceof QueryContext) {
            throw InvalidHookResult::notAQueryContext();
        }

        $classes = $services->get(ClassResolver::class);
        $content = $services->get(ContentRepository::class);
        $cache = $services->get(FragmentCache::class);

        $plan = match (true) {
            QueryKind::Embed === $context->kind => SurfacePlan::uncacheable(
                surface: new EmbedContent($context, $content, $classes),
                reason: 'embed document, rendered for one parent request',
            ),
            QueryKind::Front === $context->kind && null !== $context->objectId => SurfacePlan::wrapped(
                surface: new SinglePage($context, $content, $classes),
                cacheability: Cacheability::Shared,
                fragmentScope: FragmentScope::Shared,
                key: FragmentKey::fromParts(
                    SinglePage::class,
                    $context->objectId,
                    $context->contentPage(),
                    $context->site->locale,
                ),
                cache: $cache,
            ),
            QueryKind::Front === $context->kind => $context->outOfRangePage
                ? SurfacePlan::uncacheable(
                    surface: new BlogIndex($context, $content, $classes),
                    reason: 'page beyond the content graph, out of range',
                )
                : SurfacePlan::wrapped(
                    surface: new BlogIndex($context, $content, $classes),
                    cacheability: Cacheability::Shared,
                    fragmentScope: FragmentScope::Shared,
                    key: self::indexKey($context),
                    cache: $cache,
                ),
            QueryKind::Home === $context->kind => $context->outOfRangePage
                ? SurfacePlan::uncacheable(
                    surface: new BlogIndex($context, $content, $classes),
                    reason: 'page beyond the content graph, out of range',
                )
                : SurfacePlan::wrapped(
                    surface: new BlogIndex($context, $content, $classes),
                    cacheability: Cacheability::Shared,
                    fragmentScope: FragmentScope::Shared,
                    key: self::indexKey($context),
                    cache: $cache,
                ),
            QueryKind::Singular === $context->kind && 'post' === $context->postType => SurfacePlan::wrapped(
                surface: new SinglePost($context, $content, $classes),
                cacheability: Cacheability::Shared,
                fragmentScope: FragmentScope::Shared,
                key: FragmentKey::fromParts(
                    SinglePost::class,
                    $context->objectId ?? 0,
                    $context->contentPage(),
                    $context->site->locale,
                ),
                cache: $cache,
            ),
            QueryKind::Singular === $context->kind && 'page' === $context->postType => SurfacePlan::wrapped(
                surface: new SinglePage($context, $content, $classes),
                cacheability: Cacheability::Shared,
                fragmentScope: FragmentScope::Shared,
                key: FragmentKey::fromParts(
                    SinglePage::class,
                    $context->objectId ?? 0,
                    $context->contentPage(),
                    $context->site->locale,
                ),
                cache: $cache,
            ),
            QueryKind::Archive === $context->kind => $context->outOfRangePage
                ? SurfacePlan::uncacheable(
                    surface: new ContentArchive($context, $content, $classes),
                    reason: 'page beyond the content graph, out of range',
                )
                : SurfacePlan::wrapped(
                    surface: new ContentArchive($context, $content, $classes),
                    cacheability: Cacheability::Shared,
                    fragmentScope: FragmentScope::Shared,
                    key: self::archiveKey($context),
                    cache: $cache,
                ),
            QueryKind::Search === $context->kind => SurfacePlan::uncacheable(
                surface: new SearchResults($context, $classes, $content, $services->get(Diagnostics::class)),
                reason: 'free-text term, unbounded key space',
            ),
            QueryKind::NotFound === $context->kind => SurfacePlan::uncacheable(
                surface: new NotFound($context, $classes),
                reason: 'a 404 is a statement about the current content graph',
            ),
            default => SurfacePlan::uncacheable(
                surface: new GenericList($classes),
                reason: 'unmapped request kind',
            ),
        };

        $resolved = apply_filters(Hooks::SURFACE_RESOLVE, $plan, $context);

        if (!$resolved instanceof SurfacePlan) {
            throw InvalidHookResult::notASurfacePlan();
        }

        return $resolved;
    }

    /**
     * The resolved plan for this request, computed once. The header phase
     * (wp_headers, at send_headers time) resolves first; the render phase
     * reads the memoised plan rather than resolving a second time.
     */
    public static function plan(QueryContext $ctx, Container $services): SurfacePlan
    {
        return self::$plan ??= self::resolve($ctx, $services);
    }

    /** The test seam: forget the memoised plan between requests. */
    public static function forget(): void
    {
        self::$plan = null;
    }

    /** The posts index's fragment key: the scope word, the page, the locale. */
    private static function indexKey(QueryContext $ctx): FragmentKey
    {
        return FragmentKey::fromParts(BlogIndex::class, $ctx->kind->name, $ctx->listingPage(), $ctx->site->locale);
    }

    /**
     * The archive's fragment key: the queried object, or the queried date
     * triple drawn from the site's own published dates.
     */
    private static function archiveKey(QueryContext $ctx): FragmentKey
    {
        return FragmentKey::fromParts(
            ContentArchive::class,
            $ctx->objectSubtype ?? 'date',
            $ctx->objectId ?? 0,
            'y'.\max(0, $ctx->intVar('year')).'m'.\max(0, $ctx->intVar('monthnum')).'d'.\max(0, $ctx->intVar('day')),
            $ctx->listingPage(),
            $ctx->site->locale,
        );
    }
}
