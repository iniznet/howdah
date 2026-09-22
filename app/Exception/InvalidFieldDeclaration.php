<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

use Iniznet\Howdah\Features\Fields\FieldPanel;

/**
 * A field declaration is not a FieldPanel, or a panel's post type is empty.
 */
final class InvalidFieldDeclaration extends \InvalidArgumentException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forType(string $debugType): self
    {
        return new self(sprintf(
            'config/fields.php must list %s entries; got %s.',
            FieldPanel::class,
            $debugType,
        ));
    }

    public static function forEmptyPostType(): self
    {
        return new self(sprintf(
            'Every %s must name a post type; got an empty string.',
            FieldPanel::class,
        ));
    }
}
