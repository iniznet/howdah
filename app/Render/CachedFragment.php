<?php

/**
 * The Component decorator a wrapped SurfacePlan builds: a warm hit returns
 * the stored bytes and never renders the inner Surface; a miss renders once,
 * stores, and releases the lock; a held lock renders and never stores.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

final readonly class CachedFragment implements Component
{
    public function __construct(
        private Component $inner,
        private FragmentKey $key,
        private FragmentCache $cache,
    ) {
    }

    public function render(): string
    {
        $key = $this->key->toString();

        $hit = $this->cache->get($key);

        if (\is_string($hit)) {
            return $hit;
        }

        $token = $this->cache->acquire($key);

        if (null === $token) {
            // Loser: render and do not write. The winner's bytes are the page.
            return $this->inner->render();
        }

        $html = $this->inner->render();

        $this->cache->store($key, $html);
        $this->cache->release($key, $token);

        return $html;
    }
}
