<?php

/**
 * The response classes, declared at every dispatch arm. 13-caching §2 is the
 * authority for the header policy; no provider may add a cache header of its
 * own.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

enum Cacheability
{
    /** public, cacheable by a shared cache: anonymous reads only. */
    case Shared;
    /** cacheable by the visitor's own browser, never by a shared cache. */
    case Private;
    /** not stored anywhere, for any reason. */
    case Uncacheable;
}
