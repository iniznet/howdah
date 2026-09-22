<?php

/**
 * The reuse scopes a fragment may declare, separate from the response class.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

enum FragmentScope
{
    /** identical for every visitor; the same entry serves anonymous and authenticated requests. */
    case Shared;
    /** the key includes the sorted role list. */
    case PerRole;
    /** the key includes the user id. */
    case PerUser;
    /** not stored. */
    case Never;
}
