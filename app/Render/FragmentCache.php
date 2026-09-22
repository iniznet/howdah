<?php

/**
 * The fragment store: wp_cache_* in, wp_cache_* out, single-flight
 * regeneration. The theme's own cache use is confined to wp_cache_* — no
 * layer is required, and none is negotiated with here.
 *
 * A miss acquires the lock with wp_cache_add (the one atomic add); the winner
 * renders and stores, a loser renders and never writes, and the release
 * deletes only on a token match. Nothing sleeps and nothing spins: both
 * visitors receive a complete render.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

final class FragmentCache
{
    private const string GROUP = 'howdah/fragments';
    private const string LOCK_SUFFIX = '|lock';
    private const int LOCK_SECONDS = 5;
    private const int TTL_SECONDS = 300;

    public function get(string $key): ?string
    {
        $entry = \wp_cache_get($key, self::GROUP);

        return \is_string($entry) ? $entry : null;
    }

    public function store(string $key, string $html): void
    {
        \wp_cache_set($key, $html, self::GROUP, self::TTL_SECONDS);
    }

    /**
     * The single-flight lock. Returns the token this caller now holds, or
     * null when another regeneration holds the key.
     */
    public function acquire(string $key): ?string
    {
        $token = \bin2hex(\random_bytes(8));

        return \wp_cache_add($key.self::LOCK_SUFFIX, $token, self::GROUP, self::LOCK_SECONDS) ? $token : null;
    }

    /** Delete the lock only when the token is still ours. */
    public function release(string $key, string $token): void
    {
        if (\wp_cache_get($key.self::LOCK_SUFFIX, self::GROUP) === $token) {
            \wp_cache_delete($key.self::LOCK_SUFFIX, self::GROUP);
        }
    }
}
