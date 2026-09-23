<?php

/**
 * The fragment group's invalidation. The Shared arms make the theme a cache
 * owner, so invalidation exists from day one: a group with no declared
 * invalidation event is a bug with a delay fuse.
 *
 * Core's save and delete actions are observed through Hooks constants.
 * Autosaves, revisions and auto-drafts are skipped — the same two guards the
 * field save lifecycle applies.
 *
 * The salt and the seam coalesce at different scopes. EVERY content change
 * bumps the group's salt, because a fragment stored between two changes of
 * one request must not survive the later one. The purge seam — the expensive
 * trip to external caches — fires once per environment: on the first content
 * change after the boundary (a fresh instance, or a fresh salt), and never
 * again until the next one. The payload carries the invalidated scope,
 * because the salt strategy never enumerates keys.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support\Cache;

use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Render\FragmentCache;

final class FragmentInvalidation
{
    private bool $purged = false;

    public function __construct(private readonly FragmentCache $cache)
    {
    }

    /** Core's save_post handler. */
    public function saved(int $postId, \WP_Post $post): void
    {
        unset($postId);

        $this->invalidateFor($post);
    }

    /** Core's deleted_post handler. */
    public function deleted(int $postId, \WP_Post $post): void
    {
        unset($postId);

        $this->invalidateFor($post);
    }

    private function invalidateFor(\WP_Post $post): void
    {
        if (\wp_is_post_revision($post) || \wp_is_post_autosave($post) || 'auto-draft' === $post->post_status) {
            return;
        }

        $freshEnvironment = $this->cache->bump();

        if (!$freshEnvironment && $this->purged) {
            return;
        }

        $this->purged = true;

        \do_action(Hooks::PURGE, [FragmentCache::GROUP]);
    }
}
