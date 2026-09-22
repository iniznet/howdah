<?php

/**
 * The dispatch table's default arm: an unmapped request kind renders the
 * site's declared empty state inside the document shell. It is a defined
 * render, never a fallback that pretends: 8b's feature arms replace it for
 * every kind a feature owns, and this arm's reason states that the request
 * was unmapped.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render\Surfaces;

use Iniznet\Howdah\Components\Message\Message;
use Iniznet\Howdah\Render\Component;
use Iniznet\Howdah\Render\Document;
use Iniznet\Howdah\Support\ClassResolver;

final readonly class GenericList implements Component
{
    public function __construct(
        private ClassResolver $classes,
    ) {
    }

    public function render(): string
    {
        return new Document(
            $this->classes,
            main: new Message(
                $this->classes,
                heading: \__('Nothing has been published here yet.', 'howdah'),
            ),
        )->render();
    }
}
