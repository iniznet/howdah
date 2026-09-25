<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Exception\InvalidContentDeclaration;
use Iniznet\Howdah\Exception\InvalidHookResult;
use Iniznet\Howdah\Exception\NotBooted;
use Iniznet\Howdah\Exception\ThemeException;
use Iniznet\Howdah\Exception\UnusablePostTimestamp;
use PHPUnit\Framework\TestCase;

/**
 * Every exception carries its package marker and names its condition, not its
 * throw site. A named constructor is the only way in.
 */
final class ExceptionsTest extends TestCase
{
    public function testEveryExceptionCarriesThePackageMarker(): void
    {
        foreach ([InvalidContentDeclaration::class, NotBooted::class, InvalidHookResult::class, UnusablePostTimestamp::class] as $exception) {
            self::assertTrue(is_subclass_of($exception, ThemeException::class), $exception.' must implement the package marker');
        }
    }

    public function testTheContentDeclarationExceptionNamesTheThreeShapes(): void
    {
        $message = InvalidContentDeclaration::forType('array')->getMessage();

        self::assertStringContainsString('PostType', $message);
        self::assertStringContainsString('Taxonomy', $message);
        self::assertStringContainsString('RestRoute', $message);
        self::assertStringContainsString('got array', $message);
    }

    public function testTheRenderBoundaryExceptionNamesTheBreak(): void
    {
        self::assertStringContainsString('before Bootstrap::run()', NotBooted::beforeRender()->getMessage());
    }

    public function testTheHookResultExceptionNamesTheFilter(): void
    {
        self::assertStringContainsString('howdah/surface/rendered', InvalidHookResult::notRenderedHtml()->getMessage());
    }

    public function testTheTimestampExceptionNamesThePost(): void
    {
        self::assertStringContainsString('Post 7', UnusablePostTimestamp::forPost(7)->getMessage());
    }
}
