<?php

/**
 * The closed set of request kinds the dispatch table recognises.
 *
 * Every kind is classified in QueryContext::current(); the dispatch table is
 * the only place a kind resolves to a Surface, and its default arm is
 * written, never implied.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

enum QueryKind
{
    case Singular;
    case Embed;
    case Archive;
    case Search;
    case Front;
    case Home;
    case NotFound;
    case Generic;
}
