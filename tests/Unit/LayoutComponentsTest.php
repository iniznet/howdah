<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Layout\Cluster;
use Iniznet\Howdah\Components\Layout\Container;
use Iniznet\Howdah\Components\Layout\Grid;
use Iniznet\Howdah\Components\Layout\Section;
use Iniznet\Howdah\Components\Layout\Size;
use Iniznet\Howdah\Components\Layout\Stack;
use Iniznet\Howdah\Components\Layout\Width;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The layout primitives: children rendered inside a semantic wrapper, the
 * size carried as an enum, an out-of-range column count clamped.
 */
final class LayoutComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testAStackWrapsItsChildrenInAColumn(): void
    {
        $html = (new Stack($this->classes, ['<p>one</p>', '<p>two</p>']))->render();

        self::assertStringContainsString('<div class="stack stack--medium">', $html);
        self::assertSame(2, substr_count($html, '<p>'), 'both children render.');
    }

    public function testAStackCarriesItsSize(): void
    {
        $html = (new Stack($this->classes, ['<p>x</p>'], Size::Large))->render();

        self::assertStringContainsString('stack--large', $html);
    }

    public function testAClusterRendersAHorizontalRow(): void
    {
        $html = (new Cluster($this->classes, ['<span>a</span>'], Size::Small))->render();

        self::assertStringContainsString('cluster cluster--small', $html);
        self::assertStringContainsString('<span>a</span>', $html);
    }

    public function testAGridClampsAnOutOfRangeColumnCount(): void
    {
        $high = (new Grid($this->classes, ['<p>x</p>'], 99))->render();
        $low = (new Grid($this->classes, ['<p>x</p>'], 0))->render();

        self::assertStringContainsString('--howdah-grid-columns: 6;', $html = $high, 'the column count caps at the declared maximum.');
        self::assertStringContainsString('--howdah-grid-columns: 1;', $low, 'the column count floors at one.');
    }

    public function testAContainerCarriesItsWidth(): void
    {
        $html = (new Container($this->classes, ['<p>x</p>'], Width::Wide))->render();

        self::assertStringContainsString('container container--wide', $html);
    }

    public function testASectionIsLabelled(): void
    {
        $html = (new Section($this->classes, ['<p>x</p>'], 'Latest posts'))->render();

        self::assertStringContainsString('<section', $html);
        self::assertStringContainsString('aria-label="Latest posts"', $html, 'the section names its region.');
        self::assertStringContainsString('aria-label="&lt;script&gt;"', (new Section($this->classes, [], '<script>'))->render(), 'the label is escaped.');
    }
}
