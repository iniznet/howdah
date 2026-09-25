<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Features\Series\SeriesRepository;
use Iniznet\Howdah\Features\Series\Surfaces\SeriesArchive;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\Contracts\FieldWriter;
use Iniznet\Mahout\Fields\ObjectRef;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\QueryKind;
use Iniznet\Mahout\Render\SiteProfile;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * The worked example's integration facts: the declared post type registers,
 * the repository lists only series in date order, the tagline rides the
 * field layer, and the archive surface renders one page within its ceiling.
 */
final class SeriesRepositoryTest extends \WP_UnitTestCase
{
    private SeriesRepository $series;

    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();

        $services = Bootstrap::services();
        $this->series = $services->get(SeriesRepository::class);
        $this->classes = $services->get(ClassResolver::class);
    }

    public function testTheDeclaredPostTypeRegistersWithAnArchive(): void
    {
        $object = \get_post_type_object('howdah_series');

        self::assertNotNull($object, 'the declared content model registers.');
        self::assertTrue($object->has_archive);
    }

    public function testTheListingReturnsOnlySeriesInDateOrder(): void
    {
        self::factory()->post->create(['post_type' => 'post', 'post_date' => '2024-01-01 00:00:00']);
        $older = self::factory()->post->create(['post_type' => 'howdah_series', 'post_date' => '2024-02-01 00:00:00']);
        $newer = self::factory()->post->create(['post_type' => 'howdah_series', 'post_date' => '2024-03-01 00:00:00']);

        $page = $this->series->page(1, 10);

        self::assertSame([$newer, $older], array_column($page->items, 'id'), 'series only, newest first.');
        self::assertFalse($page->hasMore);
    }

    public function testTheTaglineRidesTheFieldLayer(): void
    {
        $id = self::factory()->post->create(['post_type' => 'howdah_series']);
        $this->writeTagline($id, 'Three parts');

        $item = $this->series->page(1, 10)->items[0];

        self::assertSame('Three parts', $item->tagline);
    }

    public function testTheArchiveSurfaceRendersWithinItsCeiling(): void
    {
        self::factory()->post->create_many(3, ['post_type' => 'howdah_series', 'post_date' => '2024-01-01 00:00:00']);
        $surface = new SeriesArchive($this->ctx(), $this->series, $this->classes);

        $markup = $surface->render();

        self::assertStringContainsString('series-card', $markup);
        self::assertSame(3, substr_count($markup, '<article'), 'one card per series.');
    }

    public function testAnEmptyArchiveRendersTheDeclaredEmptyState(): void
    {
        $surface = new SeriesArchive($this->ctx(), $this->series, $this->classes);

        self::assertStringContainsString('No series has been published yet.', $surface->render());
    }

    private function writeTagline(int $postId, string $value): void
    {
        $services = Bootstrap::services();
        $reader = $services->get(FieldReader::class);
        $writer = $services->get(FieldWriter::class);

        // The panel is the only write path; the route's own lifecycle covers
        // it in production. Here the writer is driven directly, with the
        // rendered group's hash as the lost-update guard demands.
        $writer->writeGroup(
            'series_details',
            ObjectRef::post($postId),
            ['series_tagline' => $value],
            $reader->hash('series_details', ObjectRef::post($postId)),
        );
    }

    private function ctx(): QueryContext
    {
        return new QueryContext(
            kind: QueryKind::Archive,
            postType: 'howdah_series',
            objectId: null,
            objectSubtype: null,
            queryVars: [],
            site: new SiteProfile(name: 'Test', description: '', locale: 'en_US', scheme: 'https', isRtl: false),
        );
    }
}
