<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Navigation\Breadcrumb;
use Iniznet\Howdah\Components\Navigation\Crumb;
use Iniznet\Howdah\Components\Navigation\Nav;
use Iniznet\Howdah\Components\Navigation\NavItem;
use Iniznet\Howdah\Components\Navigation\SiteFooter;
use Iniznet\Howdah\Components\Navigation\SiteHeader;
use Iniznet\Howdah\Components\Navigation\Tab;
use Iniznet\Howdah\Components\Navigation\Tabs;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The navigation primitives: labelled regions, a current item that is
 * announced, and a trail whose last crumb is the reader's page.
 */
final class NavigationComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testANavIsLabelledAndMarksItsCurrentItem(): void
    {
        $html = (new Nav($this->classes, [
            new NavItem('Home', '/'),
            new NavItem('About', '/about', current: true),
        ], 'Primary'))->render();

        self::assertStringContainsString('aria-label="Primary"', $html);
        self::assertStringContainsString('<nav', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertSame(2, substr_count($html, '<a '), 'every item renders as a link.');
    }

    public function testABreadcrumbMarksItsLastCrumb(): void
    {
        $html = (new Breadcrumb($this->classes, [
            new Crumb('Home', '/'),
            new Crumb('Fiction', '/fiction'),
            new Crumb('The Harbor'),
        ]))->render();

        self::assertStringContainsString('aria-label', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertSame(2, substr_count($html, '<a '), 'the current page is not a link.');
    }

    public function testTabsMarkTheirSelectedState(): void
    {
        $html = (new Tabs($this->classes, [
            new Tab('latest', 'Latest', selected: true),
            new Tab('popular', 'Popular'),
        ]))->render();

        self::assertStringContainsString('role="tablist"', $html);
        self::assertStringContainsString('aria-selected="true"', $html);
        self::assertStringContainsString('aria-selected="false"', $html);
        self::assertSame(2, substr_count($html, 'type="button"'));
    }

    public function testTheHeaderLinksHomeAndCarriesNav(): void
    {
        $nav = (new Nav($this->classes, [new NavItem('Home', '/')], 'Primary'))->render();
        $html = (new SiteHeader($this->classes, 'Howdah', 'https://howdah.test/', $nav))->render();

        self::assertStringContainsString('<header', $html);
        self::assertStringContainsString('href="https://howdah.test/"', $html);
        self::assertStringContainsString('aria-label="Primary"', $html, 'the nav slot renders.');
    }

    public function testTheHeaderOmitsAnEmptyNavSlot(): void
    {
        $html = (new SiteHeader($this->classes, 'Howdah', '/'))->render();

        self::assertStringContainsString('<header', $html);
        self::assertStringNotContainsString('<nav', $html);
    }

    public function testTheFooterRendersAYear(): void
    {
        $html = (new SiteFooter($this->classes, 'Built with care'))->render();

        self::assertStringContainsString('<footer', $html);
        self::assertMatchesRegularExpression('/© \d{4}/', $html);
        self::assertStringContainsString('Built with care', $html);
    }
}
