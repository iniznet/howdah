<?php

/**
 * The failed-migration notice, rendered on the same request the run failed:
 * RunMigrations catches the Throwable, records it once and holds the
 * diagnostics reference; this notice surfaces that reference so an operator
 * can find the record. Nothing is retried here -- the next run is an
 * explicit click on the status screen or wp mahout migrate.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

final readonly class MigrationFailedNotice
{
    public function __construct(private ?string $reference)
    {
    }

    /** The admin_notices entry; a run that did not fail renders nothing. */
    public function render(): void
    {
        if (null === $this->reference) {
            return;
        }

        \wp_admin_notice(
            sprintf(
                /* translators: %s: the diagnostics reference the failure was recorded under. */
                \__('The migration run failed. Diagnostics reference: %s. Nothing was applied; resolve the condition and run the migrations again.', 'howdah'),
                $this->reference,
            ),
            ['type' => 'error', 'dismissible' => false, 'paragraph' => true],
        );
    }
}
