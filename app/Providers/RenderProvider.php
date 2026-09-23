<?php

/**
 * The render pipeline's composition: the fragment store is bound here, and
 * the wp_headers listener resolves the request's SurfacePlan once — at
 * send_headers time, after WP::main() has run the main query and before any
 * body exists.
 *
 * The response's own mechanics are `mahout-render`'s: `Cache\HeaderPolicy`
 * derives the class the declaration implies from the request state,
 * `Cache\ResponseHeaders` narrows core's payload and merges the policy over
 * it, and `Cache\ConditionalGet` answers a matching `If-None-Match` with a
 * `304`. What stays here is the theme's: the derivation is recorded when
 * request state reduced the declared class, because a downgrade the operator
 * cannot see is a silent fallback.
 *
 * Feeds, robots and favicon responses are core paths and are skipped: the
 * theme declares nothing for a response it does not render.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Howdah\Support\Request;
use Iniznet\Howdah\Surfaces\Surfaces;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Render\Cache\ConditionalGet;
use Iniznet\Mahout\Render\Cache\HeaderPolicy;
use Iniznet\Mahout\Render\Cache\ResponseHeaders;
use Iniznet\Mahout\Render\Cacheability;
use Iniznet\Mahout\Render\FragmentCache;
use Iniznet\Mahout\Render\QueryContext;

final class RenderProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(new FragmentCache());
    }

    public function boot(Container $container): void
    {
        \add_filter(Hooks::WP_HEADERS, static fn ($headers, \WP $wp): array => self::headers($headers, $container), priority: 10, accepted_args: 2);
    }

    /**
     * Core's header filter payload, narrowed at the boundary, then the
     * declared policy merged over it — core first, the policy last, so no
     * subscriber can widen a response the class bounded.
     *
     * @return array<string, string|false>
     */
    private static function headers(mixed $headers, Container $container): array
    {
        if (!\is_array($headers)) {
            throw InvalidHookResult::notAHeaderMap();
        }

        $merged = ResponseHeaders::narrow($headers);

        if (!self::rendersThroughTheTheme()) {
            return $merged;
        }

        $request = Request::fromSuperglobals();
        $ctx = QueryContext::current();
        $plan = Surfaces::plan($ctx, $container);
        $policy = HeaderPolicy::derive(
            $plan->cacheability,
            $request->method,
            \is_user_logged_in(),
            outOfRangePage: $ctx->outOfRangePage,
            freeTextSearch: $ctx->freeTextSearch,
        );

        self::recordReduction($container, $plan->cacheability, $policy->effective);

        $conditional = new ConditionalGet($policy, $plan->key, $request->ifNoneMatch);

        if ($conditional->answerNotModified()) {
            exit;
        }

        return ResponseHeaders::merge($merged, $policy);
    }

    /**
     * The derivation is not a silent downgrade: when the request state
     * reduced the declared class, development records the reduction with its
     * reason. Production records nothing — a per-request log is a cost.
     */
    private static function recordReduction(Container $container, Cacheability $declared, Cacheability $effective): void
    {
        if ($declared === $effective) {
            return;
        }

        $environment = $container->get(Environment::class);

        if (!$environment->developmentMode) {
            return;
        }

        $container->get(Diagnostics::class)->log(
            level: Level::Warning,
            message: 'cacheability reduced by request state',
            context: ['declared' => $declared->name, 'effective' => $effective->name],
        );
    }

    /**
     * WordPress resolved a request this theme renders: a main query exists,
     * and the response is not a core path (feed, robots, favicon).
     */
    private static function rendersThroughTheTheme(): bool
    {
        if (\is_feed() || \is_robots() || \is_favicon()) {
            return false;
        }

        return ($GLOBALS['wp_query'] ?? null) instanceof \WP_Query;
    }
}
