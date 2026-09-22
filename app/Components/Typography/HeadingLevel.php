<?php

/**
 * The six heading levels. A component takes one of these, never an integer
 * or a string, so a call site cannot ask for a tag that skips a level or
 * invents a tag.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Typography;

enum HeadingLevel: int
{
    case One = 1;
    case Two = 2;
    case Three = 3;
    case Four = 4;
    case Five = 5;
    case Six = 6;

    /** The native element the level renders. */
    public function tag(): string
    {
        return 'h'.$this->value;
    }
}
