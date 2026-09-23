<?php

/**
 * The response policy, derived from the declared cacheability class and the
 * request state. The caching contract's derivation is the authority: the
 * three class rows are verbatim, and a reduction stricter than the
 * declaration always wins.
 *
 * The full 3 x 6 table — anonymous in range, logged in, state-changing
 * method, out-of-range page, free-text search, engaged error boundary — is
 * asserted cell by cell by the policy's test. At send-headers time four of
 * the six inputs exist (method, logged-in, out-of-range page, free-text
 * search); the error cell's runtime seam is the Error Surface's 500, which
 * is sent after the header phase and carries no validator of its own.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support\Cache;

use Iniznet\Mahout\Render\Cacheability;

final readonly class HeaderPolicy
{
    /** @var list<string> */
    private const array STATE_CHANGING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private function __construct(
        public Cacheability $effective,
    ) {
    }

    public static function derive(
        Cacheability $declared,
        string $method,
        bool $loggedIn,
        bool $outOfRangePage = false,
        bool $freeTextSearch = false,
        bool $errorBoundary = false,
    ): self {
        $effective = $declared;

        if (\in_array(\strtoupper($method), self::STATE_CHANGING, true)) {
            $effective = Cacheability::Uncacheable;
        }

        if ($outOfRangePage || $freeTextSearch || $errorBoundary) {
            $effective = Cacheability::Uncacheable;
        }

        if (Cacheability::Shared === $effective && $loggedIn) {
            $effective = Cacheability::Private;
        }

        return new self($effective);
    }

    /**
     * The headers this class adds to core's own. The theme never emits
     * Expires or Pragma — WP::send_headers() owns both — and adds no Vary
     * entry for a Shared response, which would destroy its shareability.
     *
     * @return array<string, string|false>
     */
    public function headers(): array
    {
        return match ($this->effective) {
            Cacheability::Shared => [
                'Cache-Control' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=60',
            ],
            Cacheability::Private => [
                'Cache-Control' => 'private, max-age=60, must-revalidate',
                'Vary' => 'Cookie',
            ],
            Cacheability::Uncacheable => [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Vary' => 'Cookie',
                // false unsets the header: core removes it, and core's own
                // Last-Modified for feeds is not this theme's statement.
                'Last-Modified' => false,
            ],
        };
    }

    /** Validators are carried by the cacheable classes only. */
    public function emitsValidators(): bool
    {
        return Cacheability::Uncacheable !== $this->effective;
    }
}
