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

    public function testTheFullDerivationTableCellByCell(): void
    {
        // [declared, state name] => expected class. The request state only
        // ever reduces; no cell widens a declaration.
        $expected = [
            'Shared' => [
                'anonymous, in range' => 'Shared',
                'logged in' => 'Private',
                'POST' => 'Uncacheable',
                'out-of-range page' => 'Uncacheable',
                'free-text search' => 'Uncacheable',
                'error' => 'Uncacheable',
            ],
            'Private' => [
                'anonymous, in range' => 'Private',
                'logged in' => 'Private',
                'POST' => 'Uncacheable',
                'out-of-range page' => 'Uncacheable',
                'free-text search' => 'Uncacheable',
                'error' => 'Uncacheable',
            ],
            'Uncacheable' => [
                'anonymous, in range' => 'Uncacheable',
                'logged in' => 'Uncacheable',
                'POST' => 'Uncacheable',
                'out-of-range page' => 'Uncacheable',
                'free-text search' => 'Uncacheable',
                'error' => 'Uncacheable',
            ],
        ];

        $states = [
            'anonymous, in range' => ['GET', false, false, false, false],
            'logged in' => ['GET', true, false, false, false],
            'POST' => ['POST', false, false, false, false],
            'out-of-range page' => ['GET', false, true, false, false],
            'free-text search' => ['GET', false, false, true, false],
            'error' => ['GET', false, false, false, true],
        ];

        foreach ($expected as $declaredName => $row) {
            $declared = Cacheability::{$declaredName};

            foreach ($row as $state => $expectedClass) {
                $policy = HeaderPolicy::derive($declared, ...$states[$state]);

                self::assertSame(
                    $expectedClass,
                    $policy->effective->name,
                    $declaredName.' under '.$state.' derives '.$expectedClass.'.',
                );
            }
        }
    }
}
