<?php

/**
 * A surface hook returned data the render boundary cannot use. The filter is
 * refused, never coerced into the expected shape.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

final class InvalidHookResult extends \UnexpectedValueException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function notAQueryContext(): self
    {
        return new self('The howdah/surface/context filter must return a QueryContext.');
    }

    public static function notASurfacePlan(): self
    {
        return new self('The howdah/surface/resolve filter must return a SurfacePlan.');
    }

    public static function notAHeaderMap(): self
    {
        return new self('The wp_headers filter must deliver the documented header map.');
    }

    public static function notAMigrationList(): self
    {
        return new self('The migrations filter must deliver a list of migrations.');
    }

    public static function notAMigration(): self
    {
        return new self('The migrations filter must deliver Migration instances only.');
    }

    public static function notRenderedHtml(): self
    {
        return new self('The howdah/surface/rendered filter must return the rendered HTML string.');
    }
}
