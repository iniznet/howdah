<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

/**
 * Translation timing, proved both ways. At runtime: while the composition
 * root booted (the recorder wraps Bootstrap::run() in tests/bootstrap.php),
 * no translated string was produced from the theme's own code. Statically:
 * the boot path (composition root, providers, declared configs) contains no
 * translation call at all; translated strings exist only in render-time
 * markup.
 */
final class BootI18nTest extends \WP_UnitTestCase
{
    private const string ROOT = __DIR__.'/../..';

    public function testNoTranslatedStringIsProducedDuringBoot(): void
    {
        $recorded = $GLOBALS['howdah_boot_translations'] ?? ['recorder did not run'];

        self::assertSame([], $recorded, "No translated string is produced during boot.\n".implode("\n", (array) $recorded));
    }

    public function testNoTranslationCallExistsInTheBootPath(): void
    {
        $offences = [];

        foreach ([
            self::ROOT.'/app/Bootstrap.php',
            ...(\glob(self::ROOT.'/app/Providers/*.php') ?: []),
        ] as $file) {
            $tokens = token_get_all((string) file_get_contents((string) $file));

            foreach ($tokens as $token) {
                if (is_array($token) && \in_array($token[1], ['__', '_e', '_x', '_n', 'esc_html__', 'esc_attr__'], true)) {
                    $offences[] = $file.': '.$token[1];
                }
            }
        }

        self::assertSame([], $offences, "No translated string is produced during boot or schema declaration.\n".implode("\n", $offences));
    }

    public function testDeclaredConfigFilesCarryNoTranslationCall(): void
    {
        $offences = [];

        foreach ((\glob(self::ROOT.'/config/*.php') ?: []) as $file) {
            $tokens = token_get_all((string) file_get_contents((string) $file));

            foreach ($tokens as $token) {
                if (is_array($token) && \in_array($token[1], ['__', '_e', '_n', '_x'], true)) {
                    $offences[] = $file.': '.$token[1];
                }
            }
        }

        self::assertSame([], $offences, 'declarations produce no translated string.');
    }
}
