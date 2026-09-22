<?php

/**
 * A fragment key part was empty. A key whose parts are not enumerable from
 * the site's own content graph is a cache an anonymous visitor can fill; an
 * empty part is the first step towards exactly that key space.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render\Exception;

use Iniznet\Howdah\Exception\ThemeException;

final class EmptyKeyPart extends \LogicException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forFragmentKey(): self
    {
        return new self('A fragment key part must not be empty.');
    }
}
