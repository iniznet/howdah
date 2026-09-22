<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Typography\ErrorText;
use Iniznet\Howdah\Components\Typography\Heading;
use Iniznet\Howdah\Components\Typography\HeadingLevel;
use Iniznet\Howdah\Components\Typography\HelpText;
use Iniznet\Howdah\Components\Typography\Label;
use Iniznet\Howdah\Components\Typography\Prose;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The typography primitives: a level enum that owns its tag, text that is
 * escaped once, and ids that form associations exist for.
 */
final class TypographyComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testEveryLevelRendersItsNativeTag(): void
    {
        foreach (HeadingLevel::cases() as $level) {
            $html = (new Heading($this->classes, $level, 'Title'))->render();

            self::assertStringContainsString('<'.$level->tag(), $html);
            self::assertStringContainsString('</'.$level->tag().'>', $html);
            self::assertStringContainsString('heading--'.$level->value, $html);
        }
    }

    public function testAHeadingEscapesItsText(): void
    {
        $html = (new Heading($this->classes, HeadingLevel::One, '<script>'))->render();

        self::assertStringNotContainsString('<script>Title', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testAProseWrapsItsBody(): void
    {
        $html = (new Prose($this->classes, '<p>Body</p>'))->render();

        self::assertStringContainsString('<div class="prose">', $html);
        self::assertStringContainsString('<p>Body</p>', $html, 'the trusted body prints raw, once.');
    }

    public function testALabelPointsAtItsControl(): void
    {
        $html = (new Label($this->classes, 'howdah-email', 'Email'))->render();

        self::assertStringContainsString('<label', $html);
        self::assertStringContainsString('for="howdah-email"', $html);
    }

    public function testHelpAndErrorTextCarryTheirIds(): void
    {
        self::assertStringContainsString('id="howdah-email-help"', (new HelpText($this->classes, 'howdah-email-help', 'We never share it'))->render());
        self::assertStringContainsString('id="howdah-email-error"', (new ErrorText($this->classes, 'howdah-email-error', 'Enter an address'))->render());
    }
}
