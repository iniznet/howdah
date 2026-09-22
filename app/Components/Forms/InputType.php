<?php

/**
 * The single-line input types the theme ships. Only types with a useful
 * native behaviour and keyboard contract are listed.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

enum InputType: string
{
    case Text = 'text';
    case Email = 'email';
    case Url = 'url';
    case Tel = 'tel';
    case Search = 'search';
    case Number = 'number';
    case Password = 'password';
    case Date = 'date';
}
