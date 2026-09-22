<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Features\Content\Surfaces\EmbedContent;
use Iniznet\Howdah\Render\Cacheability;
use Iniznet\Howdah\Render\FragmentScope;
use Iniznet\Howdah\Render\QueryContext;
use Iniznet\Howdah\Render\QueryKind;
use Iniznet\Howdah\Render\SiteProfile;
use Iniznet\Howdah\Render\SurfacePlan;
use Iniznet\Howdah\Render\Surfaces;
use Iniznet\Howdah\Render\Surfaces\GenericList;
use Iniznet\Howdah\Render\Surfaces\NotFound;
use Iniznet\Howdah\Support\ClassResolver;
use Iniznet\Mahout\Kernel\Container;

/**
 * The dispatch table, table-driven over QueryContext fixtures: every
 * QueryKind resolves to a Surface, every arm declares its Cacheability and
 * FragmentScope, every Uncacheable arm carries its reason, and the default
 * arm is written, not implied. The Embed kind classifies to EmbedContent.
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
            $plan = Surfaces::resolve(self::ctx($kind), self::services());

            self::assertInstanceOf(SurfacePlan::class, $plan, $kind->name.' resolves to a plan.');
            self::assertSame(Cacheability::Uncacheable, $plan->cacheability, $kind->name.' declares its class.');
            self::assertSame('Never', $plan->fragmentScope->name, $kind->name.' declares its fragment scope.');
            self::assertNotSame('', $plan->reason, $kind->name.' states why it is not cached.');
        }
    }

    public function testEmbedClassifiesToTheEmbedSurface(): void
    {
        $plan = Surfaces::resolve(self::ctx(QueryKind::Embed), self::services());

        self::assertInstanceOf(EmbedContent::class, $plan->surface, 'is_embed() requests classify as the embed Surface.');
        self::assertSame('embed document, rendered for one parent request', $plan->reason);
    }

    public function testSearchArmCarriesTheUnboundedKeySpaceReason(): void
    {
        $plan = Surfaces::resolve(self::ctx(QueryKind::Search), self::services());

        self::assertInstanceOf(SurfacePlan::class, $plan);
        self::assertSame('free-text term, unbounded key space', $plan->reason);
    }

    public function testNotFoundArmCarriesItsReason(): void
    {
        $plan = Surfaces::resolve(self::ctx(QueryKind::NotFound), self::services());

        self::assertInstanceOf(NotFound::class, $plan->surface);
        self::assertSame('a 404 is a statement about the current content graph', $plan->reason);
    }

    public function testTheDefaultArmIsExplicitForEveryUnmappedKind(): void
    {
        foreach ([QueryKind::Singular, QueryKind::Archive, QueryKind::Front, QueryKind::Home, QueryKind::Generic] as $kind) {
            $plan = Surfaces::resolve(self::ctx($kind), self::services());

            self::assertInstanceOf(GenericList::class, $plan->surface, $kind->name.' falls to the written default arm.');
            self::assertSame('unmapped request kind', $plan->reason, 'the default arm states its reason.');
        }
    }

    public function testTheResolvedPlanIsMemoisedForTheRenderPhase(): void
    {
        Surfaces::forget();

        $ctx = self::ctx(QueryKind::NotFound);
        $first = Surfaces::plan($ctx, self::services());
        $second = Surfaces::plan($ctx, self::services());

        self::assertSame($first, $second, 'the plan resolves once per request; the render phase reads the memoised plan.');
    }

    public function testAContextFilterReturningAWrongShapeIsRefused(): void
    {
        add_filter('howdah/surface/context', static fn (): int => 7);

        try {
            Surfaces::resolve(self::ctx(QueryKind::Singular), self::services());
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
            Surfaces::resolve(self::ctx(QueryKind::NotFound), self::services());
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
            FragmentScope::Never,
            'a child theme replaced the Surface through the documented seam',
        ));

        $plan = Surfaces::resolve(self::ctx(QueryKind::NotFound), self::services());

        self::assertSame('replaced', $plan->surface->render(), 'a Surface is replaced through howdah/surface/resolve, typed and greppable.');
        self::assertSame(Cacheability::Private, $plan->cacheability, 'the replacement keeps a declaration.');
    }

    private static function ctx(QueryKind $kind): QueryContext
    {
        return new QueryContext(
            kind: $kind,
            postType: QueryKind::Singular === $kind ? 'series' : null,
            objectId: 7,
            objectSubtype: null,
            queryVars: [],
            site: new SiteProfile('howdah test', '', 'en_US', 'http', false),
        );
    }

    private static function services(): Container
    {
        $container = new Container();
        $container->set(ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json'));

        return $container;
    }
}
