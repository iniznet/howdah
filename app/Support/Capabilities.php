<?php

/**
 * The theme's own capability constants. A capability is a typed constant,
 * never a string literal at the check site; each case names the owner whose
 * surface it gates, and a check site reads one of these, never a literal.
 *
 * The starter owns no capability-gated surface of its own: the status screen
 * reads the db package's capability constant, and an option screen's
 * capability is the declaration's own fact in config/display-options.php. A
 * generated theme that declares a capability-gated surface names its
 * constant here and reads the case at the declaration.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

enum Capabilities: string
{
}
