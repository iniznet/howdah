<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Features\Series\Components\SeriesCard;
use Iniznet\Howdah\Features\Series\SeriesData;
use Iniznet\Mahout\Ui\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The card's rendered output: one link, the tagline when present, absent
 * when not, and exactly one escape per interpolated value.
 */
final class SeriesCardTest extends TestCase
{
    public function testTheCardRendersTheTitleAsOneEscapedLink(): void
    {
        $markup = $this->card(title: 'Roads & Roads', tagline: 'A & series', id: 7)->render();

        self::assertSame(1, substr_count($markup, 'Roads &amp; Roads'), 'exactly one escape on the title.');
        self::assertSame(1, substr_count($markup, 'A &amp; series'), 'exactly one escape on the tagline.');
        self::assertStringContainsString('href="https://example.test/series/roads"', $markup);
    }

    public function testAnAbsentTaglineRendersNoTaglineElement(): void
    {
        $markup = $this->card(title: 'Bare', tagline: null, id: 8)->render();

        self::assertStringNotContainsString('series-card-tagline', $markup);
    }

    private function card(string $title, ?string $tagline, int $id): SeriesCard
    {
        $series = new SeriesData(
            id: $id,
            title: $title,
            permalink: 'https://example.test/series/roads',
            tagline: $tagline,
        );

        return new SeriesCard(
            ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json'),
            $series,
        );
    }
}
