<?php

/**
 * The registration record: the Surface together with its cacheability class,
 * its fragment scope and — where the class is Uncacheable — the reason it is.
 * The class is required and has no default; a plan constructed without one
 * does not type-check.
 *
 * The plan carries the wrapped component or the bare one, never both: the
 * wrap happens here, in one construction site, so it is greppable in one file.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Render\Exception\UncacheableWithoutReason;

final readonly class SurfacePlan
{
    public function __construct(
        public Component $surface,
        public Cacheability $cacheability,
        public FragmentScope $fragmentScope,
        public string $reason,
        public ?FragmentKey $key = null,
    ) {
    }

    public static function uncacheable(Component $surface, string $reason): self
    {
        if ('' === $reason) {
            throw UncacheableWithoutReason::forPlan();
        }

        return new self(
            surface: $surface,
            cacheability: Cacheability::Uncacheable,
            fragmentScope: FragmentScope::Never,
            reason: $reason,
        );
    }

    public static function wrapped(
        Component $surface,
        Cacheability $cacheability,
        FragmentScope $fragmentScope,
        FragmentKey $key,
        FragmentCache $cache,
    ): self {
        return new self(
            surface: new CachedFragment($surface, $key, $cache),
            cacheability: $cacheability,
            fragmentScope: $fragmentScope,
            reason: '',
            key: $key,
        );
    }
}
