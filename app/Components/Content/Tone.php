<?php

/**
 * The badge tones. The tone maps to a colour token; the text still says the
 * status, because colour is never the only carrier.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Content;

enum Tone: string
{
    case Neutral = 'neutral';
    case Positive = 'positive';
    case Warning = 'warning';
    case Critical = 'critical';
}
