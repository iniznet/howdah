<?php

/**
 * The wp mahout migrate binding. Every decision comes from MigratePlanner,
 * which never writes on a preview; this class is the WP-CLI I/O shell the
 * contract fixes -- log/success/warning/error and confirm, never echo -- and
 * the exit codes are the package's, so a script can tell a refusal (3) from
 * a failure (1).
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Cli;

use Iniznet\Mahout\Db\CliExitCode;

final readonly class MigrateCommand
{
    /** The flags' real spellings; the array keys strip the leading dashes. */
    private const string FLAG_ROLLBACK = '--rollback';

    private const string FLAG_DRY_RUN = '--dry-run';

    private const string FLAG_YES = '--yes';

    public function __construct(private MigratePlanner $planner)
    {
    }

    /**
     * @param array<int, string>   $args      positional args; the command takes none
     * @param array<string, mixed> $assocArgs the flags: --dry-run, --rollback, --yes
     */
    public function migrate(array $args, array $assocArgs = []): void
    {
        $rollback = isset($assocArgs[self::flag(self::FLAG_ROLLBACK)]);
        $dryRun = isset($assocArgs[self::flag(self::FLAG_DRY_RUN)]);

        $preview = $this->planner->preview($rollback, $dryRun);

        foreach ($preview->lines as $line) {
            \WP_CLI::log($line);
        }

        if (CliExitCode::Refused === $preview->exitCode) {
            \WP_CLI::error('Refused; nothing ran.', false);
            exit(CliExitCode::Refused->value);
        }

        if (MigratePreview::NONE === $preview->action) {
            // A dry run's whole job is the preview, and a plan with no
            // action is already the answer.
            return;
        }

        if ($preview->destructive) {
            $this->confirm($assocArgs);
        }

        $outcome = $this->planner->run($rollback);

        foreach ($outcome->lines as $line) {
            \WP_CLI::log($line);
        }

        if (CliExitCode::Success === $outcome->exitCode) {
            \WP_CLI::success($rollback ? 'Reversed the latest batch.' : 'Applied the pending migrations.');

            return;
        }

        \WP_CLI::error('The run failed; nothing was applied.', false);
        exit($outcome->exitCode->value);
    }

    /**
     * A destructive rollback confirms unless --yes is passed, and a --yes
     * use is logged, because an unattended run should say so.
     *
     * @param array<string, mixed> $assocArgs
     */
    private function confirm(array $assocArgs): void
    {
        if (isset($assocArgs[self::flag(self::FLAG_YES)])) {
            \WP_CLI::log('--yes passed; the destructive rollback runs without a prompt.');

            return;
        }

        \WP_CLI::confirm('Reverse the latest batch?', $assocArgs);
    }

    /** The submitted array key for a flag spelled with its leading dashes. */
    private static function flag(string $spelling): string
    {
        return \ltrim($spelling, '-');
    }
}
