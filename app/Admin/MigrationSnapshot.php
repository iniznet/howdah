<?php

/**
 * The migration state the status screen reports, read once from the db
 * package's runner. The screen renders a value, never a live runner, so a
 * test can build the snapshot by hand and the page cannot surprise itself
 * with a second read.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

use Iniznet\Mahout\Db\MigrationStatus;

final readonly class MigrationSnapshot
{
    /**
     * @param list<string> $applied
     * @param list<string> $pending
     */
    public function __construct(
        public int $codeVersion,
        public int $storedVersion,
        public array $applied,
        public array $pending,
    ) {
    }

    public static function fromStatus(MigrationStatus $status): self
    {
        return new self(
            codeVersion: $status->codeVersion,
            storedVersion: $status->storedVersion,
            applied: $status->applied,
            pending: $status->pending,
        );
    }

    public function isCurrent(): bool
    {
        return $this->codeVersion === $this->storedVersion;
    }
}
