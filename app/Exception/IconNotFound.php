<?php

/**
 * An icon name outside the theme's own set. The registry is closed on
 * purpose: a named glyph is a design decision, and a typo must fail loudly,
 * not render nothing.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

final class IconNotFound extends \UnexpectedValueException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function because(string $name): self
    {
        return new self(sprintf('The icon name is not in the theme\'s set: %s.', $name));
    }
}
