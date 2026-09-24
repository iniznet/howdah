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

namespace Iniznet\Howdah\Surfaces;

use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Features\Content\Surfaces\BlogIndex;
use Iniznet\Howdah\Features\Content\Surfaces\ContentArchive;
use Iniznet\Howdah\Features\Content\Surfaces\EmbedContent;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePage;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePost;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Howdah\Surfaces\Arms\GenericList;
use Iniznet\Howdah\Surfaces\Arms\NotFound;
use Iniznet\Howdah\Surfaces\Arms\SearchResults;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\FragmentCache;
use Iniznet\Mahout\Render\FragmentKey;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\QueryKind;
use Iniznet\Mahout\Render\SurfacePlan;
use Iniznet\Mahout\Render\SurfacePlanBuilder;
use Iniznet\Mahout\Ui\ClassResolver;

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
        $builder = new SurfacePlanBuilder($context, $services->get(FragmentCache::class));
        $chrome = SiteChrome::forContext($classes, $context->site);

        $plan = match (true) {
            QueryKind::Embed === $context->kind => $builder
                ->surface(static fn (): Component => new EmbedContent($context, $content, $classes))
                ->uncacheable('embed document, rendered for one parent request'),
            QueryKind::Front === $context->kind && null !== $context->objectId => $builder
                ->surface(static fn (): Component => new SinglePage($context, $content, $classes, $chrome))
                ->shared(FragmentKey::fromParts(
                    SinglePage::class,
                    $context->objectId,
                    $context->contentPage(),
                    $context->site->locale,
                )),
            QueryKind::Front === $context->kind => $builder
                ->surface(static fn (): Component => new BlogIndex($context, $content, $classes, $chrome))
                ->guardOverflow('page beyond the content graph, out of range')
                ->shared(self::indexKey($context)),
            QueryKind::Home === $context->kind => $builder
                ->surface(static fn (): Component => new BlogIndex($context, $content, $classes, $chrome))
                ->guardOverflow('page beyond the content graph, out of range')
                ->shared(self::indexKey($context)),
            QueryKind::Singular === $context->kind && 'post' === $context->postType => $builder
                ->surface(static fn (): Component => new SinglePost($context, $content, $classes, $chrome))
                ->shared(FragmentKey::fromParts(
                    SinglePost::class,
                    $context->objectId ?? 0,
                    $context->contentPage(),
                    $context->site->locale,
                )),
            QueryKind::Singular === $context->kind && 'page' === $context->postType => $builder
                ->surface(static fn (): Component => new SinglePage($context, $content, $classes, $chrome))
                ->shared(FragmentKey::fromParts(
                    SinglePage::class,
                    $context->objectId ?? 0,
                    $context->contentPage(),
                    $context->site->locale,
                )),
            QueryKind::Archive === $context->kind => $builder
                ->surface(static fn (): Component => new ContentArchive($context, $content, $classes, $chrome))
                ->guardOverflow('page beyond the content graph, out of range')
                ->shared(self::archiveKey($context)),
            QueryKind::Search === $context->kind => $builder
                ->surface(static fn (): Component => new SearchResults($context, $classes, $content, $chrome))
                ->uncacheable('free-text term, unbounded key space'),
            QueryKind::NotFound === $context->kind => $builder
                ->surface(static fn (): Component => new NotFound($classes, $chrome))
                ->uncacheable('a 404 is a statement about the current content graph'),
            default => $builder
                ->surface(static fn (): Component => new GenericList($classes))
                ->uncacheable('unmapped request kind'),
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
