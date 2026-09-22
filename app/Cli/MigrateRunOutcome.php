<?php

/**
 * What a migration run did, as the command reports it: the contract's exit
 * code and the lines that describe the run, batch and names included.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Cli;

use Iniznet\Mahout\Db\CliExitCode;

final readonly class MigrateRunOutcome
{
    /**
     * @param list<string> $lines
     */
    public function __construct(
        public CliExitCode $exitCode,
        public array $lines,
    ) {
    }
}
