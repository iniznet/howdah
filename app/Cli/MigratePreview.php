<?php

/**
 * What the migrate command would do, decided before anything runs: the exit
 * code the WP-CLI contract assigns, the lines the command prints, the action
 * the caller may execute, and whether executing it is destructive enough to
 * need a confirmation.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Cli;

use Iniznet\Mahout\Db\CliExitCode;

final readonly class MigratePreview
{
    public const string NONE = 'none';
    public const string MIGRATE = 'apply';
    public const string ROLLBACK = 'reverse';

    /**
     * @param list<string> $lines
     */
    public function __construct(
        public CliExitCode $exitCode,
        public array $lines,
        public string $action,
        public bool $destructive,
    ) {
    }
}
