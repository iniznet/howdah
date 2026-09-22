<?php

/**
 * The composition root's shape, asserted by a gate and not by prose:
 * functions.php is eight lines or fewer and names no provider of its own;
 * Bootstrap lists every provider and module inline, AdminProvider included;
 * no config file sits between the list and the registrations.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CompositionRootTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    public function testFunctionsPhpIsEightLinesOrFewer(): void
    {
        $lines = (string) file_get_contents(self::ROOT.'/functions.php');

        self::assertLessThanOrEqual(8, \count(\preg_split('/\r\n|\r|\n/', \rtrim($lines, "\n"))), 'functions.php must stay within eight lines.');
    }

    public function testFunctionsPhpBootsOnceAndRegistersNothingOfItsOwn(): void
    {
        $php = (string) file_get_contents(self::ROOT.'/functions.php');

        self::assertSame(1, \substr_count($php, 'Bootstrap::run()'), 'functions.php boots the composition root exactly once.');
        self::assertDoesNotMatchRegularExpression('/add_action|add_filter/', $php, 'functions.php attaches no hook of its own.');
    }

    public function testBootstrapListsEveryProviderInline(): void
    {
        $php = (string) file_get_contents(self::ROOT.'/app/Bootstrap.php');

        \preg_match_all('/^\s*\$kernel->provider\((\w+)::class\);/m', $php, $providers);

        self::assertContains('ThemeProvider', $providers[1], 'ThemeProvider is named inline.');
        self::assertContains('AssetsProvider', $providers[1], 'AssetsProvider is named inline.');
        self::assertContains('ContentProvider', $providers[1], 'ContentProvider is named inline.');
        self::assertContains('EditorProvider', $providers[1], 'EditorProvider is named inline.');
        self::assertContains('AdminProvider', $providers[1], 'AdminProvider is named inline.');
        self::assertContains('RenderProvider', $providers[1], 'RenderProvider is named inline.');
        self::assertSame(\count($providers[1]), \count(\array_unique($providers[1])), 'no provider is named twice.');
    }

    public function testBootstrapHasNoConfigIndirection(): void
    {
        $php = (string) file_get_contents(self::ROOT.'/app/Bootstrap.php');

        self::assertDoesNotMatchRegularExpression('/require |include /', \preg_replace('/^\s*\/\/.*$/m', '', $php) ?? $php, 'the composition root requires no config file between the list and the registrations.');
    }
}
