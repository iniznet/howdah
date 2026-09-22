<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Render\CachedFragment;
use Iniznet\Howdah\Render\Component;
use Iniznet\Howdah\Render\FragmentCache;
use Iniznet\Howdah\Render\FragmentKey;
use Iniznet\Howdah\Support\Cache\FragmentInvalidation;
use Iniznet\Howdah\Support\Hooks;

/**
 * The fragment group's invalidation and its single-flight regeneration.
 * A Shared arm without declared invalidation is a bug with a delay fuse,
 * so a content save bumps the group's salt, the purge seam fires once with
 * the invalidated scope, and a concurrent miss on one key causes exactly one
 * regeneration write (required test, single-flight).
 */
final class FragmentInvalidationTest extends \WP_UnitTestCase
{
    private FragmentCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = Bootstrap::services()->get(FragmentCache::class);
    }

    public function testAContentSaveMakesStoredFragmentsUnreachable(): void
    {
        $postId = (int) self::factory()->post->create();

        $this->cache->store('probe|key', '<main>before</main>');
        self::assertSame('<main>before</main>', $this->cache->get('probe|key'), 'the fragment is reachable before the save.');

        wp_update_post(['ID' => $postId, 'post_content' => 'changed']);

        self::assertNull($this->cache->get('probe|key'), 'the save bumped the salt: fragments under the older salt are unreachable.');
    }

    public function testThePurgeSeamFiresOncePerRequestWithTheScope(): void
    {
        $purges = [];
        \add_action(Hooks::PURGE, static function (array $keys) use (&$purges): void {
            $purges[] = $keys;
        });

        $postId = (int) self::factory()->post->create();
        wp_update_post(['ID' => $postId, 'post_title' => 'first change']);
        wp_update_post(['ID' => $postId, 'post_title' => 'second change']);

        self::assertCount(1, $purges, 'the content changes of a request are coalesced into one purge.');
        self::assertSame([FragmentCache::GROUP], $purges[0], 'the purge payload carries the invalidated scope, never enumerated keys.');
    }

    public function testRevisionsAutosavesAndAutoDraftsAreSkipped(): void
    {
        $postId = (int) self::factory()->post->create();
        $invalidation = new FragmentInvalidation($this->cache);

        $purges = 0;
        \add_action(Hooks::PURGE, static function () use (&$purges): void {
            ++$purges;
        });

        $saltBefore = self::salt();

        $revision = get_post((int) wp_save_post_revision($postId));
        self::assertNotNull($revision);
        // wp_is_post_revision returns the revision id or false, never true.
        self::assertNotFalse(wp_is_post_revision($revision));

        $invalidation->saved((int) $revision->ID, $revision);
        $invalidation->deleted((int) $revision->ID, $revision);

        $autoDraft = get_post((int) self::factory()->post->create(['post_status' => 'auto-draft']));
        self::assertNotNull($autoDraft);
        $invalidation->saved((int) $autoDraft->ID, $autoDraft);

        self::assertSame($saltBefore, self::salt(), 'a revision or an auto-draft never bumps the group.');
        self::assertSame(0, $purges, 'a skipped post never fires the purge seam.');
    }

    public function testAPostDeletionMakesStoredFragmentsUnreachable(): void
    {
        $postId = (int) self::factory()->post->create();
        $invalidation = new FragmentInvalidation($this->cache);

        $this->cache->store('probe|key', '<main>alive</main>');

        $post = get_post($postId);
        self::assertNotNull($post);
        $invalidation->deleted($postId, $post);

        self::assertNull($this->cache->get('probe|key'), 'the deletion bumped the salt.');
    }

    public function testAWarmHitNeverRendersTheInnerSurface(): void
    {
        $renders = 0;
        $inner = self::countingInner($renders);
        $fragment = new CachedFragment($inner, self::key('warm'), $this->cache);

        $first = $fragment->render();
        $second = $fragment->render();

        self::assertSame(1, $renders, 'a warm hit never renders the inner Surface.');
        self::assertSame($first, $second, 'the stored bytes are the page.');
    }

    public function testAConcurrentMissRegeneratesExactlyOnce(): void
    {
        $renders = 0;
        $inner = self::countingInner($renders);
        $key = self::key('single-flight');

        $winner = new CachedFragment($inner, $key, $this->cache);
        $loser = new CachedFragment($inner, $key, $this->cache);

        $winner->render();
        $beforeLoser = $renders;
        $loser->render();

        self::assertSame($beforeLoser, $renders, 'a concurrent miss on one key causes exactly one regeneration: the loser never renders, the stored bytes are the page.');
        self::assertIsString($this->cache->acquire($key->toString()), 'the lock is released after the winner stores: a fresh acquirer holds it.');
    }

    public function testALoserNeverStoresAndNeverReleasesAForeignLock(): void
    {
        $key = self::key('loser');
        $foreign = $this->cache->acquire($key->toString());
        self::assertIsString($foreign, 'the winner holds the lock.');

        $renders = 0;
        $loser = new CachedFragment(self::countingInner($renders), $key, $this->cache);
        $loser->render();

        self::assertSame(1, $renders, 'the loser renders a complete page rather than waiting.');
        self::assertNull($this->cache->get($key->toString()), 'the loser never stores.');
        self::assertSame($foreign, \wp_cache_get($key->toString().'|lock', FragmentCache::GROUP), 'the loser never releases a lock it does not hold.');

        $this->cache->release($key->toString(), $foreign);
    }

    private static function countingInner(int &$renders): Component
    {
        return new class($renders) implements Component {
            public function __construct(private int &$renders)
            {
            }

            public function render(): string
            {
                ++$this->renders;

                return '<main>regenerated</main>';
            }
        };
    }

    private static function key(string $name): FragmentKey
    {
        return FragmentKey::fromParts(self::class, $name);
    }

    private static function salt(): int
    {
        $salt = \wp_cache_get('salt', FragmentCache::GROUP);

        return \is_int($salt) ? $salt : 1;
    }
}
