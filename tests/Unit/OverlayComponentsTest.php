<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Overlays\Dialog;
use Iniznet\Howdah\Components\Overlays\Popover;
use Iniznet\Howdah\Components\Overlays\Toast;
use Iniznet\Howdah\Components\Overlays\Tone;
use Iniznet\Howdah\Components\Primitives\Icon;
use Iniznet\Howdah\Exception\IconNotFound;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The overlay components. The native elements own the behaviour — focus
 * trapping, the Escape key, the light dismiss — so the markup's job is the
 * right elements and the right labelled pairs, not a script.
 */
final class OverlayComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testADialogLabelsItsTitle(): void
    {
        $html = (new Dialog($this->classes, 'howdah-dialog', 'Confirm the move', 'The chapter moves to the draft bin.'))->render();

        self::assertStringContainsString('<dialog', $html);
        self::assertStringContainsString('aria-labelledby="howdah-dialog-title"', $html);
        self::assertStringContainsString('id="howdah-dialog-title"', $html);
        self::assertStringContainsString('method="dialog"', $html, 'closing is the native form behaviour.');
    }

    public function testAPopoverPairsItsTriggerAndItsRegion(): void
    {
        $html = (new Popover($this->classes, 'howdah-filters', 'Filters', 'Filter by status.'))->render();

        self::assertStringContainsString('popovertarget="howdah-filters"', $html);
        self::assertStringContainsString('id="howdah-filters"', $html);
        self::assertStringContainsString(' popover', $html);
        self::assertStringContainsString('type="button"', $html);
    }

    public function testAToastIsAnnouncedAndToned(): void
    {
        $html = (new Toast($this->classes, 'The chapter moved.', Tone::Positive))->render();

        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('toast--positive', $html);
        self::assertStringContainsString('The chapter moved.', $html);
    }

    public function testAnIconRendersItsNamedPath(): void
    {
        $html = (new Icon($this->classes, 'search'))->render();

        self::assertStringContainsString('<svg', $html);
        self::assertStringContainsString('aria-hidden="true"', $html, 'a named glyph is decorative when it has no label.');
        self::assertStringContainsString('viewBox="0 0 24 24"', $html);
    }

    public function testAnIconCanNameItself(): void
    {
        $html = (new Icon($this->classes, 'warning', 'Warning'))->render();

        self::assertStringContainsString('aria-hidden="false"', $html);
        self::assertStringContainsString('aria-label="Warning"', $html);
        self::assertStringContainsString('role="img"', $html);
    }

    public function testAnUnknownIconFailsLoudly(): void
    {
        $this->expectException(IconNotFound::class);

        (new Icon($this->classes, 'sparkles'))->render();
    }
}
