<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Render\Cacheability;
use Iniznet\Howdah\Support\Cache\HeaderPolicy;
use PHPUnit\Framework\TestCase;

/**
 * The header table in the caching contract is the authority, so the policy
 * is asserted against it cell by cell, plus the two request-state
 * reductions this slice owns.
 */
final class HeaderPolicyTest extends TestCase
{
    public function testSharedDeclaresTheSharedCacheControlAndNoVary(): void
    {
        $headers = HeaderPolicy::derive(Cacheability::Shared, 'GET', false)->headers();

        self::assertSame('public, max-age=60, s-maxage=300, stale-while-revalidate=60', $headers['Cache-Control']);
        self::assertArrayNotHasKey('Vary', $headers, 'a Vary entry would destroy the shareability.');
        self::assertTrue(HeaderPolicy::derive(Cacheability::Shared, 'GET', false)->emitsValidators());
    }

    public function testPrivateDeclaresThePrivateCacheControlAndCookieVary(): void
    {
        $policy = HeaderPolicy::derive(Cacheability::Private, 'GET', false);
        $headers = $policy->headers();

        self::assertSame('private, max-age=60, must-revalidate', $headers['Cache-Control']);
        self::assertSame('Cookie', $headers['Vary']);
        self::assertTrue($policy->emitsValidators());
    }

    public function testUncacheableDeclaresNoStoreAndUnsetsLastModified(): void
    {
        $policy = HeaderPolicy::derive(Cacheability::Uncacheable, 'GET', false);
        $headers = $policy->headers();

        self::assertSame('private, no-store, max-age=0', $headers['Cache-Control']);
        self::assertSame('Cookie', $headers['Vary']);
        self::assertFalse($headers['Last-Modified'], 'Last-Modified is handed to core as false so core removes it.');
        self::assertFalse($policy->emitsValidators(), 'validators are not emitted for an Uncacheable response.');
    }

    public function testAStateChangingMethodReducesEveryClassToUncacheable(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            self::assertSame(
                Cacheability::Uncacheable,
                HeaderPolicy::derive(Cacheability::Shared, $method, false)->effective,
                $method.' reduces the declared class to Uncacheable.',
            );
        }
    }

    public function testALoggedInVisitorReducesSharedToPrivate(): void
    {
        $policy = HeaderPolicy::derive(Cacheability::Shared, 'GET', true);

        self::assertSame(Cacheability::Private, $policy->effective, 'a logged-in visitor reduces Shared to Private.');
        self::assertSame('Cookie', $policy->headers()['Vary']);
    }

    public function testAnAnonymousPrivateResponseKeepsItsClass(): void
    {
        $policy = HeaderPolicy::derive(Cacheability::Private, 'GET', false);

        self::assertSame(Cacheability::Private, $policy->effective);
        self::assertTrue($policy->emitsValidators());
    }
}
