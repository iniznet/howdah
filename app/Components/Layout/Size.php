<?php

/**
 * The three rhythm steps the layout primitives use. The scale is a design
 * decision, not a free-form input: a component declares one of these, and the
 * stylesheet owns what each step means.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Layout;

enum Size: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
}
