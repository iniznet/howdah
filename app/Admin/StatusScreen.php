<?php

/**
 * The Tools > Site status screen: schema version, migration state, the
 * invalidation strategy and the environment row, in one read-only table.
 * The screen's only write is the run-pending button, whose guard lives on
 * RunMigrations; the render path touches nothing and answers to nobody but
 * the user who reached the page through manage_options.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

final readonly class StatusScreen
{
    public const string PAGE_SLUG = 'howdah-status';

    /** @param list<array{0: string, 1: string}> $environment name and value pairs reported verbatim */
    public function __construct(
        private MigrationSnapshot $snapshot,
        private array $environment = [],
    ) {
    }

    /**
     * The page body. Core's page callback contract is echo-based, so the
     * provider echoes this -- the one boundary echo, of renderer output.
     */
    public function render(): string
    {
        \ob_start();
        $snapshot = $this->snapshot;
        $environment = $this->environment;
        require __DIR__.'/markup/status.php';

        return (string) \ob_get_clean();
    }
}
