<?php

/**
 * The visual variants. The variant is a class, never an inline style.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Actions;

enum ButtonVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Quiet = 'quiet';
}
