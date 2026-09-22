<?php

/**
 * The schema-version notice: the stored version trails the code's, so the
 * lazy path has not yet run. It is a warning, not an error, and never
 * dismissible -- the condition does not resolve until a migration run
 * applies, and a dismissed notice would only hide it.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

use Iniznet\Mahout\Db\SchemaVersion;

final readonly class MigrationRequiredNotice
{
    public function __construct(private SchemaVersion $version)
    {
    }

    /** The admin_notices entry; a current schema renders nothing. */
    public function render(): void
    {
        if (!$this->version->pending()) {
            return;
        }

        \wp_admin_notice(
            sprintf(
                /* translators: 1: stored schema version, 2: code schema version. */
                \__('The database schema (version %1$d) is behind the theme\'s (version %2$d). Run the pending migrations from Tools > Site status, or wp mahout migrate.', 'howdah'),
                $this->version->stored,
                $this->version->code,
            ),
            ['type' => 'warning', 'dismissible' => false, 'paragraph' => true],
        );
    }
}
