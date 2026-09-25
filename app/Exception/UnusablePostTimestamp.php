<?php

/**
 * A post whose date core cannot resolve to a timestamp. The mapper refuses,
 * never an epoch substitute or an empty display.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

final class UnusablePostTimestamp extends \UnexpectedValueException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forPost(int $postId): self
    {
        return new self(sprintf('Post %d carries no usable timestamp; the mapper refuses rather than substitute one.', $postId));
    }
}
