<?php

/**
 * The data a Surface renders was not there. Expected absence returns ?T at
 * the repository; a Surface that was asked to render a page it cannot build
 * throws — it never returns null and never falls back silently.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render\Exception;

use Iniznet\Howdah\Exception\ThemeException;

final class SurfaceDataMissing extends \RuntimeException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forObject(?int $objectId): self
    {
        return new self(sprintf(
            'The object the surface renders was not found (object id: %s).',
            $objectId ?? 'none',
        ));
    }

    public static function forQuery(string $term): self
    {
        return new self(sprintf(
            'The search term "%s" cannot be served yet: no search repository is bound (slice 8b).',
            $term,
        ));
    }
}
