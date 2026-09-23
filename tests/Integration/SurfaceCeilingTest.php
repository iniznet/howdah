<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Features\Content\Surfaces\BlogIndex;
use Iniznet\Howdah\Features\Content\Surfaces\ContentArchive;
use Iniznet\Howdah\Features\Content\Surfaces\EmbedContent;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePage;
use Iniznet\Howdah\Features\Content\Surfaces\SinglePost;
use Iniznet\Howdah\Surfaces\Arms\GenericList;
use Iniznet\Howdah\Surfaces\Arms\NotFound;
use Iniznet\Howdah\Surfaces\Arms\SearchResults;
use Iniznet\Howdah\Tests\Support\CeilingProbe;
use Iniznet\Howdah\Tests\Support\QueryCeilingExceeded;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\QueryKind;
use Iniznet\Mahout\Render\SiteProfile;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * Every Surface's query ceiling, cold and warm, from the throughput budget's
 * per-Surface table: reader 6, archive 8, search 10. A Surface that exceeds
 * its ceiling fails this test; the negative proof renders a deliberately
 * wasteful closure against a lower ceiling through the same probe.
 */
final class SurfaceCeilingTest extends \WP_UnitTestCase
{
    private ClassResolver $classes;

    private ContentRepository $content;

    protected function setUp(): void
    {
        parent::setUp();

        $services = Bootstrap::services();
        $this->classes = $services->get(ClassResolver::class);
        $this->content = $services->get(ContentRepository::class);

        // The shell's own one-per-request cost (autoloaded options, the
        // custom-CSS post) is paid here, outside the measured renders.
        (new Document($this->classes))->render();
    }

    public function testTheSinglePostSurfaceStaysWithinTheReaderCeilingCold(): void
    {
        $postId = (int) self::factory()->post->create();
        $surface = new SinglePost(self::ctx(QueryKind::Singular, 'post', $postId), $this->content, $this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), SinglePost::QUERY_CEILING, 'SinglePost cold');
        self::assertLessThanOrEqual(SinglePost::QUERY_CEILING, $observed);
    }

    public function testTheSinglePageSurfaceStaysWithinTheReaderCeilingCold(): void
    {
        $pageId = (int) self::factory()->post->create(['post_type' => 'page']);
        $surface = new SinglePage(self::ctx(QueryKind::Singular, 'page', $pageId), $this->content, $this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), SinglePage::QUERY_CEILING, 'SinglePage cold');
        self::assertLessThanOrEqual(SinglePage::QUERY_CEILING, $observed);
    }

    public function testTheEmbedSurfaceStaysWithinTheReaderCeilingCold(): void
    {
        $postId = (int) self::factory()->post->create();
        $surface = new EmbedContent(self::ctx(QueryKind::Embed, 'post', $postId), $this->content, $this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), EmbedContent::QUERY_CEILING, 'EmbedContent cold');
        self::assertLessThanOrEqual(EmbedContent::QUERY_CEILING, $observed);
    }

    public function testTheIndexSurfaceStaysWithinTheArchiveCeilingCold(): void
    {
        self::factory()->post->create_many(3, ['post_date' => '2024-01-01 00:00:00']);
        $surface = new BlogIndex(self::ctx(QueryKind::Home, 'post'), $this->content, $this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), BlogIndex::QUERY_CEILING, 'BlogIndex cold');
        self::assertLessThanOrEqual(BlogIndex::QUERY_CEILING, $observed);
    }

    public function testTheArchiveSurfaceStaysWithinTheArchiveCeilingCold(): void
    {
        $categoryId = self::factory()->category->create();
        self::factory()->post->create_many(3, ['post_category' => [$categoryId], 'post_date' => '2024-01-01 00:00:00']);
        $ctx = self::ctx(QueryKind::Archive, 'post', $categoryId, 'category');
        $surface = new ContentArchive($ctx, $this->content, $this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), ContentArchive::QUERY_CEILING, 'ContentArchive cold');
        self::assertLessThanOrEqual(ContentArchive::QUERY_CEILING, $observed);
    }

    public function testTheSearchSurfaceStaysWithinTheSearchCeilingCold(): void
    {
        self::factory()->post->create_many(3, ['post_content' => 'searchable words', 'post_date' => '2024-01-01 00:00:00']);
        $ctx = self::ctx(QueryKind::Search, 'post', null, null, ['s' => 'searchable words']);
        $surface = new SearchResults($ctx, $this->classes, $this->content, Bootstrap::services()->get(\Iniznet\Mahout\Kernel\Diagnostics::class));

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), SearchResults::QUERY_CEILING, 'SearchResults cold');
        self::assertLessThanOrEqual(SearchResults::QUERY_CEILING, $observed);
    }

    public function testANotFoundRendersNoQueriesOfItsOwn(): void
    {
        $surface = new NotFound(self::ctx(QueryKind::NotFound), $this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), NotFound::QUERY_CEILING, 'NotFound cold');
        self::assertLessThanOrEqual(NotFound::QUERY_CEILING, $observed);
    }

    public function testTheDefaultArmRendersNoQueriesOfItsOwn(): void
    {
        $surface = new GenericList($this->classes);

        $observed = CeilingProbe::within(static fn (): string => $surface->render(), GenericList::QUERY_CEILING, 'GenericList cold');
        self::assertLessThanOrEqual(GenericList::QUERY_CEILING, $observed);
    }

    public function testTheNegativeProofRefusesAWastefulRender(): void
    {
        global $wpdb;

        $wasteful = static function (): string {
            global $wpdb;

            $wpdb->get_results('SELECT 1');
            $wpdb->get_results('SELECT 2');
            $wpdb->get_results('SELECT 3');

            return 'wasted';
        };

        try {
            CeilingProbe::within($wasteful, 2, 'the negative proof');
            self::fail('a render beyond its ceiling must fail the gate.');
        } catch (QueryCeilingExceeded $e) {
            self::assertStringContainsString('3 queries', $e->getMessage(), 'the refusal names the observed count.');
            self::assertStringContainsString('against a ceiling of 2', $e->getMessage());
        }
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
