<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Actions\Button;
use Iniznet\Howdah\Components\Actions\ButtonType;
use Iniznet\Howdah\Components\Actions\ButtonVariant;
use Iniznet\Howdah\Components\Actions\IconButton;
use Iniznet\Howdah\Components\Actions\Link;
use Iniznet\Howdah\Components\Actions\SkipLink;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The action primitives: type and variant as enums, an external link that
 * announces itself, an icon button that cannot exist without a name.
 */
final class ActionComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testAButtonRendersItsTypeAndVariant(): void
    {
        $html = (new Button($this->classes, 'Save', ButtonType::Submit, ButtonVariant::Primary))->render();

        self::assertStringContainsString('<button', $html);
        self::assertStringContainsString('type="submit"', $html, 'the declared type is rendered.');
        self::assertStringContainsString('button--primary', $html);
        self::assertStringContainsString('Save', $html);
    }

    public function testADisabledButtonCarriesTheAttribute(): void
    {
        $html = (new Button($this->classes, 'Save', disabled: true))->render();

        self::assertStringContainsString(' disabled', $html);
    }

    public function testAnExternalLinkAnnouncesItself(): void
    {
        $html = (new Link($this->classes, 'https://example.org', 'Docs', external: true))->render();

        self::assertStringContainsString('rel="noopener noreferrer"', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('href="https://example.org"', $html);
    }

    public function testAnInternalLinkStaysInternal(): void
    {
        $html = (new Link($this->classes, '/about', 'About'))->render();

        self::assertStringNotContainsString('target=', $html);
        self::assertSame('/about', (string) preg_match('#href="([^"]+)"#', $html, $matches) ? $matches[1] : '', 'the href passes through esc_url unchanged.');
    }

    public function testAnIconButtonNamesItselfForAssistiveTech(): void
    {
        $html = (new IconButton($this->classes, 'Close'))->render();

        self::assertStringContainsString('aria-label="Close"', $html);
        self::assertStringContainsString('type="button"', $html);
    }

    public function testASkipLinkPointsAtItsTarget(): void
    {
        $html = (new SkipLink($this->classes, 'main', 'Skip to content'))->render();

        self::assertStringContainsString('href="#main"', $html);
        self::assertStringContainsString('skip-link', $html);
    }
}
