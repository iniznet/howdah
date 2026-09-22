<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Content\Accordion;
use Iniznet\Howdah\Components\Content\AccordionSection;
use Iniznet\Howdah\Components\Content\Badge;
use Iniznet\Howdah\Components\Content\Card;
use Iniznet\Howdah\Components\Content\Disclosure;
use Iniznet\Howdah\Components\Content\Figure;
use Iniznet\Howdah\Components\Content\Table;
use Iniznet\Howdah\Components\Content\Tone;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The content components: structured data in, escaped HTML out, native
 * semantics over script where the platform has an element for it.
 */
final class ContentComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testACardRendersItsPartsAndOmitsEmptySlots(): void
    {
        $html = (new Card($this->classes, 'Title', 'Body text', '<a href="/">Go</a>'))->render();

        self::assertStringContainsString('<article', $html);
        self::assertStringContainsString('<h3', $html);
        self::assertStringContainsString('card-body', $html);
        self::assertStringContainsString('card-actions', $html);
    }

    public function testACardWithoutActionsOmitsTheSlot(): void
    {
        $html = (new Card($this->classes, 'Title', 'Body'))->render();

        self::assertStringNotContainsString('card-actions', $html);
    }

    public function testEveryToneRendersItsClass(): void
    {
        foreach (Tone::cases() as $tone) {
            $html = (new Badge($this->classes, 'Draft', $tone))->render();

            self::assertStringContainsString('badge--'.$tone->value, $html);
        }
    }

    public function testAFigurePairsItsImageAndCaption(): void
    {
        $html = (new Figure($this->classes, 'https://howdah.test/a.jpg', 'A harbour', 'The harbour at dawn'))->render();

        self::assertStringContainsString('<figure', $html);
        self::assertStringContainsString('alt="A harbour"', $html, 'the alt text is the image text alternative.');
        self::assertStringContainsString('<figcaption', $html);
    }

    public function testATableRendersAStructuredGrid(): void
    {
        $html = (new Table($this->classes, ['Chapter', 'Status'], [['One', 'Draft']], 'Chapters'))->render();

        self::assertStringContainsString('<caption', $html);
        self::assertSame(2, substr_count($html, '<th scope="col">'), 'every column is a header cell.');
        self::assertStringContainsString('<td>One</td>', $html);
        self::assertStringContainsString('<td>Draft</td>', $html);
    }

    public function testADisclosureOpensOnlyWhenAsked(): void
    {
        self::assertStringContainsString(' open', (new Disclosure($this->classes, 'More', 'Detail', open: true))->render());
        self::assertStringNotContainsString(' open', (new Disclosure($this->classes, 'More', 'Detail'))->render());
    }

    public function testAnAccordionRendersOneDetailsPerSection(): void
    {
        $html = (new Accordion($this->classes, [
            new AccordionSection('First', 'Body one', open: true),
            new AccordionSection('Second', 'Body two'),
        ]))->render();

        self::assertSame(2, substr_count($html, '<details'));
        self::assertSame(1, substr_count($html, ' open'), 'only the opened section starts open.');
    }
}
