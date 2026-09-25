<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Internal\WpdbQuerySource;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\QueryKind;
use Iniznet\Mahout\Render\SiteProfile;
use Iniznet\Mahout\Render\SurfaceErrorBoundary;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * The error boundary's contract: a Surface returns the page or throws.
 * Production renders the defined Error Surface with status 500; development
 * rethrows. The failure is recorded at critical and the render library's
 * surface-failed hook fires with the support reference. No white screen, no
 * substitute data.
 */
final class SurfaceErrorBoundaryTest extends \WP_UnitTestCase
{
    public function testProductionRendersTheErrorSurface(): void
    {
        $statuses = [];
        add_filter('status_header', static function (string $header, int $code) use (&$statuses): string {
            $statuses[] = $code;

            return $header;
        }, 10, 2);

        $html = $this->boundary(self::throwing('the series is gone'), self::production())->render();

        self::assertStringContainsString('Something went wrong.', $html, 'production renders the defined Error Surface.');
        self::assertStringContainsString('The support reference', $html, 'the reference Diagnostics returned reaches the page.');
        self::assertContains(500, $statuses, 'production answers 500.');
    }

    public function testTheFailureIsRecordedAndTheSeamFires(): void
    {
        $fired = [];
        add_action(\Iniznet\Mahout\Render\Hooks::SURFACE_FAILED, static function (\Throwable $e, string $reference) use (&$fired): void {
            $fired[] = [$e->getMessage(), $reference];
        }, 10, 2);

        $html = $this->boundary(self::throwing('boom'), self::production())->render();

        self::assertStringContainsString('Something went wrong', $html);
        self::assertCount(1, $fired, 'mahout/render/surface_failed fired once, with the failure and the reference.');
        self::assertSame('boom', $fired[0][0]);
        self::assertNotSame('', $fired[0][1], 'the reference Diagnostics returned is not empty.');
    }

    public function testDevelopmentRethrows(): void
    {
        $boundary = $this->boundary(self::throwing('surface failed in development'), self::development());

        try {
            $boundary->render();
            self::fail('development rethrows; the trace is not replaced by a page.');
        } catch (\RuntimeException $e) {
            self::assertSame('surface failed in development', $e->getMessage());
        }
    }

    private static function production(): Environment
    {
        return new Environment('production', false, false);
    }

    private static function development(): Environment
    {
        return new Environment('local', true, true);
    }

    private static function throwing(string $message): Component
    {
        return new class($message) implements Component {
            public function __construct(private readonly string $message)
            {
            }

            public function render(): string
            {
                throw new \RuntimeException($this->message);
            }
        };
    }

    private static function context(): QueryContext
    {
        return new QueryContext(
            kind: QueryKind::Singular,
            postType: 'series',
            objectId: 7,
            objectSubtype: null,
            queryVars: [],
            site: new SiteProfile('howdah test', '', 'en_US', 'http', false),
        );
    }

    private function boundary(Component $inner, Environment $environment): SurfaceErrorBoundary
    {
        $diagnostics = new Diagnostics($environment, new WpdbQuerySource($GLOBALS['wpdb']));

        return new SurfaceErrorBoundary(
            inner: $inner,
            ctx: self::context(),
            diagnostics: $diagnostics,
            environment: $environment,
            classes: ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json'),
        );
    }
}
