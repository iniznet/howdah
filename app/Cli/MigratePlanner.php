<?php

/**
 * The migration command's decision layer: a pure read of the runner's state
 * that never writes, not even the ledger. The WP-CLI binding executes what a
 * preview authorises, so the refusal and the dry-run distinction the WP-CLI
 * contract fixes are decided -- and testable -- before any statement runs.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Cli;

use Iniznet\Mahout\Db\CliExitCode;
use Iniznet\Mahout\Db\MigrationRunner;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Level;

final readonly class MigratePlanner
{
    public function __construct(
        private MigrationRunner $runner,
        private Diagnostics $diagnostics,
    ) {
    }

    /**
     * The command's preview: what would run, what it would cost, and the exit
     * code the contract assigns. Never writes. A refused rollback reports
     * exit 3 with the refusal's reason before a caller could confirm it.
     */
    public function preview(bool $rollback, bool $dryRun): MigratePreview
    {
        $lines = [];

        $status = $this->runner->status();
        $lines[] = sprintf(
            'Schema version: code %d, stored %d.',
            $status->codeVersion,
            $status->storedVersion,
        );

        if ($rollback) {
            return $this->previewRollback($dryRun, $lines);
        }

        $plan = $this->runner->plan();

        if ($plan->isEmpty()) {
            $lines[] = 'Nothing to migrate; the schema is current.';

            return new MigratePreview(CliExitCode::Success, $lines, MigratePreview::NONE, false);
        }

        foreach ($plan->pending as $name) {
            $lines[] = 'Would apply: '.$name;
        }

        return new MigratePreview(
            CliExitCode::Success,
            $lines,
            $dryRun ? MigratePreview::NONE : MigratePreview::MIGRATE,
            false,
        );
    }

    /** @param list<string> $lines */
    private function previewRollback(bool $dryRun, array $lines): MigratePreview
    {
        try {
            $plan = $this->runner->rollbackPlan(1);
        } catch (\Iniznet\Mahout\Db\Exception\MigrationRollbackRefused $refusal) {
            $lines[] = 'Refused: '.$refusal->getMessage();

            return new MigratePreview(CliExitCode::Refused, $lines, MigratePreview::NONE, false);
        }

        if ($plan->isEmpty()) {
            $lines[] = 'Nothing to roll back; the ledger has no batches.';

            return new MigratePreview(CliExitCode::Success, $lines, MigratePreview::NONE, false);
        }

        foreach ($plan->names() as $name) {
            $lines[] = 'Would reverse: '.$name;
        }

        return new MigratePreview(
            CliExitCode::Success,
            $lines,
            $dryRun ? MigratePreview::NONE : MigratePreview::ROLLBACK,
            true,
        );
    }

    /**
     * The execution half. The refusal is decided before the first statement:
     * a rollback whose plan is refused throws before this method writes
     * anything, and the failure's exit code comes from the package's map.
     */
    public function run(bool $rollback): MigrateRunOutcome
    {
        try {
            $ran = $rollback ? $this->runner->rollback(1) : $this->runner->migrate();
        } catch (\Throwable $failure) {
            $reference = $this->diagnostics->log(
                Level::Error,
                'wp mahout migrate failed',
                ['condition' => $failure::class, 'message' => $failure->getMessage()],
            );

            return new MigrateRunOutcome(
                CliExitCode::forFailure($failure),
                ['Failed. Diagnostics reference: '.$reference],
            );
        }

        if ($ran->isEmpty()) {
            return new MigrateRunOutcome(CliExitCode::Success, ['Nothing was applied.']);
        }

        return new MigrateRunOutcome(
            CliExitCode::Success,
            [sprintf('Batch %d:', $ran->batch), ...$ran->migrations],
        );
    }
}
