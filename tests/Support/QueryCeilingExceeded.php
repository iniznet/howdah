<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Support;

final class QueryCeilingExceeded extends \RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function beyond(string $label, int $observed, int $ceiling): self
    {
        return new self(\sprintf(
            '%s issued %d queries against a ceiling of %d.',
            $label,
            $observed,
            $ceiling,
        ));
    }
}
