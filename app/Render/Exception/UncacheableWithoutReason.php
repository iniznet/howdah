<?php

/**
 * A plan was declared Uncacheable without the reason the gate reads. The
 * reason is required and has no default; an arm without one fails a test and
 * fails the reference gate (13-caching §4).
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render\Exception;

use Iniznet\Howdah\Exception\ThemeException;

final class UncacheableWithoutReason extends \LogicException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forPlan(): self
    {
        return new self('An Uncacheable SurfacePlan must carry a non-empty reason.');
    }
}
