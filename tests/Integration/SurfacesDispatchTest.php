<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Features\Content\Surfaces\EmbedContent;
use Iniznet\Howdah\Render\Cacheability;
use Iniznet\Howdah\Render\CachedFragment;
use Iniznet\Howdah\Render\QueryContext;
use Iniznet\Howdah\Render\QueryKind;
use Iniznet\Howdah\Render\SiteProfile;
use Iniznet\Howdah\Render\SurfacePlan;
use Iniznet\Howdah\Render\Surfaces;
use Iniznet\Howdah\Render\Surfaces\GenericList;
use Iniznet\Howdah\Render\Surfaces\NotFound;
use Iniznet\Howdah\Render\Surfaces\SearchResults;

/**
 * The dispatch table, table-driven over QueryContext fixtures: every
 * QueryKind over the core post types resolves to a working Surface, every
 * arm declares its Cacheability and FragmentScope, every Uncacheable arm
 * carries its reason, the default arm is written, and the Shared arms carry
 * a fragment key.
 */
final class SurfacesDispatchTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        Surfaces::forget();

        parent::tearDown();
    }

    public function testEveryQueryKindResolvesToADeclaredPlan(): void
    {
        foreach (QueryKind::cases() as $kind) {
            $plan = Surfaces::resolve(self::ctx($kind), Bootstrap::services());

            self::assertInstanceOf(SurfacePlan::class, $plan, $kind->name.' resolves to a plan.');
            self::assertContains($kind->name, self::declaredArms(), $kind->name.' is named in the table.');
        }
    }

    public function testTheSharedArmsWrapTheirSurfaceWithAKey(): void
    {
        $shared = [
            [QueryKind::Home, 'post'],
            [QueryKind::Singular, 'post'],
        ];

        foreach ($shared as [$kind, $postType]) {
            $plan = Surfaces::resolve(self::ctx($kind, $postType, 7), Bootstrap::services());

            self::assertInstanceOf(CachedFragment::class, $plan->surface, $kind->name.' is wrapped for the Shared fragment.');
            self::assertSame(Cacheability::Shared, $plan->cacheability, $kind->name.' declares Shared.');
            self::assertSame('Shared', $plan->fragmentScope->name, $kind->name.' declares the Shared scope.');
            self::assertNotNull($plan->key, $kind->name.' carries a fragment key.');
        }
    }

    public function testAnArchiveResolvesToTheSharedArchiveSurface(): void
    {
        $ctx = self::ctx(QueryKind::Archive, 'post', null, 'category');
        $plan = Surfaces::resolve($ctx, Bootstrap::services());

        self::assertInstanceOf(CachedFragment::class, $plan->surface);
        self::assertSame(Cacheability::Shared, $plan->cacheability);
        self::assertSame('Shared', $plan->fragmentScope->name);
        self::assertNotNull($plan->key, 'the archive arm carries a fragment key.');
    }

    public function testAStaticFrontPageResolvesToThePageSurface(): void
    {
        $ctx = self::ctx(QueryKind::Front, 'page', 12);
        $plan = Surfaces::resolve($ctx, Bootstrap::services());

        self::assertInstanceOf(CachedFragment::class, $plan->surface, 'a static front page is wrapped for the Shared fragment.');
        self::assertSame(Cacheability::Shared, $plan->cacheability);
    }

    public function testThePostsFrontPageResolvesToTheIndexSurface(): void
    {
        $ctx = self::ctx(QueryKind::Front);
        $plan = Surfaces::resolve($ctx, Bootstrap::services());

        self::assertInstanceOf(CachedFragment::class, $plan->surface);
        self::assertSame(Cacheability::Shared, $plan->cacheability);
    }

    public function testEmbedClassifiesToTheEmbedSurface(): void
    {
        $plan = Surfaces::resolve(self::ctx(QueryKind::Embed, 'post', 7), Bootstrap::services());

        self::assertInstanceOf(EmbedContent::class, $plan->surface);
        self::assertSame('embed document, rendered for one parent request', $plan->reason);
    }

    public function testSearchArmCarriesTheUnboundedKeySpaceReason(): void
    {
        $plan = Surfaces::resolve(self::ctx(QueryKind::Search, 'post', null, null, ['s' => 'beta']), Bootstrap::services());

        self::assertInstanceOf(SearchResults::class, $plan->surface);
        self::assertSame('free-text term, unbounded key space', $plan->reason);
        self::assertSame(Cacheability::Uncacheable, $plan->cacheability);
    }

    public function testNotFoundArmCarriesItsReason(): void
    {
        $plan = Surfaces::resolve(self::ctx(QueryKind::NotFound), Bootstrap::services());

        self::assertInstanceOf(NotFound::class, $plan->surface);
        self::assertSame('a 404 is a statement about the current content graph', $plan->reason);
    }

    public function testAnUnmappedSingularKindFallsToTheWrittenDefaultArm(): void
    {
        $ctx = self::ctx(QueryKind::Singular, 'attachment', 5);
        $plan = Surfaces::resolve($ctx, Bootstrap::services());

        self::assertInstanceOf(GenericList::class, $plan->surface, 'an unmapped kind falls to the written default arm.');
        self::assertSame('unmapped request kind', $plan->reason, 'the default arm states its reason.');
    }

    public function testAnOutOfRangeListingIsNotStored(): void
    {
        $ctx = self::ctx(QueryKind::Home, 'post', null, null, ['paged' => '999']);
        $ctx = new QueryContext(
            kind: $ctx->kind,
            postType: $ctx->postType,
            objectId: $ctx->objectId,
            objectSubtype: $ctx->objectSubtype,
            queryVars: $ctx->queryVars,
            site: $ctx->site,
            outOfRangePage: true,
        );

        $plan = Surfaces::resolve($ctx, Bootstrap::services());

        self::assertSame(Cacheability::Uncacheable, $plan->cacheability, 'a page beyond the content graph is not stored.');
        self::assertSame('page beyond the content graph, out of range', $plan->reason, 'the refusal states its reason.');
    }

    public function testTheResolvedPlanIsMemoisedForTheRenderPhase(): void
    {
        Surfaces::forget();

        $ctx = self::ctx(QueryKind::NotFound);
        $first = Surfaces::plan($ctx, Bootstrap::services());
        $second = Surfaces::plan($ctx, Bootstrap::services());

        self::assertSame($first, $second, 'the plan resolves once per request; the render phase reads the memoised plan.');
    }

    public function testAContextFilterReturningAWrongShapeIsRefused(): void
    {
        add_filter('howdah/surface/context', static fn (): int => 7);

        try {
            Surfaces::resolve(self::ctx(QueryKind::Singular, 'post', 7), Bootstrap::services());
            self::fail('a wrong shape from howdah/surface/context must be refused, never coerced.');
        } catch (InvalidHookResult $e) {
            self::assertStringContainsString('QueryContext', $e->getMessage());
        } finally {
            remove_all_filters('howdah/surface/context');
        }
    }

    public function testAResolveFilterReturningAWrongShapeIsRefused(): void
    {
        add_filter('howdah/surface/resolve', static fn () => 'a page');

        try {
            Surfaces::resolve(self::ctx(QueryKind::NotFound), Bootstrap::services());
            self::fail('a filter returning a wrong shape must be refused, never coerced.');
        } catch (InvalidHookResult $e) {
            self::assertStringContainsString('SurfacePlan', $e->getMessage());
        } finally {
            remove_all_filters('howdah/surface/resolve');
        }
    }

    public function testASurfaceIsReplacedThroughTheResolveFilter(): void
    {
        $replacement = new class implements \Iniznet\Howdah\Render\Component {
            public function render(): string
            {
                return 'replaced';
            }
        };

        add_filter('howdah/surface/resolve', static fn (SurfacePlan $plan): SurfacePlan => new SurfacePlan(
            $replacement,
            Cacheability::Private,
            \Iniznet\Howdah\Render\FragmentScope::Never,
            'a child theme replaced the Surface through the documented seam',
        ));

        $plan = Surfaces::resolve(self::ctx(QueryKind::NotFound), Bootstrap::services());

        self::assertSame('replaced', $plan->surface->render(), 'a Surface is replaced through howdah/surface/resolve, typed and greppable.');
        self::assertSame(Cacheability::Private, $plan->cacheability, 'the replacement keeps a declaration.');
    }

    /** @return list<string> */
    private static function declaredArms(): array
    {
        return ['Embed', 'Front', 'Home', 'Singular', 'Archive', 'Search', 'NotFound', 'Generic'];
    }

    private static function ctx(
        QueryKind $kind,
        ?string $postType = null,
        ?int $objectId = null,
        ?string $subtype = null,
        array $vars = [],
    ): QueryContext {
        return new QueryContext(
            kind: $kind,
            postType: $postType,
            objectId: $objectId,
            objectSubtype: $subtype,
            queryVars: $vars,
            site: new SiteProfile('howdah test', '', 'en_US', 'http', false),
        );
    }
}
