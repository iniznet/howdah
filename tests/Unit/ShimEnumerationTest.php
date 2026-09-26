<?php

/**
 * Rule 1 of the render pipeline, proved by enumeration: the theme root
 * contains exactly the declared set of PHP files — functions.php (the boot
 * entry, gated by CompositionRootTest), index.php and embed.php (the two
 * entry points, one call each), header.php/footer.php (the two compatibility
 * shims), the scaffold's tooling configs, and preload.php (the opcode-cache
 * set, which runs once at pool start and never on a request). No other root
 * file contains logic.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ShimEnumerationTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    /** @var list<string> */
    private const array PHP_ENTRIES = ['functions.php', 'index.php', 'embed.php'];

    /** @var list<string> */
    private const array PHP_SHIMS = ['header.php', 'footer.php'];

    /**
     * The scaffold's tooling configs are framework-neutral bytes the drift
     * gate keeps identical to the stub tree; they carry no render logic and
     * no hook. The logic scan below proves that, file by file.
     */
    private const array PHP_TOOLING = ['.php-cs-fixer.dist.php', '.php-cs-fixer.paths.php', 'rector.php'];

    /**
     * The preload set compiles files and executes none of them, so it carries no
     * render path; it is declared here rather than in the tooling list because it
     * is the theme's own file, not a framework-neutral byte the drift gate keeps
     * identical to the stub tree.
     */
    private const array PHP_PRELOAD = ['preload.php'];

    public function testTheRootContainsExactlyTheDeclaredPhpFiles(): void
    {
        $php = \array_values(\array_filter(
            \scandir(self::ROOT) ?: [],
            static fn (string $name): bool => \str_ends_with($name, '.php'),
        ));

        \sort($php);

        $declared = [...self::PHP_ENTRIES, ...self::PHP_SHIMS, ...self::PHP_TOOLING, ...self::PHP_PRELOAD];
        \sort($declared);

        self::assertSame(
            $declared,
            $php,
            'the theme root PHP files are exactly the declared entries, shims and scaffold tooling.',
        );
    }

    /** The negative proof by enumeration: no other root file touches the render path. */
    public function testNoOtherRootFileTouchesTheRenderPath(): void
    {
        $offences = [];

        foreach ([...self::PHP_TOOLING, ...self::PHP_PRELOAD] as $file) {
            $php = (string) file_get_contents(self::ROOT.'/'.$file);

            if (1 === \preg_match('/Bootstrap::|add_action|add_filter/', $php)) {
                $offences[] = $file;
            }
        }

        self::assertSame([], $offences, 'no root file outside the entries and shims touches the render path.');
    }

    public function testIndexPhpIsOneCall(): void
    {
        self::assertSingleCall('index.php', 'Bootstrap::render');
    }

    public function testEmbedPhpIsOneCall(): void
    {
        self::assertSingleCall('embed.php', 'Bootstrap::render');
    }

    public function testHeaderShimRendersOneHalf(): void
    {
        $php = (string) file_get_contents(self::ROOT.'/header.php');

        self::assertSame(1, substr_count($php, '->opening()'), 'header.php resolves the shell opening half.');
        self::assertSame(0, substr_count($php, 'Bootstrap::render()'), 'the shim is not a second render path.');
    }

    public function testFooterShimRendersOneHalf(): void
    {
        $php = (string) file_get_contents(self::ROOT.'/footer.php');

        self::assertSame(1, substr_count($php, '->closing()'), 'footer.php resolves the shell closing half.');
        self::assertSame(0, substr_count($php, 'Bootstrap::render()'), 'the shim is not a second render path.');
    }

    private function assertSingleCall(string $file, string $call): void
    {
        $php = (string) file_get_contents(self::ROOT.'/'.$file);

        self::assertSame(
            1,
            substr_count($php, $call),
            sprintf('%s contains exactly one %s call and no other logic.', $file, $call),
        );
    }
}
