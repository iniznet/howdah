<?php

/**
 * The status screen's one write: running the pending migrations behind the
 * screen's manage_options capability and a verified nonce. A run's failure
 * is held on the instance as a diagnostics reference -- the failure notice
 * reads it on the same request's admin_notices, so nothing crosses a
 * superglobal twice and nothing is persisted.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

use Iniznet\Howdah\Support\Request;
use Iniznet\Mahout\Db\Capabilities;
use Iniznet\Mahout\Db\MigrationRunner;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Level;

final class RunMigrations
{
    private ?string $reference = null;

    public function __construct(
        private readonly MigrationRunner $runner,
        private readonly Diagnostics $diagnostics,
    ) {
    }

    /**
     * The load-{page} entry. A denied capability stops here; a request that
     * did not submit the run button stops at the nonce; a failed run records
     * once and is reported by the failure notice, and a successful run lands
     * back on the page, whose stored version now reads the code's.
     */
    public function handle(): void
    {
        if (!\current_user_can(Capabilities::ManageOptions->value)) {
            return;
        }

        if ('POST' !== Request::fromSuperglobals()->method
            || !Request::panel()->has('howdah_migrate')
        ) {
            return;
        }

        \check_admin_referer(self::nonceAction());

        try {
            $this->runner->migrate();
        } catch (\Throwable $failure) {
            $this->reference = $this->diagnostics->log(
                Level::Error,
                'migration run failed from the status screen',
                ['condition' => $failure::class, 'message' => $failure->getMessage()],
            );
        }
    }

    /** The failure's diagnostics reference for this request, if the run failed. */
    public function failureReference(): ?string
    {
        return $this->reference;
    }

    public static function nonceAction(): string
    {
        return 'howdah_status_migrate';
    }
}
