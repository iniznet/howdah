<?php

/**
 * The fragment store: wp_cache_* in, wp_cache_* out, single-flight
 * regeneration, salted invalidation. The theme's own cache use is confined
 * to wp_cache_* — no layer is required, and none is negotiated with here.
 *
 * Every read and write travels the group's salt: invalidation increments
 * the salt once — one write, no key enumeration — and every entry stored
 * under an older salt is unreachable on the next read. A miss acquires the
 * lock with wp_cache_add (the one atomic add); the winner renders and
 * stores, a loser renders and never writes, and the release deletes only on
 * a token match. Nothing sleeps and nothing spins: both visitors receive a
 * complete render.
 *
 * The 300-second TTL is the declared staleness window: where a key is not
 * enumerable, a stale fragment survives at most one TTL past its salt.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

final class FragmentCache
{
    public const string GROUP = 'howdah/fragments';
    public const int TTL_SECONDS = 300;

    private const string LOCK_SUFFIX = '|lock';
    private const string SALT_KEY = 'salt';
    private const int LOCK_SECONDS = 5;

    public function get(string $key): ?string
    {
        $entry = \wp_cache_get_salted($key, self::GROUP, (string) $this->salt());

        return \is_string($entry) ? $entry : null;
    }

    public function store(string $key, string $html): void
    {
        \wp_cache_set_salted($key, $html, self::GROUP, (string) $this->salt(), self::TTL_SECONDS);
    }

    /**
     * The invalidation write: one increment of the group's salt. Every
     * fragment stored under an older salt is unreachable on the next read.
     * Returns true when the salt was absent before the bump — the first
     * invalidation of a fresh environment, the request boundary.
     */
    public function bump(): bool
    {
        $first = \wp_cache_get(self::SALT_KEY, self::GROUP);

        \wp_cache_set(self::SALT_KEY, $this->salt() + 1, self::GROUP, 0);

        return !(\is_int($first) && $first > 0);
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

    /** The group's current salt; a group that was never bumped starts at 1. */
    public function salt(): int
    {
        $salt = \wp_cache_get(self::SALT_KEY, self::GROUP);

        return \is_int($salt) && $salt > 0 ? $salt : 1;
    }
}
