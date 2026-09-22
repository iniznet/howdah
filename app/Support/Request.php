<?php

/**
 * The one request boundary. The superglobals are read in exactly one place
 * in the codebase, and this is it; the value is injected through
 * constructors, and every reader downstream is testable without a request.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

final readonly class Request
{
    private function __construct(
        public string $method,
        public ?string $ifNoneMatch,
    ) {
    }

    public static function fromSuperglobals(): self
    {
        $method = isset($_SERVER['REQUEST_METHOD']) && \is_string($_SERVER['REQUEST_METHOD'])
            ? \strtoupper($_SERVER['REQUEST_METHOD'])
            : 'GET';

        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) && \is_string($_SERVER['HTTP_IF_NONE_MATCH'])
            ? \trim($_SERVER['HTTP_IF_NONE_MATCH'])
            : null;

        return new self($method, $ifNoneMatch);
    }
}
