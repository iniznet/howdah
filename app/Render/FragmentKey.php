<?php

/**
 * The deterministic fragment key: an explicit, ordered part list supplied by
 * the caller. No reflection in production — the key's contents are visible at
 * the construction site, and every part is enumerable from the site's own
 * content graph.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Render\Exception\EmptyKeyPart;

final readonly class FragmentKey
{
    /** Bumped whenever the fragment payload's shape changes. */
    private const string SALT = 'v1';

    private function __construct(private string $key)
    {
    }

    /**
     * Parts are the site's own enumerable scalars: a class name, an object
     * id, a locale. Every part is explicit; nothing is reflected.
     */
    public static function fromParts(string|int ...$parts): self
    {
        foreach ($parts as $part) {
            if (\is_string($part) && '' === $part) {
                throw EmptyKeyPart::forFragmentKey();
            }
        }

        return new self(self::SALT.'|'.implode('|', \array_map(\strval(...), $parts)));
    }

    public function toString(): string
    {
        return $this->key;
    }
}
