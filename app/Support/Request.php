<?php

/**
 * The one request boundary. The superglobals are read in exactly one place
 * in the codebase, and this is it; the value is injected through
 * constructors, and every reader downstream is testable without a request.
 * The admin form's submitted body is read here too -- panel() adapts the one
 * $_POST read to the field package's RequestInput contract, so the save
 * boundary consumes an adapter and never a superglobal.
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

    /**
     * The admin form's submitted body, unslashed once and handed to the
     * field package's save boundary through its contract. The superglobal is
     * read here, in the one reader, and the adapter is testable without a
     * request.
     */
    public static function panel(): PanelRequest
    {
        /** @var array<string, mixed> $post */
        $post = \wp_unslash($_POST);

        return PanelRequest::fromArray($post);
    }
}
