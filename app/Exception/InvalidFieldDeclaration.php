<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

use Iniznet\Mahout\Fields\FieldPanel;

/**
 * A field declaration is not a FieldPanel. The panel's own invariant — that it
 * names a post type — is the field package's, and the package throws
 * {@see \Iniznet\Mahout\Fields\Exception\InvalidPanelDeclaration} for it; this
 * exception answers only the question the theme's config file raises, which is
 * what its entries are at all.
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
}
