<?php

/**
 * The theme's own capability constants. A capability is a typed constant,
 * never a string literal at the check site; each case names the owner whose
 * surface it gates, and a check site reads one of these, never a literal.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

enum Capabilities: string
{
    /** The theme settings screen's reach and save. Theme display options are not site options. */
    case EditThemeOptions = 'edit_theme_options';
}
