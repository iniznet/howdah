<?php

/**
 * The container widths. Reading is narrow, structure is wide.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

enum Width: string
{
    case Reading = 'reading';
    case Wide = 'wide';
}
