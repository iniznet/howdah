<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Render\CachedFragment;
use Iniznet\Howdah\Render\Component;
use Iniznet\Howdah\Render\FragmentCache;
use Iniznet\Howdah\Render\FragmentKey;

/**
 * The fragment contract: a warm hit never renders the inner Surface; a miss
 * renders once and stores; a loser (the lock held elsewhere) renders and
 * never writes. Concurrent misses on one key therefore cause exactly one
 * regeneration-and-store, with no sleep and no spin anywhere.
 */
final class FragmentCacheTest extends \WP_UnitTestCase
{
    private FragmentCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new FragmentCache();
    }

    public function testAMissRendersOnceAndStores(): void
    {
        $renders = 0;
        $key = FragmentKey::fromParts('miss', 1);
        $fragment = new CachedFragment(self::counting($renders, 'first'), $key, $this->cache);

        self::assertSame('first', $fragment->render(), 'a miss renders the inner component.');
        self::assertSame(1, $renders, 'exactly one render on the first miss.');
        self::assertSame('first', $this->cache->get($key->toString()), 'the winner stored the entry.');
    }

    public function testAWarmHitReturnsTheStoredBytesAndNeverRenders(): void
    {
        $renders = 0;
        $key = FragmentKey::fromParts('warm', 2);
        $fragment = new CachedFragment(self::counting($renders, 'warm'), $key, $this->cache);

        $fragment->render();
        $again = $fragment->render();

        self::assertSame('warm', $again);
        self::assertSame(1, $renders, 'a warm hit never re-renders the inner Surface.');
    }

    public function testALoserRendersButNeverWrites(): void
    {
        $renders = 0;
        $key = FragmentKey::fromParts('race', 2);
        $fragment = new CachedFragment(self::counting($renders, 'loser'), $key, $this->cache);

        // A foreign token holds the lock: wp_cache_add refuses, so this
        // render is a loser by construction.
        wp_cache_set($key->toString().'|lock', 'foreign-token', 'howdah/fragments', 5);

        self::assertSame('loser', $fragment->render(), 'the loser still renders a complete page.');
        self::assertNull($this->cache->get($key->toString()), 'the loser never writes the entry.');
    }

    public function testTheWinnerReleasesItsLock(): void
    {
        $renders = 0;
        $key = FragmentKey::fromParts('release', 3);
        $fragment = new CachedFragment(self::counting($renders, 'winner'), $key, $this->cache);

        $fragment->render();

        self::assertFalse(wp_cache_get($key->toString().'|lock', 'howdah/fragments'), 'the winner deleted the lock.');
    }

    public function testTheStoreReportsAMissAsNull(): void
    {
        self::assertNull($this->cache->get(FragmentKey::fromParts('absent')->toString()), 'a miss is null, never false.');
    }

    private static function counting(int &$renders, string $html): Component
    {
        return new class($html, static function () use (&$renders): void {
            ++$renders;
        }) implements Component {
            /** @param callable(): void $tick */
            public function __construct(
                private readonly string $html,
                private $tick,
            ) {
            }

            public function render(): string
            {
                ($this->tick)();

                return $this->html;
            }
        };
    }
}
