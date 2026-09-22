<?php

/**
 * The dispatch table: the only place where "what renders this request" is
 * decided, and therefore the only place that decides what may be cached.
 *
 * Every arm returns a SurfacePlan and declares its Cacheability and
 * FragmentScope; every Uncacheable arm states why. The default arm is
 * written, never implied. docs/reference/surfaces.md is generated from this
 * file and drift-checked (13-caching §4).
 *
 * Reading collaborators out of the services graph here is composition, not
 * service location: this is the single boundary at which Surfaces are
 * constructed. The repositories themselves are never resolved from the
 * graph inside a Surface or a Component.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Features\Content\Surfaces\EmbedContent;
use Iniznet\Howdah\Render\Surfaces\GenericList;
use Iniznet\Howdah\Render\Surfaces\NotFound;
use Iniznet\Howdah\Render\Surfaces\SearchResults;
use Iniznet\Howdah\Support\ClassResolver;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Kernel\Container;

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

        $plan = match (true) {
            QueryKind::Embed === $context->kind => SurfacePlan::uncacheable(
                surface: new EmbedContent($context),
                reason: 'embed document, rendered for one parent request',
            ),
            QueryKind::Search === $context->kind => SurfacePlan::uncacheable(
                surface: new SearchResults($context, $classes),
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
}
