<?php

/**
 * The response policy, derived from the declared cacheability class and the
 * request state. 13-caching §2 is the authority: the three class rows are
 * verbatim, and a reduction stricter than the declaration always wins.
 *
 * The two request-state reductions whose inputs this slice owns are the
 * state-changing methods and the logged-in visitor. The out-of-range page
 * and the engaged error boundary arrive with the render's data in slice 8b;
 * their cells are stated in the class docblock of the derivation and in the
 * handoff, not silently absent.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support\Cache;

use Iniznet\Howdah\Render\Cacheability;

final readonly class HeaderPolicy
{
    /** @var list<string> */
    private const array STATE_CHANGING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private function __construct(
        public Cacheability $effective,
    ) {
    }

    public static function derive(Cacheability $declared, string $method, bool $loggedIn): self
    {
        $effective = $declared;

        if (\in_array(\strtoupper($method), self::STATE_CHANGING, true)) {
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
