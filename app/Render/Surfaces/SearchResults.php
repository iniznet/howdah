<?php

/**
 * The search arm. The key space of a free-text term is not enumerable, so
 * the arm is Uncacheable at both layers (SRCH-01).
 *
 * A term with no usable token renders the empty state and issues no query —
 * the LIKE fallback for a too-short search would be the attack. A usable
 * term needs the search repository slice 8b binds; until then the Surface
 * throws rather than pretend, and the error boundary renders the defined
 * failure.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render\Surfaces;

use Iniznet\Howdah\Components\Message\Message;
use Iniznet\Howdah\Render\Component;
use Iniznet\Howdah\Render\Document;
use Iniznet\Howdah\Render\Exception\SurfaceDataMissing;
use Iniznet\Howdah\Render\QueryContext;
use Iniznet\Howdah\Support\ClassResolver;

final readonly class SearchResults implements Component
{
    private const int MAX_TOKENS = 8;
    private const int MAX_TOKEN_BYTES = 100;
    private const int MIN_TOKEN_SIZE = 3;

    public function __construct(
        private QueryContext $ctx,
        private ClassResolver $classes,
    ) {
    }

    public function render(): string
    {
        $term = $this->term();

        if ([] === $this->usableTokens($term)) {
            return new Document(
                $this->classes,
                main: new Message(
                    $this->classes,
                    heading: \__('Nothing matched this search.', 'howdah'),
                ),
            )->render();
        }

        throw SurfaceDataMissing::forQuery($term);
    }

    /** The raw term, from the query vars — never from a superglobal. */
    private function term(): string
    {
        $s = $this->ctx->queryVars['s'] ?? '';

        return \is_string($s) ? $s : '';
    }

    /**
     * At most eight tokens, each at most 100 bytes, each at least the
     * server's minimum FULLTEXT token size. Enforced before any query is
     * built, so a malformed term never reaches AGAINST.
     *
     * @return list<string>
     */
    private function usableTokens(string $term): array
    {
        $raw = \preg_split('/\\s+/u', \trim($term)) ?: [];
        $tokens = [];

        foreach ($raw as $token) {
            if ('' === $token || \strlen($token) > self::MAX_TOKEN_BYTES) {
                continue;
            }

            if (\mb_strlen($token) < self::MIN_TOKEN_SIZE) {
                continue;
            }

            $tokens[] = $token;

            if (self::MAX_TOKENS === \count($tokens)) {
                break;
            }
        }

        return $tokens;
    }
}
