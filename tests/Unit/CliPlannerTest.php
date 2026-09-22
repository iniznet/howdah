<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Cli\MigratePlanner;
use Iniznet\Howdah\Cli\MigratePreview;
use Iniznet\Mahout\Db\CliExitCode;
use PHPUnit\Framework\TestCase;

/**
 * The migrate command's decision core. The preview never writes; the exit
 * codes are the WP-CLI contract's, so the tests pin the distinction a
 * calling script depends on -- refusal (3) against failure (1).
 */
final class CliPlannerTest extends TestCase
{
    public function testAPreviewDecidesWithoutWriting(): void
    {
        $preview = $this->planner()->preview(rollback: false, dryRun: true);

        self::assertSame(MigratePreview::NONE, $preview->action);
        self::assertNotSame([], $preview->lines);
    }

    public function testARollbackPreviewNeverMarksADryRunAsExecutable(): void
    {
        $preview = $this->planner()->preview(rollback: true, dryRun: true);

        self::assertContains($preview->exitCode, [CliExitCode::Success, CliExitCode::Refused]);

        if (CliExitCode::Success === $preview->exitCode) {
            self::assertSame(MigratePreview::NONE, $preview->action);
        }
    }

    public function testARunOutcomeCarriesTheContractExitCode(): void
    {
        $outcome = $this->planner()->run(rollback: false);

        self::assertTrue(CliExitCode::Success === $outcome->exitCode || CliExitCode::Failure === $outcome->exitCode);
        self::assertNotSame([], $outcome->lines);
    }

    public function testARefusalAndAFailureMapToDifferentExitCodes(): void
    {
        $refusal = \Iniznet\Mahout\Db\Exception\MigrationRollbackRefused::forMigrations(['fixture_irreversible']);

        self::assertSame(CliExitCode::Refused, CliExitCode::forFailure($refusal));
        self::assertSame(CliExitCode::Failure, CliExitCode::forFailure(new \RuntimeException('boom')));
    }

    private function planner(): MigratePlanner
    {
        $services = \Iniznet\Howdah\Bootstrap::services();

        return new MigratePlanner(
            $services->get(\Iniznet\Mahout\Db\MigrationRunner::class),
            $services->get(\Iniznet\Mahout\Kernel\Diagnostics::class),
        );
    }
}
