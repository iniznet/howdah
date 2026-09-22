<?php

/**
 * The native button types.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Actions;

enum ButtonType: string
{
    case Button = 'button';
    case Submit = 'submit';
    case Reset = 'reset';
}
