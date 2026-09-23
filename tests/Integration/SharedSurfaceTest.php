<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePost;
use Iniznet\Howdah\Surfaces\Surfaces;
use Iniznet\Howdah\Tests\Support\CeilingProbe;
use Iniznet\Mahout\Render\CachedFragment;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\QueryKind;
use Iniznet\Mahout\Render\SiteProfile;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * The first Shared cache arm (CAC-09): a single post's Surface is a pure
 * function of the context and the content graph, so two anonymous visitors
 * receive byte-identical markup and the one stored fragment serves an
 * authenticated visitor too. The suite runs at layer 0 — no object cache,
 * no page cache, no CDN — and the same path passes.
 */
final class SharedSurfaceTest extends \WP_UnitTestCase
{
    private ClassResolver $classes;

    private ContentRepository $content;

    protected function setUp(): void
    {
        parent::setUp();

        $services = Bootstrap::services();
        $this->classes = $services->get(ClassResolver::class);
        $this->content = $services->get(ContentRepository::class);
    }

    public function testTheSuiteRunsAtLayerZero(): void
    {
        self::assertFalse((bool) wp_using_ext_object_cache(), 'no object cache drop-in: the suite is layer 0 (CAC-06).');
    }

    public function testTwoAnonymousVisitorsReceiveByteIdenticalMarkup(): void
    {
        $postId = (int) self::factory()->post->create([
            'post_title' => 'The shared fragment',
            'post_content' => 'Two visitors, one page of bytes.',
        ]);
        $ctx = self::ctx(QueryKind::Singular, 'post', $postId);

        // Two separate anonymous profiles: separate cookie and superglobal
        // state between the renders, the same queried object. Core prints an
        // enqueued style or script once per process and registers handles it
        // learns late, so a warm-up render absorbs the process's first-pass
        // asymmetry and a fresh request's print state is restored before
        // each visitor renders.
        (new SinglePost($ctx, $this->content, $this->classes))->render();

        self::freshRequestState();
        $first = (new SinglePost($ctx, $this->content, $this->classes))->render();

        self::freshRequestState();
        $_COOKIE = ['a_different_visitor' => '1'];
        $_SERVER['HTTP_USER_AGENT'] = 'the-second-visitor';
        $second = (new SinglePost($ctx, $this->content, $this->classes))->render();

        self::assertSame($first, $second, 'two anonymous visitors receive byte-identical markup (CAC-09).');
        self::assertStringNotContainsStringIgnoringCase('nonce', $first, 'a Shared Surface registers no nonce.');
    }

    public function testTheSameStoredFragmentServesAnAuthenticatedVisitor(): void
    {
        $postId = (int) self::factory()->post->create(['post_title' => 'Shared arm']);
        $ctx = self::ctx(QueryKind::Singular, 'post', $postId);

        Surfaces::forget();
        $first = Surfaces::resolve($ctx, Bootstrap::services())->surface->render();

        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        Surfaces::forget();
        $second = Surfaces::resolve($ctx, Bootstrap::services())->surface->render();

        self::assertSame($first, $second, 'the Shared scope serves one entry to anonymous and authenticated visitors alike.');

        wp_set_current_user(0);
    }

    public function testAWarmHitIssuesNoneOfTheSurfacesOwnQueries(): void
    {
        $postId = (int) self::factory()->post->create();
        $ctx = self::ctx(QueryKind::Singular, 'post', $postId);

        Surfaces::forget();
        $plan = Surfaces::resolve($ctx, Bootstrap::services());

        self::assertInstanceOf(CachedFragment::class, $plan->surface);

        $plan->surface->render();
        $warm = CeilingProbe::count(static fn (): string => $plan->surface->render());

        self::assertSame(0, $warm, 'a warm hit issues zero Surface queries: the stored bytes are the page.');
    }

    public function testASinglePostSurfaceRegistersNoNonceMaterial(): void
    {
        $postId = (int) self::factory()->post->create(['post_content' => 'clean bytes']);
        $surface = new SinglePost(self::ctx(QueryKind::Singular, 'post', $postId), $this->content, $this->classes);

        $html = $surface->render();

        self::assertStringNotContainsStringIgnoringCase('nonce', $html, 'no nonce material inside a Shared fragment.');
    }

    /**
     * The state a fresh request would carry: empty cookies and core's asset
     * registries rebuilt from scratch, as a second process would.
     */
    private static function freshRequestState(): void
    {
        $_COOKIE = [];
        $GLOBALS['wp_styles'] = new \WP_Styles();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
    }

    private static function ctx(QueryKind $kind, ?string $postType, ?int $objectId): QueryContext
    {
        return new QueryContext(
            kind: $kind,
            postType: $postType,
            objectId: $objectId,
            objectSubtype: null,
            queryVars: [],
            site: new SiteProfile('howdah test', '', 'en_US', 'http', false),
        );
    }
}
