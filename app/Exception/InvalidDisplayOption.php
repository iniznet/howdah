<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

use Iniznet\Howdah\Features\Settings\DisplayOption;

/**
 * A display-option declaration is not a DisplayOption, or its declared type
 * is not one of the three shapes the settings screen can render and save.
 */
final class InvalidDisplayOption extends \InvalidArgumentException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forType(string $debugType): self
    {
        return new self(sprintf(
            'config/display-options.php must list %s entries; got %s.',
            DisplayOption::class,
            $debugType,
        ));
    }

    public static function forEmptyId(): self
    {
        return new self('config/display-options.php declares an option with an empty id.');
    }

    public static function forChoiceWithoutOptions(string $id): self
    {
        return new self(sprintf(
            'Display option "%s" declares the choice type with an empty choice set.',
            $id,
        ));
    }

    public static function forDefaultOutsideChoices(string $id, string $default): self
    {
        return new self(sprintf(
            'Display option "%s" declares a default of "%s" that is not one of its choices.',
            $id,
            $default,
        ));
    }

    public static function forUnknownType(string $type): self
    {
        return new self(sprintf(
            'Display option type "%s" is not one of the three shapes the settings screen renders and saves.',
            $type,
        ));
    }
}
