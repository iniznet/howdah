<?php

/**
 * The field panel's write-failure notice. The save pipeline never wp_die()s:
 * a refused classic-path save is queued as a one-shot transient by the field
 * package and surfaced here, on the next admin screen load, for the user who
 * submitted the form. The notice carries the group, the refusal reason and
 * the support reference -- nothing else, and nothing is retried.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

use Iniznet\Mahout\Fields\Admin\QueuedRefusal;
use Iniznet\Mahout\Fields\Admin\WriteFailureNotice;

final readonly class FieldWriteFailedNotice
{
    public function __construct(private WriteFailureNotice $notices)
    {
    }

    /**
     * The admin_notices entry for the edited post. The post id resolves
     * through core's post.php global, which wp-admin sets before notices
     * fire; off an edit screen there is no queued refusal to take.
     */
    public function render(): void
    {
        $post = \get_post();

        if (!$post instanceof \WP_Post) {
            return;
        }

        $refusal = $this->notices->take(\get_current_user_id(), (int) $post->ID);

        if (!$refusal instanceof QueuedRefusal) {
            return;
        }

        \wp_admin_notice(
            sprintf(
                /* translators: 1: field group id, 2: refusal reason, 3: diagnostics reference. */
                \__('The changes to field group "%1$s" were not saved (%2$s). Diagnostics reference: %3$s. Nothing was written.', 'howdah'),
                $refusal->groupId,
                $refusal->reason,
                $refusal->reference,
            ),
            ['type' => 'error', 'dismissible' => false, 'paragraph' => true],
        );
    }
}
